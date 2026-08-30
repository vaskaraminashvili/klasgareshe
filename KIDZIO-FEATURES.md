# Kidzio — Feature Checklist

Extracted from the `kidzio/` HTML template. Use this as the product backlog. Tick items as they are built.

Source UI: splash → walkthrough → signup/login → onboarding → home with 5 tabs (Home, Learn, Rewards, Ranking, Profile).

---

## How to use

- `[ ]` not started · `[~]` UI ported or backend only · `[x]` done
- Build in the **suggested order** below unless a later feature is needed earlier.
- Parent-gated screens (PIN, reports, screen time) should never be reachable by the kid without verification.
- **Porting a screen from `kidzio/*.html`:** follow `.cursor/rules/kidzio-screen-port.mdc` (also in `CLAUDE.md`). Copy `<main>` into `resources/views/pages/⚡{name}.blade.php`. Reference: login (`kidzio/login.html` → `pages::user-login`).

---

## Where we are (2026-08-31)

**Shipped:** login / register, 4-step onboarding (კლასი 1 / 2 / 3 → ქართული · მათემატიკა · ისტორია → daily goal → notifications), parent-verify, logout. Home / Profile / Daily mission / Edit profile / Monthly goals / Friends ranking ported and mostly live. Home greeting, streak / XP / league ribbon, week dots from `user_stats` + `user_activity_days`. **Week plans 1–2** (grades 1–3) seeded in Georgian; active week = lowest incomplete week. Daily mission = **3 today tasks** (1 pack per subject; done if that subject was played today). Completing a pack → `recordPlay` + badge eval. Ranking hub live (Global / Weekly / League / Friends). Profile hero, mastery, week activity, friends strip, monthly-goals chip live. Badges: 21-catalog + unlock celebration; Rewards tab → `/badges`.

### Still static / dummy (do not treat as done)

Inventory of template markup, dead `.html` links, or stored prefs with no runtime effect. Checklist sections below stay the source of truth for build order; this list is the quick scan.

| Area | Still static |
|---|---|
| **Tab bar** | Learn → `learn-categories.html` (no Livewire page). Rewards opens badges, not a Rewards dashboard. |
| **Home — social** | Friends-today feed (Leo / Ana rows + fake streak chips). Ranking / beat-friends CTAs still `ranking-friends.html` (not `route('ranking-friends')`). |
| **Home — games** | Word-search + counting featured tiles → `.html` shells (not built). |
| **Home — search** | Overlay + popular/recent chips; catalog + result links are dummy (`.html`). No voice search. |
| **Home — notifications** | Bell sheet list is hard-coded; unread badge fixed **“3”**. Rows link to `streak.html` / `rewards-dashboard.html` / `settings.html`. |
| **Home — misc** | Streak ribbon / card → `streak.html` (no streak page). Parent tip → `settings.html`. PWA install = `data-install` UI only. Header avatar emoji + online dot are fixed (not `users.avatar`). |
| **Daily mission** | Gift box hero, share button, locked speed-bonus / “kids playing” / bonus-mission cards, hardcoded **+120 XP** chips — markup only. |
| **Profile** | Rewards-dashboard row (`href="#"`, fake “3 new”). Parent zone (controls / weekly report / screen time) → `.html` or settings stubs; screen-time chip hardcodes **30 min**. Settings gear / menu → `settings.html`. Share profile button markup only. Streak menu → `streak.html`. Achievements timeline beyond recent badges not built. |
| **Edit profile** | Reset password / delete account rows dead (`href="#"`). Parent email read-only (no change + re-verify). Camera / change-avatar badge not built. |
| **Auth** | Phone login, social (Google / Apple / Facebook), forgot-password, Terms / Privacy hrefs (`#`). Parent-verify “change email” / “get help” chips dead. |
| **Badges / rewards** | Speed Runner + Social Star never unlock. Share badge / unlock share = toast markup. Badges “Rewards” chip → `#`. No Rewards dashboard, claim queue, daily-login calendar, or XP shop. |
| **Ranking / privacy** | `show_on_leaderboard` / hide-from-global toggles stored but **not applied** to leaderboard queries. Weekly prize claiming deferred. League stay/champion rewards not paid. Friends: no parent-approval gate, no suggested friends, no Home activity feed. |
| **XP / streaks** | No dedicated streak screen / month calendar / streak freeze. XP history activity log TODO (`xp-progress` subject/source placeholders). Combo / speed bonus / difficulty setting not scored. Mission-complete bonus XP not awarded beyond pack `recordPlay`. |
| **Learn library** | Entire Learn tab + subject library screens (math / alphabet / animals / words / …), lessons, chapters — not started. |
| **Other mini-games** | Everything except Quick Quiz (tap-correct, counting, trace, spell, word-search, …) — not started. |
| **Parent zone** | PIN gate, dashboard, screen time, bedtime, weekly/full reports, export PDF — not started (links only). Monthly goals page is live (system goals); parent custom targets later. |
| **Settings / legal / PWA** | No Settings page. No push delivery (onboarding prefs stored only). No Terms / Privacy / FAQ / contact / about screens. Splash + walkthrough not built. Accent / text-size themes not built. |
| **Content ops** | Week **3+** packs not seeded. Admin assign UI TODO. Demo `GameSeeder` / `game_question` path unused by Home. |

