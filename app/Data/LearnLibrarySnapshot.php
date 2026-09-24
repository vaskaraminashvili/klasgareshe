<?php

namespace App\Data;

final readonly class LearnLibrarySnapshot
{
    /**
     * @param  list<array{subject: string, label: string, emoji: string, tile: string, inkClass: string, ringClass: string, lessons: int, percent: int, blurb: string, difficulty: string, difficultyClass: string, gradeRange: string, tags: string, diff: string, age: int, status: string, href: string, favourite: bool}>  $subjects
     * @param  array{title: string, subtitle: string, href: string, percent: int, progressLabel: string, xp: int, minutes: int, emoji: string}|null  $spotlight
     * @param  list<array{title: string, subtitle: string, emoji: string, tile: string, href: string, keywords: string, tags: string}>  $games
     * @param  list<array{title: string, subtitle: string, emoji: string, tile: string, href: string, keywords: string}>  $latest
     */
    public function __construct(
        public int $subjectCount,
        public int $lessonsDone,
        public int $lessonsTotal,
        public int $gamesCount,
        public array $subjects,
        public ?array $spotlight,
        public array $games,
        public array $latest,
    ) {}
}
