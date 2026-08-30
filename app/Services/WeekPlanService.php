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
    public const CURRICULUM_WEEK = 1;

    public const MISSION_TOTAL = 3;

    public function __construct(private WeekPlanRepository $plans) {}

    public function gradeFor(User $user): SchoolGrade
    {
        return $user->grade ?? SchoolGrade::First;
    }

    public function homePlan(User $user): HomeWeekPlan
    {
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

        $missionDone = min(self::MISSION_TOTAL, $this->plans->completedTodayCount($user));
        $items = $this->plans->itemsForGrade($this->gradeFor($user), self::CURRICULUM_WEEK);
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
        foreach (SchoolSubject::ordered() as $subject) {
            $item = $this->plans->nextIncomplete(
                $user,
                $this->gradeFor($user),
                $subject,
                self::CURRICULUM_WEEK,
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
            self::CURRICULUM_WEEK,
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
        $items = $this->checklist($user);

        return new DailyMissionSnapshot(
            missionDone: $home->missionDone,
            missionTotal: $home->missionTotal,
            hoursLeft: $home->hoursLeft,
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
        $items = $this->plans->itemsForGrade($grade, self::CURRICULUM_WEEK);
        $completedIds = $this->plans->completedItemIds($user);
        $completedAt = $this->plans->completedAtByItem($user);
        $nextBySubject = [];

        foreach (SchoolSubject::ordered() as $subject) {
            $next = $this->plans->nextIncomplete($user, $grade, $subject, self::CURRICULUM_WEEK);
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
        $items = $this->plans->itemsForGrade($grade, self::CURRICULUM_WEEK);
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
