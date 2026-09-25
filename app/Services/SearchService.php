<?php

namespace App\Services;

use App\Enums\GameType;
use App\Enums\SchoolSubject;
use App\Models\User;
use App\Models\WeekPlanItem;
use App\Repositories\BadgeRepository;
use App\Repositories\SearchQueryRepository;
use App\Repositories\UserRepository;
use App\Repositories\UserStatRepository;
use App\Repositories\WeekPlanRepository;

class SearchService
{
    public function __construct(
        private WeekPlanService $week,
        private WeekPlanRepository $plans,
        private BadgeRepository $badges,
        private SearchQueryRepository $queries,
        private UserRepository $users,
        private UserStatRepository $stats,
        private LevelCalculator $levels,
    ) {}

    /**
     * Static destinations, keyed by their `home.search_to.*` lang entry.
     *
     * @var array<string, array{0: string, 1: string, 2: string}>
     */
    private const DESTINATIONS = [
        'daily_mission' => ['daily-mission', '🎯', 'tile-violet'],
        'library' => ['learn-categories', '📚', 'tile-coral'],
        'badges' => ['badges', '🏅', 'tile-mint'],
        'leaderboard' => ['leaderboard', '🏆', 'tile-sun'],
        'league' => ['league', '🥇', 'tile-sun'],
        'xp' => ['xp-progress', '⭐', 'tile-violet'],
        'monthly_goals' => ['monthly-goals', '🗓️', 'tile-mint'],
        'friends' => ['ranking-friends', '👫', 'tile-sky'],
        'settings' => ['settings', '⚙️', 'tile-mint'],
        'streak' => ['streak', '🔥', 'tile-sun'],
        'counting' => ['game-counting', '🔢', 'tile-sky'],
        'rewards' => ['rewards-dashboard', '🎁', 'tile-pink'],
    ];

    /**
     * Live player formats. Unbuilt games stay out of search until they have a route.
     *
     * @var list<GameType>
     */
    private const LIVE_GAMES = [
        GameType::MultipleChoice,
        GameType::TapCorrect,
        GameType::Counting,
    ];

    /**
     * NFC + lowercase + collapsed spaces. Georgian has no case; Latin keys still fold.
     */
    public function normalize(string $value): string
    {
        if (class_exists(\Normalizer::class)) {
            $normalized = \Normalizer::normalize($value, \Normalizer::FORM_C);
            $value = is_string($normalized) ? $normalized : $value;
        }

        $value = mb_strtolower(trim($value));
        $collapsed = preg_replace('/\s+/u', ' ', $value);

        return is_string($collapsed) ? $collapsed : $value;
    }

    /**
     * Every query word must appear inside the haystack, so a fragment such as
     * „მათ“ matches „მათემატიკა“.
     */
    public function matches(string $haystack, string $query): bool
    {
        $needle = $this->normalize($query);

        if ($needle === '') {
            return true;
        }

        $haystack = $this->normalize($haystack);

        foreach (preg_split('/\s+/u', $needle) ?: [] as $token) {
            if ($token !== '' && ! str_contains($haystack, $token)) {
                return false;
            }
        }

        return true;
    }

    /**
     * @param  list<array{name: string, keys: string, href: string, ico: string, tile: string, kind?: string, subject?: string|null, week?: int|null, status?: string|null}>  $entries
     * @return list<array{name: string, keys: string, href: string, ico: string, tile: string, kind?: string, subject?: string|null, week?: int|null, status?: string|null}>
     */
    public function filter(array $entries, string $query, ?string $subject = null, ?int $week = null, ?string $status = null): array
    {
        $hits = [];

        foreach ($entries as $entry) {
            if (! $this->matches($entry['name'].' '.$entry['keys'], $query)) {
                continue;
            }

            if ($subject !== null && ($entry['subject'] ?? null) !== $subject) {
                continue;
            }

            if ($week !== null && (int) ($entry['week'] ?? 0) !== $week) {
                continue;
            }

            if ($status !== null && ($entry['status'] ?? null) !== $status) {
                continue;
            }

            $hits[] = $entry;
        }

        return $hits;
    }

