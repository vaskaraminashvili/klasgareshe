<?php

namespace App\Services;

use App\Data\LearnLibrarySnapshot;
use App\Data\LearnPackSnapshot;
use App\Data\LearnSectionSnapshot;
use App\Enums\GameType;
use App\Enums\SchoolGrade;
use App\Enums\SchoolSubject;
use App\Models\User;
use App\Models\WeekPlanItem;
use App\Repositories\GameRepository;
use App\Repositories\QuestionRepository;
use App\Repositories\WeekPlanRepository;
use Illuminate\Support\Collection;

class LearnLibraryService
{
    /** @var array<string, int> */
    private array $xpPerCorrect = [];

    public function __construct(
        private WeekPlanService $week,
        private WeekPlanRepository $plans,
        private UserProfileService $profile,
        private BadgeService $badges,
        private QuestionRepository $questions,
        private GameRepository $games,
    ) {}

    public function library(User $user): LearnLibrarySnapshot
    {
        $grade = $this->week->gradeFor($user);
        $favourites = $this->profile->favouriteSubjectValues($user);
        $completed = array_flip($this->plans->completedItemIds($user));
        $age = $this->ageForGrade($grade);
        $tiles = [];
        $lessonsDone = 0;
        $lessonsTotal = 0;

        foreach (SchoolSubject::ordered() as $subject) {
            $items = $this->plans->itemsForSubject($grade, $subject);
            $total = $items->count();
            $done = 0;

            foreach ($items as $item) {
                if (isset($completed[$item->id])) {
                    $done++;
                }
            }

            $lessonsDone += $done;
            $lessonsTotal += $total;
            $percent = $total > 0 ? (int) round(($done / $total) * 100) : 0;
            $firstItem = $items->first();
            $level = $firstItem instanceof WeekPlanItem ? (int) $firstItem->level : 1;

            $tiles[] = [
                'subject' => $subject->value,
                'label' => $subject->label(),
                'emoji' => $subject->emoji(),
                'tile' => $subject->tile(),
                'inkClass' => $subject->inkClass(),
                'ringClass' => $subject->ringClass(),
                'lessons' => $total,
                'percent' => $percent,
                'blurb' => (string) __('learn.blurbs.'.$subject->value),
                'difficulty' => $this->difficultyLabel($level),
                'difficultyClass' => $this->difficultyClass($level),
                'gradeRange' => $grade->label(),
                'tags' => $this->filterTags($subject),
                'diff' => $this->difficultyKey($level),
                'age' => $age,
                'status' => $percent === 0 ? 'new' : 'inprogress',
                'href' => route('section-list', ['subject' => $subject->value]),
                'favourite' => in_array($subject->value, $favourites, true),
            ];
        }

        return new LearnLibrarySnapshot(
            subjectCount: count($tiles),
            lessonsDone: $lessonsDone,
            lessonsTotal: $lessonsTotal,
            gamesCount: 3,
            subjects: $tiles,
            spotlight: $this->spotlight($user),
            games: $this->games($user),
            latest: $this->latest($user),
        );
    }

