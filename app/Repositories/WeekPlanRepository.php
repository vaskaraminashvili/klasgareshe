<?php

namespace App\Repositories;

use App\Enums\PlanProgressStatus;
use App\Enums\SchoolGrade;
use App\Enums\SchoolSubject;
use App\Models\User;
use App\Models\UserPlanProgress;
use App\Models\WeekPlanItem;
use Carbon\CarbonInterface;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;

class WeekPlanRepository
{
    /**
     * @return Collection<int, WeekPlanItem>
     */
    public function itemsForGrade(SchoolGrade $grade, int $weekNumber = 1): Collection
    {
        return WeekPlanItem::query()
            ->where('grade', $grade)
            ->where('week_number', $weekNumber)
            ->orderBy('weekday')
            ->orderBy('id')
            ->get();
    }

    public function find(int $id): ?WeekPlanItem
    {
        return WeekPlanItem::query()->find($id);
    }

    public function findForGrade(int $id, SchoolGrade $grade): ?WeekPlanItem
    {
        return WeekPlanItem::query()
            ->whereKey($id)
            ->where('grade', $grade)
            ->first();
    }

    /**
     * @return list<int>
     */
    public function questionIds(WeekPlanItem $item): array
    {
        $ids = [];

        foreach ($item->questions()->orderByPivot('sort_order')->pluck('questions.id') as $id) {
            $ids[] = (int) $id;
        }

        return $ids;
    }

    /**
     * @return list<int>
     */
    public function completedItemIds(User $user): array
    {
        $ids = [];

        foreach (UserPlanProgress::query()
            ->where('user_id', $user->id)
            ->where('status', PlanProgressStatus::Completed)
            ->pluck('week_plan_item_id') as $id) {
            $ids[] = (int) $id;
        }

        return $ids;
    }

    /**
     * @return array<int, Carbon>
     */
    public function completedAtByItem(User $user): array
    {
        $rows = UserPlanProgress::query()
            ->where('user_id', $user->id)
            ->where('status', PlanProgressStatus::Completed)
            ->get(['week_plan_item_id', 'completed_at']);

        $map = [];

        foreach ($rows as $row) {
            if ($row->completed_at instanceof Carbon) {
                $map[$row->week_plan_item_id] = $row->completed_at;
            }
        }

        return $map;
    }

    public function completedTodayCount(User $user): int
    {
        return UserPlanProgress::query()
            ->where('user_id', $user->id)
            ->where('status', PlanProgressStatus::Completed)
            ->whereDate('completed_at', now()->toDateString())
            ->count();
    }

    public function isCompleted(User $user, int $itemId): bool
    {
        return UserPlanProgress::query()
            ->where('user_id', $user->id)
            ->where('week_plan_item_id', $itemId)
            ->where('status', PlanProgressStatus::Completed)
            ->exists();
    }

    public function markCompleted(User $user, int $itemId, int $correctCount): void
    {
        UserPlanProgress::query()->updateOrCreate(
            [
                'user_id' => $user->id,
                'week_plan_item_id' => $itemId,
            ],
            [
                'status' => PlanProgressStatus::Completed,
                'correct_count' => max(0, $correctCount),
                'completed_at' => now(),
            ],
        );
    }

    public function nextIncomplete(User $user, SchoolGrade $grade, SchoolSubject $subject, int $weekNumber = 1): ?WeekPlanItem
    {
        $completed = $this->completedItemIds($user);

        $query = WeekPlanItem::query()
            ->where('grade', $grade)
            ->where('week_number', $weekNumber)
            ->where('subject', $subject)
            ->orderBy('weekday')
            ->orderBy('id');

        if ($completed !== []) {
            $query->whereNotIn('id', $completed);
        }

        return $query->first();
    }

    public function completedCount(User $user): int
    {
        return UserPlanProgress::query()
            ->where('user_id', $user->id)
            ->where('status', PlanProgressStatus::Completed)
            ->count();
    }

