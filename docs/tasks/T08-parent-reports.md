# T08 — Weekly & full reports

**Priority:** P1 · core product
**Status:** not started
**Depends on:** T06 (gate), T07 (minutes data)

## Why now

The report is what keeps a paying parent engaged after the novelty wears off, and it is the natural
home for the weekly email that brings them back. All the underlying data already exists —
`user_stats`, `user_activity_days`, `user_plan_progress` — so this is mostly presentation.

## Scope

- [ ] `pages::weekly-report` from `kidzio/weekly-report.html` → `/weekly-report` (parent group)
- [ ] `pages::full-report` from `kidzio/full-report.html` → `/full-report`
- [ ] Weekly numbers: XP, packs finished, active days, minutes played, per-subject breakdown
- [ ] Week-over-week comparison ("better than last week" is the useful signal, not raw XP)
- [ ] Subject mastery per week — reuse the Profile mastery calculation, do not re-derive it
- [ ] Full report: longer range (last 8–12 weeks) with the same definitions
- [ ] Weekly email to the parent (Monday), Georgian, opt-out respected
- [ ] `pages::export-progress` from `kidzio/export-progress.html` — PDF of the report
- [ ] Monthly goals chip links here (goals page is already live)

## Data honesty

Every figure must come from one shared service so the report, Profile, and parent dashboard cannot
disagree. If a metric (e.g. minutes) has no data before T07 shipped, show the range from when
tracking started rather than a zero that reads as "my kid did nothing".

## Code touchpoints

- `app/Services/ProgressReportService.php` — one place that computes a week's figures
- `app/Repositories/UserStatRepository.php`, `WeekPlanRepository.php`
- Mail: weekly report mailable + a scheduled command in `routes/console.php`
- PDF: pick one renderer and note the choice in this file before building
- `resources/views/pages/⚡weekly-report.blade.php`, `⚡full-report.blade.php`,
  `⚡export-progress.blade.php`

## Done when

- A parent can open the weekly report behind the PIN and see figures that match Profile.
- The Monday email sends to verified parent emails only, and unsubscribing stops it.
- Export produces a readable PDF with Georgian text rendering correctly (check the font — this is
  the usual failure).
- Tests: report figures for a seeded week, email scheduling, opt-out.

## Out of scope

- Parent-set custom goals (monthly goals page is system-generated for now)
- Multi-kid comparison — v1 is one kid per account
