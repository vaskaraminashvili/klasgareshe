# T02 — Dead links & fake data sweep

**Priority:** P0 · ship blocker
**Status:** done
**Depends on:** —

## Why now

Ported screens still carry template hrefs: **14 in `⚡home.blade.php`, 7 in `⚡profile.blade.php`**.
Every one of them is a 404 in the running app. Alongside them sit numbers that look live and are
not — the notification bell badge is a literal `3`, the friends-today feed is Leo and Ana, the
screen-time chip says 30 min. A parent who taps any of these loses trust immediately. This is the
cheapest large credibility win on the roadmap.

## Rule for each dead link

Decide per link — do not invent a screen:

1. The target already exists in Laravel → use `route()` + `wire:navigate`.
2. The element carries **live data** and only the link target is missing → keep the element and the
   classes, drop the `<a>` (render a `div`/`span`). The data stays visible, the tap does nothing.
3. The element exists **only to navigate**, or its content is invented → delete the block and leave
   a one-line Blade comment naming the block and the task that re-ports it. `kidzio/*.html` is
   still the source of truth, so nothing is lost.
4. The target is a Kidzio extra we are not shipping (animals, A–Z, opposites on Home) → delete,
   per `CLAUDE.md`: those are not on Home.

Commented-out markup was tried first and rejected: it keeps dead `href`s in the file, which the
guard below cannot tell apart from live ones.

## Scope — Home (`resources/views/pages/⚡home.blade.php`)

- [x] `streak.html` stat tile and week card → inert, data kept (**T11** relinks them)
- [x] `streak.html` notification row → left with the notification sheet
- [x] `game-word-search.html`, `game-counting.html` featured tiles → deleted (**T12** / **T19**)
- [x] `ranking-friends.html` ×2 → left with the friends feed; `/profile` already links the live screen
- [x] `settings.html` parent tip chip → deleted with the tip (**T08**)
- [x] `settings.html` sheet footer + `rewards-dashboard.html` row → left with the sheet (**T16**)
- [x] `learn-math.html` / `learn-alphabet.html` / `learn-animals.html` / `learn-words.html` search
      tiles → replaced with the three live subjects (next pack each) plus the Learn library;
      animals/words dropped per product rules
- [x] Friends-today feed (Leo / Ana / fake streak chips) → deleted (**T18**)
- [x] Notification bell, unread badge `3` and the five invented rows → deleted (**T16**)
- [x] Header avatar emoji → live `users.avatar` via `UserStatService::avatarFor()`; online dot deleted
- [x] `data-install` PWA button → deleted (**T15**)
- [x] Voice-search mic → deleted; it recognised `en-US` only (**T21**)
- [x] "Recent" (hardcoded English) and "popular" chips → deleted (**T17**)

## Scope — Profile (`resources/views/pages/⚡profile.blade.php`)

- [x] Rewards-dashboard row with fake "3 new" → deleted (**T13**)
- [x] Parent zone section, including the hardcoded `30 min` → deleted (**T06**–**T08**)
- [x] Settings gear + menu row → deleted (**T10**)
- [x] Share profile button → deleted; it had no handler at all (**T21**)
- [x] Streak menu row → deleted; the live streak shortcut above stays (**T11**)
- [x] "Online" hero chip → deleted, nothing tracks presence (**T07**)

## Also found while sweeping

Not in the original scope, but the same defect:

- [x] **`home.js` search index** — the live Home search filtered a hardcoded English catalog of 16
      entries, 15 of them `.html` links. Now server-rendered from `SearchService::homeCatalog()`
      into `<script type="application/json" id="searchIndex">`: 12 Georgian destinations, every
      `href` a real route, the three subjects pointing at their next pack. English "No matches"
      strings moved to `lang/ka` via data attributes.
- [x] **`app.js` back-button fallback** — `location.href = … || "index.html"` → `"/"`.
- [x] **Fake "N online"** — `FriendshipService` set `onlineCount: $friendCount`, so every friend was
      always online. Removed from the service and both Data objects, and from the three places that
      showed it (`/profile`, and the hero chip plus filter label on `/ranking-friends`).

## Guard

- [x] `tests/Feature/NoTemplateLinksTest.php` fails if `href="….html"`, `href: '….html'` or
      `location.href = '….html'` appears in any Blade view **or** in any page script a view
      actually loads. `index.js` is excluded: it is the prebuilt Swiper bundle and
      `docs/rails-swiper.md` says not to touch it.

That guard is the real deliverable — it stops this from regressing on the next port.

## Done when

- [x] No Blade view or loaded page script links to a `.html` file.
- [x] No screen displays a number that isn't computed from the database.
- [x] Tapping every enabled control on Home and Profile lands on a real screen.
- [x] `composer test` green (116 tests, Pint, PHPStan level 7).

## Out of scope

Building any of the linked screens. This task only makes the app honest about what exists.

## Left for the task that owns it

- `/ranking-friends` filter tabs (`all` / `online` / `streak` / `near`) are still inert markup, and
  that screen has its own dead mic button → **T18** and **T21**.
- Orphaned `lang/ka` keys (`leo_*`, `ana_*`, `parent_tip_text`, `search_chip_*`, `new_today`, …) were
  left in place on purpose: the tasks above need them when they re-port their block.
