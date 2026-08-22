<?php

namespace App\Models;

use App\Enums\BadgeCategory;
use App\Enums\BadgeRarity;
use App\Enums\BadgeRule;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Carbon;

/**
 * @property int $id
 * @property string $slug
 * @property BadgeRarity $rarity
 * @property BadgeCategory $category
 * @property string $emoji
 * @property string|null $medal
 * @property int $xp_bonus
 * @property BadgeRule $rule
 * @property array<string, mixed>|null $rule_params
 * @property bool $is_secret
 * @property int $sort_order
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 */
#[Fillable([
    'slug',
    'rarity',
    'category',
    'emoji',
    'medal',
    'xp_bonus',
    'rule',
    'rule_params',
    'is_secret',
    'sort_order',
])]
class Badge extends Model
{
    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'rarity' => BadgeRarity::class,
            'category' => BadgeCategory::class,
            'xp_bonus' => 'integer',
            'rule' => BadgeRule::class,
            'rule_params' => 'array',
            'is_secret' => 'boolean',
            'sort_order' => 'integer',
        ];
    }

    public function medalClass(): string
    {
        return match ($this->medal) {
            'silver', 'bronze' => $this->medal,
            default => '',
        };
    }

    /**
     * @return HasMany<UserBadge, $this>
     */
    public function users(): HasMany
    {
        return $this->hasMany(UserBadge::class);
    }
}
