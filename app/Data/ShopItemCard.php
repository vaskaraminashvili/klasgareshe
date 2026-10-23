<?php

namespace App\Data;

final readonly class ShopItemCard
{
    public function __construct(
        public string $slug,
        public string $category,
        public string $emoji,
        public string $tile,
        public string $name,
        public string $description,
        public int $price,
        public ?int $listPrice,
        public string $tag,
        public string $tagClass,
        public string $tagLabel,
        public bool $owned,
        public bool $equipped,
        public string $action,
        public bool $enabled,
        public string $click,
    ) {}

    /**
     * @return array{slug: string, category: string, emoji: string, tile: string, name: string, description: string, price: int, listPrice: int|null, tag: string, tagClass: string, tagLabel: string, owned: bool, equipped: bool, action: string, enabled: bool, click: string}
     */
    public function toArray(): array
    {
        return [
            'slug' => $this->slug,
            'category' => $this->category,
            'emoji' => $this->emoji,
            'tile' => $this->tile,
            'name' => $this->name,
            'description' => $this->description,
            'price' => $this->price,
            'listPrice' => $this->listPrice,
            'tag' => $this->tag,
            'tagClass' => $this->tagClass,
            'tagLabel' => $this->tagLabel,
            'owned' => $this->owned,
            'equipped' => $this->equipped,
            'action' => $this->action,
            'enabled' => $this->enabled,
            'click' => $this->click,
        ];
    }
}
