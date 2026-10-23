<?php

namespace App\Services;

use App\Data\EquippedLook;
use App\Data\ShopBundleCard;
use App\Data\ShopItemCard;
use App\Data\ShopPurchaseResult;
use App\Enums\ShopCategory;
use App\Enums\ShopTag;
use App\Models\ShopItem;
use App\Models\User;
use App\Models\UserStat;
use App\Repositories\ShopRepository;
use App\Repositories\UserRepository;
use App\Repositories\UserStatRepository;
use Carbon\CarbonImmutable;
use Closure;
use Illuminate\Database\UniqueConstraintViolationException;
use Illuminate\Support\Facades\DB;

class ShopService
{
    private const GRADIENT = '/^linear-gradient\([0-9a-z%,.\s#()\-]+\)$/i';

    public function __construct(
        private ShopRepository $items,
        private UserStatRepository $stats,
        private UserStatService $userStats,
        private UserRepository $users,
    ) {}

    /**
     * @param  (Closure(): void)|null  $overlap  A second purchase that commits inside this one. Tests use it to simulate a double-submit.
     */
    public function purchase(User $user, string $slug, ?Closure $overlap = null): ShopPurchaseResult
    {
        $item = $this->items->findPurchasable($slug);

        if (! $item instanceof ShopItem) {
            return ShopPurchaseResult::missing();
        }

        $this->userStats->ensureFor($user);
        $key = $this->purchaseKey($item);

        try {
            return DB::transaction(function () use ($user, $item, $key, $overlap): ShopPurchaseResult {
                $stat = $this->stats->lockForUser($user);
                $blocked = $this->blockReason($user, $item, $key, $stat);

                if ($blocked instanceof ShopPurchaseResult) {
                    return $blocked;
                }

                if ($overlap !== null) {
                    $overlap();
                    $stat = $stat->fresh() ?? $stat;
                    $blocked = $this->blockReason($user, $item, $key, $stat);

                    if ($blocked instanceof ShopPurchaseResult) {
                        return $blocked;
                    }
                }

                if ($item->category === ShopCategory::Shield) {
                    return $this->buyShield($user, $item, $key, $stat);
                }

                if (! $this->stats->spendCoins($user->id, $item->price)) {
                    return ShopPurchaseResult::insufficient();
                }

                $this->fulfill($user, $item, $item->price, $key);

                return ShopPurchaseResult::purchased($item->price);
            });
        } catch (UniqueConstraintViolationException) {
            return ShopPurchaseResult::owned();
        }
    }

    public function equip(User $user, string $slug): bool
    {
        $item = $this->items->findBySlug($slug);

        if (! $item instanceof ShopItem || ! $item->category->equippable()) {
            return false;
        }

        if (! $this->items->hasPurchase($user, $item, '')) {
            return false;
        }

        $this->applyEquip($user, $item);

        return true;
    }

    public function syncAvatarChoice(User $user, string $emoji): void
    {
        $item = $this->items->avatarByEmoji($emoji);

        if ($item instanceof ShopItem && $this->items->hasPurchase($user, $item, '')) {
            $this->items->equip($user, $item);

            return;
        }

        $this->items->unequipCategory($user, ShopCategory::Avatar);
    }

    /**
     * @return list<string>
     */
    public function ownedAvatars(User $user): array
    {
        return $this->items->ownedAvatarEmojis($user);
    }

    public function isCatalogAvatar(string $emoji): bool
    {
        return $this->items->isCatalogAvatar($emoji);
    }

    public function tileForAvatar(string $emoji): string
    {
        return $this->items->tileForAvatar($emoji);
    }

    public function equippedSoundPack(User $user): string
    {
        $item = $this->items->equippedItem($user, ShopCategory::Sound);
        $payload = $item?->payload;
        $pack = is_array($payload) ? ($payload['pack'] ?? null) : null;

        return $pack === 'fun' ? 'fun' : 'default';
    }

    public function look(User $user): EquippedLook
    {
        $hat = $this->items->equippedItem($user, ShopCategory::Hat);
        $background = $this->items->equippedItem($user, ShopCategory::Background);

        return new EquippedLook(
            hat: $hat instanceof ShopItem ? $hat->emoji : '',
            background: $this->gradient($background),
            boostUntil: $this->boostUntil($user),
        );
    }

