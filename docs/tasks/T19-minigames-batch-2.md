# T19 — Mini-games batch 2: the rest

**Priority:** P2 · depth
**Status:** done
**Depends on:** T12 (format-driven player)

## Why now

Eleven more game screens exist in the template. After T12 proves the format-driven player, each of
these is a Blade port plus a question bank — mechanical work, best done in batches once the
scoring core is stable.

## Games, roughly in value order

- [x] `game-word-search` (a Home tile already advertises it)
- [x] `game-fill-letter` — ქართული
- [x] `game-spell-word` — ქართული
- [x] `game-trace-letter` — ქართული, handwriting; needs canvas/pointer work, size it separately
- [x] `game-match-word` — ქართული
- [x] `game-knowledge` — ისტორია
- [x] `game-connect-pair`
- [x] `game-opposites`
- [x] `game-match-animal`, `game-guess-animal`, `game-body-parts`, `game-where-live` — these are
      Kidzio extras outside the three v1 subjects. Decide whether they ship at all before porting.

## Also in this task

- [x] Difficulty setting Easy / Medium / Hard affecting question selection and XP
- [x] Per-quiz kid score ("beat yesterday", correct count)
- [x] Each new format's questions seeded grade-scoped and `locale=ka`

## Rule

If porting a game requires touching `GamePlayService` scoring, stop — that means T12's abstraction
is wrong, and fixing it is cheaper than working around it eleven times.

`award()` is still `correctCount * xp_per_correct`, plus combo +20, speed +20, and daily mission +120.
The only change is a difficulty scale on that **base pack XP** (easy ×3/4, medium ×1, hard ×3/2).
Combo, speed, and the mission bonus are unchanged, so a medium pack still scores the old 8 XP per
correct answer. `isPlayableFormat` now accepts Choice, Count, Spell, Pairs, Grid, and Trace so those
questions can be presented and graded. Hotspot stays out (body parts are not v1). That gate is not a
new scoring model: every game still finishes through `gradeChoice` / `gradeInput` and `award()`.

## Decisions

- **Trace letter shipped.** The template's pointer path is scored in `TraceStrokeService` (60% of
  guide samples within radius 18, at least 20 points). A pass calls `gradeInput` with the letter;
  three misses grade it wrong. Stars and the accuracy percent are display-only and do not change XP.
- **Kidzio extras are out of v1.** `game-match-animal`, `game-guess-animal`, `game-body-parts`, and
  `game-where-live` are not school subjects (ქართული, მათემატიკა, ისტორია). They are not ported and
  have no routes. `GameType::playerRoute()` still falls back to Quick Quiz for those slugs.
- **Settings difficulty was re-ported** from `kidzio/settings.html` into the T10 placeholder
  (Easy / Medium / Hard). It saves `users.play_difficulty`. Week-1 packs for the new games carry
  15 questions (5 easy, 5 medium, 5 hard); a round plays 5 for the kid's difficulty, falling back
  to medium, then to the whole bank. Older packs stay medium, so easy/hard kids still get a round.
- **Preferred-subjects “Starting difficulty” stays a comment.** That picker is age bands (4–6 / 6–8 / 8+),
  and this app filters content by class, not age. The live difficulty control is Settings. The
  comment in `⚡preferred-subjects.blade.php` remains on purpose.
- **Knowledge “poll” is empty on purpose** (`quiz.no_poll`). There is no class-wide vote, so the
  template percentages are not shown. Speaker buttons stay inert (sounds are T21).
- **Word-search score is correct words**, not the template's length×10. Hints reveal the first cell
  and do not change XP.

## Week 1 slots

Weeks 2–3 are unchanged. Math Monday (tap-correct) and class-1 math Wednesday (counting) are unchanged.

- Georgian: d1 Quick Quiz, d2 trace, d3 spell, d4 fill letter, d5 opposites, d6 match word, d7 word search
- History: d5 knowledge, d6 connect pair

## Code touchpoints

- `resources/views/pages/⚡game-*.blade.php`
- `app/Enums/QuestionFormat.php`, `app/Services/QuestionPlayModeResolver.php`
- `database/seeders/` per-format banks

## Done when

Each shipped game plays a pack, scores through the shared service, and appears in a week plan.

## Out of scope

Sounds and voice reading of questions → **T21**.
Kidzio extras listed above → not v1.
