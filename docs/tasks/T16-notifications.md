# T16 — Notifications: in-app + push

**Priority:** P2 · depth
**Status:** done
**Depends on:** T10 (settings), T11 (streak events)

## Why now

Onboarding collects notification preferences and a reminder time, and nothing is ever sent. The bell
sheet is a hard-coded list with a fake unread count (hidden in T02). This is the main re-engagement
lever, but it needs the events from T11 to have anything worth sending.

## Scope — in-app

- [x] `notifications` storage (Laravel's notifications table is fine) with a Georgian title/body and
      a target route per row
- [x] Bell sheet lists real rows, newest first; rows deep-link into the app (no `.html`)
- [x] Real unread count; marking read on open
- [x] Emitters: badge unlocked, league promoted/relegated, streak at risk, mission ready, new week
      unlocked, friend request accepted

## Scope — push

- [x] Web push subscription (VAPID), stored per device, respecting the onboarding opt-in
- [x] Respect the per-type switches from Settings: streak, new lessons, rewards & rankings
- [x] Scheduled sends: streak-about-to-expire, daily mission ready at the configured reminder time
      (default ~18:00), in the account timezone from T07
- [x] Quiet during bedtime hours (T07)
- [x] Unsubscribe, and stop sending to dead subscriptions

## Code touchpoints

- Migrations: notifications, push subscriptions
- `app/Notifications/*`, `app/Services/NotificationService.php`
- `routes/console.php` — scheduled reminders
- `resources/views/pages/⚡home.blade.php` (bell sheet), `⚡settings.blade.php`

## Done when

The bell shows real events, push arrives at the configured time for opted-in accounts only, and
switching a type off in Settings actually stops that type.

## Out of scope

Email — the weekly parent report email belongs to **T08**.
