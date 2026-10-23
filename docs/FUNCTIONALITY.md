# Kidzio — functionality inventory

Snapshot of what the Laravel app does today (2026-10-22). Use it as the spec if you rebuild with a
new design, or as the baseline if you keep going. Source of truth for status: `KIDZIO-FEATURES.md`
(195 done, 19 open) and `docs/roadmap.md` (T02–T14, T16–T22 done; T01, T15 open).

**Size today:** ~61 Livewire pages · 34 services · 18 repositories · 20 models · 27 enums ·
29 migrations · 4 scheduled commands · Filament staff panel · 36 feature test files ·
~21k lines PHP in `app/`, ~17k lines Blade.

**Stack:** PHP 8.3, Laravel 13, Livewire 4 single-file pages, Tailwind 4 + Vite, MySQL,
Filament (staff), Socialite, `laravel-notification-channels/webpush`, PDF export.
Pint + Larastan level 7 + PHPUnit.

---

## 1. Product rules (keep these in any rebuild)

- Georgian (`ka`) only UI and content; English is fallback for future localization.
- Parent owns the account; the kid is fields on the same `User` (one kid per account in v1).
- Parent-gated screens (PIN, reports, screen time, bedtime, parent email, delete account) are
  enforced server-side, never just hidden.
- Password reset and parent verification go to the parent email only.
- `show_on_leaderboard` hides the kid from global/weekly XP rankings; friends and league cohorts
  still include them. New accounts default to visible.
- `users.grade` (class 1–3) drives content; `age` is stored, `age_group` is not a content filter.
- Core loop: play week pack → XP → level → ranks / leagues / badges.
- Tabs: Home · Learn · Rewards · Ranking · Profile.
- School subjects: ქართული, მათემატიკა, ისტორია.
- Leagues: Bronze → Silver → Gold → Emerald → Sapphire → Diamond.
- Share never exposes a public child profile — only a generic invite to `/login`.

---

## 2. Auth & account

| Feature | Detail |
|---|---|
| Email/password login | `/login`, remember me, resumes unfinished onboarding step |
| Register | `/register`, parent email + kid fields, consent checkbox linking `/terms`, `/privacy` |
| Google / Apple sign-in | Socialite; matching parent email links to existing account; verified provider email = parent verification; provider name not copied to kid |
| Facebook | Button present, says it is not enabled (deliberately not built) |
| Phone login | 6-digit code; uses mail channel when `SMS_DRIVER` empty/`log`; other drivers fail loudly |
| Password reset | `/forgot-password` → 6-digit code to parent email → `/reset-password`; works for password-less (social) accounts |
| Parent verification | Magic link + 6-digit code; Home blocked until done |
| Legal pages | `/terms`, `/privacy` guest-readable Georgian (flagged for legal review) |
| Linked identities | `social_identities` table, removed with account |

## 3. Onboarding (blocks Home until done)

1. Class 1 / 2 / 3
2. Favourite subjects (ქართული / მათემატიკა / ისტორია)
3. Daily goal
4. Notifications + reminder time
5. Parent verification

## 4. Learning content

- **Week plan:** `week_plan_items` (week × weekday × subject × grade → a game pack) +
  `user_plan_progress`. Weeks 1–2 seeded for grades 1–3, week 3 for class 1.
- **Active week** = lowest week with incomplete packs; advances when done; stays on last seeded week.
- **Catch-up:** first incomplete weekday per subject inside the active week; Monday does not wipe progress.
- **Questions:** 15 per pack (5 easy / 5 medium / 5 hard), grade-scoped, `locale=ka`. Round = the
  5 matching `users.play_difficulty` (fallback medium, then any).
- **Difficulty XP scaling:** easy ×3/4, hard ×3/2 on base pack XP only.
- **Learn tab** `/learn-categories`: subjects with pack count / % / grade, spotlight = next pack,
  mini-games rail, filters by status/subject/week, heart toggles favourite subjects.
- `/section-list/{subject}` (weeks as chapters) → `/lesson-details/{item}` or `/lesson-locked/{item}` → player.

## 5. Games (11 players)

