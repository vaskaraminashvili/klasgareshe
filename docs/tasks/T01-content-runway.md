# T01 — Content runway: weeks 3–8

**Priority:** P0 · ship blocker
**Status:** not started
**Depends on:** —

## Why now

`WeekPlanSeeder` seeds weeks 1–2 for every grade and week 3 for **class 1 only**. A class 2 or 3 kid
finishes every pack in the app in two weeks and then sits on a completed week forever
(`activeWeekNumber()` stays on the last seeded week). Content is the product; everything else on this
roadmap is packaging around it.

## Scope

- [ ] Week 3 question banks for **grade 2** and **grade 3** (ქართული · მათემატიკა · ისტორია)
- [ ] Weeks 4–8 for **all three grades**
- [ ] Split the bank files so one file is not thousands of lines — one file per week
      (`WeekPlanQuestionBankWeek4`, …) following the existing `WeekPlanQuestionBankWeek2/3` shape
- [ ] `WeekPlanSeeder` drives weeks from a single constant/array instead of the inline
      `$grade === SchoolGrade::First ? [1, 2, 3] : [1, 2]` special case
- [ ] Re-seeding is idempotent (`updateOrCreate` on pack + questions — verify no duplicate questions
      after running twice)
- [ ] A test asserts every `(grade, week, subject, weekday)` pack has exactly 5 questions with
      `locale = ka`

## Volume

Per week, per grade: 3 subjects × 7 weekdays = **21 packs** × 5 questions = **105 questions**.

| Batch | Packs | Questions |
|---|---|---|
| Week 3, grades 2–3 | 42 | 210 |
| Weeks 4–8, grades 1–3 | 315 | 1 575 |

Do this in batches and commit per week. Do not try to write 1 785 questions in one pass.

## Content rules

- ქართული: letters, syllables, simple words. Georgian script only — no Latin A–Z.
- მათემატიკა: numbers and counting for grade 1; +/− and comparison rising through grades 2–3.
- ისტორია: საქართველო for this age — flag, თბილისი, holidays, regions. Not world history.
- Difficulty must actually rise with `grade` and with `week_number`. Week 8 grade 3 should not be
  answerable by a week 1 grade 1 kid.
- 4 options per question, one correct, distractors plausible (not obviously wrong).

## Code touchpoints

- `database/seeders/WeekPlanSeeder.php`
- `database/seeders/WeekPlanQuestionBank.php`, `…Week2.php`, `…Week3.php` (+ new per-week files)
- `app/Services/WeekPlanService.php` — confirm `activeWeekNumber()` advances across the new weeks
- `app/Enums/SchoolGrade.php`, `SchoolSubject.php`

## Done when

- A class 2 kid who completes every seeded pack has 8 weeks of content, and week N+1 unlocks when
  week N is finished.
- Seeder is re-runnable with no duplicates.
- `composer test` green.

## Out of scope

- Admin UI for authoring packs (later; content ops)
- Non-multiple-choice packs — those arrive with T12/T19
