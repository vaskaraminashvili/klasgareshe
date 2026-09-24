<?php

namespace App\Data;

use App\Enums\GameType;
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
        public ?GameType $nextGame = null,
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
            'href' => $this->playHref(),
        ];
    }

    public function playHref(): string
    {
        if ($this->nextItemId === null) {
            return route('daily-mission');
        }

        $route = ($this->nextGame ?? GameType::MultipleChoice)->playerRoute();

        return route($route, ['item' => $this->nextItemId]);
    }
}
