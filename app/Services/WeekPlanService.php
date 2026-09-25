<?php

namespace App\Services;

use App\Data\DailyMissionSnapshot;
use App\Data\HomeWeekPlan;
use App\Data\SubjectMasteryRow;
use App\Data\WeekChecklistItem;
use App\Data\WeekPlanTaskView;
use App\Enums\GameType;
use App\Enums\PlayDifficulty;
use App\Enums\SchoolGrade;
use App\Enums\SchoolSubject;
use App\Models\User;
use App\Models\WeekPlanItem;
use App\Repositories\WeekPlanRepository;
use Carbon\CarbonImmutable;
use InvalidArgumentException;

class WeekPlanService
{
    public const MISSION_TOTAL = 3;

    public function __construct(private WeekPlanRepository $plans) {}

    public function gradeFor(User $user): SchoolGrade
    {
        return $user->grade ?? SchoolGrade::First;
    }

    public function findItem(int $id): ?WeekPlanItem
    {
        return $this->plans->find($id);
    }

    public function dailyMissionJustCompleted(User $user): bool
    {
        return count($this->plans->subjectsCompletedToday($user)) >= self::MISSION_TOTAL;
    }

    /**
     * Active curriculum week: lowest week with incomplete packs, or the last
     * seeded week when everything is done.
     */
    public function activeWeekNumber(User $user): int
    {
        $grade = $this->gradeFor($user);
        $incomplete = $this->plans->lowestIncompleteWeekNumber($user, $grade);

        if ($incomplete !== null) {
            return $incomplete;
        }

        return $this->plans->maxWeekNumber($grade);
    }

    public function homePlan(User $user): HomeWeekPlan
    {
        $weekNumber = $this->activeWeekNumber($user);
        $tasks = $this->homeTasks($user);
        $continue = $this->firstIncomplete($user);
        $heroTitle = (string) __('home.week_complete');
        $continueItemId = null;
        $continueTitle = $heroTitle;

        if ($continue instanceof WeekPlanItem) {
            $heroTitle = $continue->title;
            $continueItemId = $continue->id;
            $continueTitle = $continue->title;
        }

        $missionDone = min(
            self::MISSION_TOTAL,
            count($this->plans->subjectsCompletedToday($user)),
        );
        $items = $this->plans->itemsForGrade($this->gradeFor($user), $weekNumber);
        $completedIds = $this->plans->completedItemIds($user);
        $weekCompleted = 0;

        foreach ($items as $item) {
            if (in_array($item->id, $completedIds, true)) {
                $weekCompleted++;
            }
        }

        return new HomeWeekPlan(
            missionDone: $missionDone,
            missionTotal: self::MISSION_TOTAL,
            hoursLeft: $this->hoursLeftUntilSunday(),
            heroTitle: $heroTitle,
            continueItemId: $continueItemId,
            continueTitle: $continueTitle,
            tasks: $tasks,
            weekCompleted: $weekCompleted,
            weekTotal: $items->count(),
        );
    }

    /**
     * @return list<WeekPlanTaskView>
     */
    public function homeTasks(User $user): array
    {
        $tasks = [];

        foreach (SchoolSubject::ordered() as $subject) {
            $tasks[] = $this->taskViewForSubject($user, $subject);
        }

        return $tasks;
    }

    public function firstIncomplete(User $user): ?WeekPlanItem
    {
        $weekNumber = $this->activeWeekNumber($user);

        foreach (SchoolSubject::ordered() as $subject) {
            $item = $this->plans->nextIncomplete(
                $user,
                $this->gradeFor($user),
                $subject,
                $weekNumber,
            );

            if ($item instanceof WeekPlanItem) {
                return $item;
            }
        }

        return null;
    }

