# Kidzio — Feature Checklist

Extracted from the `kidzio/` HTML template. Use this as the product backlog. Tick items as they are built.

> **Build order lives in `docs/roadmap.md`.** This file is the inventory of *what* exists in the
> template; the roadmap is *when* we build it, with one brief per task in `docs/tasks/`. When you
> finish a task, tick the matching lines here.

Source UI: splash → walkthrough → signup/login → onboarding → home with 5 tabs (Home, Learn, Rewards, Ranking, Profile).

---

## How to use

- `[ ]` not started · `[~]` UI ported or backend only · `[x]` done
- Build in the **suggested order** below unless a later feature is needed earlier.
- Parent-gated screens (PIN, reports, screen time) should never be reachable by the kid without verification.
- **Porting a screen from `kidzio/*.html`:** follow `.cursor/rules/kidzio-screen-port.mdc` (also in `CLAUDE.md`). Copy `<main>` into `resources/views/pages/⚡{name}.blade.php`. Reference: login (`kidzio/login.html` → `pages::user-login`).

---

## Where we are (2026-08-31)

**Shipped:** login / register, 4-step onboarding (კლასი 1 / 2 / 3 → ქართული · მათემატიკა · ისტორია → daily goal → notifications), parent-verify, logout. Home / Profile / Daily mission / Edit profile / Monthly goals / Friends ranking ported and mostly live. Home greeting, streak / XP / league ribbon, week dots from `user_stats` + `user_activity_days`. **Week plans 1–2** (grades 1–3) plus **week 3 for class 1** seeded in Georgian; active week = lowest incomplete week. Daily mission = **3 today tasks** (1 pack per subject; done if that subject was played today). Completing a pack → `recordPlay` + badge eval. Ranking hub live (Global / Weekly / League / Friends). Profile hero, mastery, week activity, friends strip, monthly-goals chip live. Badges: 21-catalog + unlock celebration; Rewards tab → `/badges`. Learn tab (`/learn-categories`) is a dummy library shell.

### Still static / dummy (do not treat as done)

Inventory of template markup or stored prefs with no runtime effect. Checklist sections below stay the source of truth for build order; this list is the quick scan.

**Dead `.html` links are gone** (T02, `docs/tasks/T02-dead-links-sweep.md`). Blocks with no backend were deleted, each leaving a Blade comment naming the task that re-ports them from `kidzio/`; `tests/Feature/NoTemplateLinksTest.php` keeps them from coming back. So "still static" below now means *not on the screen yet*, not *links to a 404*.

| Area | Still static |
|---|---|
| **Tab bar** | Learn → `/learn-categories` (library shell; dummy catalog). Rewards opens badges, not a Rewards dashboard. |
| **Home — social** | Friends-today feed removed (was Leo / Ana rows + fake streak chips) — no real activity feed yet. |
| **Home — games** | Word-search + counting featured tiles removed; only Quick Quiz is playable. |
| **Home — search** | Overlay + results are **live** over 12 real destinations (`SearchService`). Recent / popular chips and voice search removed — no query history, no Georgian speech model. |
| **Home — notifications** | Bell, unread badge and the whole sheet removed — no notification backend. |
| **Home — misc** | Streak ribbon / card are inert (live numbers, no streak page). Parent tip and PWA install row removed. Header avatar is **live** (`users.avatar`); online dot removed. |
| **Daily mission** | Gift box hero, share button, locked speed-bonus / “kids playing” / bonus-mission cards, hardcoded **+120 XP** chips — markup only. |
| **Profile** | Rewards-dashboard row, parent zone (controls / weekly report / screen time), settings gear + row, share button, streak menu row and the “online” chip all removed pending their tasks. Achievements timeline beyond recent badges not built. |
| **Edit profile** | Delete account row still dead. Parent email read-only (no change + re-verify). Camera / change-avatar badge not built. Password reset is live. |
| **Auth** | Phone login, social (Google / Apple / Facebook). Parent-verify “change email” / “get help” chips dead. Password reset is live. Terms / Privacy screens are live. |
| **Badges / rewards** | Speed Runner + Social Star never unlock. Share badge / unlock share = toast markup. Badges “Rewards” chip → `#`. No Rewards dashboard, claim queue, daily-login calendar, or XP shop. |
| **Ranking / privacy** | Global leaderboard honors `show_on_leaderboard`. Weekly prize claiming deferred. League stay/champion rewards not paid. Friends: no parent-approval gate, no suggested friends, no Home activity feed. `/ranking-friends` filter tabs (all / online / streak / near) are inert, and presence (“N online”) was removed as fake. |
| **XP / streaks** | No dedicated streak screen / month calendar / streak freeze. XP history activity log TODO (`xp-progress` subject/source placeholders). Combo / speed bonus / difficulty setting not scored. Mission-complete bonus XP not awarded beyond pack `recordPlay`. |
| **Learn library** | Tab shell ported (`pages::learn-categories`); subject screens (math / alphabet / animals / words / …), lessons, chapters — not started. Spotlight / stats / tiles still dummy. |
| **Other mini-games** | Everything except Quick Quiz (tap-correct, counting, trace, spell, word-search, …) — not started. |
| **Parent zone** | PIN gate, dashboard, screen time, bedtime, weekly/full reports, export PDF — not started (links only). Monthly goals page is live (system goals); parent custom targets later. |
| **Settings / legal / PWA** | No Settings page. No push delivery (onboarding prefs stored only). FAQ / contact / about still later. Splash + walkthrough not built. Accent / text-size themes not built. Terms + Privacy are live. |
| **Content ops** | Week **3** is class 1 only; week 3 for grades 2–3 and week **4+** not seeded. Admin assign UI TODO. Demo `GameSeeder` / `game_question` path unused by Home. |

