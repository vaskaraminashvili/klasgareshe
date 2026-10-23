<?php

namespace App\Models;

use App\Enums\ShopCategory;
use App\Enums\ShopTag;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Carbon;

/**
 * @property int $id
 * @property string $slug
 * @property ShopCategory $category
 * @property string $emoji
 * @property string $tile
 * @property int $price
 * @property int|null $list_price
 * @property ShopTag|null $tag
 * @property int|null $sale_percent
 * @property array<string, mixed>|null $payload
 * @property int $sort_order
 * @property bool $is_active
 * @property Carbon|null $ends_at
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 */
#[Fillable([
    'slug',
    'category',
    'emoji',
    'tile',
    'price',
    'list_price',
    'tag',
    'sale_percent',
    'payload',
    'sort_order',
    'is_active',
    'ends_at',
])]
class ShopItem extends Model
{
    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'category' => ShopCategory::class,
            'price' => 'integer',
            'list_price' => 'integer',
            'tag' => ShopTag::class,
            'sale_percent' => 'integer',
            'payload' => 'array',
            'sort_order' => 'integer',
            'is_active' => 'boolean',
            'ends_at' => 'datetime',
        ];
    }

    public function isOpen(): bool
    {
        return $this->is_active && ($this->ends_at === null || $this->ends_at->isFuture());
    }

    public function payloadString(string $key): string
    {
        $payload = $this->payload;

        if (! is_array($payload) || ! isset($payload[$key]) || ! is_string($payload[$key])) {
            return '';
        }

        return $payload[$key];
    }

    public function payloadInt(string $key, int $default): int
    {
        $payload = $this->payload;

        if (! is_array($payload) || ! isset($payload[$key]) || ! is_numeric($payload[$key])) {
            return $default;
        }

        return (int) $payload[$key];
    }

    /**
     * @return list<string>
     */
    public function includedSlugs(): array
    {
        $payload = $this->payload;
        $slugs = is_array($payload) ? ($payload['includes'] ?? null) : null;

        if (! is_array($slugs)) {
            return [];
        }

        $clean = [];

        foreach ($slugs as $slug) {
            if (is_string($slug) && $slug !== '') {
                $clean[] = $slug;
            }
        }

        return $clean;
    }
}
