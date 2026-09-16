# T20 — Reward shop

**Priority:** P2 · depth
**Status:** not started
**Depends on:** T13 (wallet + claims), T11 (streak freeze)

## Why now

The shop is the XP sink that makes earning XP feel like it buys something. It needs a wallet
(T13) and cosmetics worth buying, so it lands late — and it is pure upside, nothing breaks without
it.

## Scope

- [ ] Shop screen (part of `rewards-dashboard` in the template)
- [ ] Item catalog with categories: avatars, hats, backgrounds/themes, boosts, sound packs,
      streak shield
- [ ] Spend from the **spendable** balance, never from ranking XP (decision recorded in T13)
- [ ] Inventory: what the kid owns, what is equipped
- [ ] Equipping an avatar/hat/background actually changes the UI — otherwise the purchase is fake
- [ ] Boosts: 2× XP for a day, applied in `UserStatService::awardXp` and visible while active
- [ ] Streak shield purchase feeds the T11 freeze mechanic
- [ ] Sale / HOT / NEW tags driven by data
- [ ] Purchases are idempotent and cannot go negative (test concurrent double-submit)

## Code touchpoints

- Migrations: `shop_items`, `user_inventory`, `user_boosts`
- `app/Services/ShopService.php`, `app/Repositories/ShopRepository.php`
- `app/Services/UserStatService.php` — boost multiplier
- `resources/views/pages/⚡rewards-dashboard.blade.php`, `⚡edit-profile.blade.php` (equipped avatar)

## Done when

A kid can spend XP, own an item, equip it, see it on their profile, and a boost measurably doubles
XP for its window.

## Out of scope

Real-money purchases. Paid plans are a separate product decision, not this task.
