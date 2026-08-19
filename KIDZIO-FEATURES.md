# Kidzio — Feature Checklist

Extracted from the `kidzio/` HTML template. Use this as the product backlog. Tick items as they are built.

Source UI: splash → walkthrough → signup/login → onboarding → home with 5 tabs (Home, Learn, Rewards, Ranking, Profile).

---

## How to use

- `[ ]` not started · `[x]` done
- Build in the **suggested order** below unless a later feature is needed earlier.
- Parent-gated screens (PIN, reports, screen time) should never be reachable by the kid without verification.

---

## Suggested build order

1. Auth + parent verification + kid profile
2. Onboarding (age, subjects, daily goal, notifications)
3. Home shell (tabs, search, theme, notifications)
4. XP / levels / scoring
5. Learn library + lessons + continue/lock
6. Mini-games + game scoring
7. Daily mission + streak
8. Badges + rewards + shop
9. Leaderboard + leagues + friends
10. Parent zone (PIN, screen time, bedtime, reports)
11. Settings, PWA, offline, legal, support

---

## 1. App shell & first-run

- [ ] Splash screen
- [ ] PWA install (Add to Home Screen, standalone, offline cache)
- [ ] Light / dark theme (system default + toggle, persist)
- [ ] Theme accent colors (violet, pink, mint, sky, sun)
- [ ] Text size (small / medium / large)
- [ ] Bottom tab bar: Home · Learn · Rewards · Ranking · Profile
- [ ] Walkthrough (3 slides): play, streak, rewards — with Skip

---

## 2. Auth & accounts

Parent owns the account. Kid is a profile on that account.

- [ ] Login (email or phone + password)
- [ ] Remember me
- [ ] Show / hide password
- [ ] Social login: Google, Apple, Facebook
- [ ] Sign up: kid name, age (3–14), gender, parent email, password
- [ ] Parent/guardian consent checkbox (Terms + Privacy)
- [ ] Forgot password → send code to parent email only
- [ ] OTP verify (4-digit, paste, resend countdown)
- [ ] Log out
- [ ] Delete account (parent-gated, data removed)

### Parent verification (COPPA-style)

- [ ] After signup: verify parent via email magic link **or** 6-digit code
- [ ] Parent email stored as verified
- [ ] Change / update parent email (re-verify)

---

## 3. Onboarding (4 steps)

- [ ] **Age group** — Preschool 4–5 · Kindergarten 6–7 · Elementary 8–9 · Explorer 10+
- [ ] Age drives lesson difficulty, word length, and pace
- [ ] **Favourite subjects** — pick at least 3 (Alphabet, Math, Animals, Words, Knowledge, Opposites, …)
- [ ] **Daily learning goal** — Casual 5 min · Regular 10 min · (and any extra paces in the UI)
- [ ] **Notifications opt-in** — streak, new lessons, rewards/ranks, daily mission + reminder time

Reusable later from Settings.

---

## 4. Kid profile

- [ ] Kid display name + nickname
- [ ] Age, age group, country
- [ ] Avatar picker (animal/emoji set)
- [ ] Camera / change-avatar badge
- [ ] Online status
- [ ] Level title (e.g. Lv 7 Explorer)
- [ ] Profile stats: XP, streak, badges, rank
- [ ] Subject mastery bars (% complete per subject)
- [ ] Weekly activity recap on profile
- [ ] Achievements timeline
- [ ] Share profile
- [ ] Edit profile (name, nickname, age, avatar, favourite subject)

---

## 5. Scoring, XP & levels

Core loop: play → earn XP → level up → climb ranks.

- [ ] Award XP for lessons, games, missions, streaks, login calendar
- [ ] Show XP on home, profile, rewards, leaderboard
- [ ] Daily / weekly XP totals
- [ ] Level system (e.g. Lv 7 Explorer → Lv 8 Master) with XP-to-next
- [ ] XP history (last 7 days chart + activity log)
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

- [ ] Greeting with kid name
- [ ] Quick stats: streak, XP, league
- [ ] Today's mission hero (progress, time left, XP reward)
- [ ] Continue last lesson
- [ ] Weekly streak dots (Mon–Sun)
- [ ] Today's plan task list
- [ ] Explore subjects carousel
- [ ] Featured games
- [ ] Friends activity feed
- [ ] Recent achievements
- [ ] Parent tip card
- [ ] Install PWA prompt
- [ ] Search overlay (subjects, games, lessons)
  - [ ] Recent searches
  - [ ] Popular chips
  - [ ] Voice search (mic)
- [ ] In-app notification sheet (bell + unread badge)

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

- [ ] Quick Quiz (multiple choice)
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

- [ ] Correct / incorrect feedback + sounds
- [ ] Lives or retry (if you want it; template is mostly check-and-continue)
- [ ] Voice reader for questions
- [ ] Age-appropriate question bank per subject

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

- [ ] Daily streak counter (keep flame by finishing a daily check-in)
- [ ] Week view (days hit / missed)
- [ ] Month calendar (streak map)
- [ ] Best streak
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

- [ ] Global all-time leaderboard
- [ ] Weekly ranking + week prizes
- [ ] Friends ranking
- [ ] Podium (top 3)
- [ ] “You are here” strip
- [ ] Filters: worldwide, country, on a streak, online now
- [ ] Search players
- [ ] Top countries
- [ ] Hide kid from global ranking (parent + settings toggle)
- [ ] Show on leaderboard toggle

### Leagues (weekly seasons)

Tiers: **Bronze → Silver → Gold → Emerald → Sapphire → Diamond**

- [ ] Assign kid to a league group (~12 players)
- [ ] Weekly XP in that group
- [ ] Top 3 promote, bottom 3 relegate, rest stay
- [ ] Season timer
- [ ] League rewards (weekly stay bonus, champion badge, avatar frame)
- [ ] Season journey history

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

- [ ] App language (UI + questions + audio)
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
