# T13 — Rewards dashboard + daily login calendar

**Priority:** P1 · core product
**Status:** not started
**Depends on:** T11 (XP sources + award path)

## Why now

The Rewards tab is one of five bottom tabs and it opens the badge collection instead of a rewards
screen. Badges are live, so the missing half is the wallet and the claim loop — the part that gives a
kid a reason to open the app on a day they don't feel like studying. The daily-login calendar is the
single highest-retention item left in the template.

## Scope

- [ ] `pages::rewards-dashboard` from `kidzio/rewards-dashboard.html` → `/rewards-dashboard`
- [ ] Rewards tab in the bottom nav points here; badges become a row inside it
- [ ] XP wallet: total XP and **spendable** XP. Decide now whether spending reduces ranking XP —
      proposed: no, keep a separate `coins`/spendable balance so the leaderboard stays honest.
      Record the decision in this file.
- [ ] 7-day daily login calendar, increasing XP, bigger prize on day 7 (award rule lives in T11)
- [ ] Claim queue: daily box, newly earned badges, streak freeze. A claim is explicit and one-time.
- [ ] To-claim count badge on the tab — real, not the "3 new" placeholder removed in T02
- [ ] League standing summary + link to `/league`
- [ ] Restore the Profile rewards row and the Home notification row neutralized in T02

## Scope — data

- [ ] `reward_claims` table: `user_id`, `type`, `reference`, `claimed_at`, unique per claimable
- [ ] `daily_logins` (or derive from `user_activity_days` — check first, don't duplicate)
- [ ] Idempotent claiming: double-tap or double-submit must not pay twice

## Code touchpoints

- Migration: `reward_claims`, spendable balance on `user_stats`
- `app/Services/RewardService.php`, `app/Repositories/RewardRepository.php`
- `app/Services/UserStatService.php` — claims award through the T11 `awardXp` path
- `resources/views/pages/⚡rewards-dashboard.blade.php`, `⚡badges.blade.php`, `⚡profile.blade.php`
- `resources/views/components/⚡bottom-nav-bar.blade.php`

## Done when

- The Rewards tab opens a dashboard with a real wallet, a real claim count, and a working 7-day
  calendar.
- Claiming twice pays once (test it explicitly).
- No placeholder counts remain on Rewards, Profile, or the tab bar.

## Out of scope

- Spending XP on items → **T20**
- Weekly league prize payout and champion rewards → **T18**
- Share badge backend → **T21**
