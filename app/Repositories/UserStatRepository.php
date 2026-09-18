<?php

namespace App\Repositories;

use App\Models\User;
use App\Models\UserActivityDay;
use App\Models\UserStat;
use Carbon\CarbonImmutable;
use Carbon\CarbonInterface;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;

class UserStatRepository
{
    public function firstOrCreateFor(User $user): UserStat
    {
        return UserStat::query()->firstOrCreate(
            ['user_id' => $user->id],
            UserStat::defaults(),
        );
    }

    /**
     * @param  array<string, mixed>  $attributes
     */
    public function update(UserStat $stat, array $attributes): UserStat
    {
        $stat->update($attributes);

        return $stat->fresh() ?? $stat;
    }

    public function addDayXp(User $user, string $playedOn, int $xp): void
    {
        $day = UserActivityDay::query()
            ->where('user_id', $user->id)
            ->whereDate('played_on', $playedOn)
            ->first();

        if ($day === null) {
            $day = UserActivityDay::query()->create([
                'user_id' => $user->id,
                'played_on' => $playedOn,
                'xp_earned' => 0,
            ]);
        }

        if ($xp > 0) {
            $day->increment('xp_earned', $xp);
        }
    }

    /**
     * @return list<string>
     */
    public function playedDatesBetween(User $user, string $from, string $to): array
    {
        $dates = [];

        foreach (UserActivityDay::query()
            ->where('user_id', $user->id)
            ->whereDate('played_on', '>=', $from)
            ->whereDate('played_on', '<=', $to)
            ->orderBy('played_on')
            ->pluck('played_on') as $date) {
            $dates[] = $date instanceof CarbonInterface
                ? $date->toDateString()
                : CarbonImmutable::parse((string) $date)->toDateString();
        }

        return $dates;
    }

    /**
     * @return array<string, int> keyed by Y-m-d
     */
    public function xpByDateBetween(User $user, string $from, string $to): array
    {
        $map = [];

        foreach (UserActivityDay::query()
            ->where('user_id', $user->id)
            ->whereDate('played_on', '>=', $from)
            ->whereDate('played_on', '<=', $to)
            ->get(['played_on', 'xp_earned']) as $day) {
            $map[$day->played_on->toDateString()] = (int) $day->xp_earned;
        }

        return $map;
    }

    public function sumXpBetween(User $user, string $from, string $to): int
    {
        return (int) UserActivityDay::query()
            ->where('user_id', $user->id)
            ->whereDate('played_on', '>=', $from)
            ->whereDate('played_on', '<=', $to)
            ->sum('xp_earned');
    }

    public function countLearners(): int
    {
        return $this->publicRankingQuery()->count();
    }

    /**
     * @return Collection<int, UserStat>
     */
    public function topByXp(int $limit = 50): Collection
    {
        return $this->publicRankingQuery()
            ->with('user')
            ->orderByDesc('xp')
            ->orderBy('user_id')
            ->limit($limit)
            ->get();
    }

    public function rankFor(User $user): ?int
    {
        if (! $user->show_on_leaderboard) {
            return null;
        }

        $stat = $this->firstOrCreateFor($user);

        return 1 + (int) $this->publicRankingQuery()
            ->where(function ($query) use ($stat): void {
                $query->where('xp', '>', $stat->xp)
                    ->orWhere(function ($inner) use ($stat): void {
                        $inner->where('xp', $stat->xp)
                            ->where('user_id', '<', $stat->user_id);
                    });
            })
            ->count();
    }

    public function xpAtRank(int $rank): ?int
    {
        if ($rank < 1) {
            return null;
        }

        $row = $this->publicRankingQuery()
            ->orderByDesc('xp')
            ->orderBy('user_id')
            ->skip($rank - 1)
            ->take(1)
            ->first();

        return $row?->xp;
    }

    /**
     * Public weekly ranking: visible kids ordered by XP earned in [from, to].
     *
     * @return Collection<int, UserStat>
     */
    public function topByWeekXp(string $from, string $to, int $limit = 50): Collection
    {
        return $this->publicRankingQuery()
            ->with('user')
            ->leftJoinSub($this->weekXpSubquery($from, $to), 'week_totals', 'week_totals.user_id', '=', 'user_stats.user_id')
            ->orderByRaw('COALESCE(week_totals.week_xp, 0) DESC')
            ->orderBy('user_stats.user_id')
            ->limit($limit)
            ->get(['user_stats.*']);
    }

    public function rankForWeek(User $user, string $from, string $to): ?int
    {
        if (! $user->show_on_leaderboard) {
            return null;
        }

        $mine = $this->sumXpBetween($user, $from, $to);

        return 1 + (int) $this->publicRankingQuery()
            ->leftJoinSub($this->weekXpSubquery($from, $to), 'week_totals', 'week_totals.user_id', '=', 'user_stats.user_id')
            ->where(function ($query) use ($mine, $user): void {
                $query->whereRaw('COALESCE(week_totals.week_xp, 0) > ?', [$mine])
                    ->orWhere(function ($inner) use ($mine, $user): void {
                        $inner->whereRaw('COALESCE(week_totals.week_xp, 0) = ?', [$mine])
                            ->where('user_stats.user_id', '<', $user->id);
                    });
            })
            ->count();
    }

    public function weekXpAtRank(int $rank, string $from, string $to): ?int
    {
        if ($rank < 1) {
            return null;
        }

        $row = $this->publicRankingQuery()
            ->leftJoinSub($this->weekXpSubquery($from, $to), 'week_totals', 'week_totals.user_id', '=', 'user_stats.user_id')
            ->orderByRaw('COALESCE(week_totals.week_xp, 0) DESC')
            ->orderBy('user_stats.user_id')
            ->skip($rank - 1)
            ->take(1)
            ->first(['user_stats.user_id']);

        if ($row === null) {
            return null;
        }

        return $this->sumXpBetween(User::query()->findOrFail($row->user_id), $from, $to);
    }

    /**
     * @return Builder<UserStat>
     */
    private function publicRankingQuery(): Builder
    {
        return UserStat::query()->visibleOnLeaderboard();
    }

    /**
     * @return Builder<UserActivityDay>
     */
    private function weekXpSubquery(string $from, string $to): Builder
    {
        return UserActivityDay::query()
            ->select('user_id')
            ->selectRaw('SUM(xp_earned) as week_xp')
            ->whereDate('played_on', '>=', $from)
            ->whereDate('played_on', '<=', $to)
            ->groupBy('user_id');
    }
}
