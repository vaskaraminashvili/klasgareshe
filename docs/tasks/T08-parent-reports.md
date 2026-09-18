# T08 — Weekly & full reports

**Priority:** P1 · core product
**Status:** in progress (paused 18 Sep 2026 — finish verification, then mark done)
**Depends on:** T06 (gate), T07 (minutes data)

## Why now

The report is what keeps a paying parent engaged after the novelty wears off, and it is the natural
home for the weekly email that brings them back. All the underlying data already exists —
`user_stats`, `user_activity_days`, `user_plan_progress` — so this is mostly presentation.

## Scope

- [x] `pages::weekly-report` from `kidzio/weekly-report.html` → `/weekly-report` (parent group)
- [x] `pages::full-report` from `kidzio/full-report.html` → `/full-report`
- [x] Weekly numbers: XP, packs finished, active days, minutes played, per-subject breakdown
- [x] Week-over-week comparison ("better than last week" is the useful signal, not raw XP)
- [x] Subject mastery per week — reuse the Profile mastery calculation, do not re-derive it
- [x] Full report: longer range (last 8–12 weeks) with the same definitions
- [x] Weekly email to the parent (Monday), Georgian, opt-out respected
- [x] `pages::export-progress` from `kidzio/export-progress.html` — PDF of the report
- [x] Monthly goals chip links here (goals page is already live)

## Data honesty

Every figure must come from one shared service so the report, Profile, and parent dashboard cannot
disagree. If a metric (e.g. minutes) has no data before T07 shipped, show the range from when
tracking started rather than a zero that reads as "my kid did nothing".

## Code touchpoints

- `app/Services/ProgressReportService.php` — one place that computes a week's figures
- `app/Repositories/UserStatRepository.php`, `WeekPlanRepository.php`
- Mail: weekly report mailable + a scheduled command in `routes/console.php`
- PDF: **barryvdh/laravel-dompdf** + embedded **Noto Sans Georgian** (Mkhedruli). Kidzio CSS cannot run in DomPDF; the export is a readable recap of the same `ProgressReportService` numbers. Check the font in the PDF — Latin-only DejaVu will tofu ქართული.
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

## Paused — resume here

Implementation is in the working tree (not committed). Pickup: finish the checks below, then mark this
task / `docs/roadmap.md` / `CLAUDE.md` **done** and set Build next to **T09**.

### Already wired

- PIN-gated `/weekly-report`, `/full-report`, `/export-progress` (`parent.verified`)
- `ProgressReportService` is the shared week source for Profile, parent dashboard, and Home tip
- Mastery on reports reuses `WeekPlanService::subjectMastery` (Profile still calls that, not the snapshot)
- Monday `reports:send-weekly` at 08:00; opt-out `notification_preferences.weekly_report` + signed
  `/weekly-report/opt-out/{user}`
- PDF: `barryvdh/laravel-dompdf` + `resources/fonts/NotoSansGeorgian-{Regular,Bold}.ttf`
- Tests: `tests/Feature/ProgressReportTest.php` (plus Profile / PIN week-number match)

### Still to do

1. **PDF in the browser** — unlock PIN, `/export-progress` → download, confirm ქართული is not tofu.
2. **PHPStan** — Herd `dump-loader.php` auto_prepend breaks parallel workers. Run with php84 and a dummy
   prepend file, e.g.  
   `C:\Users\vaska\.config\herd\bin\php84\php.exe -d auto_prepend_file=%TEMP%\kidzio-empty-prepend.php vendor/phpunit/phpunit/phpunit --filter=ProgressReportTest`  
   PHPStan needs a single-process run (parallel workers ignore `-d` and die on dump-loader).
3. Pint already ran on dirty PHP (`ProgressPdfService` was cleaned). Re-run `--dirty` after any last edits.
4. Browser tab was left on `https://klasgareshe.test/full-report` (Mia, PIN `2580`). Unlocked.

### Known quirks (not blockers unless you want to polish)

- Week chip copy is `კ:n` → renders `კ38`. Could be `კვ. :n`.
- Livewire sheets live **inside** `<main>` (one root element). Do not move them back out.
- Highlight opener is `showHighlight()` — property `$openHighlight` cannot share the method name.
- Home `syncHome` uses `$reportWeek` so it does not overwrite `WeekPlanService $week`.
- Per-subject minutes/XP are not stored; subject rows are packs + Profile mastery. Minutes are `—` until
  the first play session exists.
- PHP tests: call `vendor/phpunit/phpunit/phpunit` via `php84.exe`, not `artisan test` / `php.bat`
  (those re-spawn PHP and hit dump-loader).
