<?php

namespace App\Services;

use App\Data\ScreenTimeSnapshot;
use App\Data\WeekMinuteBar;
use App\Enums\DailyGoal;
use App\Enums\PlayPauseReason;
use App\Models\User;
use App\Models\UserPlaySession;
use App\Repositories\PlaySessionRepository;
use App\Repositories\UserRepository;
use Carbon\CarbonImmutable;
use Carbon\CarbonInterface;
use DateTimeZone;
use InvalidArgumentException;

class ScreenTimeService
{
    public const DEFAULT_TIMEZONE = 'Asia/Tbilisi';

    public const HEARTBEAT_MAX_GAP_SECONDS = 90;

    public const BREAK_AFTER_SECONDS = 900;

    public const WARN_BEFORE_SECONDS = 300;

    public const EXTENSION_MINUTES = 15;

    /** @var list<int> */
    public const LIMIT_PRESETS = [15, 30, 45, 60];

    public const DEFAULT_BEDTIME_START = '21:00';

    public const DEFAULT_BEDTIME_END = '07:00';

    private const SESSION_BREAK_ACK = 'screen_time.break_ack_seconds';

    public function __construct(
        private PlaySessionRepository $sessions,
        private UserRepository $users,
    ) {}

    public function timezone(User $user): string
    {
        $tz = $user->timezone !== '' ? $user->timezone : self::DEFAULT_TIMEZONE;

        return $this->isValidTimezone($tz) ? $tz : self::DEFAULT_TIMEZONE;
    }

    public function nowFor(User $user): CarbonImmutable
    {
        return CarbonImmutable::now($this->timezone($user));
    }

    public function usedTodaySeconds(User $user): int
    {
        $this->closeStale($user);

        $now = $this->nowFor($user);

        return $this->usedSecondsBetween($user, $now->startOfDay(), $now);
    }

    public function snapshot(User $user): ScreenTimeSnapshot
    {
        $this->closeStale($user);

        $tz = $this->timezone($user);
        $now = $this->nowFor($user);
        $used = $this->usedSecondsBetween($user, $now->startOfDay(), $now);
        $limit = $this->effectiveLimitSeconds($user);
        $remaining = $limit === null ? null : max(0, $limit - $used);
        $limitMinutes = $user->daily_limit_minutes;
        $extra = $this->extraMinutesToday($user);
        $week = $this->weekBars($user);
        $goal = ($user->daily_goal ?? DailyGoal::Regular)->minutes();
        $hit = 0;

        foreach ($week as $bar) {
            if ($bar->minutes >= $goal) {
                $hit++;
            }
        }

        $barPercent = 0;

        if ($limit !== null && $limit > 0) {
            $barPercent = min(100, (int) round(($used / $limit) * 100));
        }

        return new ScreenTimeSnapshot(
            usedTodaySeconds: $used,
            limitMinutes: is_int($limitMinutes) ? $limitMinutes : null,
            extraMinutesToday: $extra,
            remainingSeconds: $remaining,
            barPercent: $barPercent,
            breakReminders: (bool) $user->break_reminders,
            warnBeforeLimit: (bool) $user->warn_before_limit,
            bedtimeEnabled: (bool) $user->bedtime_enabled,
            bedtimeStart: $this->normalizeClock($user->bedtime_start) ?? self::DEFAULT_BEDTIME_START,
            bedtimeEnd: $this->normalizeClock($user->bedtime_end) ?? self::DEFAULT_BEDTIME_END,
            bedtimeDays: $this->bedtimeDays($user),
            timezone: $tz,
            weekBars: $week,
            weekMinutes: array_sum(array_map(fn (WeekMinuteBar $bar): int => $bar->minutes, $week)),
            daysHitGoal: $hit,
            dailyGoalMinutes: $goal,
            extraGrantedToday: $extra > 0,
        );
    }