    /**
     * @param  list<array{id: int|null, subject: string, title: string, subtitle: string, completed: bool, playable: bool, emoji: string, tile: string, inkClass: string, href?: string}>  $planTasks
     * @return list<array{name: string, keys: string, href: string, ico: string, tile: string}>
     */
    public function homeCatalog(array $planTasks, ?int $continueItemId = null): array
    {
        $entries = [];

        foreach ($planTasks as $task) {
            $entries[] = [
                'name' => $task['subject'],
                'keys' => trim($task['subject'].' '.$task['title']),
                'href' => $task['href'] ?? $this->week->playUrl($task['playable'] ? $task['id'] : null, missionIfMissing: true),
                'ico' => $task['emoji'],
                'tile' => $task['tile'],
            ];
        }

        $entries[] = [
            'name' => (string) __('home.search_to.quick_quiz.name'),
            'keys' => (string) __('home.search_to.quick_quiz.keys'),
            'href' => $this->week->playUrl($continueItemId),
            'ico' => '❓',
            'tile' => 'tile-violet',
        ];

        foreach (self::DESTINATIONS as $key => [$route, $ico, $tile]) {
            $entries[] = [
                'name' => (string) __("home.search_to.{$key}.name"),
                'keys' => (string) __("home.search_to.{$key}.keys"),
                'href' => route($route),
                'ico' => $ico,
                'tile' => $tile,
            ];
        }

        foreach (SchoolSubject::ordered() as $subject) {
            $entries[] = [
                'name' => (string) __('learn.subject_library', ['subject' => $subject->label()]),
                'keys' => $subject->label().' '.(string) __('learn.library'),
                'href' => route('section-list', ['subject' => $subject->value]),
                'ico' => $subject->emoji(),
                'tile' => $subject->tile(),
            ];
        }

        return $entries;
    }

    /**
     * Subjects, weeks, packs, live games, and earned-or-public badges for this kid's class.
     *
     * @param  list<array{id: int|null, subject: string, title: string, subtitle: string, completed: bool, playable: bool, emoji: string, tile: string, inkClass: string, href?: string}>  $planTasks
     * @return list<array{name: string, keys: string, href: string, ico: string, tile: string, kind: string, subject: string|null, week: int|null, status: string|null}>
     */
    public function indexFor(User $user, array $planTasks = [], ?int $continueItemId = null): array
    {
        $entries = [];

        foreach ($this->homeCatalog($planTasks, $continueItemId) as $entry) {
            $entries[] = $entry + [
                'kind' => 'place',
                'subject' => null,
                'week' => null,
                'status' => null,
            ];
        }

        $grade = $this->week->gradeFor($user);
        $completed = array_flip($this->plans->completedItemIds($user));
        $opened = [];

        foreach ($this->plans->allForGrade($grade) as $item) {
            $status = $this->packStatus($item, $completed, $opened);
            $entries[] = [
                'name' => $item->title,
                'keys' => trim($item->title.' '.$item->subject->label().' '.(string) __('learn.week_n', ['n' => $item->week_number])),
                'href' => $this->week->packHref($user, $item),
                'ico' => $item->subject->emoji(),
                'tile' => $item->subject->tile(),
                'kind' => 'pack',
                'subject' => $item->subject->value,
                'week' => $item->week_number,
                'status' => $status,
            ];
        }

        foreach (SchoolSubject::ordered() as $subject) {
            $subjectItems = $this->plans->itemsForSubject($grade, $subject);
            $weeks = [];

            foreach ($subjectItems as $item) {
                $weeks[$item->week_number][] = $item->id;
            }

            foreach ($weeks as $number => $ids) {
                $done = 0;

                foreach ($ids as $id) {
                    if (isset($completed[$id])) {
                        $done++;
                    }
                }

                $weekStatus = $done === 0 ? 'new' : ($done === count($ids) ? 'done' : 'inprogress');

                $entries[] = [
                    'name' => (string) __('learn.subject_week', [
                        'subject' => $subject->label(),
                        'week' => $number,
                    ]),
                    'keys' => $subject->label().' '.(string) __('learn.week_n', ['n' => $number]),
                    'href' => route('section-list', ['subject' => $subject->value, 'week' => $number]),
                    'ico' => $subject->emoji(),
                    'tile' => $subject->tile(),
                    'kind' => 'week',
                    'subject' => $subject->value,
                    'week' => (int) $number,
                    'status' => $weekStatus,
                ];
            }
        }

        foreach (self::LIVE_GAMES as $type) {
            $next = $this->week->firstIncompleteOfType($user, $type);
            $entries[] = [
                'name' => $type->title(),
                'keys' => $type->title().' '.$type->value,
                'href' => $this->week->playUrl($next?->id, missionIfMissing: true),
                'ico' => match ($type) {
                    GameType::TapCorrect => '👆',
                    GameType::Counting => '🔢',
                    default => '❓',
                },
                'tile' => match ($type) {
                    GameType::TapCorrect => 'tile-mint',
                    GameType::Counting => 'tile-sky',
                    default => 'tile-violet',
                },
                'kind' => 'game',
                'subject' => null,
                'week' => null,
                'status' => null,
            ];
        }

        $earned = [];

        foreach ($this->badges->forUser($user) as $row) {
            if ($row->badge !== null) {
                $earned[$row->badge->slug] = true;
            }
        }

        foreach ($this->badges->catalog() as $badge) {
            if ($badge->is_secret && ! isset($earned[$badge->slug])) {
                continue;
            }

            $name = (string) __('badges.items.'.$badge->slug.'.name');
            $entries[] = [
                'name' => $name,
                'keys' => $name.' '.(string) __('badges.items.'.$badge->slug.'.blurb'),
                'href' => route('badges'),
                'ico' => $badge->emoji,
                'tile' => 'tile-mint',
                'kind' => 'badge',
                'subject' => null,
                'week' => null,
                'status' => isset($earned[$badge->slug]) ? 'done' : 'new',
            ];
        }

        return $entries;
    }

