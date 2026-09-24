# T12 — Mini-games batch 1: tap-correct + counting

**Priority:** P1 · core product
**Status:** done
**Depends on:** T01 (content model proven)

## Why now

Quick Quiz is the only game. A kid plays the same multiple-choice screen 21 times a week, which is
the fastest way to lose a 7-year-old. Two more formats roughly triple the perceived variety, and
counting fits მათემატიკა directly. This task also proves the *second* format end to end, which is
what makes T19 (the remaining eleven games) mechanical instead of exploratory.

Home already has a `game-counting.html` tile neutralized in T02.

## Scope — make the player format-driven

The plumbing matters more than the two screens:

- [x] `QuestionFormat` already exists; confirm `game_question` `payload`/`answer` JSON covers
      tap-correct and counting without a schema change
- [x] `QuestionPlayModeResolver` routes a pack to the right player component by format
- [x] Week plan packs can declare a format, so a week can mix Quick Quiz and counting days
- [x] Shared scoring: lives, XP per correct, pack completion, `recordPlay`, badge eval — extracted
      once (`GamePlayService`) and reused, not copied per game

## Scope — the two games

- [x] `pages::game-tap-correct` from `kidzio/game-tap-correct.html`
- [x] `pages::game-counting` from `kidzio/game-counting.html`
- [x] Routes `/game-tap-correct/{item?}`, `/game-counting/{item?}` mirroring the existing
      `/game-multiple-choice/{item?}` behaviour (bare URL → next incomplete item)
- [x] Georgian question content for both formats, grade-scoped, seeded like T01
- [x] Correct/incorrect feedback matching the existing quiz behaviour
- [x] Restore the Home featured tile for counting

## Scope — where these appear

- [x] Today's plan and subject tiles link to the right player for the pack's format
- [x] Daily mission tasks likewise

## Code touchpoints

- `app/Services/GamePlayService.php`, `QuestionPlayModeResolver.php`
- `app/Enums/QuestionFormat.php`, `GameType.php`
- `database/seeders/` — counting/tap-correct banks
- `resources/views/pages/⚡game-tap-correct.blade.php`, `⚡game-counting.blade.php`
- `routes/web.php`

## Done when

- A week can contain packs of three different formats and the correct player opens for each.
- All three games award XP, consume lives, complete packs, and evaluate badges through one service.
- Adding a fourth format requires a Blade file and a seeder — no changes to scoring.
- Tests: each format plays to completion and records a play.

## Out of scope

- Sounds and voice reader → **T21**
- The remaining eleven games → **T19**
- Difficulty setting → **T19**