    public function section(User $user, SchoolSubject $subject, ?int $week = null): LearnSectionSnapshot
    {
        $grade = $this->week->gradeFor($user);
        $items = $this->plans->itemsForSubject($grade, $subject);
        $weeks = $this->plans->weekNumbersForGrade($grade);
        $active = $this->week->activeWeekNumber($user);
        $selected = $week ?? $active;

        if (! in_array($selected, $weeks, true)) {
            $selected = $active;
        }

        $weekItems = $items->where('week_number', $selected)->values();
        $completed = array_flip($this->plans->completedItemIds($user));
        $continue = $this->week->nextIncompleteForSubject($user, $subject);
        $weekDone = 0;

        foreach ($weekItems as $item) {
            if (isset($completed[$item->id])) {
                $weekDone++;
            }
        }

        $weekTotal = $weekItems->count();
        $percent = $weekTotal > 0 ? (int) round(($weekDone / $weekTotal) * 100) : 0;

        $weekChips = [];

        foreach ($weeks as $number) {
            $set = $items->where('week_number', $number);
            $setTotal = $set->count();
            $setDone = 0;

            foreach ($set as $item) {
                if (isset($completed[$item->id])) {
                    $setDone++;
                }
            }

            $status = 'locked';
            $statusLabel = (string) __('learn.locked');

            if ($number === $selected) {
                $status = 'current';
                $statusLabel = (string) __('learn.in_progress');
            } elseif ($setTotal > 0 && $setDone === $setTotal) {
                $status = 'done';
                $statusLabel = (string) __('learn.done');
            } elseif ($number <= $active) {
                $status = '';
                $statusLabel = (string) __('learn.week_open');
            }

            $weekChips[] = [
                'week' => $number,
                'label' => (string) __('learn.week_n', ['n' => $number]),
                'name' => (string) __('learn.week_name', ['n' => $number]),
                'status' => $status,
                'statusLabel' => $statusLabel,
            ];
        }

        $lessons = [];
        $index = 1;

        foreach ($weekItems as $item) {
            $state = $this->state($user, $item);
            $xpReward = $this->packXp($item);
            $minutes = $item->questions_per_round;
            $correct = $this->plans->correctCountFor($user, $item->id);
            $stars = $state === 'done'
                ? $correct.'/'.$item->questions_per_round
                : '';

            $lessons[] = [
                'id' => $item->id,
                'title' => (string) __('learn.lesson_n_title', [
                    'n' => $index,
                    'title' => $item->title,
                ]),
                'subtitle' => $this->lessonSubtitle($user, $item, $state),
                'state' => $state,
                'href' => $this->week->packHref($user, $item),
                'minutes' => $minutes,
                'xp' => $xpReward,
                'stars' => $stars,
                'chip' => $this->lessonChip($state),
                'chipClass' => $this->lessonChipClass($state),
                'icon' => match ($state) {
                    'done' => 'ph-fill ph-check',
                    'playable' => 'ph-fill ph-play',
                    default => 'ph-fill ph-lock',
                },
                'percent' => $state === 'done' ? 100 : 0,
            ];
            $index++;
        }

        $continueCard = null;

        if ($continue instanceof WeekPlanItem) {
            $continueCard = [
                'title' => $continue->title,
                'subtitle' => (string) __('learn.continue_meta', [
                    'xp' => $this->packXp($continue),
                    'minutes' => $continue->questions_per_round,
                ]),
                'href' => $this->week->playUrl($continue->id),
            ];
        }

        $badgeCards = [];

        foreach ($this->badges->subjectCards($user, $subject) as $card) {
            $badgeCards[] = [
                'slug' => $card->slug,
                'name' => $card->name,
                'emoji' => $card->emoji,
                'medalClass' => $card->medalClass,
                'meta' => $card->meta,
                'href' => $card->href !== '#' ? $card->href : route('badges'),
                'locked' => $card->status !== 'got',
            ];
        }

        $remaining = max(0, $weekTotal - $weekDone);

        return new LearnSectionSnapshot(
            subject: $subject->value,
            subjectLabel: $subject->label(),
            emoji: $subject->emoji(),
            week: $selected,
            weekDone: $weekDone,
            weekTotal: $weekTotal,
            percent: $percent,
            xpEarned: $this->subjectXp($user, $items, $completed),
            minutesLeft: $remaining * 5,
            favourite: in_array($subject->value, $this->profile->favouriteSubjectValues($user), true),
            continue: $continueCard,
            weeks: $weekChips,
            lessons: $lessons,
            badges: $badgeCards,
        );
    }

