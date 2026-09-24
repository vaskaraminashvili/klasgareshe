# Kidzio — execution roadmap

Ordered task list for getting from "core loop works" to "product ready". One task per file in
`docs/tasks/`. Work top to bottom unless something is needed earlier.

- `KIDZIO-FEATURES.md` = the full feature inventory (what exists in the template).
- This file = **the order we build it in**.
- `docs/tasks/T{nn}-*.md` = the brief for one task: scope, template screens, code touchpoints, done-when.

Each task is sized to be finishable in one sitting. If a task grows past that, split it and add a
`T{nn}b` file rather than letting it sprawl.

## Status legend

`not started` · `in progress` · `blocked` · `done`

Update the **Status** line inside the task file, and the table below, when a task changes state.

---

## P0 — ship blockers

Nothing ships while these are open. Each one is either a dead end for the kid, a broken promise to
the parent, or a legal gap.

| # | Task | Why it blocks | Status |
|---|---|---|---|
| T01 | [Content runway — weeks 3–8](tasks/T01-content-runway.md) | Kid runs out of packs in ~2 weeks (grades 2–3) | not started |
| T02 | [Dead links & fake data sweep](tasks/T02-dead-links-sweep.md) | 21 `.html` hrefs 404; hardcoded "3" unread, fake friends | **done** |
| T03 | [Password reset via parent email](tasks/T03-password-reset.md) | No account recovery at all | **done** |
| T04 | [Enforce privacy toggles](tasks/T04-privacy-toggles.md) | `show_on_leaderboard` is stored and ignored — a kid opted out is still listed | **done** |
| T05 | [Terms & Privacy screens](tasks/T05-legal-screens.md) | Signup consent checkbox links to `#`; COPPA/GDPR-K claims with no document | **done** |

## P1 — core product

What makes this a parent-trustworthy kids app rather than a quiz demo. The parent zone is a product
rule in `CLAUDE.md`, not a nice-to-have.

| # | Task | Status |
|---|---|---|
| T06 | [Parent PIN gate + parent controls](tasks/T06-parent-pin-gate.md) | **done** |
| T07 | [Screen time + bedtime lock](tasks/T07-screen-time-bedtime.md) | **done** |
| T08 | [Weekly & full reports](tasks/T08-parent-reports.md) | **done** |
| T09 | [Account & data control](tasks/T09-account-and-data.md) | **done** |
| T10 | [Settings screen](tasks/T10-settings-screen.md) | **done** |
| T11 | [Streaks & XP completeness](tasks/T11-streaks-and-xp.md) | not started |
| T12 | [Mini-games batch 1 — tap-correct + counting](tasks/T12-minigames-batch-1.md) | not started |
| T13 | [Rewards dashboard + login calendar](tasks/T13-rewards-dashboard.md) | not started |

## P2 — depth & growth

Real features, but the app is usable and sellable without them.

| # | Task | Status |
|---|---|---|
| T14 | [Learn library — live](tasks/T14-learn-library.md) | not started |
| T15 | [Splash, walkthrough, PWA](tasks/T15-splash-walkthrough-pwa.md) | not started |
| T16 | [Notifications — in-app + push](tasks/T16-notifications.md) | not started |
| T17 | [Search — live index](tasks/T17-search.md) | not started |
| T18 | [Ranking depth + league rewards](tasks/T18-ranking-depth.md) | not started |
| T19 | [Mini-games batch 2 — the rest](tasks/T19-minigames-batch-2.md) | not started |
| T20 | [Reward shop](tasks/T20-reward-shop.md) | not started |
| T21 | [Sound, voice & appearance](tasks/T21-sound-voice-appearance.md) | not started |
| T22 | [Social & phone login](tasks/T22-social-phone-login.md) | not started |

---

## Rules that apply to every task

1. **Port, never redesign.** The screen already exists in `kidzio/{screen}.html`. Copy `<main>`,
   then wire it. Playbook: `.cursor/rules/kidzio-screen-port.mdc`.
2. **Georgian first.** Copy goes in `lang/ka`; DB content is seeded `locale=ka`. No hardcoded
   English in Blade.
3. **Repository + service.** No Eloquent in a Livewire page, ever.
4. **Parent-gated stays gated.** PIN or parent verification, enforced server-side in middleware —
   not by hiding a link.
5. **No new dummy data.** If the backend isn't ready, the markup is out of scope for that task —
   don't ship a fake number to fill the slot. `tests/Feature/NoTemplateLinksTest.php` enforces the
   dead-link half of this; the fake-data half is on you.
6. **Re-porting a block T02 removed.** Several tasks below inherit a one-line Blade comment naming
   the block and this task. Copy the markup back from `kidzio/{screen}.html` — do not reinvent it —
   then wire it to the real data and delete the comment.
7. Finish with `composer test` (Pint + PHPStan level 7 + PHPUnit) green.

## Definition of "done" for a task

- Every checkbox in the task file is ticked or explicitly moved to a later task.
- The matching lines in `KIDZIO-FEATURES.md` are updated (`[ ]` → `[~]` / `[x]`).
- `CLAUDE.md` "Current status" reflects the change if the task shipped a screen or a rule.
- No new `.html` href, no new hardcoded stat.
