<?php

namespace App\Data;

final readonly class RecentBadgeView
{
    public function __construct(
        public string $slug,
        public string $name,
        public string $emoji,
        public string $medalClass,
        public string $meta,
        public string $href,
        public bool $locked,
        public bool $unseen,
    ) {}

    /**
     * @return array{
     *     slug: string,
     *     name: string,
     *     emoji: string,
     *     medalClass: string,
     *     meta: string,
     *     href: string,
     *     locked: bool,
     *     unseen: bool
     * }
     */
    public function toArray(): array
    {
        return [
            'slug' => $this->slug,
            'name' => $this->name,
            'emoji' => $this->emoji,
            'medalClass' => $this->medalClass,
            'meta' => $this->meta,
            'href' => $this->href,
            'locked' => $this->locked,
            'unseen' => $this->unseen,
        ];
    }
}
