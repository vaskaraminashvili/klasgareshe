# T14 — Learn library: live

**Priority:** P2 · depth
**Status:** done
**Depends on:** T01, T12

## Why now (and why not earlier)

The Learn tab was deliberately skipped: the week plan answers "what do I play next" better than a
library does, so the library is discovery rather than a core path. But it is one of five tabs and it
is currently a shell with fake counts — so it stays on the list.

## Scope

- [x] Real subject tiles for ქართული / მათემატიკა / ისტორია: pack count, % complete, grade range —
      from `WeekPlanRepository`, no dummy numbers
- [x] `pages::section-list` from `kidzio/section-list.html` — weeks as sections for a subject
- [x] `pages::lesson-details` from `kidzio/lesson-details.html` — a pack: questions, XP, duration
- [x] `pages::lesson-locked` from `kidzio/lesson-locked.html` — locked pack with its requirement
      (finish the previous weekday / week)
- [x] Continue = the same "next incomplete pack" the Home hero uses (one service, one answer)
- [x] Today's spotlight — a real pick, not markup
- [x] Favourite / heart a subject
- [x] Per-subject badges section

## Decide before building

Kidzio extras (Math / Alphabet / Animals / Words / Knowledge / Opposites hubs, word-of-the-day,
animal sounds, read-along) stay **out of v1**. The Learn tab shows the three school subjects;
`section-list/{subject}` is the subject hub (`learn-*.html` not ported). Favourites reuse
`users.favourite_subjects` (no `user_subject_favourites` table). Lock = previous pack in that
subject (weekday / week order), not a fake XP threshold. Chapter boss, dummy vocab 11–20, kid
rating 4.9, Notify, and the XP sheet are omitted.

## Code touchpoints

- `app/Services/LearnLibraryService.php`, `app/Repositories/WeekPlanRepository.php`, `app/Services/WeekPlanService.php`
- `resources/views/pages/⚡learn-categories.blade.php`, `⚡section-list.blade.php`, `⚡lesson-details.blade.php`, `⚡lesson-locked.blade.php`
- Favourites: `UserProfileService::toggleFavouriteSubject()` on `users.favourite_subjects`

## Done when

Every number on the Learn tab comes from the database, and tapping through subject → week → pack
reaches a real player.

## Out of scope

Search index → **T17**. Audio (letter/animal sounds, read-along) → **T21**.
Shop → **T20**. Notifications → **T16**. Unbuilt mini-games → **T19**. Week 3–8 packs → **T01**.
