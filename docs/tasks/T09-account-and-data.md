# T09 — Account & data control

**Priority:** P1 · core product
**Status:** done
**Depends on:** T03 (code service), T05 (policy text), T06 (PIN gate)

## Why now

The privacy policy shipped in T05 promises a deletion path, and `/edit-profile` currently has a dead
"delete account" row. A children's app that collects a parent email needs a working way to change
that email and to erase the child's data. Both are parent-gated.

## Scope — change parent email

- [x] `pages::parent-email` from `kidzio/parent-email.html` → `/parent-email` (parent group)
- [x] New address must be verified before it replaces the old one (pending-email pattern)
- [x] Notify the **old** address that a change was requested
- [x] Until verified, the old email stays authoritative for reset and reports
- [x] Edit-profile's read-only parent email links here

## Scope — delete account

- [x] Delete flow behind PIN **and** a fresh parent email confirmation — two factors, because it is
      irreversible
- [x] Explain exactly what is removed before confirming (profile, XP, streak, badges, friendships,
      play history)
- [x] Soft delete the `users` row (project convention: soft deletes where recoverable) with a grace
      window, then a scheduled job hard-deletes and purges dependent rows
- [x] Friendship rows, league membership, leaderboard presence removed immediately on request — the
      grace window must not keep the kid publicly visible
- [x] Nickname freed or reserved — decide and record here

## Scope — data export

- [x] "Export all data" (JSON or the T08 PDF) for the parent, delivered to the verified email

## Decisions recorded

- **Grace window:** 14 days (`AccountService::GRACE_DAYS`). Template copy said 24h; 14 days matches
  “soft deletes where recoverable”.
- **Nickname / email:** reserved while the row is soft-deleted (unique indexes + `withTrashed`
  occupancy checks). Freed on `accounts:purge-deleted` hard delete.
- **Email change:** signed magic link (60 min) **and** 6-digit code (10 min, same as other parent
  codes) go to the **new** address. The **old** address gets an alert. `users.email` does not change
  until confirm.
- **JSON export:** emailed from `/export-progress` (JSON format card). PDF remains an on-device
  download (T08).
- **Hero stats** on parent-email: live week XP / active days / streak (template “reports sent /
  opens” had no backend). Badge / streak / product-update mail toggles stay T16.

## Code touchpoints

- Migration: `pending_parent_email`, `pending_parent_email_token`, `deletion_requested_at` on
  `users`; confirm `SoftDeletes` on the model
- `app/Services/AccountService.php` (email change + deletion request/execute)
- `app/Repositories/UserRepository.php`, `FriendshipRepository.php`, `LeagueRepository.php`
- Scheduled purge command in `routes/console.php`
- `resources/views/pages/⚡parent-email.blade.php` + delete confirmation

## Done when

- Changing the parent email requires verification and the old address is notified.
- Requesting deletion immediately removes the kid from rankings and friends, and the purge job
  removes all rows after the grace window.
- A kid without the PIN cannot reach either flow, including by direct URL.
- Tests: pending-email verification, deletion request hides from rankings, purge removes dependents.

## Out of scope

- Account transfer between parents
- Paid plan cancellation — no billing yet
