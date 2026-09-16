# T09 — Account & data control

**Priority:** P1 · core product
**Status:** not started
**Depends on:** T03 (code service), T05 (policy text), T06 (PIN gate)

## Why now

The privacy policy shipped in T05 promises a deletion path, and `/edit-profile` currently has a dead
"delete account" row. A children's app that collects a parent email needs a working way to change
that email and to erase the child's data. Both are parent-gated.

## Scope — change parent email

- [ ] `pages::parent-email` from `kidzio/parent-email.html` → `/parent-email` (parent group)
- [ ] New address must be verified before it replaces the old one (pending-email pattern)
- [ ] Notify the **old** address that a change was requested
- [ ] Until verified, the old email stays authoritative for reset and reports
- [ ] Edit-profile's read-only parent email links here

## Scope — delete account

- [ ] Delete flow behind PIN **and** a fresh parent email confirmation — two factors, because it is
      irreversible
- [ ] Explain exactly what is removed before confirming (profile, XP, streak, badges, friendships,
      play history)
- [ ] Soft delete the `users` row (project convention: soft deletes where recoverable) with a grace
      window, then a scheduled job hard-deletes and purges dependent rows
- [ ] Friendship rows, league membership, leaderboard presence removed immediately on request — the
      grace window must not keep the kid publicly visible
- [ ] Nickname freed or reserved — decide and record here

## Scope — data export

- [ ] "Export all data" (JSON or the T08 PDF) for the parent, delivered to the verified email

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
