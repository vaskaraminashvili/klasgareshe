# T06 — Parent PIN gate + parent controls

**Priority:** P1 · core product
**Status:** done
**Depends on:** T02 (links neutralized)

## Why now

`CLAUDE.md` states parent-gated screens must never be reachable by the kid without verification.
Right now there is no gate and no parent zone at all — the Profile rows are template stubs. This task
builds the lock first, so every parent screen after it (T07, T08, T09) has somewhere to live.

## Scope — the gate

- [x] `parent_pin` on `users` (hashed, nullable) + `parent_pin_set_at`
- [x] PIN setup: first time a parent opens the zone, they create a 4-digit PIN
- [x] PIN gate overlay + numeric pad from `kidzio/parent-controls.html` (the overlay markup)
- [x] `pages::change-pin` from `kidzio/change-pin.html`
- [x] Forgot PIN → parent email verification (reuse **T03**'s code service), then set a new PIN
- [x] **Middleware** `parent.verified` — server-side, on every parent route except the gate/OTP.
      Hiding a link is not a gate; a kid typing `/change-pin` is bounced to `/parent-controls`.
- [x] Unlock is session-scoped and short-lived (15 min idle, or until the zone is left)
- [x] Throttle wrong PIN attempts (lockout after 5, 60s backoff)

## Scope — parent controls dashboard

- [x] `pages::parent-controls` from `kidzio/parent-controls.html` → `/parent-controls`
- [x] Live this-week numbers: XP, packs finished, active days (from `UserStatRepository` /
      `user_activity_days` — the same source Profile already uses, no second definition)
- [x] Rows linking to screen time (**T07**), reports (**T08**), account (**T09**) — T02 comments
      until those tasks; monthly goals / edit profile / privacy policy are live
- [x] Parent email shown with verified state (change-email is **T09**)
- [x] `pages::preferred-subjects` from `kidzio/preferred-subjects.html` — parent override of the
      kid's subject choice (v1 school subjects only)
- [x] Wire the Profile parent-zone row for controls; screen time / weekly report stay T07/T08

## Explicitly deferred inside this screen

- Minutes-played chart → needs session tracking, which arrives in **T07**
- Break reminders → **T07**
- Age-appropriate content filter → content is already grade-scoped; shown as always-on, no second filter

## Code touchpoints

- Migration: `parent_pin`, `parent_pin_set_at` on `users`
- `app/Http/Middleware/EnsureParentUnlocked.php` + `LockParentZoneOnExit`
- `app/Services/ParentZoneService.php` (PIN set/verify/lock) + `UserRepository`
- `app/Services/VerificationCodeService.php` — reused for forgot-PIN
- `routes/web.php` — parent routes behind `auth:web`; change-pin / preferred-subjects behind `parent.verified`

## Done when

- A kid cannot reach any `/parent-*` URL without entering the PIN, including by direct URL.
- PIN can be set, changed, and recovered through the parent email.
- Parent controls shows real week numbers matching Profile.
- Tests: unauthenticated bounce, wrong-PIN throttle, session expiry, direct-URL bounce.

## Out of scope

Screen time, bedtime, reports, deletion — the next three tasks.
