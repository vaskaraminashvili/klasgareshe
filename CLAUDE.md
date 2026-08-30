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
- **Class (`users.grade` 1–3)** drives week-plan packs (difficulty and question bank). Signup still stores `age`; `age_group` is set in the background and is not the content filter.
- Scoring loop: play week pack → XP → level up → ranks / leagues / badges.

Tabs: Home · Learn · Rewards · Ranking · Profile.

School subjects (v1): ქართული, მათემატიკა, ისტორია (საქართველო for this age). Kidzio extras (animals, A–Z, opposites) are not on Home.

Leagues: Bronze → Silver → Gold → Emerald → Sapphire → Diamond.

---

## Current status

Started, not product-ready. Checklist: `KIDZIO-FEATURES.md`.

- Auth: `/login` (`pages::user-login`), `/register` (`pages::user-register`). Phone and social login are not wired.
- After register: onboarding (**კლასი 1 / 2 / 3** → ქართული / მათემატიკა / ისტორია → daily goal → notifications) then parent-verify (magic link + 6-digit code). Home is blocked until both are done. Login resumes the unfinished step. Kids without `grade` play class 1 packs.
- One `User` for v1 (parent email + kid fields). Avatar/nickname picker and paid plans are later.
- Home (`/`, `pages::home`) is the Kidzio shell: greeting, live streak / XP / league, week dots, **live week plan**. Mission hero, continue, today’s plan, 3 subject tiles, and featured Quick Quiz all link to the next incomplete pack (`/game-multiple-choice/{item}`) or `daily-mission`. Friends, search, and notification list are still dummy. Recent badges on Home and Profile are live. Logout works.
- Profile (`/profile`, `pages::profile`): live name, age · class, XP / streak / badges / global rank, level bar, league shortcut, subject mastery (week-1 %), this-week XP / days / packs, recent badge achievements, friends strip, monthly-goals chip. Edit profile and friends ranking are live; parent zone still template.
- Edit profile (`/edit-profile`): name, nickname, avatar emoji, age, gender, class 1–3, favourite subject, daily goal, privacy toggles. Parent email read-only. Reset/delete not wired.
- Monthly goals (`/monthly-goals`): system goals for the calendar month (packs / XP / streak / badges) from live stats. Add/custom goals deferred.
- Friends ranking (`/ranking-friends`): add by nickname (auto-accept v1), XP podium + list among friends. Parent approval later.
- Daily mission (`/daily-mission`, `pages::daily-mission`): week checklist of 21 packs (done / next / locked). Gift box / share / bonus markup only.
- Week plan: `week_plan_items` + `user_plan_progress`. Curriculum week 1 seeded for grades 1–3 (`WeekPlanSeeder`, `locale=ka`). Catch-up: first incomplete weekday per subject; progress is not wiped on Monday. Completing a pack calls `UserStatService::recordPlay()`.
- Quick Quiz (`/game-multiple-choice/{item}`): that pack’s 5 Georgian questions, 3 lives, XP on finish. Bare `/game-multiple-choice` redirects to the next incomplete item. Finishing a pack evaluates badges and may redirect to `/badge-unlock/{slug}`.
- Badges (`/badges`, `pages::badges`) + unlock (`/badge-unlock/{slug}`): 21 Kidzio badges, Georgian names, immediate unlock + one-time celebration. Speed Runner and Social Star stay locked. Rewards tab opens the collection. Shop / claim queue / Rewards dashboard are later.

Build next: week 2+ packs in the same tables, then another mini-game shell or Learn tab. Review seeded ისტორია facts.

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
| Icons | Phosphor in `public/assets/icons/{regular,fill}/` — keep `ph` / `ph-fill` classes |
| Page JS | Only if the HTML still needs a unique script; copy from `kidzio/assets/js/` into `public/assets/js/` and load from the layout or that page. Prefer layout `app.js`. |

Do **not** copy `<head>`, HTTrack comments, or template `<script src="assets/js/app.js">` into a Livewire page.

### Screen → Livewire → route

| `kidzio/` HTML | Livewire page | Route name | URL |
|---|---|---|---|
| `login.html` | `pages::user-login` | `user-login` | `/login` |
| `signup.html` | `pages::user-register` | `user-register` | `/register` |
| `home.html` | `pages::home` | `home` | `/` |
| `daily-mission.html` | `pages::daily-mission` | `daily-mission` | `/daily-mission` |
| `game-multiple-choice.html` | `pages::game-multiple-choice` | `game-multiple-choice` | `/game-multiple-choice/{item?}` |
| `badges.html` | `pages::badges` | `badges` | `/badges` |
| `badge-unlock.html` | `pages::badge-unlock` | `badge-unlock` | `/badge-unlock/{slug}` |
| `index.html` (splash) | not built yet; back buttons use `home` | `home` | `/` |
| any other `{name}.html` | `pages::{name}` (kebab-case) | `{name}` | `/{name}` unless a name already exists |

Keep existing component names. Do not create `pages::login` when `pages::user-login` already exists.

### How to port a screen

1. Open `kidzio/{screen}.html`. Copy **only** `<main>…</main>` (and overlays/sheets in `<body>`).
2. Put it in `resources/views/pages/⚡{name}.blade.php`. If that file is a placeholder, **replace it**. Do not nest `livewire:…-form` inside the copied `<main>`.
3. Root element is the copied `<main class="device-frame …">`. No extra wrapper `<div>`.
4. PHP class at the top of the same file: `#[Title]` from the HTML title; validate + action here; no Eloquent in Livewire.
5. Add `Route::livewire` in `routes/web.php` only if the route is missing.
6. Wire in place — same tags and classes:

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

7. Auth login pattern (already on the login page): `Auth::attempt(..., $remember)` → `session()->regenerate()` → `$this->redirectRoute('home', navigate: true)`.

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
```