**Week plan + games bank:** `week_plan_items` + `week_plan_item_question` + `user_plan_progress` (weeks 1–2 for grades 1–3; week 3 for class 1). Play is pack-based (`/game-multiple-choice/{item}`), not a random catalog. Shared `games` + `questions` still exist (`game_question`); demo `GameSeeder` items are not the week path. Content is `locale=ka`, grade-scoped.

---

## Suggested build order

1. ~~Auth + parent verification + kid profile~~ — auth + verify + edit-profile + live Profile stats done
2. ~~Onboarding (class, school subjects, daily goal, notifications)~~ — კლასი 1–3 + ქართული / მათემატიკა / ისტორია; class drives week packs
3. ~~Home shell (tabs, search, theme, notifications)~~ — shell ported; Learn tab is a dummy library; Rewards → badges; Ranking wired; Home search/notif still dummy
4. ~~XP / levels / scoring~~ — levels + xp-progress + award-from-play done
5. Learn library + lessons + continue/lock — skipped; week plan stands in for “what next”
6. ~~Mini-games + game scoring~~ — Quick Quiz plays the week pack (`startPlanItem`); other shells later
7. ~~Daily mission + week plan~~ — 3 today tasks + catch-up live; gift box / bonus cards still dummy
8. ~~Badges + rewards + shop~~ — collection + unlock live; shop / dashboard / claim queue later
9. ~~Leaderboard + leagues + friends~~ — Global / Weekly / League / Friends ranking live; Home friends feed + prize claim later
10. Parent zone (PIN, screen time, bedtime, reports)
11. Settings, PWA, offline, legal, support
12. Week 3 for grades 2–3 + week 4+ curriculum packs + remaining mini-game shells

---

## 1. App shell & first-run

- [ ] Splash screen
- [ ] PWA install (Add to Home Screen, standalone, offline cache)
- [x] Light / dark theme (system default + toggle, persist)
- [ ] Theme accent colors (violet, pink, mint, sky, sun)
- [ ] Text size (small / medium / large)
- [~] Bottom tab bar: Home · Learn · Rewards · Ranking · Profile — all five routed; Rewards → badges; Learn is a dummy library shell
- [ ] Walkthrough (3 slides): play, streak, rewards — with Skip

---

## 2. Auth & accounts

Parent owns the account. Kid is a profile on that account.

- [x] Login (email + password UI; phone not wired) — `pages::user-login`
- [x] Remember me
- [x] Show / hide password
- [ ] Social login: Google, Apple, Facebook
- [x] Sign up: kid name, age (3–14), gender, parent email, password — `pages::user-register`
- [x] Parent/guardian consent checkbox (Terms + Privacy)
- [x] Forgot password → send 6-digit code to parent email only — `pages::forgot-password`
- [x] OTP verify (6-digit to match parent-verify, paste, resend) — `pages::otp` then `pages::reset-password`
- [x] Log out — Profile
- [ ] Delete account (parent-gated, data removed)