    public function blockReason(User $user, ?CarbonInterface $at = null): ?PlayPauseReason
    {
        $this->closeStale($user);

        $moment = $at !== null
            ? CarbonImmutable::instance($at)->setTimezone($this->timezone($user))
            : $this->nowFor($user);

        if ($this->isBedtime($user, $moment)) {
            return PlayPauseReason::Bedtime;
        }

        $limit = $this->effectiveLimitSeconds($user);

        if ($limit === null) {
            return null;
        }

        $used = $this->usedSecondsBetween($user, $moment->startOfDay(), $moment);

        return $used >= $limit ? PlayPauseReason::Limit : null;
    }

    public function tick(User $user): ?PlayPauseReason
    {
        $blocked = $this->blockReason($user);

        if ($blocked !== null) {
            $this->closeOpen($user);

            return $blocked;
        }

        $now = CarbonImmutable::now();
        $open = $this->sessions->openFor($user);

        if ($open instanceof UserPlaySession) {
            $last = CarbonImmutable::instance($open->last_heartbeat_at);

            if ($now->getTimestamp() - $last->getTimestamp() > self::HEARTBEAT_MAX_GAP_SECONDS) {
                $this->closeSession($open, $last);
                $open = $this->sessions->create($user, $now);
            } else {
                $this->sessions->update($open, [
                    'last_heartbeat_at' => $now,
                    'seconds' => max(0, $now->getTimestamp() - CarbonImmutable::instance($open->started_at)->getTimestamp()),
                ]);
            }
        } else {
            $this->sessions->create($user, $now);
        }

        $blocked = $this->blockReason($user);

        if ($blocked !== null) {
            $this->closeOpen($user);
        }

        return $blocked;
    }

    public function closeStale(User $user): void
    {
        $open = $this->sessions->openFor($user);

        if (! $open instanceof UserPlaySession) {
            return;
        }

        $last = CarbonImmutable::instance($open->last_heartbeat_at);

        if (CarbonImmutable::now()->getTimestamp() - $last->getTimestamp() > self::HEARTBEAT_MAX_GAP_SECONDS) {
            $this->closeSession($open, $last);
        }
    }

    public function isBedtime(User $user, ?CarbonInterface $at = null): bool
    {
        if (! $user->bedtime_enabled) {
            return false;
        }

        $moment = $at !== null
            ? CarbonImmutable::instance($at)->setTimezone($this->timezone($user))
            : $this->nowFor($user);

        $days = $this->bedtimeDays($user);
        $startClock = $this->normalizeClock($user->bedtime_start) ?? self::DEFAULT_BEDTIME_START;
        $endClock = $this->normalizeClock($user->bedtime_end) ?? self::DEFAULT_BEDTIME_END;
        $startMinutes = $this->clockToMinutes($startClock);
        $endMinutes = $this->clockToMinutes($endClock);
        $nowMinutes = ($moment->hour * 60) + $moment->minute;
        $todayIso = $moment->dayOfWeekIso;
        $yesterdayIso = $todayIso === 1 ? 7 : $todayIso - 1;

        if ($startMinutes === $endMinutes) {
            return in_array($todayIso, $days, true);
        }

        if ($startMinutes < $endMinutes) {
            return in_array($todayIso, $days, true)
                && $nowMinutes >= $startMinutes
                && $nowMinutes < $endMinutes;
        }

        if ($nowMinutes >= $startMinutes) {
            return in_array($todayIso, $days, true);
        }

        return $nowMinutes < $endMinutes && in_array($yesterdayIso, $days, true);
    }

    public function bedtimeSoon(User $user): bool
    {
        if (! $user->bedtime_enabled) {
            return false;
        }

        $now = $this->nowFor($user);

        return $this->isBedtime($user, $now->addMinutes(15)) && ! $this->isBedtime($user, $now);
    }

    public function shouldWarn(User $user): bool
    {
        if (! $user->warn_before_limit) {
            return false;
        }

        $remaining = $this->snapshot($user)->remainingSeconds;

        return $remaining !== null && $remaining > 0 && $remaining <= self::WARN_BEFORE_SECONDS;
    }