    public function firstIncompleteOfType(User $user, GameType $type): ?WeekPlanItem
    {
        $grade = $this->gradeFor($user);
        $weekNumber = $this->activeWeekNumber($user);
        $completed = array_flip($this->plans->completedItemIds($user));

        foreach ($this->plans->itemsForGrade($grade, $weekNumber) as $item) {
            if (isset($completed[$item->id]) || $item->game_slug !== $type) {
                continue;
            }

            $next = $this->plans->nextIncomplete($user, $grade, $item->subject, $weekNumber);

            if ($next instanceof WeekPlanItem && $next->id === $item->id) {
                return $item;
            }
        }

        return null;
    }

    public function nextIncompleteForSubject(User $user, SchoolSubject $subject): ?WeekPlanItem
    {
        return $this->plans->nextIncomplete(
            $user,
            $this->gradeFor($user),
            $subject,
            $this->activeWeekNumber($user),
        );
    }

    public function findPlayable(User $user, int $itemId): WeekPlanItem
    {
        $item = $this->plans->findForGrade($itemId, $this->gradeFor($user));

        if ($item === null) {
            throw new InvalidArgumentException('Week plan item is not available.');
        }

        if ($this->plans->isCompleted($user, $item->id)) {
            throw new InvalidArgumentException('Week plan item is already completed.');
        }

        if (! $this->isPlayable($user, $item)) {
            throw new InvalidArgumentException('Week plan item is locked until earlier packs are finished.');
        }

        return $item;
    }

    public function isCompleted(User $user, WeekPlanItem $item): bool
    {
        return $this->plans->isCompleted($user, $item->id);
    }

    public function isPlayable(User $user, WeekPlanItem $item): bool
    {
        if ($item->grade !== $this->gradeFor($user)) {
            return false;
        }

        if ($this->plans->isCompleted($user, $item->id)) {
            return false;
        }

        if ($item->week_number > $this->activeWeekNumber($user)) {
            return false;
        }

        $next = $this->plans->nextIncomplete(
            $user,
            $item->grade,
            $item->subject,
            $item->week_number,
        );

        return $next instanceof WeekPlanItem && $next->id === $item->id;
    }

    public function blockingPack(User $user, WeekPlanItem $item): ?WeekPlanItem
    {
        $completed = array_flip($this->plans->completedItemIds($user));

        foreach ($this->plans->itemsForSubject($this->gradeFor($user), $item->subject) as $candidate) {
            if ($candidate->id === $item->id) {
                break;
            }

            if (! isset($completed[$candidate->id])) {
                return $candidate;
            }
        }

        return null;
    }

    public function packHref(User $user, WeekPlanItem $item): string
    {
        if ($this->isCompleted($user, $item) || $this->isPlayable($user, $item)) {
            return route('lesson-details', ['item' => $item->id]);
        }

        return route('lesson-locked', ['item' => $item->id]);
    }

    /**
     * @return list<int>
     */
    public function questionIds(WeekPlanItem $item, ?User $user = null): array
    {
        $rows = $this->plans->questionRows($item);
        $difficulty = PlayDifficulty::Medium;

        if ($user?->play_difficulty instanceof PlayDifficulty) {
            $difficulty = $user->play_difficulty;
        }

        $want = $difficulty->value;
        $picked = $this->rowsForDifficulty($rows, $want);

        if ($picked === []) {
            $picked = $this->rowsForDifficulty($rows, PlayDifficulty::Medium->value);
        }

        if ($picked === []) {
            $picked = $rows;
        }

        $ids = [];

        foreach ($picked as $row) {
            $ids[] = $row['id'];
        }

        return array_slice($ids, 0, $item->questions_per_round);
    }

    /**
     * @param  list<array{id: int, difficulty: string}>  $rows
     * @return list<array{id: int, difficulty: string}>
     */
    private function rowsForDifficulty(array $rows, string $difficulty): array
    {
        $picked = [];

        foreach ($rows as $row) {
            if ($row['difficulty'] === $difficulty) {
                $picked[] = $row;
            }
        }

        return $picked;
    }

