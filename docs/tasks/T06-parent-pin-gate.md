# T06 — Parent PIN gate + parent controls

**Priority:** P1 · core product
**Status:** not started
**Depends on:** T02 (links neutralized)

## Why now

`CLAUDE.md` states parent-gated screens must never be reachable by the kid without verification.
Right now there is no gate and no parent zone at all — the Profile rows are template stubs. This task
builds the lock first, so every parent screen after it (T07, T08, T09) has somewhere to live.

## Scope — the gate

- [ ] `parent_pin` on `users` (hashed, nullable) + `parent_pin_set_at`
- [ ] PIN setup: first time a parent opens the zone, they create a 4-digit PIN
- [ ] PIN gate overlay + numeric pad from `kidzio/parent-controls.html` (the overlay markup)
- [ ] `pages::change-pin` from `kidzio/change-pin.html`
- [ ] Forgot PIN → parent email verification (reuse **T03**'s code service), then set a new PIN
- [ ] **Middleware** `parent.verified` — server-side, on every parent route. Hiding a link is not a
      gate; a kid typing the URL must be bounced.
- [ ] Unlock is session-scoped and short-lived (15 min idle, or until the zone is left) — "lock
      parent zone after viewing"
- [ ] Throttle wrong PIN attempts (lockout after ~5, backoff)

## Scope — parent controls dashboard

- [ ] `pages::parent-controls` from `kidzio/parent-controls.html` → `/parent-controls`
- [ ] Live this-week numbers: XP, packs finished, active days (from `UserStatRepository` /
      `user_activity_days` — the same source Profile already uses, no second definition)
- [ ] Rows linking to screen time (**T07**), reports (**T08**), account (**T09**)
- [ ] Parent email shown with verified state
- [ ] `pages::preferred-subjects` from `kidzio/preferred-subjects.html` — parent override of the
      kid's subject choice
- [ ] Wire the Profile parent-zone rows unblocked in T02

## Explicitly deferred inside this screen

- Minutes-played chart → needs session tracking, which arrives in **T07**
- Break reminders → **T07**
- Age-appropriate content filter → content is already grade-scoped; note it as satisfied and don't
  build a second filter

## Code touchpoints

- Migration: `parent_pin`, `parent_pin_set_at` on `users`
- `app/Http/Middleware/EnsureParentUnlocked.php` + registration in the route group
- `app/Services/ParentZoneService.php` (PIN set/verify/lock) + `UserRepository`
- `app/Services/ParentVerificationService.php` — reuse for forgot-PIN
- `routes/web.php` — a `parent` route group behind `auth:web` + the new middleware

## Done when

- A kid cannot reach any `/parent-*` URL without entering the PIN, including by direct URL.
- PIN can be set, changed, and recovered through the parent email.
- Parent controls shows real week numbers matching Profile.
- Tests: unauthenticated bounce, wrong-PIN throttle, session expiry, direct-URL bounce.

## Out of scope

Screen time, bedtime, reports, deletion — the next three tasks.
