<?php

namespace App\Data;

final readonly class AlertCard
{
    public function __construct(
        public string $id,
        public string $title,
        public string $body,
        public string $href,
        public string $icon,
        public string $tile,
        public string $when,
        public bool $unread,
    ) {}

    /**
     * @return array{id: string, title: string, body: string, href: string, icon: string, tile: string, when: string, unread: bool}
     */
    public function toArray(): array
    {
        return [
            'id' => $this->id,
            'title' => $this->title,
            'body' => $this->body,
            'href' => $this->href,
            'icon' => $this->icon,
            'tile' => $this->tile,
            'when' => $this->when,
            'unread' => $this->unread,
        ];
    }
}
