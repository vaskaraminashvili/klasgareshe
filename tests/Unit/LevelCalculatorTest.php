<?php

namespace Tests\Unit;

use App\Services\LevelCalculator;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

class LevelCalculatorTest extends TestCase
{
    #[DataProvider('xpLevels')]
    public function test_level_from_xp(int $xp, int $expectedLevel): void
    {
        $progress = app(LevelCalculator::class)->forXp($xp);

        $this->assertSame($expectedLevel, $progress->level);
        $this->assertGreaterThanOrEqual(0, $progress->xpToNext);
        $this->assertGreaterThanOrEqual(0, $progress->percent);
        $this->assertLessThanOrEqual(100, $progress->percent);
    }

    /**
     * @return list<array{0: int, 1: int}>
     */
    public static function xpLevels(): array
    {
        return [
            [0, 1],
            [99, 1],
            [100, 2],
            [300, 3],
            [600, 4],
            [1000, 5],
            [1240, 5],
            [1500, 6],
            [2100, 7],
        ];
    }

    public function test_xp_for_level_formula(): void
    {
        $calc = app(LevelCalculator::class);

        $this->assertSame(0, $calc->xpForLevel(1));
        $this->assertSame(100, $calc->xpForLevel(2));
        $this->assertSame(2100, $calc->xpForLevel(7));
    }
}