    public function completedCountForSubject(User $user, SchoolSubject $subject): int
    {
        return UserPlanProgress::query()
            ->where('user_id', $user->id)
            ->where('status', PlanProgressStatus::Completed)
            ->whereHas('item', fn ($query) => $query->where('subject', $subject))
            ->count();
    }

    public function distinctCompletedSubjectCount(User $user): int
    {
        return (int) UserPlanProgress::query()
            ->where('user_plan_progress.user_id', $user->id)
            ->where('user_plan_progress.status', PlanProgressStatus::Completed)
            ->join('week_plan_items', 'week_plan_items.id', '=', 'user_plan_progress.week_plan_item_id')
            ->distinct()
            ->count('week_plan_items.subject');
    }

    public function subjectCorrectCount(User $user, SchoolSubject $subject): int
    {
        return (int) UserPlanProgress::query()
            ->where('user_id', $user->id)
            ->where('status', PlanProgressStatus::Completed)
            ->whereHas('item', fn ($query) => $query->where('subject', $subject))
            ->sum('correct_count');
    }

    public function perfectPackCount(User $user): int
    {
        return UserPlanProgress::query()
            ->where('user_plan_progress.user_id', $user->id)
            ->where('user_plan_progress.status', PlanProgressStatus::Completed)
            ->join('week_plan_items', 'week_plan_items.id', '=', 'user_plan_progress.week_plan_item_id')
            ->whereColumn('user_plan_progress.correct_count', '>=', 'week_plan_items.questions_per_round')
            ->count();
    }

    public function consecutivePerfectPackCount(User $user): int
    {
        $rows = UserPlanProgress::query()
            ->where('user_plan_progress.user_id', $user->id)
            ->where('user_plan_progress.status', PlanProgressStatus::Completed)
            ->join('week_plan_items', 'week_plan_items.id', '=', 'user_plan_progress.week_plan_item_id')
            ->orderBy('user_plan_progress.completed_at')
            ->orderBy('user_plan_progress.id')
            ->get([
                'user_plan_progress.correct_count',
                'week_plan_items.questions_per_round',
            ]);

        $streak = 0;

        foreach ($rows->reverse() as $row) {
            $needed = (int) $row->getAttribute('questions_per_round');
            if ((int) $row->correct_count >= $needed) {
                $streak++;

                continue;
            }

            break;
        }

        return $streak;
    }

    public function completedAfterHourCount(User $user, int $hour): int
    {
        $count = 0;

        foreach (UserPlanProgress::query()
            ->where('user_id', $user->id)
            ->where('status', PlanProgressStatus::Completed)
            ->whereNotNull('completed_at')
            ->get(['completed_at']) as $row) {
            if ($row->completed_at instanceof CarbonInterface && $row->completed_at->hour >= $hour) {
                $count++;
            }
        }

        return $count;
    }

    public function hasPerfectSubjectWeek(User $user, SchoolGrade $grade, int $weekNumber = 1): bool
    {
        $items = $this->itemsForGrade($grade, $weekNumber);
        $progress = UserPlanProgress::query()
            ->where('user_id', $user->id)
            ->where('status', PlanProgressStatus::Completed)
            ->whereIn('week_plan_item_id', $items->pluck('id')->all())
            ->get()
            ->keyBy('week_plan_item_id');

        foreach (SchoolSubject::ordered() as $subject) {
            $subjectItems = $items->where('subject', $subject);

            if ($subjectItems->isEmpty()) {
                continue;
            }

            $allPerfect = true;

            foreach ($subjectItems as $item) {
                $row = $progress->get($item->id);

                if (! $row instanceof UserPlanProgress
                    || $row->correct_count < $item->questions_per_round) {
                    $allPerfect = false;

                    break;
                }
            }

            if ($allPerfect) {
                return true;
            }
        }

        return false;
    }
}
