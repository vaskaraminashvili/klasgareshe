<?php

namespace App\Services;

class WordSearchBoard
{
    public const SIZE = 8;

    /**
     * @param  list<string>  $words
     * @return array{rows: list<list<string>>, placements: list<array{word: string, cells: list<array{0: int, 1: int}>}>}
     */
    public function build(array $words): array
    {
        $rows = array_fill(0, self::SIZE, array_fill(0, self::SIZE, ''));

        $placements = [];
        $row = 0;

        foreach ($words as $word) {
            $chars = mb_str_split($word);

            if ($chars === [] || count($chars) > self::SIZE || $row >= self::SIZE) {
                continue;
            }

            $cells = [];

            foreach ($chars as $column => $char) {
                $rows[$row][$column] = $char;
                $cells[] = [$row, $column];
            }

            $placements[] = ['word' => $word, 'cells' => $cells];
            $row++;
        }

        $filler = $this->filler($words);
        $grid = [];

        for ($r = 0; $r < self::SIZE; $r++) {
            $line = [];

            for ($c = 0; $c < self::SIZE; $c++) {
                $letter = $rows[$r][$c];
                $line[] = $letter === '' ? $filler : $letter;
            }

            $grid[] = $line;
        }

        return ['rows' => $grid, 'placements' => $placements];
    }

    /**
     * Letters on a straight line between two cells, including both ends.
     *
     * @param  list<list<string>>  $rows
     */
    public function lettersBetween(array $rows, int $r1, int $c1, int $r2, int $c2): ?string
    {
        if (! $this->inside($r1, $c1) || ! $this->inside($r2, $c2)) {
            return null;
        }

        $dr = $r2 <=> $r1;
        $dc = $c2 <=> $c1;

        if ($dr === 0 && $dc === 0) {
            return null;
        }

        if ($dr !== 0 && $dc !== 0 && abs($r2 - $r1) !== abs($c2 - $c1)) {
            return null;
        }

        $steps = max(abs($r2 - $r1), abs($c2 - $c1));
        $letters = '';

        for ($i = 0; $i <= $steps; $i++) {
            $letters .= $rows[$r1 + ($dr * $i)][$c1 + ($dc * $i)] ?? '';
        }

        return $letters;
    }

    /**
     * @param  list<string>  $words
     */
    private function filler(array $words): string
    {
        $used = [];

        foreach ($words as $word) {
            foreach (mb_str_split($word) as $char) {
                $used[$char] = true;
            }
        }

        foreach (mb_str_split('ღჭშჩცძწხჯჰქ') as $candidate) {
            if (! isset($used[$candidate])) {
                return $candidate;
            }
        }

        return '·';
    }

    private function inside(int $row, int $column): bool
    {
        return $row >= 0 && $column >= 0 && $row < self::SIZE && $column < self::SIZE;
    }
}