    /**
     * @return array{items: list<array{slug: string, category: string, emoji: string, tile: string, name: string, description: string, price: int, listPrice: int|null, tag: string, tagClass: string, tagLabel: string, owned: bool, equipped: bool, action: string, enabled: bool, click: string}>, count: int, owned: int, bundle: ShopBundleCard|null, boostUntil: string}
     */
    public function page(User $user): array
    {
        $cards = [];

        foreach ($this->items->catalog() as $item) {
            $cards[] = $this->card($user, $item)->toArray();
        }

        return [
            'items' => $cards,
            'count' => count($cards),
            'owned' => $this->items->ownedCount($user),
            'bundle' => $this->bundleCard($user),
            'boostUntil' => $this->boostUntil($user),
        ];
    }

    private function buyShield(User $user, ShopItem $item, string $key, UserStat $stat): ShopPurchaseResult
    {
        if (! $this->stats->spendForShield($user->id, $item->price, UserStatService::FREEZE_CAP)) {
            $stat = $stat->fresh() ?? $stat;

            if ($stat->streak_freezes >= UserStatService::FREEZE_CAP) {
                return ShopPurchaseResult::capped();
            }

            return ShopPurchaseResult::insufficient();
        }

        $this->items->record($user, $item, $key, $item->price, false);

        return ShopPurchaseResult::purchased($item->price);
    }

    private function fulfill(User $user, ShopItem $item, int $paid, string $key): void
    {
        if ($item->category === ShopCategory::Boost) {
            $this->activateBoost($user, $item, $key, $paid);

            return;
        }

        if ($item->category === ShopCategory::Bundle) {
            $this->grantBundle($user, $item, $paid);

            return;
        }

        $this->grantCosmetic($user, $item, $paid, true);
    }

    private function grantBundle(User $user, ShopItem $item, int $paid): void
    {
        $this->items->record($user, $item, '', $paid, false);

        foreach ($item->includedSlugs() as $slug) {
            $part = $this->items->findBySlug($slug);

            if (! $part instanceof ShopItem) {
                continue;
            }

            if ($part->category === ShopCategory::Boost) {
                if ($this->items->activeBoost($user) === null) {
                    $this->activateBoost($user, $part, 'bundle-'.$item->id, 0);
                }

                continue;
            }

            if ($this->items->hasPurchase($user, $part, '')) {
                $this->applyEquip($user, $part);

                continue;
            }

            $this->grantCosmetic($user, $part, 0, true);
        }
    }

    private function grantCosmetic(User $user, ShopItem $item, int $paid, bool $equip): void
    {
        $this->items->record($user, $item, '', $paid, false);

        if ($equip) {
            $this->applyEquip($user, $item);
        }
    }

    private function activateBoost(User $user, ShopItem $item, string $key, int $paid): void
    {
        $starts = CarbonImmutable::now();
        $hours = max(1, $item->payloadInt('hours', 24));
        $multiplier = max(2, $item->payloadInt('multiplier', 2));

        $this->items->startBoost($user, $item, $multiplier, $starts, $starts->addHours($hours));
        $this->items->record($user, $item, $key, $paid, false);
    }

    private function applyEquip(User $user, ShopItem $item): void
    {
        if (! $item->category->equippable()) {
            return;
        }

        $this->items->equip($user, $item);

        if ($item->category === ShopCategory::Avatar) {
            $this->users->update($user, ['avatar' => $item->emoji]);
        }
    }

    private function blockReason(User $user, ShopItem $item, string $key, UserStat $stat): ?ShopPurchaseResult
    {
        if ($item->category === ShopCategory::Shield) {
            if ($this->items->hasPurchase($user, $item, $key)) {
                return ShopPurchaseResult::owned();
            }

            if ($stat->streak_freezes >= UserStatService::FREEZE_CAP) {
                return ShopPurchaseResult::capped();
            }

            return null;
        }

        if ($item->category === ShopCategory::Boost) {
            if ($this->items->activeBoost($user) !== null || $this->items->hasPurchase($user, $item, $key)) {
                return ShopPurchaseResult::owned();
            }

            return null;
        }

        if ($this->items->hasPurchase($user, $item, '')) {
            return ShopPurchaseResult::owned();
        }

        return null;
    }

    private function purchaseKey(ShopItem $item): string
    {
        if ($item->category->stacks()) {
            return now()->format('YmdHis');
        }

        return '';
    }