    public function pack(User $user, int $itemId): ?LearnPackSnapshot
    {
        $item = $this->week->findItem($itemId);

        if ($item === null || $item->grade !== $this->week->gradeFor($user)) {
            return null;
        }

        $grade = $this->week->gradeFor($user);
        $weekItems = $this->plans->itemsForGrade($grade, $item->week_number)
            ->where('subject', $item->subject)
            ->values();
        $index = 1;

        foreach ($weekItems as $row) {
            if ($row->id === $item->id) {
                break;
            }
            $index++;
        }

        $state = $this->state($user, $item);
        $ids = $this->week->questionIds($item);
        $questions = [];
        $n = 1;
        $allDone = $state === 'done';

        foreach ($this->questions->findMany($ids) as $question) {
            $prompt = is_string($question->prompt) && $question->prompt !== ''
                ? $question->prompt
                : (string) __('learn.question_n', ['n' => $n]);
            $questions[] = [
                'n' => $n,
                'title' => $prompt,
                'subtitle' => $item->game_slug->title(),
                'state' => $allDone ? 'done' : ($n === 1 && $state === 'playable' ? 'now' : 'locked'),
                'tile' => $n === 1 ? $item->subject->tile() : 'tile-sky',
            ];
            $n++;
        }

        $subjectItems = $this->plans->itemsForSubject($grade, $item->subject);
        $previous = null;
        $next = null;
        $found = false;

        foreach ($subjectItems as $candidate) {
            if ($candidate->id === $item->id) {
                $found = true;

                continue;
            }

            if (! $found) {
                $previous = $candidate;
            } elseif ($next === null) {
                $next = $candidate;
            }
        }

        $blocking = $this->week->blockingPack($user, $item);

        if ($blocking === null && $state === 'locked') {
            $blocking = $this->week->firstIncomplete($user);
        }

        $correct = $this->plans->correctCountFor($user, $item->id);
        $percent = $state === 'done' ? 100 : 0;
        $badges = [];

        foreach ($this->badges->subjectCards($user, $item->subject, 1) as $card) {
            $badges[] = [
                'slug' => $card->slug,
                'name' => $card->name,
                'emoji' => $card->emoji,
                'medalClass' => $card->medalClass,
                'meta' => $card->meta,
                'href' => $card->href !== '#' ? $card->href : route('badges'),
                'locked' => $card->status !== 'got',
            ];
        }

        $unlockDone = 0;
        $unlockSteps = 1;

        if ($blocking instanceof WeekPlanItem) {
            $unlockSteps = 1;
            $unlockDone = $this->week->isCompleted($user, $blocking) ? 1 : 0;
        } elseif ($state !== 'locked') {
            $unlockDone = 1;
        }

        return new LearnPackSnapshot(
            id: $item->id,
            title: $item->title,
            subject: $item->subject->value,
            subjectLabel: $item->subject->label(),
            emoji: $item->subject->emoji(),
            tile: $item->subject->tile(),
            week: $item->week_number,
            index: $index,
            weekTotal: $weekItems->count(),
            questions: $item->questions_per_round,
            correct: $correct,
            xp: $this->packXp($item),
            minutes: $item->questions_per_round,
            percent: $percent,
            state: $state,
            playHref: $this->week->playUrl($item->id),
            sectionHref: route('section-list', ['subject' => $item->subject->value]),
            favourite: in_array($item->subject->value, $this->profile->favouriteSubjectValues($user), true),
            gradeLabel: $grade->label(),
            difficulty: $this->difficultyLabel((int) $item->level),
            gameLabel: $item->game_slug->title(),
            previous: $previous instanceof WeekPlanItem ? [
                'id' => $previous->id,
                'title' => $previous->title,
                'href' => $this->week->packHref($user, $previous),
                'done' => $this->week->isCompleted($user, $previous),
            ] : null,
            next: $next instanceof WeekPlanItem ? [
                'id' => $next->id,
                'title' => $next->title,
                'href' => $this->week->packHref($user, $next),
                'minutes' => $next->questions_per_round,
                'xp' => $this->packXp($next),
                'locked' => $this->state($user, $next) === 'locked',
            ] : null,
            questionsList: $questions,
            badges: $badges,
            blockingId: $blocking instanceof WeekPlanItem ? $blocking->id : null,
            blockingTitle: $blocking instanceof WeekPlanItem ? $blocking->title : '',
            blockingHref: $blocking instanceof WeekPlanItem
                ? $this->week->playUrl($blocking->id)
                : route('learn-categories'),
            unlockSteps: $unlockSteps,
            unlockDone: $unlockDone,
        );
    }

    public function toggleFavourite(User $user, SchoolSubject $subject): bool
    {
        return $this->profile->toggleFavouriteSubject($user, $subject);
    }

    /**
     * @return array{title: string, subtitle: string, href: string, percent: int, progressLabel: string, xp: int, minutes: int, emoji: string}
     */
    private function spotlight(User $user): array
    {
        $item = $this->week->firstIncomplete($user);

        if (! $item instanceof WeekPlanItem) {
            return [
                'title' => (string) __('home.week_complete'),
                'subtitle' => (string) __('learn.finish_todays_plan'),
                'href' => route('daily-mission'),
                'percent' => 100,
                'progressLabel' => (string) __('learn.progress_n_of_n', ['done' => 1, 'total' => 1]),
                'xp' => 0,
                'minutes' => 0,
                'emoji' => '🎓',
            ];
        }

        return [
            'title' => $item->title,
            'subtitle' => $item->subject->label(),
            'href' => $this->week->playUrl($item->id),
            'percent' => 0,
            'progressLabel' => (string) __('learn.progress_n_of_n', [
                'done' => 0,
                'total' => $item->questions_per_round,
            ]),
            'xp' => $this->packXp($item),
            'minutes' => $item->questions_per_round,
            'emoji' => $item->subject->emoji(),
        ];
    }

    /**
     * @return list<array{title: string, subtitle: string, emoji: string, tile: string, href: string, keywords: string, tags: string}>
     */
    private function games(User $user): array
    {
        $cards = [];

        foreach ([
            [GameType::MultipleChoice, '❓', 'tile-violet', 'pop games'],
            [GameType::TapCorrect, '👆', 'tile-mint', 'games math'],
            [GameType::Counting, '🔢', 'tile-sky', 'games math'],
        ] as [$type, $emoji, $tile, $tags]) {
            $item = $this->week->firstIncompleteOfType($user, $type);
            $title = $type->title();
            $cards[] = [
                'title' => $title,
                'subtitle' => $item instanceof WeekPlanItem
                    ? $item->title
                    : (string) __('learn.play_learn'),
                'emoji' => $emoji,
                'tile' => $tile,
                'href' => $this->week->playUrl($item?->id, missionIfMissing: true),
                'keywords' => $title,
                'tags' => $tags,
            ];
        }

        return $cards;
    }

