<?php

namespace App\Services;

use App\Data\CohortMemberRow;
use App\Data\WeeklyLeagueSnapshot;
use App\Enums\LeagueOutcome;
use App\Enums\LeagueWeekStatus;
use App\Models\LeagueGroup;
use App\Models\LeagueGroupMember;
use App\Models\LeagueWeek;
use App\Models\User;
use App\Repositories\LeagueRepository;
use App\Repositories\UserStatRepository;
use Carbon\CarbonImmutable;
use Illuminate\Support\Collection;

class LeagueSeasonService
{
    /** @var list<string> */
    private const AVATARS = ['🐻', '🦊', '🐰', '🐼', '🐨', '🦄', '🐢', '🦁', '🐸', '🐯'];

    public function __construct(
        private LeagueRepository $leagues,
        private UserStatRepository $stats,
        private LevelCalculator $levels,
    ) {}

    public function ensureCurrentWeek(?CarbonImmutable $now = null): LeagueWeek
    {
        $now ??= CarbonImmutable::now();
        $start = $now->startOfWeek(CarbonImmutable::MONDAY);
        $startsOn = $start->toDateString();

        $existing = $this->leagues->findWeekStarting($startsOn);
        if ($existing !== null) {
            return $existing;
        }

        return $this->leagues->createWeek([
            'starts_on' => $startsOn,
            'ends_on' => $start->addDays(6)->toDateString(),
            'status' => LeagueWeekStatus::Open,
        ]);
    }

    public function ensureMembership(User $user, ?CarbonImmutable $now = null): LeagueGroupMember
    {
        $week = $this->ensureCurrentWeek($now);
        $existing = $this->leagues->membershipForUserInWeek($user, $week);
        if ($existing !== null) {
            return $existing;
        }

        $stat = $this->stats->firstOrCreateFor($user);
        $group = $this->leagues->findOpenGroupWithSpace($week, $stat->league)
            ?? $this->leagues->createGroup($week, $stat->league);

        return $this->leagues->addMember($group, $user);
    }

    public function addWeekXp(User $user, int $xp, ?CarbonImmutable $now = null): void
    {
        if ($xp <= 0) {
            return;
        }

        $member = $this->ensureMembership($user, $now);
        $this->leagues->incrementWeekXp($member, $xp);
    }

    public function weeklySnapshot(User $user, ?CarbonImmutable $now = null): WeeklyLeagueSnapshot
    {
        $now ??= CarbonImmutable::now();
        $member = $this->ensureMembership($user, $now);
        $group = $member->group;
        $week = $group->week;
        $stat = $this->stats->firstOrCreateFor($user);
        $level = $this->levels->forXp($stat->xp);

        $ranked = $this->leagues->membersRanked($group);
        $rows = $this->mapCohortRows($ranked, $user->id);
        $yourRank = 1;
        foreach ($rows as $row) {
            if ($row->isYou) {
                $yourRank = $row->rank;
                break;
            }
        }

        $xpGap = 0;
        if ($yourRank > 1) {
            $above = $rows[$yourRank - 2] ?? null;
            if ($above !== null) {
                $xpGap = max(0, $above->weekXp - $member->week_xp + 1);
            }
        }

        $promoteThreshold = $this->promoteThresholdXp($rows);
        $xpToPromote = max(0, $promoteThreshold - $member->week_xp);
        $statusLabel = $this->zoneStatusLabel($yourRank, count($rows));

        $start = CarbonImmutable::parse($week->starts_on->toDateString());
        $end = CarbonImmutable::parse($week->ends_on->toDateString())->endOfDay();
        $endsIn = $this->formatCountdown($now, $end);

        $xpMap = $this->stats->xpByDateBetween(
            $user,
            $week->starts_on->toDateString(),
            $week->ends_on->toDateString(),
        );

        $chartDays = [];
        $bestDayXp = 0;
        $bestDayLabel = '—';
        for ($i = 0; $i < 7; $i++) {
            $day = $start->addDays($i);
            $date = $day->toDateString();
            $value = $xpMap[$date] ?? 0;
            $label = match ($day->dayOfWeekIso) {
                1 => 'M', 2 => 'T', 3 => 'W', 4 => 'T', 5 => 'F', 6 => 'S', default => 'S',
            };
            $chartDays[] = ['label' => $label, 'value' => $value];
            if ($value > $bestDayXp) {
                $bestDayXp = $value;
                $bestDayLabel = $label;
            }
        }

        $weekXpTotal = (int) array_sum(array_column($chartDays, 'value'));
        $prevStart = $start->subDays(7);
        $prevEnd = $start->subDay();
        $lastWeekXp = $this->stats->sumXpBetween($user, $prevStart->toDateString(), $prevEnd->toDateString());
        $vsLastWeek = $lastWeekXp > 0
            ? (int) round((($weekXpTotal - $lastWeekXp) / $lastWeekXp) * 100)
            : ($weekXpTotal > 0 ? 100 : 0);

        $journey = [];
        foreach ($this->leagues->recentHistoryFor($user) as $past) {
            $journey[] = [
                'weekLabel' => $past->group->week->starts_on->format('M j'),
                'tier' => $past->group->tier->label(),
                'rank' => (int) $past->finish_rank,
                'outcome' => $past->outcome?->label() ?? '',
            ];
        }

        return new WeeklyLeagueSnapshot(
            tier: $group->tier,
            tierLabel: $group->tier->label(),
            yourRank: $yourRank,
            yourWeekXp: $member->week_xp,
            memberCount: count($rows),
            xpGapToNext: $xpGap,
            promoteThresholdXp: $promoteThreshold,
            xpToPromote: $xpToPromote,
            statusLabel: $statusLabel,
            endsInShort: $endsIn,
            weekRangeLabel: $start->format('M j').' — '.$end->format('j'),
            startsOn: $week->starts_on->toDateString(),
            endsOn: $week->ends_on->toDateString(),
            members: $rows,
            chartDays: $chartDays,
            chartJson: json_encode($chartDays, JSON_THROW_ON_ERROR),
            bestDayXp: $bestDayXp,
            bestDayLabel: $bestDayLabel,
            weekXpTotal: $weekXpTotal,
            vsLastWeekPercent: $vsLastWeek,
            journey: $journey,
            level: $level,
        );
    }