### Parent verification (COPPA-style)

- [x] After signup: verify parent via email magic link **or** 6-digit code — `pages::parent-verify`
- [x] Parent email stored as verified
- [ ] Change / update parent email (re-verify)

---

## 3. Onboarding (4 steps)

- [x] **Class (კლასი 1 / 2 / 3)** — `pages::onboarding-age`; signup `age` still stored; `age_group` set in the background
- [x] Class drives week-plan packs (grade 1–3 question banks; no fallback across grades)
- [ ] Age group (preschool / kindergarten / …) no longer shown; leftover `users.age_group` unused for content
- [x] **School subjects** — ქართული, მათემატიკა, ისტორია only (`pages::onboarding-categories`); extras (animals, A–Z, opposites) not offered
- [x] **Daily learning goal** — Casual 5 min · Regular 10 min · Serious 15 · Intense 20
- [x] **Notifications opt-in** — streak, new lessons, rewards/ranks, daily mission + reminder time (“Maybe later” allowed); prefs stored, no push yet

Reusable later from Settings.

---

## 4. Kid profile

- [~] Kid display name + nickname — stored (nickname auto from name); home greeting + Profile hero use name
- [~] Age, class (`users.grade` 1–3), age group stored; country not stored; Profile shows age · class
- [x] Avatar picker (animal/emoji set) — edit-profile emoji sheet; stored on `users.avatar`
- [ ] Camera / change-avatar badge
- [ ] Online status
- [x] Level title (e.g. Lv 7 Explorer) — Profile chip + XP bar from `LevelCalculator`
- [x] Profile stats: XP, streak, badges, rank — hero metrics + shortcuts live
- [x] Subject mastery bars (% complete per subject) — active curriculum week packs for ქართული / მათემატიკა / ისტორია
- [x] Weekly activity recap on profile — XP / active days / packs + week dots
- [~] Achievements timeline — recent badges; streak/mission milestone rows still later
- [ ] Share profile
- [x] Edit profile (name, nickname, age, avatar, favourite subject) — `/edit-profile`; password reset live; delete deferred

---

## 5. Scoring, XP & levels

Core loop: play → earn XP → level up → climb ranks.

- [~] Award XP for lessons, games, missions, streaks, login calendar — Quick Quiz calls `recordPlay`; other actions do not yet
- [~] Show XP on home, profile, rewards, leaderboard — home + leaderboard + xp-progress live; profile/rewards still partly dummy
- [x] Daily / weekly XP totals — stored and shown on xp-progress / weekly ranking
- [x] Level system (e.g. Lv 7 Explorer → Lv 8 Master) with XP-to-next
- [x] League stored on `user_stats`; weekly seasons with promote/relegate
- [~] XP history (last 7 days chart live; activity log still TODO)
- [ ] Difficulty setting: Easy / Medium / Hard (affects questions and XP)
- [ ] Kid ratings / score per quiz (correct answers, beat yesterday)
- [ ] Combo / speed bonus (e.g. 5-in-a-row extra XP)

Suggested XP examples from the template (tune later):

| Action | XP |
|---|---|
| Finish a lesson | +40–50 |
| Play a mini-game | +40 |
| Quick quiz (up to) | +80 |
| Daily mission complete | +120 |
| Speed bonus | +20 |
| Daily login day 1–7 | +10 → +100 |
| Streak milestone 3 / 7 / 14 days | +20 / +50 / +100 |

---

## 6. Home

Shell: `pages::home` + `profile-header` + `bottom-nav-bar`. Week-plan blocks are live; social/rewards still template copy.

