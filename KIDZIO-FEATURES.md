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

## Where we are (2026-08-20)

**Shipped:** login / register, 4-step onboarding, parent-verify, logout. Home + Profile screens are ported. Home greeting, streak / XP / league ribbon, and week dots read from `user_stats` + `user_activity_days`. Quick Quiz plays from DB and awards XP via `recordPlay`. Levels derived from XP; Ranking hub live (Global / Weekly / League) with Monday cohort promote/relegate.

**Still dummy on Home / Profile:** daily mission, continue lesson, today's plan, subjects, featured games (link works), friends, badges, search catalog, notification list, profile hero stats.

**Games bank:** `games` + `questions` linked by `game_question` (many-to-many). Play picks active questions attached to that game for `APP_LOCALE` (default `ka`). Payload uses `QuestionFormat` JSON. Seeded content is Georgian (`locale=ka`), adapted from `kidzio/game-*.html`. Admin assign UI still TODO; next: another game shell, or filter by age group.

---

## Suggested build order

1. ~~Auth + parent verification + kid profile~~ — auth + verify done; profile UI still dummy
2. ~~Onboarding (age, subjects, daily goal, notifications)~~ — prefs saved; age does not drive content yet
3. ~~Home shell (tabs, search, theme, notifications)~~ — shell ported; Learn / Rewards tabs still dead; Ranking wired
4. ~~XP / levels / scoring~~ — levels + xp-progress + award-from-play done
5. Learn library + lessons + continue/lock — skipped for now
6. **Mini-games + game scoring ← current** (Quick Quiz done)
7. Daily mission + streak
8. Badges + rewards + shop
9. ~~Leaderboard + leagues~~ — Global / Weekly / League live; Friends deferred
10. Parent zone (PIN, screen time, bedtime, reports)
11. Settings, PWA, offline, legal, support

---

## 1. App shell & first-run

- [ ] Splash screen
- [ ] PWA install (Add to Home Screen, standalone, offline cache)
- [x] Light / dark theme (system default + toggle, persist)
- [ ] Theme accent colors (violet, pink, mint, sky, sun)
- [ ] Text size (small / medium / large)
- [~] Bottom tab bar: Home · Learn · Rewards · Ranking · Profile — Home + Profile + Ranking routed; Learn / Rewards still `.html`
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

- [x] **Age group** — Preschool 4–5 · Kindergarten 6–7 · Elementary 8–9 · Explorer 10+
- [ ] Age drives lesson difficulty, word length, and pace
- [x] **Favourite subjects** — pick at least 3 (Alphabet, Math, Animals, Words, Knowledge, Opposites, …); skip allowed for v1
- [x] **Daily learning goal** — Casual 5 min · Regular 10 min · Serious 15 · Intense 20
- [x] **Notifications opt-in** — streak, new lessons, rewards/ranks, daily mission + reminder time (“Maybe later” allowed); prefs stored, no push yet

Reusable later from Settings.

---

## 4. Kid profile

- [~] Kid display name + nickname — stored (nickname auto from name); home greeting uses name; Profile still dummy “Luna”
- [~] Age, age group stored; country not stored; Profile still dummy
- [ ] Avatar picker (animal/emoji set)
- [ ] Camera / change-avatar badge
- [ ] Online status
- [ ] Level title (e.g. Lv 7 Explorer)
- [ ] Profile stats: XP, streak, badges, rank — Profile still hardcoded
- [ ] Subject mastery bars (% complete per subject)
- [ ] Weekly activity recap on profile
- [ ] Achievements timeline
- [ ] Share profile
- [ ] Edit profile (name, nickname, age, avatar, favourite subject)

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

Shell: `pages::home` + `profile-header` + `bottom-nav-bar`. Most blocks are still template copy.

- [x] Greeting with kid name
- [x] Quick stats: streak, XP, league
- [ ] Today's mission hero (progress, time left, XP reward)
- [ ] Continue last lesson
- [x] Weekly streak dots (Mon–Sun)
- [ ] Today's plan task list
- [ ] Explore subjects carousel
- [~] Featured games — Quick Quiz route wired; other tiles still `.html`
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

