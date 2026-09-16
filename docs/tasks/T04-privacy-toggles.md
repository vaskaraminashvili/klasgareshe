# T04 — Enforce privacy toggles

**Priority:** P0 · ship blocker
**Status:** not started
**Depends on:** —

## Why now

`users.show_on_leaderboard` is written by `UserProfileService` and read by **nothing**. Grepping the
app, the only privacy flag actually enforced is `allow_friend_requests` in `FriendshipService`. So a
parent who switches a child off the global ranking still sees that child listed with their name and
XP. That is a broken privacy promise about a minor, and it is a small fix — it just has to happen
before anyone else uses the app.

## Scope

- [ ] Filter `show_on_leaderboard = false` out of every ranking read:
  - [ ] `UserStatRepository::topByXp()`
  - [ ] `UserStatRepository::rankFor()` and `xpAtRank()` — an opted-out kid must not consume a rank
        slot, and must still see a sensible "you" strip
  - [ ] `UserStatRepository::countLearners()`
  - [ ] weekly ranking queries
  - [ ] `LeagueRepository` group standings — decide and document: a league is a closed ~12-player
        group, so opting out of *global* should not remove a kid from their league. Write the
        decision into the repository docblock.
- [ ] Opted-out kid's own view: they still see their own XP and progress, just not a public rank
- [ ] Friends ranking: still allowed (explicit mutual relationship), but respect
      `allow_friend_requests` for *new* adds — already done, add a test
- [ ] Profile "global rank" metric reflects the same rule as the leaderboard (no contradiction
      between two screens)
- [ ] Feature tests: opted-out kid absent from `topByXp`, ranks of other kids close the gap, own
      screens still work

## Decisions to record

Write these in the task file as you make them, then into `CLAUDE.md` product rules:

- Does opting out hide the kid from **friends** ranking too? (proposed: no — friendship is consent)
- Does it hide them from their **league**? (proposed: no — closed group, needed for promote/relegate)
- Default for a new account? (proposed: visible, since `UserForm` defaults exist — confirm and make
  it explicit in the migration default rather than implicit)

## Code touchpoints

- `app/Repositories/UserStatRepository.php`
- `app/Repositories/LeagueRepository.php`
- `app/Models/User.php` — consider a `visibleOnLeaderboard` scope so the filter lives in one place
- `resources/views/pages/⚡leaderboard.blade.php`, `⚡ranking-weekly.blade.php`, `⚡profile.blade.php`

## Done when

- Toggling the switch on `/edit-profile` visibly removes the kid from `/leaderboard` and
  `/ranking-weekly` on the next load.
- No ranking query bypasses the filter (one scope, used everywhere).
- Tests cover opted-out exclusion and rank renumbering.

## Out of scope

- Parent PIN gating of the toggle → **T06**
- Ranking filters (country, on-a-streak, online) → **T18**