    public function shouldBreak(User $user): bool
    {
        if (! $user->break_reminders) {
            return false;
        }

        $open = $this->sessions->openFor($user);

        if (! $open instanceof UserPlaySession) {
            return false;
        }

        $played = $this->openSessionSeconds($open);
        $ack = session(self::SESSION_BREAK_ACK);
        $acked = is_numeric($ack) ? (int) $ack : 0;

        return ($played - $acked) >= self::BREAK_AFTER_SECONDS;
    }

    public function dismissBreak(User $user): void
    {
        $open = $this->sessions->openFor($user);
        $played = $open instanceof UserPlaySession ? $this->openSessionSeconds($open) : 0;
        session([self::SESSION_BREAK_ACK => $played]);
    }

    public function setDailyLimit(User $user, ?int $minutes): void
    {
        if ($minutes !== null && ! in_array($minutes, self::LIMIT_PRESETS, true)) {
            throw new InvalidArgumentException('Invalid daily limit.');
        }

        $this->users->update($user, [
            'daily_limit_minutes' => $minutes,
        ]);
    }

    public function grantExtension(User $user): bool
    {
        if ($this->extraMinutesToday($user) > 0) {
            return false;
        }

        $today = $this->nowFor($user)->toDateString();

        $this->users->update($user, [
            'screen_time_extra_minutes' => self::EXTENSION_MINUTES,
            'screen_time_extra_on' => $today,
        ]);

        return true;
    }

    public function setBreakReminders(User $user, bool $on): void
    {
        $this->users->update($user, [
            'break_reminders' => $on,
        ]);
    }

    public function setWarnBeforeLimit(User $user, bool $on): void
    {
        $this->users->update($user, [
            'warn_before_limit' => $on,
        ]);
    }

    /**
     * @param  list<int>  $days
     */
    public function saveBedtime(User $user, bool $enabled, string $start, string $end, array $days): void
    {
        $startClock = $this->normalizeClock($start);
        $endClock = $this->normalizeClock($end);

        if ($startClock === null || $endClock === null) {
            throw new InvalidArgumentException('Invalid bedtime clock.');
        }

        $this->users->update($user, [
            'bedtime_enabled' => $enabled,
            'bedtime_start' => $startClock,
            'bedtime_end' => $endClock,
            'bedtime_days' => $this->normalizeDays($days),
        ]);
    }

    /**
     * @return list<WeekMinuteBar>
     */
    public function weekBars(User $user): array
    {
        $now = $this->nowFor($user);
        $start = $now->startOfWeek(CarbonImmutable::MONDAY);
        $goal = max(1, ($user->daily_goal ?? DailyGoal::Regular)->minutes());
        $today = $now->toDateString();
        $bars = [];

        for ($i = 0; $i < 7; $i++) {
            $day = $start->addDays($i);
            $from = $day->startOfDay();
            $to = $day->isSameDay($now) ? $now : $day->endOfDay();
            $minutes = intdiv($this->usedSecondsBetween($user, $from, $to), 60);

            $bars[] = new WeekMinuteBar(
                letter: $this->weekdayLetter($day->dayOfWeekIso),
                date: $day->toDateString(),
                minutes: $minutes,
                percent: min(100, (int) round(($minutes / $goal) * 100)),
                today: $day->toDateString() === $today,
            );
        }

        return $bars;
    }

    public function formatClock(string $clock, string $timezone): string
    {
        $normalized = $this->normalizeClock($clock) ?? self::DEFAULT_BEDTIME_START;

        return CarbonImmutable::createFromFormat('H:i', $normalized, $timezone)?->isoFormat('h:mm A')
            ?? $normalized;
    }

