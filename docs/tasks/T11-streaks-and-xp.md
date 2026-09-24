# T11 — Streaks & XP completeness

**Priority:** P1 · core product
**Status:** done
**Depends on:** T02

## Why now

The streak is the retention mechanic and it is half-built: `current_streak` and `longest_streak` are
stored and bumped by `recordPlay`, but there is no streak screen (three Home links pointed at
`streak.html`), no milestones, and no XP for anything except finishing a pack. The template's XP
table lists eight earning actions; we award one.

## Scope — streak screen

- [x] `pages::streak` from `kidzio/streak.html` → `/streak`
- [x] Current streak, best streak (`longest_streak` is stored but never shown)
- [x] Week view (reuse the Home dots) + **month calendar** streak map from `user_activity_days`
- [x] Milestones 3 / 7 / 14 / 30 / 100 days with XP + badge awards
- [x] Streak freeze / shield: one save, consumable. Decide where it comes from — earned at a
      milestone now, purchasable in **T20** later.
- [x] Restore the three Home streak links neutralized in T02

## Scope — XP earning parity

Award XP for the actions the UI already advertises, via `UserStatService` so streak/league/badges
all update through one path:

- [x] Daily mission complete (+120) — currently not awarded beyond the packs themselves
- [x] Streak milestones (+20 / +50 / +100)
- [x] 7-day daily-login calendar (+10 → +100) — the calendar UI is **T13**; the award rule belongs
      here
- [x] Combo / 5-in-a-row bonus inside a pack
- [x] Speed bonus (+20) — this also unblocks the **Speed Runner** badge, which currently can never
      unlock
- [x] Record each award with a source so XP history stops using placeholders

## Scope — XP history

- [x] `xp_events` table: `user_id`, `source` (enum), `subject`, `amount`, `created_at`
- [x] `/xp-progress` activity log reads real rows (the 7-day chart is already live; the subject and
      source labels are placeholders)

## Code touchpoints

- Migration: `xp_events`; `streak_freezes` count on `user_stats`
- `app/Services/UserStatService.php` — single `awardXp(user, source, amount)` entry point
- `app/Services/BadgeService.php` — Speed Runner becomes achievable
- `app/Repositories/UserStatRepository.php`
- `resources/views/pages/⚡streak.blade.php`, `⚡xp-progress.blade.php`, `⚡daily-mission.blade.php`

## Done when

- The streak screen shows a real month map and best streak.
- Every XP award in the app writes an `xp_events` row, and `/xp-progress` lists them with real
  source and subject.
- Speed Runner can unlock.
- Tests: milestone award fires once, freeze consumes on a missed day, combo and speed bonuses.

## Out of scope

- Streak reminder push → **T16**
- Buying a shield with XP → **T20**
- Social Star badge (needs the friends feed) → **T18**
