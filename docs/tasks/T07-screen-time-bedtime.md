# T07 — Screen time + bedtime lock

**Priority:** P1 · core product
**Status:** not started
**Depends on:** T06 (PIN gate + middleware)

## Why now

This is the feature parents of 6–9 year olds actually ask about, and the Profile screen already
advertises it with a hardcoded "30 min" chip. It also unblocks the minutes chart on the parent
dashboard, which has no data source until play sessions are tracked.

## Scope — session tracking (prerequisite)

- [ ] `user_sessions` (or `user_play_sessions`): `user_id`, `started_at`, `ended_at`,
      `seconds`, indexes on `user_id + started_at`
- [ ] Heartbeat from the app while a kid is on a game/lesson screen — Livewire poll or a small
      endpoint; tolerate a closed tab (cap a dangling session, don't count it forever)
- [ ] `ScreenTimeService::usedTodaySeconds(User)` — one definition, used by every screen

## Scope — screen time

- [ ] `pages::screen-time` from `kidzio/screen-time.html` → `/screen-time` (parent group)
- [ ] Daily limit presets 15 / 30 / 45 / 60 min + off
- [ ] Used vs remaining today
- [ ] Gentle pause when the limit is hit: a full-screen friendly block, **not** a logout. Kid can
      still see their stats; play routes are blocked.
- [ ] Break reminder every 15 min of continuous play (dismissible nudge)
- [ ] Enforce server-side in middleware on play routes (`/game-*`) — the block must survive a reload
- [ ] Parent can grant a one-off extension from the parent zone

## Scope — bedtime lock

- [ ] `pages::bedtime-lock` from `kidzio/bedtime-lock.html` → `/bedtime-lock`
- [ ] Enable/disable, bedtime + wake time
- [ ] During sleep hours the app shows the pause screen instead of play routes
- [ ] Timezone: store the account timezone explicitly; do not rely on the server default

## Scope — wiring back

- [ ] Profile screen-time chip shows the real remaining minutes (removes the T02 placeholder)
- [ ] Parent dashboard daily-minutes chart vs the daily goal

## Code touchpoints

- Migration: `user_play_sessions`; `daily_limit_minutes`, `bedtime_start`, `bedtime_end`,
  `timezone` on `users`
- `app/Services/ScreenTimeService.php`, `app/Repositories/PlaySessionRepository.php`
- `app/Http/Middleware/EnforceScreenTime.php` on the play route group
- `resources/views/pages/⚡screen-time.blade.php`, `⚡bedtime-lock.blade.php`

## Done when

- Limit set to 15 min → after 15 minutes of play the kid gets the pause screen, and a reload does
  not bypass it.
- Bedtime 21:00–07:00 → play blocked in that window in the account timezone.
- Profile and parent dashboard show real minutes.
- Tests: limit hit, reload after limit, extension grant, bedtime window across midnight.

## Out of scope

- Reports and charts beyond the daily minutes line → **T08**
- Push notification for bedtime → **T16**
