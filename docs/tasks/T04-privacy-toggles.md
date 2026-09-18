# T04 — Enforce privacy toggles

**Priority:** P0 · ship blocker
**Status:** done
**Depends on:** —

## Why now

`users.show_on_leaderboard` is written by `UserProfileService` and read by **nothing**. Grepping the
app, the only privacy flag actually enforced is `allow_friend_requests` in `FriendshipService`. So a
parent who switches a child off the global ranking still sees that child listed with their name and
XP. That is a broken privacy promise about a minor, and it is a small fix — it just has to happen
before anyone else uses the app.

## Scope

- [x] Filter `show_on_leaderboard = false` out of every ranking read:
  - [x] `UserStatRepository::topByXp()`
  - [x] `UserStatRepository::rankFor()` and `xpAtRank()` — an opted-out kid must not consume a rank
        slot, and must still see a sensible "you" strip
  - [x] `UserStatRepository::countLearners()`
  - [x] weekly ranking queries (`topByWeekXp`, `rankForWeek`, `weekXpAtRank`)
  - [x] `LeagueRepository` group standings — a league is a closed ~12-player group, so opting out of
        *global* does not remove a kid from their league. Documented on `membersRanked()`.
- [x] Opted-out kid's own view: they still see their own XP and progress, just not a public rank
- [x] Friends ranking: still allowed (explicit mutual relationship), but respect
      `allow_friend_requests` for *new* adds — already done, add a test
- [x] Profile "global rank" metric reflects the same rule as the leaderboard (no contradiction
      between two screens)
- [x] Feature tests: opted-out kid absent from `topByXp`, ranks of other kids close the gap, own
      screens still work

## Decisions recorded

- **Friends ranking:** opting out of global does **not** hide the kid. Friendship is consent.
- **League / `/ranking-weekly`:** opting out of global does **not** hide the kid. A league is a
  closed ~12-player group and promote/relegate needs every member. `/ranking-weekly` currently
  renders that same cohort, so the public filter is the global weekly XP queries, not that page.
- **Default for a new account:** visible. Confirmed on the migration (`default(true)`), `UserFactory`,
  and Filament `UserForm`.

## Code touchpoints

- `app/Repositories/UserStatRepository.php`
- `app/Repositories/LeagueRepository.php`
- `app/Models/User.php` — `visibleOnLeaderboard` scope; `UserStat` applies it
- `resources/views/pages/⚡leaderboard.blade.php`, `⚡ranking-weekly.blade.php`, `⚡profile.blade.php`

## Done when

- Toggling the switch on `/edit-profile` visibly removes the kid from `/leaderboard` on the next load.
- `/ranking-weekly` and `/league` still list them (closed league group). `/ranking-friends` still lists them.
- No public ranking query bypasses the filter (one `visibleOnLeaderboard` scope, used everywhere).
- Tests cover opted-out exclusion and rank renumbering.

## Out of scope

- Parent PIN gating of the toggle → **T06**
- Ranking filters (country, on-a-streak, online) → **T18**
