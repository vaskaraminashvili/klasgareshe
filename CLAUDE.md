# Kidzio (klasgareshe)

Kids learning app. Parents own the account; a kid is a profile on that account. Port the `kidzio/` HTML/CSS template into Laravel Livewire — do not redesign it.

Full backlog: `KIDZIO-FEATURES.md`. Design source: `kidzio/` (ignore `kidzio-three.vercel.app/`).

---

## Stack

- PHP 8.3+, Laravel 13, Livewire 4 (single-file Volt-style components)
- Tailwind CSS 4 + Vite
- Mysql locally (EnvKit)
- Pint, PHPStan (Larastan level 7), PHPUnit

This is **Livewire, not Vue**. Do not add Vue, Nuxt, Vuex, or Inertia.

---

## Product rules

- **Language:** the product ships in Georgian (`ka` / ქართული). UI strings live in `lang/ka`; game questions and other user-facing DB content are seeded with `locale = ka`. Keep `__()` / `lang` files for every screen copy — do not hardcode English in Blade. English (`lang/en`) and other locales are for future localization only (`APP_FALLBACK_LOCALE=en`); do not make English the default content.
- Parent-gated screens (PIN, reports, screen time, bedtime, delete account) must never be reachable by the kid without verification.
- Password reset and parent verification go to the **parent email only**.
- **Privacy:** `show_on_leaderboard` hides the kid from public (global all-time and weekly XP) ranking queries. Friends ranking still lists them (friendship is consent). League groups — and `/ranking-weekly`, which currently renders that closed cohort — still include them, because promote/relegate needs every member. New accounts default to visible.
- **Class (`users.grade` 1–3)** drives week-plan packs (difficulty and question bank). Signup still stores `age`; `age_group` is set in the background and is not the content filter.
- Scoring loop: play week pack → XP → level up → ranks / leagues / badges.

Tabs: Home · Learn · Rewards · Ranking · Profile.

School subjects (v1): ქართული, მათემატიკა, ისტორია (საქართველო for this age). Kidzio extras (animals, A–Z, opposites) are not on Home.

Leagues: Bronze → Silver → Gold → Emerald → Sapphire → Diamond.

---

## Current status

Started, not product-ready. Checklist: `KIDZIO-FEATURES.md`.