**Week plan + games bank:** `week_plan_items` + `week_plan_item_question` + `user_plan_progress` (weeks 1–2 seeded). Play is pack-based (`/game-multiple-choice/{item}`), not a random catalog. Shared `games` + `questions` still exist (`game_question`); demo `GameSeeder` items are not the week path. Content is `locale=ka`, grade-scoped.

---

## Suggested build order

1. ~~Auth + parent verification + kid profile~~ — auth + verify + edit-profile + live Profile stats done
2. ~~Onboarding (class, school subjects, daily goal, notifications)~~ — კლასი 1–3 + ქართული / მათემატიკა / ისტორია; class drives week packs
3. ~~Home shell (tabs, search, theme, notifications)~~ — shell ported; Learn tab still dead; Rewards → badges; Ranking wired; search/notif still dummy
4. ~~XP / levels / scoring~~ — levels + xp-progress + award-from-play done
5. Learn library + lessons + continue/lock — skipped; week plan stands in for “what next”
6. ~~Mini-games + game scoring~~ — Quick Quiz plays the week pack (`startPlanItem`); other shells later
7. ~~Daily mission + week plan~~ — 3 today tasks + catch-up live; gift box / bonus cards still dummy
8. ~~Badges + rewards + shop~~ — collection + unlock live; shop / dashboard / claim queue later
9. ~~Leaderboard + leagues + friends~~ — Global / Weekly / League / Friends ranking live; Home friends feed + prize claim later
10. Parent zone (PIN, screen time, bedtime, reports)
11. Settings, PWA, offline, legal, support
12. Week 3+ curriculum packs + remaining mini-game shells

---

## 1. App shell & first-run

- [ ] Splash screen
- [ ] PWA install (Add to Home Screen, standalone, offline cache)
- [x] Light / dark theme (system default + toggle, persist)
- [ ] Theme accent colors (violet, pink, mint, sky, sun)
- [ ] Text size (small / medium / large)
- [~] Bottom tab bar: Home · Learn · Rewards · Ranking · Profile — Home / Profile / Ranking / Rewards→badges routed; Learn still `learn-categories.html`
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
- [ ] Forgot password → send code to parent email only
- [ ] OTP verify (4-digit, paste, resend countdown) — parent-verify uses a 6-digit code, not this screen
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
- [x] Edit profile (name, nickname, age, avatar, favourite subject) — `/edit-profile`; reset/delete deferred

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
- [~] Featured games — Quick Quiz → next incomplete pack; word-search / counting tiles still `.html`
- [ ] Friends activity feed
- [ ] Recent achievements
- [ ] Parent tip card
- [ ] Install PWA prompt — `data-install` UI only
- [~] Search overlay (subjects, games, lessons) — overlay + `home.js`; catalog is dummy, results still `.html`
  - [ ] Recent searches
  - [ ] Popular chips
  - [ ] Voice search (mic)
