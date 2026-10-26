# Kidzio — Build Plan (TALL stack, mobile‑first, Georgian‑only)

> **Purpose of this document:** a complete brief for an AI coding agent to build Kidzio, a Duolingo‑style learning game for Georgian school kids, as a Laravel TALL‑stack app (Tailwind, Alpine.js, Laravel, Livewire).
> **Build from scratch.** Create a brand‑new Laravel project in its own folder (e.g. `D:\Herd\klasgareshe\kidzio-app`). Don't build inside, copy from or convert the old `kidzio` folder.
> **Old prototype = reference only.** The static HTML prototype (`kidzio/*.html`, also at https://kidzio-three.vercel.app) is useful **only as a list of game ideas**. Its pages are cluttered: too many numbers, cards, badges and buttons on every screen, so a child can't tell what is happening or what to tap. **Don't reuse its layouts, HTML, CSS or JS.** Design every screen fresh, following the rules in §11.

---

## 0. Instructions for the AI agent (read first)

1. Build in the **phases in §14** in order. Finish each phase with passing tests before you start the next one.
2. **All user‑facing text is Georgian (`ka`).** Still put every string in `lang/ka/*.php` (or `lang/ka.json`). Never hard‑code text in Blade, so other languages can be added later.
3. **Mobile‑first:** design for a 360–430px width. On desktop, center the app in a `max-w-[480px]` column.
4. **Content is data, not code.** Subjects, grades, courses, lessons and exercises live in the database and are edited through the admin panel. Never hard‑code a question in a Blade view (the prototype does this with `TARGET = 'APPLE'`; don't copy that).
5. Each game type is a plug‑in, made of one PHP class (validation and answer checking) and one Blade + Alpine component (UI). See §6.
6. **Simplicity is the top requirement.** Every screen must pass the "5‑second test" in §11.1: a 6‑year‑old understands what the screen is for and what to tap within 5 seconds. Follow the content budget per screen in §11.3. If a feature doesn't fit the budget, it goes on another screen, goes to the parent zone, or is dropped.
7. Use Pest for tests and seeders with real Georgian sample content, so the app is playable right after `php artisan migrate --seed`.
8. Start from an empty `laravel new` project. Don't copy files from the old prototype folder.

---

## 1. Product summary

| | |
|---|---|
| **Who plays** | School kids from grade 1 (able to read a few simple words) up to grade 6 at launch. The model must allow grades 1–12. |
| **Who owns the account** | A **parent**, registered with email. One parent can have several **child profiles**. |
| **Core loop** | Pick a subject → follow the grade **path** (units → lessons) → each lesson is 6–10 short mini‑games → earn stars/XP → keep the streak → unlock achievements and avatar items. |
| **Two kinds of content** | (1) **Grade path**: the main curriculum for each subject, matched to the child's grade. (2) **Themes**: optional topic courses inside a subject (e.g. Math → "საათი და დრო") that deepen knowledge and aren't tied strictly to the grade. |
| **Language** | Georgian only (UI and content). |
| **Business** | Free at launch. Subscription/paid plan later (§12), so build feature gates now. |

### Design principles ("game‑like but not over‑flattering")
- **Celebrate proportionally.** A correct answer gets a short green flash and a sound. A finished lesson gets a single star screen. Big confetti only for real milestones (unit finished, new badge, 7‑day streak). No "AMAZING!!! 🎉🎉" on every tap.
- **Mistakes are gentle.** Show "სცადე თავიდან" ("try again"), show the right answer, and repeat that exercise at the end of the lesson. **No hearts/lives** for grades 1–3. Lives can be an optional setting for grades 4+.
- **One currency.** Use XP shown as ⭐ only. No gems, coins or multiple currencies.
- **Read aloud.** Every instruction and prompt has a 🔊 button with pre‑recorded Georgian audio, because grade‑1 readers are slow.
- **No free‑text social features.** No chat or typed usernames visible to others (see §8.4, child safety).

---

## 2. What to take from the old prototype (reference only)

**Take:** the list of game mechanics (§2.2) and the color palette as a starting point.
**Don't take:** page layouts, components, HTML/CSS/JS, the number of elements on each page, or the information density. See §2.4 for why.

### 2.1 Visual system (starting palette, new design)
Use these colors as the base palette for the **new** design. Everything else (layout, components, spacing, typography) is designed fresh under §11.
- **Colors** (from the prototype's `assets/css/index.css`):
  - primary `#7C5CFF` (ink `#4B2FD6`), mint `#4ED6A8`, coral `#FF7A8A`, sun `#FFB547`, sky `#49B8FF`, pink `#FF8FC5`, lemon `#FFE066`
  - light: bg `#FFF8F1`, surface `#FFFFFF`, ink `#1B1240`, border `#EDE7F6`
  - dark: bg `#0F0B22`, surface `#1A1436`, ink `#F5F1FF`, border `#2A2150`
  - correct `#0E7A5A`, wrong `#D2304A`
- **Theme switching:** `.dark` class on `<html>` + `meta[theme-color]`. Store the choice in the child's settings in the DB and mirror it to localStorage so the page doesn't flash on load.
- **Icons:** one icon set only (e.g. Phosphor) + emoji or simple illustrations for content. Don't mix several icon styles on one screen.
- **Use color sparingly:** one accent color for the main action, green for correct, soft red for wrong. The prototype paints almost every card a different color, which makes nothing stand out.
- ⚠️ **Fonts:** the prototype uses *Baloo 2* and *Nunito*, which **don't include Georgian glyphs**. Replace them with Georgian‑capable fonts, e.g. **Noto Sans Georgian** (body) and **FiraGO** or **Noto Sans Georgian 800** (headings). Self‑host them for offline use.
- ⚠️ **Don't use `uppercase` on Georgian text.** Modern browsers turn Mkhedruli into Mtavruli capitals, which grade‑1 kids can't read yet. The prototype uses uppercase labels ("SPELL THE WORD"); drop `uppercase` everywhere in the kid UI.

### 2.2 Game screens found → generic exercise types
The prototype has 15 game pages. Several are the same mechanic with different content, so they collapse into **12 generic exercise types**:

| Prototype page | Mechanic | → Exercise type (`type` key) |
|---|---|---|
| `game-multiple-choice` | Question + 4 options (A–D) | `choice` |
| `game-tap-correct` | "Which one is a number?", tap 1 of 4 tiles | `choice` (variant `tiles`) |
| `game-match-animal` | Picture → pick its name, 🔊 | `choice` (variant `image_prompt`) |
| `game-where-live` | Animal → pick habitat | `choice` (variant `image_prompt`) |
| `game-opposites` | Word → pick its antonym | `choice` (variant `word_prompt`) |
| `game-knowledge` | Timed quiz, 50/50 and hint lifelines | `choice` + lesson option `timer_seconds`, lifeline `fifty_fifty` (drop the "Poll" lifeline) |
| `game-match-word` | Tap word, then its picture (4 pairs) | `match_pairs` |
| `game-connect-pair` | Tap left, then right; lines are drawn (associations) | `match_pairs` (variant `lines`) |
| `game-spell-word` | Picture + 🔊 → tap letter tiles to build the word; wrong letters marked red | `build_word` |
| `game-fill-letter` | Word with a missing letter → pick the letter | `fill_gap` |
| `game-trace-letter` | Trace a letter along an SVG path | `trace` |
| `game-word-search` | Find N words in a letter grid | `word_search` |
| `game-counting` | Count objects, enter the number with keypad/± | `number_input` |
| `game-guess-animal` | Riddle with progressive clues + hint | `riddle` (Georgian **გამოცანა**) |
| `game-body-parts` | "Tap the elbow" on a picture/labels | `hotspot` |
| *(from `lesson-details`: "Order the numbers")* | Drag to order | `order` |
| *(new, needed for school subjects)* | Sort items into 2–3 baskets | `categorize` |
| *(new)* | Statement → მართალია / მცდარია | `true_false` |

### 2.3 Prototype screens → features (ideas only, not layouts)
The prototype has ~60 pages. The new app has far fewer screens (full list in §11.3). The prototype pages only show **which features exist**:

| Feature idea from the prototype | What happens in the new app |
|---|---|
| leaderboard, ranking‑weekly, ranking‑friends, league (4 pages) | **One** simple League screen |
| xp‑progress, rewards‑dashboard, monthly‑goals | Dropped for kids. A small level ring on the profile is enough. |
| weekly‑report, full‑report, export‑progress | **One** report screen in the parent zone |
| section‑list, lesson‑details, lesson‑locked, lesson‑continue | The home path + one small "start lesson" sheet. No separate details page. |
| daily‑mission (big page with timers and bonus missions) | One small card on home with 3 ticks |
| streak (calendar, milestones, freezes, friend flames) | One small streak sheet: flame, number, this week's 7 dots |
| badges (filters, rarity counters, percentages) | A simple grid of badges: earned in color, others greyed out |
| app‑language, country, clear‑cache | Dropped |
| 9 `badge-unlock*.html` copies | One reusable "new badge" modal |
| "Kid rating ⭐4.9", "1,240 playing", "Top 12% of learners", "Poll" lifeline | Dropped |

### 2.4 Why the prototype is cluttered (mistakes to avoid)
- **Too much on one screen.** For example, the home page shows a greeting, a streak, XP, a daily mission, a continue card, several subjects, stats and notifications all at once. A child can't find the one thing to tap.
- **Too many numbers.** XP, percentages, minutes, "2 / 5", ratings, ranks and timers sit next to each other. Kids in grades 1–2 can't read most of these, and they compete for attention.
- **Several buttons with equal weight.** When everything looks clickable, nothing looks like the next step.
- **Long scrolling pages.** Streak, badges, league and the parent dashboard scroll through many sections. Kids don't scroll to find things.
- **Mixed colors and decoration on every card.** Nothing stands out because everything stands out.
- **English UI words and small labels in capital letters.** Hard for young Georgian readers.

The rules in §11 exist to prevent each of these.

---

## 3. Tech stack

| Layer | Choice |
|---|---|
| Backend | Latest stable **Laravel** (12.x or newer), PHP 8.3+ |
| UI | **Livewire** (latest stable major) full‑page components + **Alpine.js** for in‑game interactions |
| CSS | **Tailwind CSS v4** with the design tokens from §2.1 as `@theme` variables |
| Admin / content CMS | **Filament** (latest stable; it's TALL‑based) at `/admin` |
| DB | MySQL 8 or PostgreSQL. Local dev on Laravel Herd. |
| Auth | Laravel Breeze/Fortify‑style auth for **parents**, with email verification by **6‑digit OTP** |
| Queue / cache | Redis (or `database` driver at first). Queue for emails, audio processing, league rollover. |
| Media | `spatie/laravel-medialibrary` for exercise images and audio, on the S3‑compatible `public` disk |
| PWA | Web manifest + service worker (install to home screen, cache fonts, icons, audio and the current lesson) |
| Tests | Pest |
| Optional native wrapper later | NativePHP Mobile or Capacitor around the PWA |

---

## 4. Domain model

### 4.1 Accounts
```
users                     -- parents (the only real login)
  id, name, email (unique), password, email_verified_at,
  parent_pin_hash (4 digits, for the parent zone),
  locale default 'ka', timezone default 'Asia/Tbilisi',
  marketing_opt_in bool, timestamps

children                  -- kid profiles, belong to a parent
  id, user_id FK, display_name (shown only to the parent),
  username (public, unique, picked from a generator — see §8.4),
  avatar_id FK, avatar_frame_id FK nullable,
  grade_id FK, birth_year nullable,
  xp_total int default 0, level int default 1,
  streak_current int, streak_best int, streak_freezes int default 2,
  last_active_date date nullable,
  settings json   -- see §10
  leaderboard_enabled bool default (grade >= 3),
  timestamps, soft deletes
```
- **Session model:** the parent logs in. The app shows a **"ვინ თამაშობს?"** ("Who's playing?") profile picker, like Netflix. The chosen `child_id` is stored in the session. A `EnsureChildSelected` middleware protects all kid routes. Switching profile or entering the parent zone requires the **parent PIN**.
- Kids never type an email or password.

### 4.2 Curriculum and content
```
grades            id, number (1..12), name ('I კლასი'), is_active
subjects          id, slug, name, icon, color_token, sort, is_active
courses           id, subject_id, kind enum('path','theme'),
                  title, description, icon, cover_media,
                  grade_min, grade_max,          -- a path course: grade_min = grade_max
                  is_premium bool default false, sort, is_published
units             id, course_id, title, description, sort, icon
lessons           id, unit_id, title, sort, est_minutes,
                  xp_reward default 10, is_checkpoint bool,   -- checkpoint = unit "boss" test
                  settings json (timer_seconds, shuffle, lives_enabled, pass_ratio default 0.7)
exercises         id, lesson_id, type (string key, see §6),
                  prompt (text), prompt_audio (media), image (media),
                  payload json (type‑specific, validated by its type class),
                  explanation nullable (shown after a wrong answer),
                  difficulty tinyint 1..3, sort, is_active
```
- **Grade path** = `courses.kind = 'path'`, one per (subject, grade). Example: "მათემატიკა — II კლასი".
- **Theme** = `courses.kind = 'theme'`, with a grade range. Example: "ფული და ლარი" (grades 2–4).
- A child sees the path courses for **their grade** (plus earlier grades, for review) and the themes whose range includes their grade.

### 4.3 Progress
```
lesson_attempts     id, child_id, lesson_id, started_at, finished_at,
                    correct_count, wrong_count, stars (0..3), xp_earned, duration_sec
exercise_answers    id, lesson_attempt_id, exercise_id, answer json, is_correct, ms_taken, attempt_no
lesson_progress     child_id, lesson_id (PK pair), best_stars, completed_at, times_played
unit_progress       child_id, unit_id, completed_at          -- (or compute from lesson_progress)
xp_events           id, child_id, amount, source enum(lesson, streak_bonus, mission, achievement, league), source_id, created_at
daily_activity      child_id, date, xp, lessons, seconds  -- for streaks, parent reports and screen time
```
**Stars rule:** finished = 1★, ≥ 80% first‑try correct = 2★, 100% = 3★. Replaying improves `best_stars`; XP on replays is reduced (e.g. 50%).
**Unlock rule:** a lesson unlocks when the previous one has ≥ 1★. The next unit unlocks when the checkpoint lesson is passed. A setting lets a parent unlock all.

### 4.4 Gamification
```
avatars              id, key, emoji_or_image, unlock_rule json nullable (null = free)
avatar_frames        id, key, image, unlock_rule json
achievements         id, key, name, description, icon, rarity enum(common,rare,epic,legend),
                     rule json  -- e.g. {"type":"lessons_completed","subject":"math","count":20}
child_achievements   child_id, achievement_id, progress int, unlocked_at nullable
daily_missions       id, date, child_id, tasks json, completed_at, reward_xp
leagues              id, tier (1..6: ბრინჯაო, ვერცხლი, ოქრო, ზურმუხტი, საფირონი, ბრილიანტი)
league_weeks         id, league_id, week_start, group_no
league_memberships   league_week_id, child_id, xp_week, final_rank, result enum(promote, stay, demote)
```

### 4.5 Billing (create tables now, use later)
```
plans           id, key ('free','premium'), name, price_gel, interval, features json
subscriptions   id, user_id, plan_id, provider, provider_ref, status, current_period_end, cancelled_at
payments        id, subscription_id, amount_gel, status, provider_payload json
```

---

## 5. Subjects and content seed (Georgian school, grades 1–6)

> The agent must seed **sample content** only. Real content will be authored in the admin panel. Subject names follow the Georgian national curriculum (ეროვნული სასწავლო გეგმა). The product owner should check the grade ranges against the current curriculum.

| Subject (`slug`) | Name | Grades | Example **path** units | Example **themes** |
|---|---|---|---|---|
| `georgian` | ქართული ენა | 1–6 | ანბანი (ა–ჰ, 33 ასო), მარცვლები, პირველი სიტყვები, წინადადება | ანდაზები, გამოცანები, ზღაპრის გმირები, სინონიმები და ანტონიმები |
| `math` | მათემატიკა | 1–6 | რიცხვები 1–20, შეკრება/გამოკლება, გამრავლების ტაბულა, წილადები | საათი და დრო, ფული და ლარი, გეომეტრიული ფიგურები, ლოგიკური ამოცანები |
| `nature` | ბუნებისმეტყველება | 1–6 | ცხოველები, მცენარეები, ადამიანის სხეული, ამინდი და სეზონები | საქართველოს ცხოველები, კოსმოსი, წყლის მოგზაურობა |
| `society` | მე და საზოგადოება | 3–4 | ოჯახი, ჩემი ქალაქი, წესები და უსაფრთხოება | ჩემი საქართველო (რეგიონები, სიმბოლოები) |
| `history` | ისტორია | 5–6 | (later) | (later) |

**Seed size:** for grades 1 and 2 seed `georgian`, `math` and `nature` path courses with 2 units × 3 lessons × 6–8 exercises, using **every** exercise type at least once, plus 2 theme courses. Use emoji as images where possible so seeds don't need media files.

**Georgian alphabet data:** add a `config/georgian.php` with the 33 Mkhedruli letters in order (ა ბ გ დ ე ვ ზ თ ი კ ლ მ ნ ო პ ჟ რ ს ტ უ ფ ქ ღ ყ შ ჩ ც ძ წ ჭ ხ ჯ ჰ), vowels (ა ე ი ო უ), and SVG stroke paths for each letter (used by `trace`).

---

## 6. Exercise engine

### 6.1 Architecture
```
app/Exercises/
  Contracts/ExerciseType.php        -- interface
  ExerciseRegistry.php              -- maps 'choice' => ChoiceExercise::class, ...
  Types/ChoiceExercise.php
  Types/MatchPairsExercise.php
  ... (one class per type)
resources/views/components/exercise/
  choice.blade.php                  -- Alpine component, one per type
  match-pairs.blade.php
  ...
```
```php
interface ExerciseType {
    public static function key(): string;                 // 'choice'
    public static function label(): string;               // shown in admin
    public function rules(): array;                       // Laravel validation rules for payload
    public function filamentSchema(): array;              // admin form fields for payload
    public function clientPayload(Exercise $e): array;    // what the browser receives
    public function check(Exercise $e, mixed $answer): CheckResult; // isCorrect, correctAnswer, perItem feedback
}
```
- **Instant feedback:** `clientPayload()` includes the data Alpine needs to check the answer immediately, so there's no network wait for a 6‑year‑old.
- **Server is the source of truth:** Alpine then calls `$wire.submitAnswer(exerciseId, answer)`. The server runs `check()` again, stores the `exercise_answers` row, and only the server awards XP.
- Each Blade component receives `payload` and dispatches `answered` with `{correct, answer}`. The shared lesson shell handles the footer button, feedback sheet, sounds and progress bar.

### 6.2 Payload schemas (JSON)
```jsonc
// choice — covers multiple-choice, tap-correct, match-animal, where-live, opposites, knowledge
{ "variant": "list|tiles|image_prompt|word_prompt",
  "options": [ {"id":"a","text":"ძროხა","emoji":"🐄","image":null,"audio":null}, ... ],
  "correct": ["a"],                 // >1 allowed → multi‑select
  "shuffle": true }

// match_pairs — match-word, connect-pair
{ "variant": "tap|lines",
  "pairs": [ {"left":{"text":"მზე"}, "right":{"emoji":"☀️"}}, ... ] }   // 3–5 pairs

// build_word — spell-word (Georgian letter tiles)
{ "word": "ვაშლი", "emoji": "🍎", "extra_letters": 3,   // random distractor letters added
  "mark_wrong_positions": true }

// fill_gap — fill-letter, also missing number / missing word
{ "template": "ვა_ლი", "options": ["შ","ს","ჩ","ც"], "correct": "შ", "emoji":"🍎" }

// trace
{ "letter": "ა", "path_key": "ani", "tolerance_px": 22, "min_coverage": 0.8 }

// word_search
{ "size": 6, "words": ["კატა","მზე","ძაღლი"], "directions": ["h","v"] }   // grid generated server‑side & cached per attempt

// number_input — counting
{ "emoji": "🍎", "count": 8, "min": 0, "max": 20, "show_keypad": true }
// or arithmetic: { "expression": "7 + 5", "answer": 12 }

// riddle — guess-animal / გამოცანა
{ "clues": ["შავ-თეთრი ზოლები მაქვს","აფრიკაში ვცხოვრობ","ცხენს ვგავარ"],
  "options": [...choice options...], "correct": ["zebra"], "fewer_clues_bonus": true }

// hotspot — body-parts
{ "image": "media:body.png", "targets": [{"id":"elbow","label":"იდაყვი","x":62,"y":41,"r":7}],
  "ask": "elbow" }   // or "mode":"labels" to use label buttons like the prototype

// order
{ "items": [{"id":"1","text":"11"},{"id":"2","text":"14"},{"id":"3","text":"19"}], "correct_order": ["1","2","3"] }

// categorize
{ "buckets": [{"id":"farm","label":"🌾 ფერმა"},{"id":"wild","label":"🌴 ჯუნგლები"}],
  "items": [{"id":"cow","emoji":"🐄","bucket":"farm"}, ...] }

// true_false
{ "statement": "პინგვინი ცხელ უდაბნოში ცხოვრობს.", "correct": false }
```

### 6.3 Georgian‑specific rules for language exercises
- Georgian has **no upper/lower case**. Compare letters with exact Unicode match after NFC normalization.
- The `build_word` keyboard uses Georgian letters only. Distractors come from `config/georgian.php`, preferring visually similar letters (e.g. ც/წ, ჩ/ჭ, კ/ქ, პ/ფ, ტ/თ) to train real mistakes.
- Use `mb_*` functions and `preg_split('//u', ...)` for splitting words into letters.

### 6.4 Audio
- Every exercise may have `prompt_audio`. Every exercise type has a generic **instruction audio** ("აირჩიე სწორი პასუხი", "შეაერთე წყვილები", ...) in `public/audio/instructions/`.
- Browser `speechSynthesis` usually **has no Georgian voice**, so don't rely on it. Pre‑generate audio files. A queued `GenerateExerciseAudio` job can call a TTS provider with Georgian neural voices (Azure has `ka-GE` voices; check the current offer). Admins can also upload a human recording.
- The prototype's `speakWord()` fallback becomes: play the media file if it exists, otherwise hide the 🔊 button.

---

## 7. Lesson player (core screen)

`App\Livewire\LessonPlayer` (full page, route `/lesson/{lesson}`):

1. **Start:** create a `lesson_attempts` row and build the queue: the lesson's active exercises, shuffled if the lesson setting says so, max 10.
2. **Screen layout (mobile):**
   - top: ✕ close (asks "გინდა გასვლა?") + progress bar + (optional) timer
   - middle: prompt text + 🔊 + image/emoji, then the exercise component
   - bottom: one sticky main button: **შემოწმება** (check), which becomes **გაგრძელება** (continue)
3. **After checking:** a bottom sheet slides up.
   - correct: green, short sound, one encouraging word picked at random from a small list ("ყოჩაღ!", "სწორია!", "ძალიან კარგი!")
   - wrong: soft red, shows the correct answer + `explanation` + 🔊, the exercise is **re‑queued** at the end (once)
4. **Finish:** a result screen with 0–3 animated stars, XP earned, the streak flame if today's streak was extended, and **one** button "გაგრძელება" back to the path. Badge unlocks show next as a modal, one at a time.
5. **On finish the server dispatches `LessonCompleted`.** Listeners:
   - `AwardXp` (writes `xp_events`, updates `children.xp_total` and level)
   - `UpdateStreak` (uses the child's timezone, default Asia/Tbilisi)
   - `UpdateDailyActivity`
   - `EvaluateAchievements`
   - `UpdateDailyMission`
   - `AddLeagueXp`
   - `UpdateLessonProgress`
6. **Resume:** if the app is closed mid‑lesson, the home screen "Continue" button resumes the open attempt (prototype `lesson-continue`).
7. **Screen‑time and bedtime limits** are checked when a lesson starts (§9).

Sounds: `correct.mp3`, `wrong.mp3`, `complete.mp3`, `tap.mp3`, respecting the child's sound setting. Haptics with `navigator.vibrate(30)` where supported.

---

## 8. Gamification details

### 8.1 XP and levels
- Lesson: `xp_reward` (default 10) + 5 for 3★. Replays: 50%.
- Level curve: `level = floor(sqrt(xp_total / 50)) + 1`. Show a progress ring around the avatar.

### 8.2 Streak
- A day counts when the child finishes ≥ 1 lesson (Asia/Tbilisi date).
- **Streak freeze**: 2 by default, auto‑used when a day is missed, refilled +1 per week (max 2).
- Milestones 3 / 7 / 14 / 30 / 100 days give a badge + bonus XP. Keep the streak screen simple: the flame, the current number and this week's 7 dots.

### 8.3 Achievements (seed ~20)
Rule types the engine must support: `lessons_completed` (optional `subject`), `perfect_lessons`, `streak_days`, `unit_completed`, `course_completed`, `xp_total`, `exercise_type_correct` (e.g. 50 riddles solved), `league_finish`.
Examples: "პირველი ნაბიჯი" (1st lesson), "ანბანის ოსტატი" (Georgian alphabet unit done), "მათემატიკოსი" (20 math lessons), "ცეცხლოვანი კვირა" (7‑day streak), "გამოცანების მცოდნე" (50 riddles).
Some achievements unlock **avatar frames or new avatars**. That gives cosmetics a purpose without a second currency.

### 8.4 Usernames, avatars, child safety
- **Username:** generated from Georgian adjective + animal + number, e.g. "მხიარული ზებრა 27". The kid can tap 🔄 to generate another. **No free typing**, so no real names or bad words appear on leaderboards. The parent may see and change it in the parent zone.
- **Avatars:** ~24 free animal/character avatars (emoji or SVG illustrations, original art only) + unlockable ones. A background color is picked from the theme colors.
- Kids' public data on leaderboards = username + avatar + weekly XP only.
- Collect the minimum personal data. The parent gives consent at signup (checkbox + privacy policy link). Follow Georgia's personal data protection law and GDPR‑style rules for children (get a legal review before launch).

### 8.5 League (weekly)
- Groups of up to 20 children **of similar grade** (grade bands 1–2, 3–4, 5–6).
- Monday 00:00 Tbilisi: a scheduled `RolloverLeagues` command ranks the groups. Top 3 promote, bottom 3 demote, then new groups are formed.
- Off by default for grades 1–2. The parent can switch it on or off.
- **One** League screen with tabs: "ჩემი ლიგა" and "მეგობრები" (friends = siblings under the same parent for now; real friends later).

### 8.6 Daily mission
3 simple tasks generated daily (e.g. finish 1 lesson, answer 10 correctly, play 1 theme lesson). Finishing all three gives +XP and a small surprise (a random avatar item from the unlockable pool). Shown as a small card on home, not a separate busy screen.

---

## 9. Parent zone

Route prefix `/parent`, protected by `auth` + `verified` + `parent.pin` middleware (PIN re‑asked after 10 min idle).

- **Children:** add/edit/remove profiles, grade, username, avatar, "unlock all lessons" toggle, leaderboard toggle.
- **Reports per child:** minutes learned per day (7‑day bar chart), lessons finished, accuracy per subject, weakest topics (lowest accuracy exercises' units), recent badges.
- **Weekly email report** (queued, Sunday evening): short summary per child.
- **Screen time:** daily limit (10–120 min). When the limit is reached, the kid sees a friendly "დროა დავისვენოთ 🌙" screen. **Bedtime lock:** from–to hours. **Break reminder:** every N minutes.
- **Account:** email, password, change PIN, delete account (deletes all child data), subscription (later).

---

## 10. Settings (per child)

Stored in `children.settings` json, edited at `/settings` (kid‑safe items). Parent‑only items live in the parent zone.
```json
{ "theme": "system|light|dark",
  "accent": "primary|mint|coral|sun|sky|pink",
  "text_size": "md|lg|xl",
  "sound_effects": true, "music": false, "voice_reader": true,
  "daily_goal_minutes": 10,
  "reminder_time": "18:00",
  "difficulty": "auto|easy|medium|hard" }
```
- `accent` overrides the `--color-k-primary` CSS variable at runtime.
- `text_size` sets a root class that scales prompt/option font sizes.
- `difficulty: auto` picks exercises by `difficulty` based on recent accuracy (simple rule: < 60% → easier, > 90% → harder).

---

## 11. Screen design rules (kid UI) — the most important section

### 11.1 The 5‑second test
Every kid screen must answer two questions within 5 seconds for a 6‑year‑old who reads slowly:
1. **Where am I?** One short title or one clear picture.
2. **What do I tap?** One obvious main button, bigger and more colorful than anything else.

If a screen fails, remove things until it passes. Removing is always preferred over adding explanations.

### 11.2 Hard rules for every kid screen
| Rule | Limit |
|---|---|
| Main purpose | **1** per screen. A screen does one job. |
| Primary button | **Exactly 1**, full width, at the bottom (thumb zone), accent color. |
| Secondary actions | Max **2**, styled as plain text or small icons (e.g. ✕ close, 🔊 listen). |
| Numbers visible at once | Max **2** (e.g. streak 🔥5 and ⭐120). No percentages, no "x / y" counters, no ranks on home. |
| Text | Max **2 short lines** of instruction text (≤ 8 words each). Every instruction also has 🔊 audio. |
| Content blocks | Max **3** visible blocks without scrolling (e.g. header, content, button). |
| Scrolling | The lesson, result and modal screens **never scroll**. Only the path and the badge grid may scroll. |
| Colors | Neutral background, **one** accent color for the action, green/red only for answer feedback. No rainbow cards. |
| Fonts | 1 font family, max 2 weights per screen. Prompt text ≥ 22px, options ≥ 20px, labels ≥ 16px. No text smaller than 14px anywhere in the kid UI. No `uppercase`. |
| Touch targets | ≥ 56px tall for answers and main buttons, ≥ 48px for icons. ≥ 12px gap between tappable items. |
| Decoration | No background patterns, no badges on badges, no "NEW" / "HOT" pills, no notification dots in the kid UI. |
| Animation | Short (≤ 400ms), only to show cause and effect (tap → feedback). Respect `prefers-reduced-motion`. |
| Words | Georgian only, everyday words a grade‑1 child knows. No English, no jargon like "XP", "League", "Streak" in labels: use ვარსკვლავები, ლიგა, ცეცხლი. |
| Popups | Max **1** popup at a time. Never show a popup on top of a popup. Badge unlocks queue and show one by one after the result screen. |

### 11.3 Screen list with content budget
This is the **complete** list of kid screens. Anything not listed doesn't exist in the kid UI.

**Bottom navigation: 3 tabs only** (big icons + one‑word labels), hidden during lessons:
🏠 **სწავლა** (learn) · 🏆 **ჯილდოები** (rewards) · 🐻 **მე** (me)

| # | Screen | Shows (nothing else) | Primary button |
|---|---|---|---|
| 1 | **Who's playing?** | Avatars + usernames of the children, one "parent" lock icon in a corner | Tap own avatar |
| 2 | **Home / path** | Top bar: subject picker (current subject icon + name), 🔥 streak number, ⭐ total. Below: the lesson path for that subject. A small "themes" row at the very end of the path. | The current lesson node (big, pulsing, with "დაწყება") |
| 3 | **Subject picker** (bottom sheet) | Max 5 subject tiles: big icon + name | Tap a subject |
| 4 | **Start lesson** (bottom sheet) | Lesson title, 3 stars (earned/empty) | "დაწყება" |
| 5 | **Locked lesson** (bottom sheet) | 🔒 + "ჯერ დაასრულე წინა გაკვეთილი" | "კარგი" |
| 6 | **Lesson player** | ✕, progress bar, 🔊 + one prompt, the exercise | "შემოწმება" → "გაგრძელება" |
| 7 | **Answer feedback** (sheet inside the lesson) | ✓ or ✕ icon, one word ("სწორია!" / "სწორი პასუხია: …"), 🔊 | "გაგრძელება" |
| 8 | **Lesson result** | 1–3 stars, "+15 ⭐", 🔥 if the streak grew today | "გაგრძელება" |
| 9 | **New badge** (modal) | The badge, its name | "ვაუ!" (closes) |
| 10 | **Themes** (from home, end of path) | Grid of theme cards: icon + title + stars done | Tap a theme → its own path (screen 2 layout) |
| 11 | **Rewards** | Two sub‑tabs: **ბეჯები** (badges grid, earned in color, locked grey, tap = sheet with name + how to get it) and **ლიგა** (if enabled: my league name + a list of max 20 rows: place, avatar, username, ⭐ this week; my row highlighted) | — |
| 12 | **Me** | Avatar with level ring, username, 3 tiles max (🔥 best streak, ⭐ total, 🏆 badges) | "პარამეტრები" (settings) as a text link |
| 13 | **Avatar picker** | Grid of avatars (locked ones greyed) | "შენახვა" |
| 14 | **Settings** (kid‑safe) | 5 rows max: theme (light/dark), color, text size, sounds on/off, read aloud on/off | — (auto‑save) |
| 15 | **Streak** (sheet, tap 🔥 on home) | Big flame + number, 7 day dots for this week | "კარგი" |
| 16 | **Daily mission** (sheet, tap a small 🎯 icon on home) | 3 tasks with ticks | "კარგი" |
| 17 | **Time's up** (screen‑time / bedtime) | Moon picture + "დროა დავისვენოთ 🌙" | none (only the parent can unlock) |

**Not in the kid UI (moved or dropped):** reports, minutes, accuracy, percentages, level XP numbers, onboarding questions about goals, notification settings, help/FAQ, legal pages, subscription. These belong to the **parent zone** or the website footer.

### 11.4 Wireframes (follow the structure, not the exact look)

**Home / path**
```
┌──────────────────────────────┐
│ [🔢 მათემატიკა ▾]   🔥5  ⭐120 │  ← top bar: max 3 items
│                              │
│        ( ★★★ )               │  ← done lesson
│              ( ★★☆ )         │
│        ( ▶ დაწყება )          │  ← current lesson: big, pulsing
│              ( 🔒 )          │
│        ( 🏆 )                │  ← unit checkpoint
│                              │
├──────────────────────────────┤
│   🏠 სწავლა   🏆 ჯილდოები  🐻 მე │
└──────────────────────────────┘
```

**Lesson player**
```
┌──────────────────────────────┐
│ ✕   ▓▓▓▓▓▓░░░░░░             │
│                              │
│  🔊  რომელია ძროხა?            │  ← one prompt, one line
│                              │
│   ┌──────┐   ┌──────┐        │
│   │  🐶  │   │  🐄  │        │  ← 2×2 big answers
│   └──────┘   └──────┘        │
│   ┌──────┐   ┌──────┐        │
│   │  🐱  │   │  🐑  │        │
│   └──────┘   └──────┘        │
│                              │
│ [        შემოწმება         ] │  ← disabled until something is picked
└──────────────────────────────┘
```

**Lesson result**
```
┌──────────────────────────────┐
│                              │
│          ⭐  ⭐  ☆            │
│                              │
│          +15 ⭐               │
│          🔥 6                │
│                              │
│ [        გაგრძელება        ] │
└──────────────────────────────┘
```

### 11.5 Navigation and flow rules
- Max **2 taps** from Home to the start of a lesson (tap node → tap "დაწყება").
- Every sheet and modal closes with a tap outside, a ✕, or the main button.
- No dead ends: every screen has a clear way back.
- Use `wire:navigate` for SPA‑like navigation. Show simple skeletons, not spinners.
- Routes:
```
/                         → /profiles or /login
/login, /register, /forgot-password, /verify   (parent)
/onboarding               (parent creates first child: grade → avatar → username)
/profiles                 Who's playing?
/home                     path of the current subject (subject & theme chosen via sheets, ?course=)
/lesson/{lesson}          lesson player (result is a state of the same component)
/rewards                  badges + league tabs
/me, /me/avatar, /me/settings
/parent/...               parent zone (§9)
```

### 11.6 Parent zone UI
The parent zone is for adults and may show more information, but it's still mobile‑first and calm:
- One child at a time (switcher at the top).
- **Report screen:** 4 numbers max at the top (minutes this week, lessons, accuracy, current streak), one 7‑day bar chart, then a short list "needs practice" (max 3 topics).
- Settings grouped into short lists: Children, Time limits, Account, Subscription. No long dashboards.

### 11.7 How the AI must check its own work
After building each screen:
1. Take a screenshot at **360×740** in light and dark mode (Laravel Dusk or Playwright).
2. Check it against the table in §11.2 and the budget in §11.3: count primary buttons, visible numbers, text lines and blocks.
3. If it breaks a limit, simplify and screenshot again.
4. Ask: "Would a 6‑year‑old know what to tap?" If not sure, remove something.

---

## 12. Subscription (design now, build in Phase 8)

- **Free:** grade path for all subjects for 1 child, limited themes (e.g. 2 per subject), basic parent report.
- **Premium:** all themes, up to 4 children, full reports and weekly email, offline packs, extra avatar items.
- Implement gates now: `Gate::define('premium', ...)`, a `courses.is_premium` flag, and `@can('premium')` in the UI with a friendly lock card (shown to the parent, never a sales pitch to the child).
- **Payments:** build a `PaymentGateway` interface with drivers. For Georgian merchants, look at local providers (Bank of Georgia iPay / BOG Payments, TBC Pay/TPay), since Stripe may not be available to Georgian businesses. Prices in **GEL (₾)**. Check provider options before building.

---

## 13. Admin panel (Filament, `/admin`)

- **Resources:** Grades, Subjects, Courses (with nested Units → Lessons via relation managers), Exercises, Avatars, Avatar frames, Achievements, Plans, Users (parents, read‑mostly), Children (read‑only, support).
- **Exercise form:** select `type`, then the form fields come from `ExerciseType::filamentSchema()`. Validate with `rules()`.
- **Preview:** a "Preview" action renders the exercise inside a phone‑sized frame using the real Blade component.
- **Bulk import:** upload JSON/CSV of exercises for a lesson (for fast content creation, including AI‑drafted content reviewed by a teacher).
- **Audio:** a "Generate audio" action (queued TTS job) + manual upload.
- Admin access: `users.is_admin` flag or a separate `admins` guard.

---

## 14. Build phases (deliver in this order)

**Phase 0 — Setup**
Laravel + Livewire + Tailwind v4 + Alpine + Filament + Pest. Design tokens from §2.1 in `resources/css/app.css` (`@theme`). Georgian fonts. Base layout (`max-w-[480px]`, safe‑area padding, dark mode, accent variable). `lang/ka`. App timezone `Asia/Tbilisi`, locale `ka`.

**Phase 1 — Accounts**
Parent registration, login, OTP email verification, password reset, parent PIN set‑up. Child profiles CRUD, "Who's playing?" picker, `EnsureChildSelected` middleware, username generator, avatar picker. Onboarding flow (grade → avatar → username → subjects → daily goal).

**Phase 2 — Content model + admin**
Migrations and models for §4.2. Filament resources. `ExerciseRegistry` + all type classes with `rules()` and `check()`, fully unit‑tested. Seeders from §5.

**Phase 3 — Lesson player + first 6 exercise types**
`LessonPlayer`, shared lesson shell, feedback sheet, sounds, re‑queue of wrong answers, result screen, attempts and answers saved. Types: `choice`, `match_pairs`, `build_word`, `fill_gap`, `number_input`, `true_false`.

**Phase 4 — Path and progress**
Home path, subject switcher, theme courses tab, lock/unlock rules, stars, resume lesson, lesson intro and locked sheets.

**Phase 5 — Remaining exercise types**
`riddle`, `categorize`, `order`, `hotspot`, `word_search`, `trace` (Georgian letter paths).

**Phase 6 — Gamification**
XP and levels, streak + freezes, achievements engine + unlock modal, avatars/frames unlocks, daily mission, league + weekly rollover command (scheduled).

**Phase 7 — Parent zone and settings**
PIN gate, reports with charts, weekly email, screen time, bedtime lock, break reminder, child settings (theme, accent, text size, sound, voice reader, difficulty).

**Phase 8 — PWA, polish, billing**
Manifest + service worker (offline cache for fonts/icons/sounds and the current lesson's media), install prompt (shown to parent), performance pass, accessibility pass. Plans, subscriptions, gates, payment driver.

---

## 15. Quality checklist

- [ ] Every `ExerciseType::check()` has Pest tests for correct, wrong and malformed answers.
- [ ] Feature tests: registration → OTP → child creation → first lesson completed → XP, streak and achievement updated.
- [ ] Streak tests across midnight in Asia/Tbilisi and with freezes.
- [ ] League rollover test (promote/stay/demote, grade bands).
- [ ] No hard‑coded Georgian strings in Blade (all in `lang/ka`).
- [ ] No `uppercase` class on Georgian text in the kid UI.
- [ ] Lighthouse mobile: Performance ≥ 85, Accessibility ≥ 95, PWA installable.
- [ ] Every page checked at 360px width in light and dark mode.
- [ ] N+1 queries checked (Laravel Debugbar or `Model::preventLazyLoading()` in dev).
- [ ] Rate limiting on auth, OTP and answer submission routes.

---

## 16. Open decisions for the product owner
1. Exact grade range at launch (plan assumes grades 1–4 with real content, model supports 1–12).
2. Subject list and grade ranges, checked against the current national curriculum.
3. TTS provider and voice, or human voice recordings.
4. Payment provider and prices (GEL).
5. Whether lives/hearts exist at all for grades 4+.
6. Who authors content (teachers? AI‑drafted + teacher‑reviewed?).
