<?php

namespace App\Data;

final readonly class LearnSectionSnapshot
{
    /**
     * @param  list<array{week: int, label: string, name: string, status: string, statusLabel: string}>  $weeks
     * @param  list<array{id: int, title: string, subtitle: string, state: string, href: string, minutes: int, xp: int, stars: string, chip: string, chipClass: string, icon: string, percent: int}>  $lessons
     * @param  list<array{slug: string, name: string, emoji: string, medalClass: string, meta: string, href: string, locked: bool}>  $badges
     * @param  array{title: string, subtitle: string, href: string}|null  $continue
     */
    public function __construct(
        public string $subject,
        public string $subjectLabel,
        public string $emoji,
        public int $week,
        public int $weekDone,
        public int $weekTotal,
        public int $percent,
        public int $xpEarned,
        public int $minutesLeft,
        public bool $favourite,
        public ?array $continue,
        public array $weeks,
        public array $lessons,
        public array $badges,
    ) {}
}
