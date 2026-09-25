# T18 — Ranking depth + league rewards

**Priority:** P2 · depth
**Status:** done
**Depends on:** T04 (privacy enforced), T13 (claim mechanics)

## Why now

Global, weekly, league, and friends rankings all work. What's missing is the payoff — weekly prizes
and league rewards are never paid — plus the social surfaces on Home. Worth doing once the reward
plumbing from T13 exists so prizes can be claimed rather than silently granted.

## Scope — prizes & rewards

- [x] Weekly ranking prize claiming (the screen already shows prizes)
- [x] League rewards: weekly stay bonus, champion badge, avatar frame
- [x] Season close job pays out before promote/relegate, exactly once per season per user

## Scope — friends

- [x] Friend requests behind **parent approval** (the product rule; v1 auto-accepts by nickname)
- [x] Suggested friends — same grade/league, respecting privacy toggles
- [x] Friends-today activity feed on Home (the Leo/Ana block hidden in T02) from real plays
- [x] This unblocks the **Social Star** badge, which currently can never unlock

## Scope — ranking filters

- [x] Filters: worldwide, country, on a streak, online now
- [x] Country on the account (`kidzio/country.html` → `pages::country`) — needed before a country
      filter means anything
- [x] Top countries block
- [x] Online-now presence — only if T07's session tracking gives a real signal; otherwise drop the
      filter rather than fake it

## Code touchpoints

- `app/Services/LeagueSeasonService.php`, `FriendshipService.php`, `RewardService.php`
- `app/Repositories/LeagueRepository.php`, `FriendshipRepository.php`
- Migration: `country` on `users`; friend-request approval state
- `resources/views/pages/⚡ranking-weekly.blade.php`, `⚡league.blade.php`, `⚡ranking-friends.blade.php`,
  `⚡home.blade.php`

## Done when

A closed season pays its rewards once, a friend request needs parent approval, the Home friends feed
shows real activity, and Social Star can unlock.

## Out of scope

Chat or any kid-to-kid free text. Not in v1 — safety scope we are not taking on.