All: 5 Georgian questions, XP via `GamePlayService` + `PlaysWeekPlanPack`, result sheet (correct
count, XP, beat-yesterday from `pack_plays`), then home or badge unlock. One-question players have
3 lives. Bare URL redirects to next incomplete pack of that format. Hear-aloud (`speechSynthesis`
`ka-GE`) on every game.

| Game | Route |
|---|---|
| Quick Quiz (multiple choice) | `/game-multiple-choice/{item?}` |
| Tap correct | `/game-tap-correct/{item?}` |
| Counting | `/game-counting/{item?}` |
| Word search | `/game-word-search/{item?}` (`WordSearchBoard`) |
| Fill letter | `/game-fill-letter/{item?}` |
| Spell word | `/game-spell-word/{item?}` |
| Trace letter | `/game-trace-letter/{item?}` (`TraceStrokeService`, pass at 60%) |
| Match word | `/game-match-word/{item?}` |
| Connect pair | `/game-connect-pair/{item?}` |
| Opposites | `/game-opposites/{item?}` |
| Knowledge | `/game-knowledge/{item?}` |

Not in v1: animal match, guess-animal, body parts, habitats, read-along stories, word/letter of the day.

## 6. XP, levels, streaks

- `UserStatService::awardXp()` writes `xp_events`, adds spendable `coins` 1:1; ranking XP never decreases.
- Level bar via `LevelCalculator`.
- Bonuses: combo +20, speed +20 (5-question pack), daily mission +120 once/day, daily box +40,
  login calendar +10 → +100 (`awardDailyLogin`).
- Active 2× boost (shop) doubles grants for 24 h.
- `/xp-progress`: XP by source and subject.
- `/streak`: current/best, week dots, month map (`user_activity_days`), milestones 3/7/14/30/100,
  streak freeze (claimed at 7-day milestone or bought) auto-consumed on a 1-day gap.

## 7. Home `/`