    public function completeItem(User $user, int $itemId, int $correctCount): void
    {
        $item = $this->plans->findForGrade($itemId, $this->gradeFor($user));

        if ($item === null) {
            return;
        }

        $weekBefore = $this->activeWeekNumber($user);
        $this->plans->markCompleted($user, $item->id, $correctCount);
        $weekAfter = $this->activeWeekNumber($user);

        if ($weekAfter > $weekBefore) {
            app(NotificationService::class)->newWeekUnlocked($user, $weekAfter);
        }
    }

    public function dailyMission(User $user): DailyMissionSnapshot
    {
        $home = $this->homePlan($user);
        $completedAt = $this->plans->completedAtByItem($user);
        $items = [];

        foreach ($home->tasks as $task) {
            $weekday = 0;
            $completedAtLabel = null;

            if ($task->id !== null) {
                $pack = $this->plans->find($task->id);
                $weekday = $pack->weekday ?? 0;

                if ($task->completed && isset($completedAt[$task->id])) {
                    $completedAtLabel = $completedAt[$task->id]->format('H:i');
                }
            }

            $items[] = new WeekChecklistItem(
                id: $task->id ?? 0,
                weekday: $weekday,
                subject: $task->subject,
                title: $task->title,
                completed: $task->completed,
                playable: $task->playable,
                current: $task->playable,
                emoji: $task->emoji,
                completedAt: $completedAtLabel,
                subtitle: $task->subtitle,
                href: $task->href,
            );
        }

        return new DailyMissionSnapshot(
            missionDone: $home->missionDone,
            missionTotal: $home->missionTotal,
            hoursLeft: $this->hoursLeftUntilEndOfDay(),
            weekCompleted: $home->weekCompleted,
            weekTotal: $home->weekTotal,
            items: $items,
            streak: 0,
        );
    }

    /**
     * @return list<WeekChecklistItem>
     */
    public function checklist(User $user): array
    {
        $grade = $this->gradeFor($user);
        $weekNumber = $this->activeWeekNumber($user);
        $items = $this->plans->itemsForGrade($grade, $weekNumber);
        $completedIds = $this->plans->completedItemIds($user);
        $completedAt = $this->plans->completedAtByItem($user);
        $nextBySubject = [];

        foreach (SchoolSubject::ordered() as $subject) {
            $next = $this->plans->nextIncomplete($user, $grade, $subject, $weekNumber);
            $nextBySubject[$subject->value] = $next?->id;
        }

        $rows = [];

        foreach ($items as $item) {
            $done = in_array($item->id, $completedIds, true);
            $nextId = $nextBySubject[$item->subject->value] ?? null;
            $playable = ! $done && $nextId === $item->id;
            $completedAtLabel = null;

            if ($done && isset($completedAt[$item->id])) {
                $completedAtLabel = $completedAt[$item->id]->format('H:i');
            }

            $rows[] = new WeekChecklistItem(
                id: $item->id,
                weekday: $item->weekday,
                subject: $item->subject,
                title: $item->title,
                completed: $done,
                playable: $playable,
                current: $playable,
                emoji: $item->subject->emoji(),
                completedAt: $completedAtLabel,
                href: $playable ? $this->playUrl($item->id) : route('daily-mission'),
            );
        }

        return $rows;
    }

    public function quizRoute(?int $itemId): string
    {
        return $this->playUrl($itemId);
    }

    public function playUrl(?int $itemId, bool $missionIfMissing = false): string
    {
        if ($itemId === null) {
            return $missionIfMissing
                ? route('daily-mission')
                : route(GameType::MultipleChoice->playerRoute());
        }

        $item = $this->plans->find($itemId);
        $type = $item === null ? GameType::MultipleChoice : $item->game_slug;

        return route($type->playerRoute(), ['item' => $itemId]);
    }

    public function hoursLeftUntilSunday(): int
    {
        $end = CarbonImmutable::now()->endOfWeek(CarbonImmutable::SUNDAY);

        return max(0, (int) round(CarbonImmutable::now()->diffInHours($end, false)));
    }