    private function card(User $user, ShopItem $item): ShopItemCard
    {
        $owned = $this->owns($user, $item);
        $equipped = $owned && $this->isEquipped($user, $item);
        $active = $item->category === ShopCategory::Boost && $this->items->activeBoost($user) !== null;
        $tag = $item->tag;
        $listPrice = $tag === ShopTag::Sale && $item->list_price !== null && $item->list_price > $item->price
            ? $item->list_price
            : null;

        [$action, $enabled, $click] = $this->action($item, $owned, $equipped, $active);

        return new ShopItemCard(
            slug: $item->slug,
            category: $item->category->value,
            emoji: $item->emoji,
            tile: $item->tile,
            name: (string) __('shop.items.'.$item->slug.'.name'),
            description: (string) __('shop.items.'.$item->slug.'.desc'),
            price: $item->price,
            listPrice: $listPrice,
            tag: $tag instanceof ShopTag ? $tag->value : '',
            tagClass: $tag instanceof ShopTag ? $tag->value : '',
            tagLabel: $this->tagLabel($item),
            owned: $owned,
            equipped: $equipped,
            action: $action,
            enabled: $enabled,
            click: $click,
        );
    }

    /**
     * @return array{0: string, 1: bool, 2: string}
     */
    private function action(ShopItem $item, bool $owned, bool $equipped, bool $active): array
    {
        if ($item->category === ShopCategory::Boost && $active) {
            return [(string) __('shop.active'), false, ''];
        }

        if ($item->category === ShopCategory::Shield) {
            return [(string) __('shop.buy'), true, "buy('".$item->slug."')"];
        }

        if ($equipped) {
            return [(string) __('shop.equipped'), false, ''];
        }

        if ($owned && $item->category->equippable()) {
            return [(string) __('shop.equip'), true, "equip('".$item->slug."')"];
        }

        if ($owned) {
            return [(string) __('shop.equipped'), false, ''];
        }

        return [(string) __('shop.buy'), true, "buy('".$item->slug."')"];
    }

    private function owns(User $user, ShopItem $item): bool
    {
        if ($item->category === ShopCategory::Shield) {
            return false;
        }

        if ($item->category === ShopCategory::Boost) {
            return $this->items->activeBoost($user) !== null;
        }

        return $this->items->hasPurchase($user, $item, '');
    }

    private function isEquipped(User $user, ShopItem $item): bool
    {
        $equipped = $this->items->equippedItem($user, $item->category);

        return $equipped instanceof ShopItem && $equipped->id === $item->id;
    }

    private function tagLabel(ShopItem $item): string
    {
        $tag = $item->tag;

        if (! $tag instanceof ShopTag) {
            return '';
        }

        if ($tag === ShopTag::Sale) {
            return (string) __('shop.tags.sale', ['percent' => $item->sale_percent ?? 0]);
        }

        return (string) __('shop.tags.'.$tag->value);
    }

    private function bundleCard(User $user): ?ShopBundleCard
    {
        $item = $this->items->activeBundle();

        if (! $item instanceof ShopItem || $item->ends_at === null) {
            return null;
        }

        $seconds = CarbonImmutable::now()->diffInSeconds($item->ends_at, false);

        if ($seconds <= 0) {
            return null;
        }

        $days = max(1, (int) ceil($seconds / 86400));

        return new ShopBundleCard(
            slug: $item->slug,
            emoji: $item->emoji,
            name: (string) __('shop.items.'.$item->slug.'.name'),
            kicker: (string) __('shop.bundle_kicker', ['days' => $days]),
            body: (string) __('shop.bundle_body', ['percent' => $item->payloadInt('save_percent', 0)]),
            price: $item->price,
            owned: $this->items->hasPurchase($user, $item, ''),
        );
    }

    private function boostUntil(User $user): string
    {
        $boost = $this->items->activeBoost($user);

        if ($boost === null) {
            return '';
        }

        return $boost->ends_at->format('d.m H:i');
    }

    private function gradient(?ShopItem $item): string
    {
        if (! $item instanceof ShopItem) {
            return '';
        }

        $gradient = $item->payloadString('gradient');

        if (preg_match(self::GRADIENT, $gradient) !== 1) {
            return '';
        }

        return $gradient;
    }
}
