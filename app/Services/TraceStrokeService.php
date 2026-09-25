<?php

namespace App\Services;

class TraceStrokeService
{
    public const PASS = 60;

    private const MIN_POINTS = 20;

    private const RADIUS = 18;

    /**
     * Same rule as the template: share of guide samples within 18 units of a drawn point.
     *
     * @param  list<list<array{0: float|int, 1: float|int}>>  $strokes
     * @param  list<array{0: float|int, 1: float|int}>  $points
     */
    public function accuracy(array $strokes, array $points): int
    {
        $refs = $this->references($strokes);

        if ($refs === [] || count($points) < self::MIN_POINTS) {
            return 0;
        }

        $hits = 0;
        $radius = self::RADIUS * self::RADIUS;

        foreach ($refs as $ref) {
            foreach ($points as $point) {
                $dx = ((float) $point[0]) - $ref[0];
                $dy = ((float) $point[1]) - $ref[1];

                if (($dx * $dx) + ($dy * $dy) <= $radius) {
                    $hits++;

                    break;
                }
            }
        }

        return (int) round(($hits / count($refs)) * 100);
    }

    /**
     * @param  list<list<array{0: float|int, 1: float|int}>>  $strokes
     */
    public function path(array $strokes): string
    {
        $parts = [];

        foreach ($strokes as $stroke) {
            if ($stroke === []) {
                continue;
            }

            $first = $stroke[0];
            $d = 'M'.$first[0].' '.$first[1];

            for ($i = 1, $n = count($stroke); $i < $n; $i++) {
                $d .= ' L'.$stroke[$i][0].' '.$stroke[$i][1];
            }

            $parts[] = $d;
        }

        return implode(' ', $parts);
    }

    /**
     * @param  list<list<array{0: float|int, 1: float|int}>>  $strokes
     * @return list<array{0: float, 1: float}>
     */
    public function dots(array $strokes): array
    {
        $dots = [];

        foreach ($strokes as $stroke) {
            if ($stroke === []) {
                continue;
            }

            $first = $stroke[0];
            $last = $stroke[count($stroke) - 1];
            $dots[] = [(float) $first[0], (float) $first[1]];
            $dots[] = [(float) $last[0], (float) $last[1]];
        }

        return $dots;
    }

    /**
     * Dense samples along the guide, for tests and for a kid who followed the line.
     *
     * @param  list<list<array{0: float|int, 1: float|int}>>  $strokes
     * @return list<array{0: float, 1: float}>
     */
    public function sample(array $strokes): array
    {
        return $this->references($strokes);
    }

    /**
     * @param  list<list<array{0: float|int, 1: float|int}>>  $strokes
     * @return list<array{0: float, 1: float}>
     */
    private function references(array $strokes): array
    {
        $refs = [];

        foreach ($strokes as $stroke) {
            $count = count($stroke);

            for ($i = 1; $i < $count; $i++) {
                $from = $stroke[$i - 1];
                $to = $stroke[$i];

                for ($step = 0; $step <= 8; $step++) {
                    $t = $step / 8;
                    $refs[] = [
                        ((float) $from[0]) + (((float) $to[0]) - ((float) $from[0])) * $t,
                        ((float) $from[1]) + (((float) $to[1]) - ((float) $from[1])) * $t,
                    ];
                }
            }
        }

        return $refs;
    }
}
