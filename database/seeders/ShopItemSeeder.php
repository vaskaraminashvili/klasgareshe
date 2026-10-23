<?php

namespace Database\Seeders;

use App\Enums\ShopCategory;
use App\Enums\ShopTag;
use App\Models\ShopItem;
use Illuminate\Database\Seeder;

class ShopItemSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        foreach ($this->catalog() as $index => $item) {
            ShopItem::query()->updateOrCreate(
                ['slug' => $item['slug']],
                [
                    'category' => $item['category'],
                    'emoji' => $item['emoji'],
                    'tile' => $item['tile'],
                    'price' => $item['price'],
                    'list_price' => $item['list_price'],
                    'tag' => $item['tag'],
                    'sale_percent' => $item['sale_percent'],
                    'payload' => $item['payload'],
                    'sort_order' => $index + 1,
                    'is_active' => true,
                    'ends_at' => $item['ends_at'],
                ],
            );
        }
    }

    /**
     * @return list<array{
     *     slug: string,
     *     category: ShopCategory,
     *     emoji: string,
     *     tile: string,
     *     price: int,
     *     list_price: int|null,
     *     tag: ShopTag|null,
     *     sale_percent: int|null,
     *     payload: array<string, mixed>|null,
     *     ends_at: \DateTimeInterface|null
     * }>
     */
    private function catalog(): array
    {
        return [
            $this->row('avatar-dragon', ShopCategory::Avatar, '🐲', 'tile-violet', 500, null, ShopTag::Hot, null, null),
            $this->row('avatar-peacock', ShopCategory::Avatar, '🦚', 'tile-mint', 180, null, ShopTag::New, null, null),
            $this->row('avatar-parrot', ShopCategory::Avatar, '🦜', 'tile-coral', 160, null, null, null, null),
            $this->row('avatar-hamster', ShopCategory::Avatar, '🐹', 'tile-sun', 140, null, null, null, null),
            $this->row('avatar-hedgehog', ShopCategory::Avatar, '🦔', 'tile-sky', 140, null, null, null, null),
            $this->row('avatar-squirrel', ShopCategory::Avatar, '🐿️', 'tile-pink', 120, null, null, null, null),
            $this->row('sound-fun', ShopCategory::Sound, '🎧', 'tile-sky', 300, null, null, null, ['pack' => 'fun']),
            $this->row('boost-star', ShopCategory::Boost, '🌟', 'tile-sun', 100, 200, ShopTag::Sale, 50, ['multiplier' => 2, 'hours' => 24]),
            $this->row('hat-crown', ShopCategory::Hat, '🎩', 'tile-coral', 400, null, ShopTag::New, null, null),
            $this->row('hat-party', ShopCategory::Hat, '👑', 'tile-sun', 220, null, null, null, null),
            $this->row('bg-rainbow', ShopCategory::Background, '🌈', 'tile-mint', 250, null, null, null, [
                'gradient' => 'linear-gradient(135deg, #7c5cff 0%, #4ed6a8 45%, #ff8fc5 100%)',
            ]),
            $this->row('bg-sunset', ShopCategory::Background, '🌅', 'tile-coral', 200, 400, ShopTag::Sale, 50, [
                'gradient' => 'linear-gradient(135deg, #ff7a59 0%, #ffb547 55%, #ffe27a 100%)',
            ]),
            $this->row('shield', ShopCategory::Shield, '🛡️', 'tile-pink', 150, null, null, null, null),
            $this->row('bundle-explorer', ShopCategory::Bundle, '🎟️', 'tile-violet', 700, 1000, null, null, [
                'includes' => ['avatar-dragon', 'bg-rainbow', 'boost-star'],
                'save_percent' => 30,
            ], now()->addDays(14)),
        ];
    }

    /**
     * @param  array<string, mixed>|null  $payload
     * @return array{
     *     slug: string,
     *     category: ShopCategory,
     *     emoji: string,
     *     tile: string,
     *     price: int,
     *     list_price: int|null,
     *     tag: ShopTag|null,
     *     sale_percent: int|null,
     *     payload: array<string, mixed>|null,
     *     ends_at: \DateTimeInterface|null
     * }
     */
    private function row(
        string $slug,
        ShopCategory $category,
        string $emoji,
        string $tile,
        int $price,
        ?int $listPrice,
        ?ShopTag $tag,
        ?int $salePercent,
        ?array $payload,
        ?\DateTimeInterface $endsAt = null,
    ): array {
        return [
            'slug' => $slug,
            'category' => $category,
            'emoji' => $emoji,
            'tile' => $tile,
            'price' => $price,
            'list_price' => $listPrice,
            'tag' => $tag,
            'sale_percent' => $salePercent,
            'payload' => $payload,
            'ends_at' => $endsAt,
        ];
    }
}