Greeting, live streak / XP / league, week dots, live week plan, mission hero, continue, today's plan,
3 subject tiles, featured Quick Quiz / counting / word search → next pack. Live search, notification
bell + sheet, friends-today feed (accepted friends' packs and badges), recent badges, logout.

## 8. Daily mission `/daily-mission`

3 tasks (next pack per subject, or done if played today); all 3 → +120 XP once/day; generic share.
Gift box / bonus cards still static markup.

## 9. Rewards, badges, shop

- **Badges** `/badges`: 21 badges, Georgian names, rarity/category, secret badges, immediate unlock,
  one-time celebration `/badge-unlock/{slug}`. Speed Runner (< 2 min pack), Social Star (5 friends), etc.
- **Rewards dashboard** `/rewards-dashboard`: wallet, claim queue (`reward_claims`), 7-day login
  calendar, weekly place prizes, daily box.
- **Shop** (spends `coins`, never ranking XP): avatars, hats, backgrounds, 2× XP day boost, sound
  packs (`default`, `fun`), streak shield, limited bundle. Equipped items show on Profile / Edit profile.
- **Monthly goals** `/monthly-goals`: system goals (packs / XP / streak / badges) for the calendar
  month. Custom goals deferred.

## 10. Ranking, leagues, friends

- `/leaderboard`: global all-time; filters worldwide / my country / on a streak / online now
  (heartbeat < 2 min); top countries by real learner count; nickname search; respects `show_on_leaderboard`.
- `/ranking-weekly`: league cohort; weekly place prizes claimed here (1st +500 / 2nd +300 /
  3rd +150, top 7 token).
- `/league`: tier view. Monday close job (`CloseLeagueWeekCommand`) writes `league_season_payouts`
  before promote/relegate: stay bonus +200 XP, `avatar_frame` = tier on hold, champion progress
  after 4 holds (`user_stats.champion_tiers`).
- `/ranking-friends`: add by nickname → pending until the other parent approves in parent controls;
  suggestions = same grade + league, skip opted-out kids; podium + tabs all / online / streak / near. No chat.
- `/country`: sets `users.country`.

## 11. Profile & edit

- `/profile`: name, age · class, XP / streak / badges / global rank, level bar, league shortcut,
  subject mastery, this-week XP / days / packs, recent badges, friends strip, monthly-goals chip,
  rewards row with claim count, equipped cosmetics, screen-time chip, parent zone entry.
- `/edit-profile`: name, nickname, avatar emoji, age, gender, class, favourite subject, daily goal,
  privacy toggles; parent email read-only (link to `/parent-email`); password reset; delete link.

## 12. Parent zone (4-digit PIN)

| Feature | Route |
|---|---|
| PIN setup / unlock / change / email recovery | `/parent-controls`, `/change-pin`, `/parent-pin-otp` |
| Approve friend requests | `/parent-controls` |
| Screen time daily limit | `/screen-time` |
| Bedtime lock (sleep hours) | `/bedtime-lock` |
| Pause screen when limit/bedtime hit | `/play-paused` (heartbeat via `user_play_sessions`) |
| Weekly / full report | `/weekly-report`, `/full-report` (figures match Profile) |
| PDF export | `/export-progress` (`ProgressPdfService`) |
| Weekly email Monday 08:00 Georgian | `reports:send-weekly`, opt-out `/weekly-report-opt-out` |
| Change parent email (re-verify) | `/parent-email` |
| Delete account | `/delete-account`: PIN + email code → 14-day soft delete → `accounts:purge-deleted` |
| Preferred subjects | `/preferred-subjects` |

## 13. Settings `/settings`

Dark mode, accent (violet / pink / mint / sky / sun), text size, sound effects, background music,
voice reader (device-stored); notification prefs + reminder time; daily goal; favourite subjects;
privacy toggles; difficulty (`play_difficulty`); country; language picker (Georgian only)
`/app-language`; help/FAQ `/help-faq`; contact `/contact-us` (stored + mailed, also "rate the app");
about `/about`; clear cache `/clear-cache`; parent zone, parent email, delete account. Row search.

## 14. Notifications

In-app rows (badge, league, streak, mission, new week, friend request, friend accepted) + web push
(VAPID). `alerts:send-reminders` every 15 min in account timezone; bedtime quiet hours skip sends.

## 15. Search

`SearchService::indexFor()`: destinations, subjects, weeks, packs, live games, non-secret badges.
Georgian case-folded, partial-word. Per-user recent chips, popular chips from `search_queries`.

## 16. Sound & appearance

Correct / incorrect / level-up cues + looping music in `public/assets/audio/` (`default`, `fun`).
TTS for questions and letters (no recorded voice files).

## 17. Staff admin (Filament, `/director`)

Separate `directors` table + guard. Resources: Badges, Friendships, Games, LeagueWeeks, Questions,
UserBadges, UserPlanProgress, Users, UserStats, WeekPlanItems.

## 18. Scheduled jobs

| Command | When |
|---|---|
| `alerts:send-reminders` | every 15 min |
| `reports:send-weekly` | Monday 08:00 |
| league week close (`CloseLeagueWeekCommand`) | Monday |
| `accounts:purge-deleted` | daily |

## 19. Data model (tables)

users · user_stats · user_activity_days · xp_events · games · questions · game_question ·
week_plan_items · user_plan_progress · pack_plays · badges · user_badges · friendships ·
league_weeks · league_groups · league_group_members · league_season_payouts · reward_claims ·
shop tables · notifications · push_subscriptions · search_queries · user_play_sessions ·
support_messages · social_identities · directors.

---

## 20. Not built yet / open

- **T01** curriculum weeks 3–8 (content — biggest real gap; kids run out after ~2 weeks).
- **T15** splash, 3-slide walkthrough, PWA install, offline lesson cache.
- Camera / change-avatar, online status on profile, share profile.
- Parent tip card, install-PWA prompt on Home.
- Custom monthly goals; daily-mission gift box / bonus cards.
- Facebook login; voice search; multiple kids per parent; paid plans; avatar/nickname picker.

## 21. Ideas to add on top (not in the backlog)

- Multiple kid profiles per parent account (schema change: split `users` into `parents` + `kids`).
- Admin content authoring flow for packs (bulk import, preview as kid) — unblocks T01.
- Adaptive difficulty per kid from `pack_plays` accuracy instead of a manual setting.
- Parent mobile view / notifications for weekly summary in-app, not just email.
- Subscription plans (deferred in `CLAUDE.md`).
