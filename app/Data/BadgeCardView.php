<?php

namespace App\Data;

final readonly class BadgeCardView
{
    public function __construct(
        public string $slug,
        public string $name,
        public string $hint,
        public string $status,
        public string $category,
        public string $rarity,
        public string $rarityLabel,
        public string $rarityClass,
        public string $emoji,
        public string $medalClass,
        public bool $isSecret,
        public bool $unseen,
        public string $meta,
        public string $progressLabel,
        public int $percent,
        public string $chipClass,
        public string $href,
    ) {}

    /**
     * @return array{
     *     slug: string,
     *     name: string,
     *     hint: string,
     *     status: string,
     *     category: string,
     *     rarity: string,
     *     rarityLabel: string,
     *     rarityClass: string,
     *     emoji: string,
     *     medalClass: string,
     *     isSecret: bool,
     *     unseen: bool,
     *     meta: string,
     *     progressLabel: string,
     *     percent: int,
     *     chipClass: string,
     *     href: string
     * }
     */
    public function toArray(): array
    {
        return [
            'slug' => $this->slug,
            'name' => $this->name,
            'hint' => $this->hint,
            'status' => $this->status,
            'category' => $this->category,
            'rarity' => $this->rarity,
            'rarityLabel' => $this->rarityLabel,
            'rarityClass' => $this->rarityClass,
            'emoji' => $this->emoji,
            'medalClass' => $this->medalClass,
            'isSecret' => $this->isSecret,
            'unseen' => $this->unseen,
            'meta' => $this->meta,
            'progressLabel' => $this->progressLabel,
            'percent' => $this->percent,
            'chipClass' => $this->chipClass,
            'href' => $this->href,
        ];
    }
}
