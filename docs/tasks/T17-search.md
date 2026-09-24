# T17 — Search: live index

**Priority:** P2 · depth
**Status:** not started
**Depends on:** T14 (library content to search)

## Why now

Home and Learn both have a full search overlay driving a dummy catalog with `.html` results (the
links were neutralized in T02). With only three subjects and a week plan, search is a convenience
rather than the way anyone finds content — hence P2.

## Scope

- [ ] One search service over real records: subjects, weeks, packs, games, badges
- [ ] Georgian matching that actually works — normalize case and handle partial words; test with
      Georgian input, not English
- [ ] Home search overlay results link to real routes
- [ ] Learn library search + filters (status, subject, week) against the live index
- [ ] Recent searches, per user
- [ ] Popular chips from real query counts, not a fixed list
- [ ] Leaderboard player search by nickname, respecting `show_on_leaderboard` (T04)
- [x] Settings search (row filter shipped in **T10**)

## Code touchpoints

- `app/Services/SearchService.php`
- `app/Repositories/WeekPlanRepository.php`, `BadgeRepository.php`, `UserRepository.php`
- Migration: `search_queries` if popular chips are counted
- `resources/views/pages/⚡home.blade.php`, `⚡learn-categories.blade.php`, `⚡leaderboard.blade.php`

## Done when

Typing a Georgian subject or badge name returns real results that navigate correctly, and no search
surface shows a hardcoded suggestion.

## Out of scope

Voice search → **T21**. Full-text engine (Scout/Meilisearch) unless the dataset outgrows SQL — note
it if so.