    private function usedSecondsBetween(User $user, CarbonInterface $from, CarbonInterface $to): int
    {
        $fromTs = $from->getTimestamp();
        $toTs = $to->getTimestamp();

        if ($toTs <= $fromTs) {
            return 0;
        }

        $total = 0;

        foreach ($this->sessions->overlapping($user, $from, $to) as $session) {
            $start = CarbonImmutable::instance($session->started_at)->getTimestamp();
            $end = $this->effectiveEnd($session)->getTimestamp();
            $overlap = min($end, $toTs) - max($start, $fromTs);

            if ($overlap > 0) {
                $total += $overlap;
            }
        }

        return $total;
    }

    private function effectiveEnd(UserPlaySession $session): CarbonImmutable
    {
        if ($session->ended_at !== null) {
            return CarbonImmutable::instance($session->ended_at);
        }

        $last = CarbonImmutable::instance($session->last_heartbeat_at);
        $now = CarbonImmutable::now();

        if ($now->getTimestamp() - $last->getTimestamp() <= self::HEARTBEAT_MAX_GAP_SECONDS) {
            return $now;
        }

        return $last;
    }

    private function effectiveLimitSeconds(User $user): ?int
    {
        $minutes = $user->daily_limit_minutes;

        if (! is_int($minutes) || $minutes < 1) {
            return null;
        }

        return ($minutes + $this->extraMinutesToday($user)) * 60;
    }

    private function extraMinutesToday(User $user): int
    {
        $on = $user->screen_time_extra_on;

        if ($on === null) {
            return 0;
        }

        $date = $on->toDateString();

        if ($date !== $this->nowFor($user)->toDateString()) {
            return 0;
        }

        return max(0, (int) $user->screen_time_extra_minutes);
    }

    private function closeOpen(User $user): void
    {
        $open = $this->sessions->openFor($user);

        if ($open instanceof UserPlaySession) {
            $this->closeSession($open, $this->effectiveEnd($open));
        }
    }

    private function closeSession(UserPlaySession $session, CarbonInterface $endedAt): void
    {
        $start = CarbonImmutable::instance($session->started_at);
        $end = CarbonImmutable::instance($endedAt);

        if ($end->lessThan($start)) {
            $end = $start;
        }

        $this->sessions->update($session, [
            'ended_at' => $end,
            'last_heartbeat_at' => $end,
            'seconds' => max(0, $end->getTimestamp() - $start->getTimestamp()),
        ]);
    }

    private function openSessionSeconds(UserPlaySession $session): int
    {
        return max(0, $this->effectiveEnd($session)->getTimestamp() - CarbonImmutable::instance($session->started_at)->getTimestamp());
    }

    /**
     * @return list<int>
     */
    private function bedtimeDays(User $user): array
    {
        $days = $user->bedtime_days;

        return is_array($days) ? $this->normalizeDays($days) : [1, 2, 3, 4, 5, 6, 7];
    }

    /**
     * @param  list<mixed>  $days
     * @return list<int>
     */
    private function normalizeDays(array $days): array
    {
        $clean = [];

        foreach ($days as $day) {
            if (is_numeric($day)) {
                $value = (int) $day;

                if ($value >= 1 && $value <= 7) {
                    $clean[$value] = $value;
                }
            }
        }

        $values = array_values($clean);
        sort($values);

        return $values === [] ? [1, 2, 3, 4, 5, 6, 7] : $values;
    }

    private function normalizeClock(mixed $value): ?string
    {
        if (! is_string($value) || $value === '') {
            return null;
        }

        if (preg_match('/^(\d{1,2}):(\d{2})(?::\d{2})?$/', $value, $match) !== 1) {
            return null;
        }

        $hour = (int) $match[1];
        $minute = (int) $match[2];

        if ($hour > 23 || $minute > 59) {
            return null;
        }

        return sprintf('%02d:%02d', $hour, $minute);
    }

    private function clockToMinutes(string $clock): int
    {
        [$hour, $minute] = array_map('intval', explode(':', $clock));

        return ($hour * 60) + $minute;
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

    private function isValidTimezone(string $tz): bool
    {
        return in_array($tz, DateTimeZone::listIdentifiers(), true);
    }
}
