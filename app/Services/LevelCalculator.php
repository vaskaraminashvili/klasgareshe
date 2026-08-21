<?php

namespace App\Services;

use App\Data\LevelProgress;

class LevelCalculator
{
    /**
     * Cumulative XP required to reach level n (level 1 starts at 0).
     * Formula: 50 * n * (n - 1).
     */
    public function xpForLevel(int $level): int
    {
        if ($level <= 1) {
            return 0;
        }

        return 50 * $level * ($level - 1);
    }

    public function forXp(int $xp): LevelProgress
    {
        $xp = max(0, $xp);
        $level = 1;

        while ($this->xpForLevel($level + 1) <= $xp) {
            $level++;
        }

        $floor = $this->xpForLevel($level);
        $ceiling = $this->xpForLevel($level + 1);
        $span = max(1, $ceiling - $floor);
        $into = $xp - $floor;
        $toNext = $ceiling - $xp;
        $percent = (int) min(100, round(($into / $span) * 100));

        return new LevelProgress(
            level: $level,
            title: $this->titleFor($level),
            titleKey: $this->titleKeyFor($level),
            xp: $xp,
            xpIntoLevel: $into,
            xpToNext: $toNext,
            percent: $percent,
            nextLevel: $level + 1,
            nextTitle: $this->titleFor($level + 1),
        );
    }

    public function titleFor(int $level): string
    {
        return (string) __('levels.titles.'.$this->titleKeyFor($level));
    }

    public function titleKeyFor(int $level): string
    {
        return match (true) {
            $level >= 10 => 'legend',
            $level >= 8 => 'master',
            $level >= 7 => 'explorer',
            $level >= 5 => 'adventurer',
            $level >= 3 => 'learner',
            default => 'starter',
        };
    }
}