- [x] Greeting with kid name
- [x] Quick stats: streak, XP, league
- [x] Today's mission hero — real `0/3` (packs finished today), hours left until Sunday, CTA → `daily-mission` / next pack
- [x] Continue — first incomplete week-plan pack (not `lesson-continue.html`)
- [x] Weekly streak dots (Mon–Sun)
- [x] Today's plan — next incomplete pack per subject; Play → `/game-multiple-choice/{item}`; “ყველას ნახვა” → `daily-mission`
- [x] Explore subjects — three tiles only: ქართული, მათემატიკა, ისტორია → that subject’s next pack
- [~] Featured games — Quick Quiz → next incomplete pack; word-search / counting tiles removed until those games exist
- [ ] Friends activity feed — template rows removed (invented kids); needs a real feed
- [x] Recent achievements — live badge rail
- [ ] Parent tip card — removed; copy named a hardcoded kid and invented a study habit
- [ ] Install PWA prompt — removed; no manifest or service worker, so it never fired
- [~] Search overlay (subjects, games, lessons) — overlay + `home.js` live over `SearchService::homeCatalog()`: 12 Georgian destinations, real routes, subjects open their next pack. No lesson-level index yet (T17)
  - [ ] Recent searches — chips removed until query history exists
  - [ ] Popular chips — removed until real query counts exist
  - [ ] Voice search (mic) — removed, was `en-US` only
- [ ] In-app notification sheet (bell + unread badge) — removed in T02; needs a real backend (T16)

---

## 7. Learn library

### School subjects (v1, grades 1–3)

Home week plan — not the Learn tab yet.

- [x] ქართული — letters, syllables, simple words (`locale=ka`; no Latin A–Z)
- [x] მათემატიკა — numbers, count, +1 / −1 (harder in grades 2–3)
- [x] ისტორია — საქართველო (flag, თბილისი, holidays, regions); not world history
- [~] Learn tab library — `pages::learn-categories` shell ported; Kidzio Math / Alphabet / Animals / Words / Knowledge / Opposites screens not built

### Library UX

- [~] Subject tiles with lesson count, % complete, difficulty, age range — markup + dummy numbers
- [~] Search + filters (difficulty, age, status, tags) — overlay JS on dummy catalog
- [ ] Favourite / heart a subject or lesson
- [~] Today's spotlight on Learn tab — markup; links to daily-mission
- [ ] Per-subject: continue, lessons list, mini-games, subject badges
- [ ] Word / letter / animal / pair of the day
- [ ] Letter sounds & animal sounds (audio)
- [ ] Read-along stories (Words)

### Lessons & chapters

- [ ] Chapter list (e.g. Numbers & counting)
- [ ] Lesson list with locked / in progress / complete
- [ ] Lesson details: duration, XP, activities, age, difficulty, kid rating
- [ ] Lesson progress (e.g. 2 of 5 activities)
- [ ] Continue lesson (resume where they left off)
- [ ] Locked lesson: requirements (finish previous + XP threshold)
- [ ] Chapter rewards when a chapter is finished

---

## 8. Mini-games

Each game: progress bar, hear-aloud, check answer, XP on finish.

Quick Quiz is live as the **week-plan player**. Other games will reuse attached `questions` via `game_question` (`format` + `payload` / `answer` JSON).

- [x] Quick Quiz (multiple choice) — `pages::game-multiple-choice`; `/game-multiple-choice/{item}` plays that pack (5 questions, fixed order); bare URL redirects to the next incomplete item; 3 lives; XP + pack complete on finish
- [ ] Tap the correct answer
- [ ] Counting (count objects)
- [ ] Trace letter (follow dots / handwriting)
- [ ] Fill missing letter
- [ ] Spell the word (letter tiles)
- [ ] Match word to picture
- [ ] Match animal
- [ ] Guess the animal (who am I)
- [ ] Word search
- [ ] Connect the pair
- [ ] Opposites
- [ ] Body parts (tap the named part)
- [ ] Where do I live (habitats)
- [ ] Knowledge quiz

Shared game rules:

- [~] Correct / incorrect feedback + sounds — visual correct/wrong on quiz; no sounds yet
- [x] Lives or retry (if you want it; template is mostly check-and-continue) — 3 lives on Quick Quiz
- [ ] Voice reader for questions
- [x] Grade-appropriate week bank — `users.grade` + `week_plan_items` weeks 1–2 (all classes) and week 3 (class 1); quiz cannot load another class’s pack; catch-up is first incomplete weekday per subject in the active week; finishing week N unlocks week N+1 when seeded
- [x] Curriculum week advancement — `WeekPlanService::activeWeekNumber()` picks lowest incomplete week (stays on last when all done)

---

## 9. Daily mission