    /**
     * @return list<int>
     */
    public function weekNumbers(User $user): array
    {
        return $this->plans->weekNumbersForGrade($this->week->gradeFor($user));
    }

    public function record(User $user, string $query): void
    {
        $display = trim($query);
        $normalized = $this->normalize($display);

        if (mb_strlen($normalized) < 2 || mb_strlen($normalized) > 80) {
            return;
        }

        $this->queries->record($user, mb_substr($display, 0, 80), $normalized);
    }

    /**
     * @return list<string>
     */
    public function recent(User $user): array
    {
        return $this->queries->recentFor($user);
    }

    /**
     * @return list<string>
     */
    public function popular(): array
    {
        return $this->queries->popular();
    }

    /**
     * Public leaderboard hits. Kids with show_on_leaderboard off are excluded.
     *
     * @return list<array{userId: int, nickname: string, name: string, xp: int, level: int, streak: int, rank: int|null, avatar: string}>
     */
    public function players(string $query): array
    {
        $fragment = $this->normalize($query);

        if (mb_strlen($fragment) < 2) {
            return [];
        }

        $users = $this->users->searchVisibleByNickname($fragment);
        $ids = array_values($users->pluck('id')->map(fn ($id) => (int) $id)->all());
        $summaries = $this->stats->summaryByUserId($ids);
        $hits = [];

        foreach ($users as $user) {
            if (! $this->matches($user->nickname, $fragment)) {
                continue;
            }

            $summary = $summaries[$user->id] ?? ['xp' => 0, 'streak' => 0];

            $hits[] = [
                'userId' => $user->id,
                'nickname' => $user->nickname,
                'name' => $user->name,
                'xp' => $summary['xp'],
                'level' => $this->levels->forXp($summary['xp'])->level,
                'streak' => $summary['streak'],
                'rank' => $this->stats->rankFor($user),
                'avatar' => is_string($user->avatar) && $user->avatar !== '' ? $user->avatar : '🐻',
            ];
        }

        return $hits;
    }

    /**
     * @param  array<int, int>  $completed
     * @param  array<string, true>  $opened
     */
    private function packStatus(WeekPlanItem $item, array $completed, array &$opened): string
    {
        if (isset($completed[$item->id])) {
            return 'done';
        }

        $key = $item->subject->value;

        if (! isset($opened[$key])) {
            $opened[$key] = true;

            return 'inprogress';
        }

        return 'new';
    }
}
