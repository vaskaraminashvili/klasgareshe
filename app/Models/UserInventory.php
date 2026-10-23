<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;

/**
 * @property int $id
 * @property int $user_id
 * @property int $shop_item_id
 * @property string $purchase_key
 * @property int $price_paid
 * @property bool $equipped
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 */
#[Fillable([
    'user_id',
    'shop_item_id',
    'purchase_key',
    'price_paid',
    'equipped',
])]
class UserInventory extends Model
{
    protected $table = 'user_inventory';

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'price_paid' => 'integer',
            'equipped' => 'boolean',
        ];
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * @return BelongsTo<ShopItem, $this>
     */
    public function item(): BelongsTo
    {
        return $this->belongsTo(ShopItem::class, 'shop_item_id');
    }
}