- [x] Daily checklist — `pages::daily-mission`; **3 today tasks** (1 per subject), done if that subject was played today
- [x] 3 Home tasks = same daily set; mission `n/3` = subjects finished today
- [x] Hours left until end of day on Daily mission; Home week hero still uses hours until Sunday
- [x] Catch-up: missed weekday packs stay until finished (progress not wiped Monday); next pack opens after today’s subject slot is done
- [~] Locked bonus / gift box / share / “kids playing” — markup only, no backend

---

## 10. Streaks

- [~] Daily streak counter (keep flame by finishing a daily check-in) — `current_streak` stored + shown on Home; `recordPlay` bumps it; no dedicated streak screen
- [~] Week view (days hit / missed) — Home week dots live
- [ ] Month calendar (streak map)
- [~] Best streak — `longest_streak` stored, not shown
- [ ] Milestones: 3, 7, 14, 30, 100 days (XP + badges)
- [ ] Streak freeze / streak shield (save flame 1×)
- [ ] Streak reminder notification (default ~6 PM, configurable)

---

## 11. Rewards, badges & shop

- [ ] Rewards dashboard: XP wallet, to-claim count, badges, league
- [ ] Claim queue: daily box, new badges, avatar items, streak freeze
- [ ] 7-day daily login calendar (increasing XP, bigger prize on day 7)
- [x] Badge collection (21 from the template grid; hero dummy said 24)
  - [x] Earned / in progress / locked
  - [x] Rarity: Common · Rare · Epic · Legend
  - [x] Gold / silver / bronze medal styles
  - [x] Badge unlock celebration screen
  - [~] Share badge — markup + toast only; no backend
- [ ] Reward shop (spend XP)
  - [ ] Avatars
  - [ ] Hats
  - [ ] Backgrounds / themes
  - [ ] Boosts (e.g. 2× XP for 1 day)
  - [ ] Sound packs
  - [ ] Streak shield
  - [ ] Sales / HOT / NEW tags

---

## 12. Leaderboard, leagues & friends

### Rankings

- [x] Global all-time leaderboard
- [x] Weekly ranking + week prizes — ranking live; prize claiming deferred
- [x] Friends ranking
- [x] Podium (top 3)
- [x] “You are here” strip
- [ ] Filters: worldwide, country, on a streak, online now
- [ ] Search players
- [ ] Top countries
- [x] Hide kid from global ranking (parent + settings toggle) — toggle on edit-profile; public ranking queries filter it (T04). League / friends stay listed.
- [x] Show on leaderboard toggle — stored and applied to global all-time + weekly XP ranking queries

### Leagues (weekly seasons)

Tiers: **Bronze → Silver → Gold → Emerald → Sapphire → Diamond**

- [x] Assign kid to a league group (~12 players)
- [x] Weekly XP in that group
- [x] Top 3 promote, bottom 3 relegate, rest stay (tiny groups hold all)
- [x] Season timer
- [ ] League rewards (weekly stay bonus, champion badge, avatar frame)
- [~] Season journey history — closed weeks listed on League screen

### Friends

- [x] Friend list — friends ranking page
- [~] Friend requests (parent approval by default) — nickname add auto-accepts in v1; parent PIN later
- [ ] Suggested friends
- [x] Add friend — by nickname on `/ranking-friends`
- [~] Friends-today activity on Home — profile strip live; Home feed later
- [x] Toggle: allow friend requests — edit-profile

---

## 13. Parent zone

All of this is behind a **4-digit parent PIN**. Forgot PIN → parent verify.

- [ ] PIN gate overlay + numeric pad
- [ ] Change PIN
- [ ] Lock parent zone after viewing
- [ ] Parent dashboard: XP, minutes, lessons this week
- [ ] Daily minutes chart vs daily goal
- [ ] Break reminders (every 15 min)
- [ ] Age-appropriate content filter
- [ ] Preferred subjects (parent override)
- [ ] Kid profile shortcut
- [ ] Parent email (verified)
- [ ] Delete account

### Screen time

- [ ] Daily limit presets (15 / 30 / 45 / 60 min)
- [ ] Used vs remaining today
- [ ] Gentle pause when limit is hit
- [ ] How-to-pause options (from the screen-time UI)

### Bedtime lock

- [ ] Enable / disable
- [ ] Bedtime + wake time
- [ ] Hide / pause app during sleep hours

### Reports & goals