- Auth: `/login` (`pages::user-login`), `/register` (`pages::user-register`). Phone and social login are not wired. Password reset is live: `/forgot-password` → 6-digit code at the parent email → `/reset-password`. Login “დაგავიწყდა?” and Edit profile’s reset row both start that flow. Signup consent links open `/terms` and `/privacy` (guest-readable Georgian documents; flagged for legal review).
- After register: onboarding (**კლასი 1 / 2 / 3** → ქართული / მათემატიკა / ისტორია → daily goal → notifications) then parent-verify (magic link + 6-digit code). Home is blocked until both are done. Login resumes the unfinished step. Kids without `grade` play class 1 packs.
- One `User` for v1 (parent email + kid fields). Avatar/nickname picker and paid plans are later.
- Home (`/`, `pages::home`) is the Kidzio shell: greeting, live streak / XP / league, week dots, **live week plan**. Mission hero, continue, today’s plan, 3 subject tiles, and featured Quick Quiz all link to the next incomplete pack (`/game-multiple-choice/{item}`) or `daily-mission`. Friends, search, and notification list are still dummy. Recent badges on Home and Profile are live. Logout works. Learn tab (`/learn-categories`, `pages::learn-categories`) is the Kidzio library shell (search / filter / subject tiles / mini-games); counts and Kidzio extras are still dummy. Spotlight and Quick Quiz link to `daily-mission` / `game-multiple-choice`.
- Profile (`/profile`, `pages::profile`): live name, age · class, XP / streak / badges / global rank, level bar, league shortcut, subject mastery (active curriculum week %), this-week XP / days / packs, recent badge achievements, friends strip, monthly-goals chip. Edit profile and friends ranking are live. Parent zone links to `/parent-controls` behind a 4-digit PIN (setup / unlock / change / email recovery). Screen-time chip shows remaining minutes (or off). Screen time (`/screen-time`) and bedtime (`/bedtime-lock`) are PIN-gated; play routes pause at the daily limit or during sleep hours (`/play-paused`). Heartbeat tracks minutes on Quick Quiz. Weekly/full reports (`/weekly-report`, `/full-report`) and PDF export (`/export-progress`) are PIN-gated; figures match Profile. Monday 08:00 Georgian parent email (`reports:send-weekly`) respects `notification_preferences.weekly_report`. Parent email change (`/parent-email`) and delete (`/delete-account`) are PIN-gated; delete also needs a 6-digit code at the parent email, then a 14-day soft-delete grace before `accounts:purge-deleted`.
- Edit profile (`/edit-profile`): name, nickname, avatar emoji, age, gender, class 1–3, favourite subject, daily goal, privacy toggles. Parent email read-only with a link to PIN-gated `/parent-email` (pending-email re-verify). Password reset is live (parent email code). Delete goes to `/delete-account` (PIN + email code). **`show_on_leaderboard` is enforced** on `/leaderboard` (and weekly XP ranking queries); friends + league stay visible.
- Monthly goals (`/monthly-goals`): system goals for the calendar month (packs / XP / streak / badges) from live stats. Add/custom goals deferred.
- Friends ranking (`/ranking-friends`): add by nickname (auto-accept v1), XP podium + list among friends. Parent approval later.
- Daily mission (`/daily-mission`, `pages::daily-mission`): **3 today tasks** (next pack per subject, or done if already played today). Gift box / share / bonus markup only.
- Week plan: `week_plan_items` + `user_plan_progress`. Curriculum weeks 1–2 seeded for grades 1–3; **week 3** for class 1 (`WeekPlanSeeder`, `locale=ka`). Active week = lowest week with incomplete packs; advances to N+1 when N is fully done; stays on last seeded week when all complete. Catch-up: first incomplete weekday per subject within the active week; progress is not wiped on Monday. Completing a pack calls `UserStatService::recordPlay()`.
- Quick Quiz (`/game-multiple-choice/{item}`): that pack’s 5 Georgian questions, 3 lives, XP on finish. Bare `/game-multiple-choice` redirects to the next incomplete item. Finishing a pack evaluates badges and may redirect to `/badge-unlock/{slug}`.
- Badges (`/badges`, `pages::badges`) + unlock (`/badge-unlock/{slug}`): 21 Kidzio badges, Georgian names, immediate unlock + one-time celebration. Speed Runner and Social Star stay locked. Rewards tab opens the collection. Shop / claim queue / Rewards dashboard are later.

**Ordered plan: `docs/roadmap.md`** — 22 tasks in build order, one brief per task in `docs/tasks/`.
Read it before starting work; update the task's status and this section when one ships.

**T02 (dead links & fake data) is done.** Home, Profile, the shared header and `/ranking-friends` no
longer link to template `.html` files or show invented numbers. Two rules now hold:

- No Blade view or loaded page script may link to a `.html` file —
  `tests/Feature/NoTemplateLinksTest.php` fails the build if one appears.
- Blocks whose backend does not exist were **deleted**, each leaving a one-line Blade comment naming
  the block and the task that re-ports it. Copy the markup back from `kidzio/{screen}.html` when you
  get there. Elements that showed live data but had no link target were kept and made inert.
- Home search is real: `SearchService::homeCatalog()` renders 12 Georgian destinations into
  `<script type="application/json" id="searchIndex">`, which `public/assets/js/home.js` reads.

