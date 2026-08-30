<?php

namespace App\Services;

use App\Data\DailyMissionSnapshot;
use App\Data\HomeWeekPlan;
use App\Data\SubjectMasteryRow;
use App\Data\WeekChecklistItem;
use App\Data\WeekPlanTaskView;
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

        $next = $this->plans->nextIncomplete(
            $user,
            $item->grade,
            $item->subject,
            $item->week_number,
        );

        if ($next === null || $next->id !== $item->id) {
            throw new InvalidArgumentException('Week plan item is locked until earlier packs are finished.');
        }

        // Packs from a future curriculum week stay locked until earlier weeks are done.
        if ($item->week_number > $this->activeWeekNumber($user)) {
            throw new InvalidArgumentException('Week plan item is locked until earlier packs are finished.');
        }

        return $item;
    }

    /**
     * @return list<int>
     */
    public function questionIds(WeekPlanItem $item): array
    {
        return array_slice(
            $this->plans->questionIds($item),
            0,
            $item->questions_per_round,
        );
    }

    public function completeItem(User $user, int $itemId, int $correctCount): void
    {
        $item = $this->plans->findForGrade($itemId, $this->gradeFor($user));

        if ($item === null) {
            return;
        }

        $this->plans->markCompleted($user, $item->id, $correctCount);
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
                $weekday = $pack?->weekday ?? 0;

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
            );
        }

        return $rows;
    }

    public function quizRoute(?int $itemId): string
    {
        if ($itemId === null) {
            return route('game-multiple-choice');
        }

        return route('game-multiple-choice', ['item' => $itemId]);
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
        );
    }
}