- [ ] Weekly report (XP, lessons, active days, charts)
- [ ] Email weekly report to parent (e.g. Monday / Sunday)
- [ ] Full report (longer charts)
- [x] Monthly goals (parent-set targets, % complete, XP/day) — system month goals live on `/monthly-goals`; parent custom targets later
- [ ] Export progress / all data as PDF
- [ ] Parent tips

---

## 14. Settings

### Appearance

- [ ] Dark mode
- [ ] Accent color
- [ ] Text size

### Sound

- [ ] Sound effects
- [ ] Background music
- [ ] Voice reader

### Notifications

- [ ] Streak reminders
- [ ] New lesson alerts
- [ ] Rewards & rankings
- [ ] Reminder time

### Learning

- [ ] Daily goal
- [ ] Favourite subjects
- [ ] Age group
- [ ] Difficulty

### Privacy & safety

- [ ] Show on leaderboard
- [ ] Friend requests
- [x] Privacy policy (COPPA / GDPR-K mentioned in UI) — `/privacy` + `/terms`; technical Georgian copy, not a certification

### Storage

- [ ] Offline lessons cache
- [ ] Clear cache
- [ ] Export progress PDF

### Language & region

- [~] App language (UI + questions + audio) — product default Georgian (`APP_LOCALE=ka`, `lang/ka`); questions seeded `locale=ka`; `lang/en` kept for future; no language picker yet
- [ ] Country (ranking region)

### Support & about

- [ ] Help & FAQ
- [ ] Contact us
- [ ] Rate the app
- [ ] About (version)
- [x] Terms & Privacy
- [ ] Install app (PWA)

Settings search.

---

## 15. Notifications (in-app + push)

- [ ] In-app notification list (bell)
- [ ] Unread badge
- [ ] Push: streak about to expire
- [ ] Push: daily mission ready
- [ ] Push: new lessons
- [ ] Push: rewards / league moves
- [ ] Configurable reminder clock time

---

## 16. Search

- [ ] Home search: subjects, games, lessons
- [~] Learn library search + filters — overlay JS on dummy catalog; no live index
- [ ] Leaderboard player search
- [ ] Settings search
- [ ] Language list search
- [ ] Recent + popular queries

---

## 17. Platform extras

- [ ] Offline / airplane-mode lessons
- [ ] Clear cache
- [ ] Share: profile, badge, mission, weekly report
- [ ] Voice search
- [x] Legal: Terms, Privacy
- [ ] Help FAQ + contact form/email

---

## Screen map (template files)

Use this when matching a feature to a UI screen.

| Area | Screens |
|---|---|
| First run | `index` splash, `walkthrough-1/2/3` |
| Auth | `login`, `signup`, `forgot-password`, `otp`, `parent-verify` (`reset-password` is the missing third step — no template file) |
| Onboarding | `onboarding-age`, `onboarding-categories`, `onboarding-goals`, `onboarding-notifications` |
| Main | `home`, `daily-mission`, `learn-categories`, `rewards-dashboard`, `leaderboard`, `profile` |
| Learn | `learn-math`, `learn-alphabet`, `learn-animals`, `learn-words`, `learn-knowledge`, `learn-opposites`, `section-list`, `lesson-details`, `lesson-continue`, `lesson-locked` |
| Games | `game-multiple-choice`, `game-tap-correct`, `game-counting`, `game-trace-letter`, `game-fill-letter`, `game-spell-word`, `game-match-word`, `game-match-animal`, `game-guess-animal`, `game-word-search`, `game-connect-pair`, `game-opposites`, `game-body-parts`, `game-where-live`, `game-knowledge` |
| Progress | `daily-mission` (ported + week checklist), `streak`, `xp-progress`, `badges` (ported + live catalog), `badge-unlock` (one celebration screen) |
| Rank | `leaderboard`, `ranking-weekly`, `ranking-friends`, `league` |
| Parent | `parent-controls`, `parent-email`, `change-pin`, `screen-time`, `bedtime-lock`, `weekly-report`, `full-report`, `monthly-goals`, `preferred-subjects`, `export-progress` |
| Settings | `settings`, `edit-profile`, `app-language`, `country`, `clear-cache`, `help-faq`, `contact-us`, `about`, `privacy-policy`, `terms-privacy` |

`badge-unlock*.html` files are the same celebration screen with different query hashes — one unlock screen is enough.
