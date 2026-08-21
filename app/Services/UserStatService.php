<?php

namespace App\Services;

use App\Data\HomeStats;
use App\Data\LeaderboardEntry;
use App\Data\LeaderboardSnapshot;
use App\Data\XpProgressSnapshot;
use App\Models\User;
use App\Models\UserStat;
use App\Repositories\UserStatRepository;
use Carbon\CarbonImmutable;
use Carbon\CarbonInterface;
use InvalidArgumentException;

class UserStatService
{
    /** @var list<string> */
    private const AVATARS = ['🐻', '🦊', '🐰', '🐼', '🐨', '🦄', '🐢', '🦁', '🐸', '🐯'];

    public function __construct(
        private UserStatRepository $stats,
        private LevelCalculator $levels,
        private LeagueSeasonService $leagues,
    ) {}

    public function ensureFor(User $user): UserStat
    {
        return $this->stats->firstOrCreateFor($user);
    }

    public function homeSnapshot(User $user): HomeStats
    {
        $stat = $this->ensureFor($user);
        $start = now()->startOfWeek(CarbonImmutable::MONDAY);
        $played = $this->stats->playedDatesBetween(
            $user,
            $start->toDateString(),
            $start->addDays(6)->toDateString(),
        );
        $playedSet = array_flip($played);
        $today = now()->toDateString();
        $weekDays = [];

        for ($i = 0; $i < 7; $i++) {
            $day = $start->addDays($i);
            $date = $day->toDateString();

            $weekDays[] = [
                'letter' => $this->weekdayLetter($day->dayOfWeekIso),
                'on' => array_key_exists($date, $playedSet),
                'today' => $date === $today,
            ];
        }

        return new HomeStats(
            streak: $stat->current_streak,
            xp: $stat->xp,
            league: $stat->league,
            leagueLabel: $stat->league->label(),
            weekActiveDays: count($played),
            weekDays: $weekDays,
        );
    }

    public function xpProgressSnapshot(User $user): XpProgressSnapshot
    {
        $stat = $this->ensureFor($user);
        $level = $this->levels->forXp($stat->xp);
        $end = CarbonImmutable::now()->startOfDay();
        $start = $end->subDays(6);
        $xpMap = $this->stats->xpByDateBetween($user, $start->toDateString(), $end->toDateString());

        $chartDays = [];
        $activeDays = 0;
        $bestDayXp = 0;
        $bestDayLabel = '—';
        $quietDayLabel = '—';
        $quietDayXp = PHP_INT_MAX;

        for ($i = 0; $i < 7; $i++) {
            $day = $start->addDays($i);
            $date = $day->toDateString();
            $value = $xpMap[$date] ?? 0;
            $label = $this->weekdayLetter($day->dayOfWeekIso);
            $chartDays[] = ['label' => $label, 'value' => $value];

            if ($value > 0) {
                $activeDays++;
            }
            if ($value > $bestDayXp) {
                $bestDayXp = $value;
                $bestDayLabel = $label;
            }
            if ($value < $quietDayXp) {
                $quietDayXp = $value;
                $quietDayLabel = $label;
            }
        }

        $weekXp = array_sum(array_column($chartDays, 'value'));
        $todayXp = $xpMap[$end->toDateString()] ?? 0;
        $avgPerDay = $activeDays > 0 ? (int) round($weekXp / $activeDays) : 0;

        $prevEnd = $start->subDay();
        $prevStart = $prevEnd->subDays(6);
        $lastWeekXp = $this->stats->sumXpBetween($user, $prevStart->toDateString(), $prevEnd->toDateString());
        $vsLastWeek = $lastWeekXp > 0
            ? (int) round((($weekXp - $lastWeekXp) / $lastWeekXp) * 100)
            : ($weekXp > 0 ? 100 : 0);

        return new XpProgressSnapshot(
            level: $level,
            todayXp: $todayXp,
            weekXp: $weekXp,
            avgPerDay: $avgPerDay,
            vsLastWeekPercent: $vsLastWeek,
            bestDayXp: $bestDayXp,
            bestDayLabel: $bestDayLabel,
            quietDayLabel: $quietDayLabel,
            activeDays: $activeDays,
            chartDays: $chartDays,
            chartJson: json_encode($chartDays, JSON_THROW_ON_ERROR),
        );
    }

    public function leaderboardSnapshot(User $user, int $limit = 50): LeaderboardSnapshot
    {
        $stat = $this->ensureFor($user);
        $level = $this->levels->forXp($stat->xp);
        $yourRank = $this->stats->rankFor($user);
        $total = max(1, $this->stats->countLearners());
        $percentile = (int) max(1, min(100, ceil(($yourRank / $total) * 100)));

        $xpAbove = null;
        if ($yourRank > 1) {
            $xpAbove = $this->stats->xpAtRank($yourRank - 1);
        }
        $xpToNext = $xpAbove !== null ? max(0, $xpAbove - $stat->xp + 1) : 0;

        $entries = [];
        $rank = 0;
        foreach ($this->stats->topByXp($limit) as $row) {
            $rank++;
            $owner = $row->user;
            if ($owner === null) {
                continue;
            }
            $entries[] = new LeaderboardEntry(
                rank: $rank,
                userId: $owner->id,
                name: $owner->name,
                xp: $row->xp,
                level: $this->levels->forXp($row->xp)->level,
                streak: $row->current_streak,
                isYou: $owner->id === $user->id,
                avatar: $this->avatarFor($owner->id),
            );
        }

        $podium = [];
        foreach ($entries as $entry) {
            if ($entry->rank <= 3) {
                $podium[] = $entry;
            }
        }

        return new LeaderboardSnapshot(
            totalPlayers: $total,
            yourRank: $yourRank,
            yourXp: $stat->xp,
            yourName: $user->name,
            yourLevel: $level->level,
            yourStreak: $stat->current_streak,
            yourAvatar: $this->avatarFor($user->id),
            xpToNextRank: $xpToNext,
            percentileLabel: __('ranking.top_percentile', ['percent' => $percentile]),
            podium: $podium,
            rows: $entries,
            level: $level,
        );
    }

    public function recordPlay(User $user, int $xp = 0, ?CarbonInterface $playedOn = null): UserStat
    {
        if ($xp < 0) {
            throw new InvalidArgumentException('XP cannot be negative.');
        }

        $on = CarbonImmutable::parse($playedOn ?? now())->startOfDay();
        $onDate = $on->toDateString();
        $stat = $this->ensureFor($user);

        $this->stats->addDayXp($user, $onDate, $xp);

        $lastDate = $stat->last_played_on?->toDateString();
        $streak = $stat->current_streak;

        if ($lastDate !== $onDate) {
            $streak = $lastDate === $on->subDay()->toDateString()
                ? $stat->current_streak + 1
                : 1;
        }

        $updated = $this->stats->update($stat, [
            'xp' => $stat->xp + $xp,
            'current_streak' => $streak,
            'longest_streak' => max($stat->longest_streak, $streak),
            'last_played_on' => $onDate,
        ]);

        if ($xp > 0) {
            $this->leagues->addWeekXp($user, $xp);
        }

        return $updated;
    }

    public function avatarFor(int $userId): string
    {
        return self::AVATARS[$userId % count(self::AVATARS)];
    }

    private function weekdayLetter(int $isoDay): string
    {
        return match ($isoDay) {
            1 => 'M',
            2 => 'T',
            3 => 'W',
            4 => 'T',
            5 => 'F',
            6 => 'S',
            default => 'S',
        };
    }
}