- [~] In-app notification sheet (bell + unread badge) — sheet UI; dummy list, badge hardcoded “3”

---

## 7. Learn library

### School subjects (v1, grades 1–3)

Home week plan — not the Learn tab yet.

- [x] ქართული — letters, syllables, simple words (`locale=ka`; no Latin A–Z)
- [x] მათემატიკა — numbers, count, +1 / −1 (harder in grades 2–3)
- [x] ისტორია — საქართველო (flag, თბილისი, holidays, regions); not world history
- [ ] Learn tab library (Kidzio Math / Alphabet / Animals / Words / Knowledge / Opposites screens)

### Library UX

- [ ] Subject tiles with lesson count, % complete, difficulty, age range
- [ ] Search + filters (difficulty, age, status, tags)
- [ ] Favourite / heart a subject or lesson
- [ ] Today's spotlight on Learn tab
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
- [x] Grade-appropriate week bank — `users.grade` + `week_plan_items` weeks 1–2; quiz cannot load another class’s pack; catch-up is first incomplete weekday per subject in the active week; finishing week N unlocks week N+1 when seeded
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
- [~] Hide kid from global ranking (parent + settings toggle) — toggle stored on edit-profile; leaderboard filter later
- [~] Show on leaderboard toggle — stored; not yet applied to global queries

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
- [ ] Privacy policy (COPPA / GDPR-K mentioned in UI)

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
- [ ] Terms & Privacy
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
- [ ] Learn library search + filters
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
- [ ] Legal: Terms, Privacy
- [ ] Help FAQ + contact form/email

---

## Screen map (template files)

Use this when matching a feature to a UI screen.

| Area | Screens |
|---|---|
| First run | `index` splash, `walkthrough-1/2/3` |
| Auth | `login`, `signup`, `forgot-password`, `otp`, `parent-verify` |
| Onboarding | `onboarding-age`, `onboarding-categories`, `onboarding-goals`, `onboarding-notifications` |
| Main | `home`, `daily-mission`, `learn-categories`, `rewards-dashboard`, `leaderboard`, `profile` |
| Learn | `learn-math`, `learn-alphabet`, `learn-animals`, `learn-words`, `learn-knowledge`, `learn-opposites`, `section-list`, `lesson-details`, `lesson-continue`, `lesson-locked` |
| Games | `game-multiple-choice`, `game-tap-correct`, `game-counting`, `game-trace-letter`, `game-fill-letter`, `game-spell-word`, `game-match-word`, `game-match-animal`, `game-guess-animal`, `game-word-search`, `game-connect-pair`, `game-opposites`, `game-body-parts`, `game-where-live`, `game-knowledge` |
| Progress | `daily-mission` (ported + week checklist), `streak`, `xp-progress`, `badges` (ported + live catalog), `badge-unlock` (one celebration screen) |
| Rank | `leaderboard`, `ranking-weekly`, `ranking-friends`, `league` |
| Parent | `parent-controls`, `parent-email`, `change-pin`, `screen-time`, `bedtime-lock`, `weekly-report`, `full-report`, `monthly-goals`, `preferred-subjects`, `export-progress` |
| Settings | `settings`, `edit-profile`, `app-language`, `country`, `clear-cache`, `help-faq`, `contact-us`, `about`, `privacy-policy`, `terms-privacy` |

`badge-unlock*.html` files are the same celebration screen with different query hashes — one unlock screen is enough.
