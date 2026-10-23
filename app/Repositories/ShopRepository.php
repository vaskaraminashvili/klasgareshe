<?php

namespace App\Repositories;

use App\Enums\ShopCategory;
use App\Models\ShopItem;
use App\Models\User;
use App\Models\UserBoost;
use App\Models\UserInventory;
use Carbon\CarbonInterface;
use Illuminate\Support\Collection;

class ShopRepository
{
    public function findBySlug(string $slug): ?ShopItem
    {
        return ShopItem::query()->where('slug', $slug)->first();
    }

    public function findPurchasable(string $slug): ?ShopItem
    {
        $item = $this->findBySlug($slug);

        if (! $item instanceof ShopItem || ! $item->isOpen()) {
            return null;
        }

        return $item;
    }

    /**
     * @return Collection<int, ShopItem>
     */
    public function catalog(): Collection
    {
        return ShopItem::query()
            ->where('is_active', true)
            ->where('category', '!=', ShopCategory::Bundle->value)
            ->where(function ($query): void {
                $query->whereNull('ends_at')->orWhere('ends_at', '>', now());
            })
            ->orderBy('sort_order')
            ->orderBy('id')
            ->get();
    }

    public function activeBundle(): ?ShopItem
    {
        $item = ShopItem::query()
            ->where('is_active', true)
            ->where('category', ShopCategory::Bundle->value)
            ->where(function ($query): void {
                $query->whereNull('ends_at')->orWhere('ends_at', '>', now());
            })
            ->orderBy('sort_order')
            ->first();

        return $item instanceof ShopItem ? $item : null;
    }

    public function hasPurchase(User $user, ShopItem $item, string $key): bool
    {
        return UserInventory::query()
            ->where('user_id', $user->id)
            ->where('shop_item_id', $item->id)
            ->where('purchase_key', $key)
            ->exists();
    }

    public function record(User $user, ShopItem $item, string $key, int $pricePaid, bool $equipped): UserInventory
    {
        return UserInventory::query()->create([
            'user_id' => $user->id,
            'shop_item_id' => $item->id,
            'purchase_key' => $key,
            'price_paid' => $pricePaid,
            'equipped' => $equipped,
        ]);
    }

    public function equip(User $user, ShopItem $item): void
    {
        $categoryIds = ShopItem::query()
            ->where('category', $item->category)
            ->pluck('id');

        UserInventory::query()
            ->where('user_id', $user->id)
            ->whereIn('shop_item_id', $categoryIds)
            ->update(['equipped' => false]);

        UserInventory::query()
            ->where('user_id', $user->id)
            ->where('shop_item_id', $item->id)
            ->where('purchase_key', '')
            ->update(['equipped' => true]);
    }

    public function unequipCategory(User $user, ShopCategory $category): void
    {
        $ids = ShopItem::query()->where('category', $category)->pluck('id');

        UserInventory::query()
            ->where('user_id', $user->id)
            ->whereIn('shop_item_id', $ids)
            ->update(['equipped' => false]);
    }

    public function avatarByEmoji(string $emoji): ?ShopItem
    {
        return ShopItem::query()
            ->where('category', ShopCategory::Avatar)
            ->where('emoji', $emoji)
            ->first();
    }

    public function isCatalogAvatar(string $emoji): bool
    {
        return ShopItem::query()
            ->where('category', ShopCategory::Avatar)
            ->where('emoji', $emoji)
            ->exists();
    }

    public function tileForAvatar(string $emoji): string
    {
        $tile = ShopItem::query()
            ->where('category', ShopCategory::Avatar)
            ->where('emoji', $emoji)
            ->value('tile');

        return is_string($tile) && $tile !== '' ? $tile : 'tile-sun';
    }

    /**
     * @return list<string>
     */
    public function ownedAvatarEmojis(User $user): array
    {
        $emojis = [];

        $rows = UserInventory::query()
            ->where('user_id', $user->id)
            ->where('purchase_key', '')
            ->whereHas('item', function ($query): void {
                $query->where('category', ShopCategory::Avatar);
            })
            ->with('item')
            ->get();

        foreach ($rows as $row) {
            $emoji = $row->item?->emoji;

            if (is_string($emoji) && $emoji !== '') {
                $emojis[] = $emoji;
            }
        }

        return array_values(array_unique($emojis));
    }

    public function equippedItem(User $user, ShopCategory $category): ?ShopItem
    {
        $row = UserInventory::query()
            ->where('user_id', $user->id)
            ->where('equipped', true)
            ->whereHas('item', function ($query) use ($category): void {
                $query->where('category', $category);
            })
            ->with('item')
            ->first();

        $item = $row?->item;

        return $item instanceof ShopItem ? $item : null;
    }

    public function activeBoost(User $user, ?CarbonInterface $at = null): ?UserBoost
    {
        $at ??= now();

        return UserBoost::query()
            ->where('user_id', $user->id)
            ->where('starts_at', '<=', $at)
            ->where('ends_at', '>', $at)
            ->orderByDesc('ends_at')
            ->first();
    }

    public function activeMultiplier(User $user, ?CarbonInterface $at = null): int
    {
        $boost = $this->activeBoost($user, $at);

        if (! $boost instanceof UserBoost) {
            return 1;
        }

        return max(1, $boost->multiplier);
    }

    public function startBoost(User $user, ShopItem $item, int $multiplier, CarbonInterface $starts, CarbonInterface $ends): UserBoost
    {
        return UserBoost::query()->create([
            'user_id' => $user->id,
            'shop_item_id' => $item->id,
            'multiplier' => $multiplier,
            'starts_at' => $starts,
            'ends_at' => $ends,
        ]);
    }

    /**
     * @return Collection<int, UserInventory>
     */
    public function recentSpends(User $user, int $limit = 8): Collection
    {
        return UserInventory::query()
            ->where('user_id', $user->id)
            ->where('price_paid', '>', 0)
            ->with('item')
            ->orderByDesc('created_at')
            ->orderByDesc('id')
            ->limit($limit)
            ->get();
    }

    public function ownedCount(User $user): int
    {
        return UserInventory::query()
            ->where('user_id', $user->id)
            ->where('purchase_key', '')
            ->count();
    }
}