Build next: **T10** settings screen.
(**T01**, the week 3–8 curriculum packs, is parked at the user's request.)

---

## UI — copy from `kidzio/`, never redesign

The look is already done. Every screen must be a **verbatim port** of `kidzio/{screen}.html` plus `kidzio/assets/css/index.css`.

Cursor playbook (always apply): `.cursor/rules/kidzio-screen-port.mdc`.  
Reference port: `kidzio/login.html` → `resources/views/pages/⚡user-login.blade.php`.

### Already wired — do not recopy per page

| Need | Where it lives |
|---|---|
| Layout `<head>` (theme script, Phosphor, Baloo 2 + Nunito, theme-color) | `resources/views/layouts/app.blade.php` |
| CSS | `public/assets/css/index.css` (after Vite in the layout). Do not rewrite in `resources/css/app.css`. |
| Images | `public/assets/images/` → `asset('assets/images/…')` |
| Theme / back / password-eye / sheets / tabs | `public/assets/js/app.js` (`data-theme-toggle`, `data-back`, `data-pwd-toggle`, …) |
| Horizontal rails / sliders | `public/assets/js/app.js` (`data-swiper-rail`, `data-swiper-rail-tabs`); Swiper comes from the prebuilt `public/assets/js/index.js`, not npm. **When a rail stops scrolling, read `docs/rails-swiper.md` first** — it has broken four times for four different reasons and the console is clean every time. |
| Icons | Phosphor in `public/assets/icons/{regular,fill}/` — keep `ph` / `ph-fill` classes |
| Page JS | Only if the HTML still needs a unique script; copy from `kidzio/assets/js/` into `public/assets/js/` and load from the layout or that page. Prefer layout `app.js`. |

Do **not** copy `<head>`, HTTrack comments, or template `<script src="assets/js/app.js">` into a Livewire page.

### Screen → Livewire → route

| `kidzio/` HTML | Livewire page | Route name | URL |
|---|---|---|---|
| `login.html` | `pages::user-login` | `user-login` | `/login` |
| `signup.html` | `pages::user-register` | `user-register` | `/register` |
| `home.html` | `pages::home` | `home` | `/` |
| `learn-categories.html` | `pages::learn-categories` | `learn-categories` | `/learn-categories` |
| `daily-mission.html` | `pages::daily-mission` | `daily-mission` | `/daily-mission` |
| `game-multiple-choice.html` | `pages::game-multiple-choice` | `game-multiple-choice` | `/game-multiple-choice/{item?}` |
| `badges.html` | `pages::badges` | `badges` | `/badges` |
| `badge-unlock.html` | `pages::badge-unlock` | `badge-unlock` | `/badge-unlock/{slug}` |
| `forgot-password.html` | `pages::forgot-password` | `forgot-password` | `/forgot-password` |
| `otp.html` | `pages::otp` | `otp` | `/otp` |
| — | `pages::reset-password` | `reset-password` | `/reset-password` |
| `terms-privacy.html` | `pages::terms-privacy` | `terms-privacy` | `/terms` |
| `privacy-policy.html` | `pages::privacy-policy` | `privacy-policy` | `/privacy` |
| `parent-controls.html` | `pages::parent-controls` | `parent-controls` | `/parent-controls` |
| `change-pin.html` | `pages::change-pin` | `change-pin` | `/change-pin` |
| `preferred-subjects.html` | `pages::preferred-subjects` | `preferred-subjects` | `/preferred-subjects` |
| `otp.html` (PIN reset) | `pages::parent-pin-otp` | `parent-pin-otp` | `/parent-pin-otp` |
| `screen-time.html` | `pages::screen-time` | `screen-time` | `/screen-time` |
| `bedtime-lock.html` | `pages::bedtime-lock` | `bedtime-lock` | `/bedtime-lock` |
| — | `pages::play-paused` | `play-paused` | `/play-paused` |
| `export-progress.html` | `pages::export-progress` | `export-progress` | `/export-progress` |
| `parent-email.html` | `pages::parent-email` | `parent-email` | `/parent-email` |
| — | `pages::delete-account` | `delete-account` | `/delete-account` |
| `index.html` (splash) | not built yet; back buttons use `home` | `home` | `/` |
| any other `{name}.html` | `pages::{name}` (kebab-case) | `{name}` | `/{name}` unless a name already exists |

Keep existing component names. Do not create `pages::login` when `pages::user-login` already exists.

### How to port a screen

1. Create the Livewire page (do **not** omit `pages::` — that writes to `resources/views/components/` instead):

```bash
php artisan make:livewire pages::{name}
```

Example: `php artisan make:livewire pages::learn-categories` → `resources/views/pages/⚡learn-categories.blade.php`. Nested: `php artisan make:livewire pages::post.create` → `resources/views/pages/post/⚡create.blade.php`. The `⚡` prefix is added automatically.

2. Open `kidzio/{screen}.html`. Copy **only** `<main>…</main>` (and overlays/sheets in `<body>`).
3. Put it in `resources/views/pages/⚡{name}.blade.php`. If that file is a placeholder, **replace it**. Do not nest `livewire:…-form` inside the copied `<main>`.
4. Root element is the copied `<main class="device-frame …">`. No extra wrapper `<div>`.
5. PHP class at the top of the same file: `#[Title]` from the HTML title; validate + action here; no Eloquent in Livewire.
6. Add `Route::livewire` in `routes/web.php` only if the route is missing.
7. Wire in place — same tags and classes:

   - `href="signup.html"` → `route('user-register')` + `wire:navigate`
   - `href="login.html"` → `route('user-login')` + `wire:navigate`
   - `href="index.html"` → `route('home')`, keep `data-back`, **no** `wire:navigate` (layout JS uses history)
   - `href="{page}.html"` with no Laravel route yet → `href="#"` (do not invent screens)
   - `src="assets/images/icon.png"` → `asset('assets/images/icon.png')`
   - `onsubmit` / `location.href='home.html'` → `wire:submit`
   - `<input>` / checkbox → `wire:model`
   - Keep `id="pwd"` + `data-pwd-toggle="pwd"` so layout JS still toggles visibility
   - Extra buttons inside a `<form>` → `type="button"`
   - Hardcoded names/XP → Livewire/Blade variables **inside the same node**
   - Validation: `@error` as `<p class="text-sm" style="color:var(--color-k-coral)">` next to the field

8. Auth login pattern (already on the login page): `Auth::attempt(..., $remember)` → `session()->regenerate()` → `$this->redirectRoute('home', navigate: true)`.

### Allowed vs forbidden

**Allowed:** copy HTML; bind Livewire; named routes; replace dummy data.

**Forbidden:**
- Restyling or “cleaning up” Tailwind classes
- New layouts, new color palettes, new typography
- Rebuilding a screen from scratch because Livewire exists
- Writing equivalent UI in `resources/css/app.css`
- Dropping sections that exist in the template (social login, remember me, chips, etc.)
- Copying `<head>` or page scripts that the layout already loads
- Querying `User::` (or any Eloquent) from the Livewire page

Wrong: a plain `<h1>User Login</h1>` form while `kidzio/login.html` has the real UI.  
Right: the `kidzio/login.html` `<main>` in `⚡user-login.blade.php`, with `wire:model` / `wire:submit` on the existing inputs and button.

---

## Architecture

Repository + service. Keep Livewire components thin.

```
app/
  Repositories/     data access + Eloquent relations
  Services/         business logic
  Providers/RepositoryServiceProvider.php
resources/views/
  pages/⚡{name}.blade.php        full-page Livewire
  components/⚡{name}.blade.php   reusable Livewire
  layouts/app.blade.php
routes/web.php
```

- Access models **only** through repositories. Never query Eloquent from Livewire, controllers, or services.
- Put relations on repositories, not on Livewire classes.
- Bind repositories and services in `RepositoryServiceProvider`.
- Validate in the Livewire component (or a FormRequest if a controller is used).
- Controllers stay lean if they exist; prefer Livewire pages.

Staff (Filament) is a separate identity from parent/kid accounts. Table `directors`, model `Director`, guard `director`. Panel path `/director`. Kid routes stay `auth:web`. Do not put an `is_admin` flag on `users`.

### Routes

```php
Route::livewire('/login', 'pages::user-login')->name('user-login');
```

Use `wire:navigate` for in-app links.

### Livewire file shape

Match `resources/views/pages/⚡user-login.blade.php`. PHP class at the top, then the copied `<main>` as root — no wrapper `<div>`, no nested form component.

```php
<?php

use Livewire\Attributes\Title;
use Livewire\Component;

new #[Title('Login · Kidzio')] class extends Component
{
    // validate + action here (Auth facade or a service — not Eloquent)
};
?>

<main class="device-frame min-h-screen flex flex-col safe-top">
    {{-- pasted from kidzio/{screen}.html, then wired --}}
</main>
```

---

## Laravel conventions

- Migrations: timestamps, foreign keys, indexes on search columns, soft deletes where records should be recoverable.
- Hash passwords with the `hashed` cast (do not `Hash::make` in Livewire if the cast is set).
- Named routes. No magic strings for redirects.
- Match existing code style; run Pint on PHP you touch.

---

## Do not

- Redesign screens. Copy `kidzio/` HTML + CSS component by component.
- Put business logic or Eloquent in Blade.
- Commit `.env` or secrets.
- Invent features that are not in `KIDZIO-FEATURES.md` unless asked.

---

## Commands

```bash
composer setup          # install, migrate, npm build
composer dev            # php artisan dev
composer test           # pint + phpstan + phpunit
vendor/bin/pint --dirty
php artisan test --filter=Example
php artisan make:livewire pages::{name}   # full-page SFC → resources/views/pages/⚡{name}.blade.php
# php artisan make:livewire pages::learn-categories
# php artisan make:livewire pages::post.create   # nested → resources/views/pages/post/⚡create.blade.php
```
