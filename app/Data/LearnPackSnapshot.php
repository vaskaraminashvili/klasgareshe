<?php

namespace App\Data;

final readonly class LearnPackSnapshot
{
    /**
     * @param  array{id: int, title: string, href: string, done: bool}|null  $previous
     * @param  array{id: int, title: string, href: string, minutes: int, xp: int, locked: bool}|null  $next
     * @param  list<array{n: int, title: string, subtitle: string, state: string, tile: string}>  $questionsList
     * @param  list<array{slug: string, name: string, emoji: string, medalClass: string, meta: string, href: string, locked: bool}>  $badges
     */
    public function __construct(
        public int $id,
        public string $title,
        public string $subject,
        public string $subjectLabel,
        public string $emoji,
        public string $tile,
        public int $week,
        public int $index,
        public int $weekTotal,
        public int $questions,
        public int $correct,
        public int $xp,
        public int $minutes,
        public int $percent,
        public string $state,
        public string $playHref,
        public string $sectionHref,
        public bool $favourite,
        public string $gradeLabel,
        public string $difficulty,
        public string $gameLabel,
        public ?array $previous,
        public ?array $next,
        public array $questionsList,
        public array $badges,
        public ?int $blockingId,
        public string $blockingTitle,
        public string $blockingHref,
        public int $unlockSteps,
        public int $unlockDone,
    ) {}
}
