# T19 — Mini-games batch 2: the rest

**Priority:** P2 · depth
**Status:** not started
**Depends on:** T12 (format-driven player)

## Why now

Eleven more game screens exist in the template. After T12 proves the format-driven player, each of
these is a Blade port plus a question bank — mechanical work, best done in batches once the
scoring core is stable.

## Games, roughly in value order

- [ ] `game-word-search` (a Home tile already advertises it)
- [ ] `game-fill-letter` — ქართული
- [ ] `game-spell-word` — ქართული
- [ ] `game-trace-letter` — ქართული, handwriting; needs canvas/pointer work, size it separately
- [ ] `game-match-word` — ქართული
- [ ] `game-knowledge` — ისტორია
- [ ] `game-connect-pair`
- [ ] `game-opposites`
- [ ] `game-match-animal`, `game-guess-animal`, `game-body-parts`, `game-where-live` — these are
      Kidzio extras outside the three v1 subjects. Decide whether they ship at all before porting.

## Also in this task

- [ ] Difficulty setting Easy / Medium / Hard affecting question selection and XP
- [ ] Per-quiz kid score ("beat yesterday", correct count)
- [ ] Each new format's questions seeded grade-scoped and `locale=ka`

## Rule

If porting a game requires touching `GamePlayService` scoring, stop — that means T12's abstraction
is wrong, and fixing it is cheaper than working around it eleven times.

## Code touchpoints

- `resources/views/pages/⚡game-*.blade.php`
- `app/Enums/QuestionFormat.php`, `app/Services/QuestionPlayModeResolver.php`
- `database/seeders/` per-format banks

## Done when

Each shipped game plays a pack, scores through the shared service, and appears in a week plan.

## Out of scope

Sounds and voice reading of questions → **T21**.
