# T14 — Learn library: live

**Priority:** P2 · depth
**Status:** not started
**Depends on:** T01, T12

## Why now (and why not earlier)

The Learn tab was deliberately skipped: the week plan answers "what do I play next" better than a
library does, so the library is discovery rather than a core path. But it is one of five tabs and it
is currently a shell with fake counts — so it stays on the list.

## Scope

- [ ] Real subject tiles for ქართული / მათემატიკა / ისტორია: pack count, % complete, grade range —
      from `WeekPlanRepository`, no dummy numbers
- [ ] `pages::section-list` from `kidzio/section-list.html` — weeks as sections for a subject
- [ ] `pages::lesson-details` from `kidzio/lesson-details.html` — a pack: questions, XP, duration
- [ ] `pages::lesson-locked` from `kidzio/lesson-locked.html` — locked pack with its requirement
      (finish the previous weekday / week)
- [ ] Continue = the same "next incomplete pack" the Home hero uses (one service, one answer)
- [ ] Today's spotlight — a real pick, not markup
- [ ] Favourite / heart a subject
- [ ] Per-subject badges section

## Decide before building

Kidzio's extras (Math/Alphabet/Animals/Words/Knowledge/Opposites screens, word-of-the-day, animal
sounds, read-along stories) are **not** v1 product per `CLAUDE.md` — v1 is three school subjects.
Either map those template screens onto the three subjects or drop them. Record the decision here
before porting anything.

## Code touchpoints

- `app/Repositories/WeekPlanRepository.php`, `app/Services/WeekPlanService.php`
- `resources/views/pages/⚡learn-categories.blade.php` + the three new pages
- Migration if favourites ship: `user_subject_favourites`

## Done when

Every number on the Learn tab comes from the database, and tapping through subject → week → pack
reaches a real player.

## Out of scope

Search index → **T17**. Audio (letter/animal sounds, read-along) → **T21**.