    public function hoursLeftUntilEndOfDay(): int
    {
        $end = CarbonImmutable::now()->endOfDay();

        return max(0, (int) round(CarbonImmutable::now()->diffInHours($end, false)));
    }

    public function minutesLeftUntilEndOfDay(): int
    {
        $end = CarbonImmutable::now()->endOfDay();
        $seconds = max(0, (int) CarbonImmutable::now()->diffInSeconds($end, false));

        return intdiv($seconds % 3600, 60);
    }

    public function secondsLeftUntilEndOfDay(): int
    {
        $end = CarbonImmutable::now()->endOfDay();
        $seconds = max(0, (int) CarbonImmutable::now()->diffInSeconds($end, false));

        return $seconds % 60;
    }

    public function lessonsCompletedThisWeek(User $user): int
    {
        $start = CarbonImmutable::now()->startOfWeek(CarbonImmutable::MONDAY);

        return $this->plans->completedCountBetween(
            $user,
            $start->toDateString(),
            $start->addDays(6)->toDateString(),
        );
    }

    /**
     * @return list<SubjectMasteryRow>
     */
    public function subjectMastery(User $user): array
    {
        $grade = $this->gradeFor($user);
        $weekNumber = $this->activeWeekNumber($user);
        $items = $this->plans->itemsForGrade($grade, $weekNumber);
        $completedIds = array_flip($this->plans->completedItemIds($user));
        $rows = [];

        foreach (SchoolSubject::ordered() as $subject) {
            $subjectItems = $items->where('subject', $subject);
            $total = $subjectItems->count();
            $done = 0;

            foreach ($subjectItems as $item) {
                if (isset($completedIds[$item->id])) {
                    $done++;
                }
            }

            $percent = $total > 0 ? (int) round(($done / $total) * 100) : 0;
            $next = $this->nextIncompleteForSubject($user, $subject);

            $rows[] = new SubjectMasteryRow(
                subject: $subject,
                label: $subject->label(),
                emoji: $subject->emoji(),
                tile: $subject->tile(),
                progressClass: $subject->progressClass(),
                percent: $percent,
                done: $done,
                total: $total,
                nextItemId: $next?->id,
                nextGame: $next?->game_slug,
            );
        }

        return $rows;
    }

    private function taskViewForSubject(User $user, SchoolSubject $subject): WeekPlanTaskView
    {
        $doneToday = $this->plans->latestCompletedTodayForSubject($user, $subject);

        if ($doneToday instanceof WeekPlanItem) {
            $completedAt = $this->plans->completedAtByItem($user)[$doneToday->id] ?? null;

            return new WeekPlanTaskView(
                id: $doneToday->id,
                subject: $subject,
                title: $doneToday->title,
                subtitle: $completedAt !== null
                    ? (string) __('home.completed_today_at', ['time' => $completedAt->format('H:i')])
                    : (string) __('home.completed_today', ['time' => '']),
                completed: true,
                playable: false,
                emoji: $subject->emoji(),
                tile: $subject->tile(),
                inkClass: $subject->inkClass(),
                href: route('daily-mission'),
            );
        }

        $item = $this->nextIncompleteForSubject($user, $subject);

        if ($item === null) {
            return new WeekPlanTaskView(
                id: null,
                subject: $subject,
                title: $subject->label(),
                subtitle: (string) __('home.subject_complete'),
                completed: true,
                playable: false,
                emoji: $subject->emoji(),
                tile: $subject->tile(),
                inkClass: $subject->inkClass(),
                href: route('daily-mission'),
            );
        }

        return new WeekPlanTaskView(
            id: $item->id,
            subject: $subject,
            title: $item->title,
            subtitle: (string) __('home.weekday_pack', [
                'day' => __('home.weekdays.'.$item->weekday),
            ]),
            completed: false,
            playable: true,
            emoji: $subject->emoji(),
            tile: $subject->tile(),
            inkClass: $subject->inkClass(),
            href: $this->playUrl($item->id),
        );
    }
}
