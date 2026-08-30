<?php

namespace App\Data;

final readonly class MonthlyGoalRow
{
    public function __construct(
        public string $key,
        public string $name,
        public string $emoji,
        public string $tile,
        public string $progressClass,
        public string $chipClass,
        public int $current,
        public int $target,
        public string $unit,
        public int $percent,
        public string $statusLabel,
        public bool $done,
    ) {}

    /**
     * @return array{
     *     key: string,
     *     name: string,
     *     emoji: string,
     *     tile: string,
     *     progressClass: string,
     *     chipClass: string,
     *     current: int,
     *     target: int,
     *     unit: string,
     *     percent: int,
     *     statusLabel: string,
     *     done: bool
     * }
     */
    public function toArray(): array
    {
        return [
            'key' => $this->key,
            'name' => $this->name,
            'emoji' => $this->emoji,
            'tile' => $this->tile,
            'progressClass' => $this->progressClass,
            'chipClass' => $this->chipClass,
            'current' => $this->current,
            'target' => $this->target,
            'unit' => $this->unit,
            'percent' => $this->percent,
            'statusLabel' => $this->statusLabel,
            'done' => $this->done,
        ];
    }
}