    /**
     * @return list<array{title: string, subtitle: string, emoji: string, tile: string, href: string, keywords: string}>
     */
    private function latest(User $user): array
    {
        $grade = $this->week->gradeFor($user);
        $week = $this->plans->maxWeekNumber($grade);
        $rows = [];

        foreach ($this->plans->itemsForGrade($grade, $week) as $item) {
            $rows[] = [
                'title' => $item->title,
                'subtitle' => (string) __('learn.latest_meta', [
                    'subject' => $item->subject->label(),
                    'week' => $week,
                    'xp' => $this->packXp($item),
                ]),
                'emoji' => $item->subject->emoji(),
                'tile' => $item->subject->tile(),
                'href' => $this->week->packHref($user, $item),
                'keywords' => $item->title.' '.$item->subject->label(),
            ];

            if (count($rows) >= 3) {
                break;
            }
        }

        return $rows;
    }

    private function state(User $user, WeekPlanItem $item): string
    {
        if ($this->week->isCompleted($user, $item)) {
            return 'done';
        }

        if ($this->week->isPlayable($user, $item)) {
            return 'playable';
        }

        return 'locked';
    }

    private function lessonSubtitle(User $user, WeekPlanItem $item, string $state): string
    {
        if ($state === 'done') {
            $xp = $this->packXp($item);

            return (string) __('learn.completed_xp', ['xp' => $xp]);
        }

        if ($state === 'playable') {
            return (string) __('learn.ready_to_play');
        }

        $blocking = $this->week->blockingPack($user, $item) ?? $this->week->firstIncomplete($user);

        if ($blocking instanceof WeekPlanItem) {
            return (string) __('learn.unlock_after', ['title' => $blocking->title]);
        }

        return (string) __('learn.locked');
    }

    private function lessonChip(string $state): string
    {
        return match ($state) {
            'done' => (string) __('learn.done'),
            'playable' => (string) __('learn.now'),
            default => (string) __('learn.locked'),
        };
    }

    private function lessonChipClass(string $state): string
    {
        return match ($state) {
            'done' => 'chip chip-mint',
            'playable' => 'chip chip-primary',
            default => 'chip',
        };
    }

    private function packXp(WeekPlanItem $item): int
    {
        return $item->questions_per_round * $this->xpPerCorrect($item);
    }

    /**
     * @param  Collection<int, WeekPlanItem>  $items
     * @param  array<int, int>  $completed
     */
    private function subjectXp(User $user, Collection $items, array $completed): int
    {
        $xp = 0;

        foreach ($items as $item) {
            if (isset($completed[$item->id])) {
                $xp += $this->plans->correctCountFor($user, $item->id) * $this->xpPerCorrect($item);
            }
        }

        return $xp;
    }

    private function xpPerCorrect(WeekPlanItem $item): int
    {
        $key = $item->game_slug->value;

        if (! isset($this->xpPerCorrect[$key])) {
            $game = $this->games->findBySlug($item->game_slug);
            $this->xpPerCorrect[$key] = $game === null ? 8 : (int) $game->xp_per_correct;
        }

        return $this->xpPerCorrect[$key];
    }

    private function difficultyLabel(int $level): string
    {
        return match (true) {
            $level >= 3 => (string) __('learn.challenge'),
            $level === 2 => (string) __('learn.medium'),
            default => (string) __('learn.easy'),
        };
    }

    private function difficultyClass(int $level): string
    {
        return match (true) {
            $level >= 3 => 'pill-hard',
            $level === 2 => 'pill-medium',
            default => 'pill-easy',
        };
    }

    private function difficultyKey(int $level): string
    {
        return match (true) {
            $level >= 3 => 'hard',
            $level === 2 => 'medium',
            default => 'easy',
        };
    }

    private function filterTags(SchoolSubject $subject): string
    {
        return match ($subject) {
            SchoolSubject::Georgian => 'pop read',
            SchoolSubject::Math => 'pop math',
            SchoolSubject::History => 'new',
        };
    }

    private function ageForGrade(SchoolGrade $grade): int
    {
        return match ($grade) {
            SchoolGrade::First => 6,
            SchoolGrade::Second => 7,
            SchoolGrade::Third => 8,
        };
    }
}
