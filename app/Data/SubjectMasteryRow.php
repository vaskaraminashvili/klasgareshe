<?php

namespace App\Data;

use App\Enums\SchoolSubject;

final readonly class SubjectMasteryRow
{
    public function __construct(
        public SchoolSubject $subject,
        public string $label,
        public string $emoji,
        public string $tile,
        public string $progressClass,
        public int $percent,
        public int $done,
        public int $total,
        public ?int $nextItemId,
    ) {}

    /**
     * @return array{
     *     subject: string,
     *     label: string,
     *     emoji: string,
     *     tile: string,
     *     progressClass: string,
     *     percent: int,
     *     done: int,
     *     total: int,
     *     nextItemId: int|null,
     *     href: string
     * }
     */
    public function toArray(): array
    {
        return [
            'subject' => $this->subject->value,
            'label' => $this->label,
            'emoji' => $this->emoji,
            'tile' => $this->tile,
            'progressClass' => $this->progressClass,
            'percent' => $this->percent,
            'done' => $this->done,
            'total' => $this->total,
            'nextItemId' => $this->nextItemId,
            'href' => $this->nextItemId !== null
                ? route('game-multiple-choice', ['item' => $this->nextItemId])
                : route('daily-mission'),
        ];
    }
}