    public function closeWeek(LeagueWeek $week): void
    {
        if ($week->status === LeagueWeekStatus::Closed) {
            return;
        }

        $this->leagues->transaction(function () use ($week): void {
            foreach ($this->leagues->groupsForWeek($week) as $group) {
                $this->closeGroup($group);
            }

            $this->leagues->updateWeek($week, ['status' => LeagueWeekStatus::Closed]);
        });
    }

    public function closeDueWeeks(?CarbonImmutable $now = null): int
    {
        $now ??= CarbonImmutable::now();
        $closed = 0;

        foreach ($this->leagues->openWeeks() as $week) {
            $ends = CarbonImmutable::parse($week->ends_on->toDateString())->endOfDay();
            if ($now->greaterThan($ends)) {
                $this->closeWeek($week);
                $closed++;
            }
        }

        $this->ensureCurrentWeek($now);

        return $closed;
    }

    private function closeGroup(LeagueGroup $group): void
    {
        $ranked = $this->leagues->membersRanked($group);
        $n = $ranked->count();
        $promoteCount = 0;
        $relegateCount = 0;

        if ($n >= 4) {
            $promoteCount = min(3, $n - 1);
            $relegateCount = min(3, $n - $promoteCount);
        }

        $rank = 0;
        foreach ($ranked as $member) {
            $rank++;
            $outcome = LeagueOutcome::Hold;

            if ($promoteCount > 0 && $rank <= $promoteCount) {
                $outcome = LeagueOutcome::Promote;
            } elseif ($relegateCount > 0 && $rank > $n - $relegateCount) {
                $outcome = LeagueOutcome::Relegate;
            }

            $this->leagues->updateMember($member, [
                'finish_rank' => $rank,
                'outcome' => $outcome,
            ]);

            $stat = $this->stats->firstOrCreateFor($member->user);
            $newLeague = match ($outcome) {
                LeagueOutcome::Promote => $stat->league->promote(),
                LeagueOutcome::Relegate => $stat->league->relegate(),
                LeagueOutcome::Hold => $stat->league,
            };

            if ($newLeague !== $stat->league) {
                $this->stats->update($stat, ['league' => $newLeague]);
            }
        }
    }

    /**
     * @param  Collection<int, LeagueGroupMember>  $ranked
     * @return list<CohortMemberRow>
     */
    private function mapCohortRows(Collection $ranked, int $youId): array
    {
        $n = $ranked->count();
        $rows = [];
        $rank = 0;

        foreach ($ranked as $member) {
            $rank++;
            $owner = $member->user;
            if ($owner === null) {
                continue;
            }
            $stat = $this->stats->firstOrCreateFor($owner);
            $rows[] = new CohortMemberRow(
                rank: $rank,
                userId: $owner->id,
                name: $owner->name,
                weekXp: $member->week_xp,
                level: $this->levels->forXp($stat->xp)->level,
                streak: $stat->current_streak,
                isYou: $owner->id === $youId,
                avatar: $this->avatarFor($owner->id),
                zone: $this->zoneForRank($rank, $n),
                outcome: $member->outcome,
            );
        }

        return $rows;
    }

    private function avatarFor(int $userId): string
    {
        return self::AVATARS[$userId % count(self::AVATARS)];
    }

    /**
     * @param  list<CohortMemberRow>  $rows
     */
    private function promoteThresholdXp(array $rows): int
    {
        $n = count($rows);
        if ($n < 4) {
            return $rows[0]->weekXp ?? 0;
        }

        $third = $rows[2] ?? null;

        return $third?->weekXp ?? 0;
    }

    private function zoneForRank(int $rank, int $n): string
    {
        if ($n < 4) {
            return 'hold';
        }

        if ($rank <= 3) {
            return 'promote';
        }

        if ($rank > $n - 3) {
            return 'relegate';
        }

        return 'hold';
    }

    private function zoneStatusLabel(int $rank, int $n): string
    {
        return match ($this->zoneForRank($rank, $n)) {
            'promote' => __('ranking.outcome_promote'),
            'relegate' => __('ranking.outcome_relegate'),
            default => __('ranking.hold'),
        };
    }

    private function formatCountdown(CarbonImmutable $now, CarbonImmutable $end): string
    {
        if ($now->greaterThanOrEqualTo($end)) {
            return '0h';
        }

        $diff = $now->diff($end);
        $days = (int) $diff->days;
        $hours = (int) $diff->h;

        if ($days > 0) {
            return $days.'d '.$hours.'h';
        }

        return $hours.'h';
    }
}
