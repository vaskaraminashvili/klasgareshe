# T02 — Dead links & fake data sweep

**Priority:** P0 · ship blocker
**Status:** not started
**Depends on:** —

## Why now

Ported screens still carry template hrefs: **14 in `⚡home.blade.php`, 7 in `⚡profile.blade.php`**.
Every one of them is a 404 in the running app. Alongside them sit numbers that look live and are
not — the notification bell badge is a literal `3`, the friends-today feed is Leo and Ana, the
screen-time chip says 30 min. A parent who taps any of these loses trust immediately. This is the
cheapest large credibility win on the roadmap.

## Rule for each dead link

Decide per link — do not invent a screen:

1. The target screen is on this roadmap → point at the task and leave the link **neutralized**
   (`href="#"` plus `aria-disabled`, or the section hidden) until that task lands.
2. The target already exists in Laravel → use `route()` + `wire:navigate`.
3. The target is a Kidzio extra we are not shipping (animals, A–Z, opposites on Home) → remove the
   block, per `CLAUDE.md`: those are not on Home.

## Scope — Home (`resources/views/pages/⚡home.blade.php`)

- [ ] `streak.html` ×3 (stat tile, streak card, notification row) → neutralize until **T11**
- [ ] `game-word-search.html`, `game-counting.html` featured tiles → neutralize until **T12/T19**
- [ ] `ranking-friends.html` ×2 → `route('ranking-friends')` + `wire:navigate` (screen is live)
- [ ] `settings.html` ×2 (parent tip chip, sheet footer) → neutralize until **T10**
- [ ] `rewards-dashboard.html` notification row → neutralize until **T13**
- [ ] `learn-math.html` / `learn-alphabet.html` / `learn-animals.html` / `learn-words.html` search
      catalog → point the two school subjects at their next pack; drop animals/words per product rules
- [ ] Friends-today feed (Leo / Ana / fake streak chips) → hide the block until **T18**
- [ ] Notification bell: unread badge hardcoded `3` and a hard-coded list → hide the badge until
      **T16**; do not ship a fake count
- [ ] Header avatar emoji + online dot → `users.avatar`, or drop the dot until presence exists
- [ ] `data-install` PWA button → hide until **T15**

## Scope — Profile (`resources/views/pages/⚡profile.blade.php`)

- [ ] Rewards-dashboard row with fake "3 new" → neutralize + drop the count until **T13**
- [ ] Parent zone rows (controls / weekly report / screen time) → neutralize until **T06–T08**
- [ ] Screen-time chip hardcoded `30 min` → remove the number until **T07**
- [ ] Settings gear + menu → neutralize until **T10**
- [ ] Share profile button → neutralize until **T21**
- [ ] Streak menu row → neutralize until **T11**

## Guard

- [ ] Add a test that fails if `href="…​.html"` appears anywhere under `resources/views/`

That guard is the real deliverable — it stops this from regressing on the next port.

## Done when

- `rg '\.html' resources/views` returns nothing.
- No screen displays a number that isn't computed from the database.
- Tapping every enabled control on Home and Profile lands on a real screen.

## Out of scope

Building any of the linked screens. This task only makes the app honest about what exists.