### Subjects (6)

- [ ] Math — numbers, counting, shapes, addition
- [ ] Alphabet — A–Z, phonics, letter sounds
- [ ] Animals — wildlife, habitats, sounds, facts
- [ ] Words — sight words, spelling, read-along stories
- [ ] Knowledge — world, science, space
- [ ] Opposites — pair matching (big/small, hot/cold)

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

Quick Quiz is live. Other games will reuse attached `questions` via `game_question` (`format` + `payload` / `answer` JSON).

- [x] Quick Quiz (multiple choice) — `pages::game-multiple-choice`; DB questions; 3 lives; XP on finish
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
- [~] Age-appropriate question bank per subject — `questions.age_group` exists; quiz does not filter yet

---

## 9. Daily mission

- [ ] One mission per day with countdown to reset
- [ ] 3 tasks (example: finish 1 lesson, play 1 game, get 5 answers right)
- [ ] Task progress + completed timestamps
- [ ] Locked bonus task after main tasks (speed bonus)
- [ ] Reward: XP + gift box + badge
- [ ] Share mission
- [ ] “X kids playing” social proof (optional)

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
- [ ] Badge collection (24 in the template)
  - [ ] Earned / in progress / locked
  - [ ] Rarity: Common · Rare · Epic · Legend
  - [ ] Gold / silver / bronze medal styles
  - [ ] Badge unlock celebration screen
  - [ ] Share badge
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
- [ ] Friends ranking
- [x] Podium (top 3)
- [x] “You are here” strip
- [ ] Filters: worldwide, country, on a streak, online now
- [ ] Search players
- [ ] Top countries
- [ ] Hide kid from global ranking (parent + settings toggle)
- [ ] Show on leaderboard toggle

### Leagues (weekly seasons)

Tiers: **Bronze → Silver → Gold → Emerald → Sapphire → Diamond**

- [x] Assign kid to a league group (~12 players)
- [x] Weekly XP in that group
- [x] Top 3 promote, bottom 3 relegate, rest stay (tiny groups hold all)
- [x] Season timer
- [ ] League rewards (weekly stay bonus, champion badge, avatar frame)
- [~] Season journey history — closed weeks listed on League screen

### Friends

- [ ] Friend list
- [ ] Friend requests (parent approval by default)
- [ ] Suggested friends
- [ ] Add friend
- [ ] Friends-today activity on Home
- [ ] Toggle: allow friend requests

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
- [ ] Monthly goals (parent-set targets, % complete, XP/day)
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
| Main | `home`, `learn-categories`, `rewards-dashboard`, `leaderboard`, `profile` |
| Learn | `learn-math`, `learn-alphabet`, `learn-animals`, `learn-words`, `learn-knowledge`, `learn-opposites`, `section-list`, `lesson-details`, `lesson-continue`, `lesson-locked` |
| Games | `game-multiple-choice`, `game-tap-correct`, `game-counting`, `game-trace-letter`, `game-fill-letter`, `game-spell-word`, `game-match-word`, `game-match-animal`, `game-guess-animal`, `game-word-search`, `game-connect-pair`, `game-opposites`, `game-body-parts`, `game-where-live`, `game-knowledge` |
| Progress | `daily-mission`, `streak`, `xp-progress`, `badges`, `badge-unlock*` |
| Rank | `leaderboard`, `ranking-weekly`, `ranking-friends`, `league` |
| Parent | `parent-controls`, `parent-email`, `change-pin`, `screen-time`, `bedtime-lock`, `weekly-report`, `full-report`, `monthly-goals`, `preferred-subjects`, `export-progress` |
| Settings | `settings`, `edit-profile`, `app-language`, `country`, `clear-cache`, `help-faq`, `contact-us`, `about`, `privacy-policy`, `terms-privacy` |

`badge-unlock*.html` files are the same celebration screen with different query hashes — one unlock screen is enough.
