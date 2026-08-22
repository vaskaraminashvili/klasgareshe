<?php

namespace App\Repositories;

use App\Enums\PlanProgressStatus;
use App\Enums\SchoolGrade;
use App\Enums\SchoolSubject;
use App\Models\User;
use App\Models\UserPlanProgress;
use App\Models\WeekPlanItem;
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
}
