<?php

namespace App\Services;

use App\Data\HomeStats;
use App\Models\User;
use App\Models\UserStat;
use App\Repositories\UserStatRepository;
use Carbon\CarbonImmutable;
use Carbon\CarbonInterface;
use InvalidArgumentException;

class UserStatService
{
    public function __construct(private UserStatRepository $stats) {}

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

        return $this->stats->update($stat, [
            'xp' => $stat->xp + $xp,
            'current_streak' => $streak,
            'longest_streak' => max($stat->longest_streak, $streak),
            'last_played_on' => $onDate,
        ]);
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
