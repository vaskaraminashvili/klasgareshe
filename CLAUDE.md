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

- Parent-gated screens (PIN, reports, screen time, bedtime, delete account) must never be reachable by the kid without verification.
- Password reset and parent verification go to the **parent email only**.
- Age group drives lesson difficulty, word length, and pace.
- Scoring loop: play → XP → level up → ranks / leagues / badges.

Tabs: Home · Learn · Rewards · Ranking · Profile.

Subjects: Math, Alphabet, Animals, Words, Knowledge, Opposites.

Leagues: Bronze → Silver → Gold → Emerald → Sapphire → Diamond.

---

## Current status

Started, not product-ready.

- Routes: `/login`, `/register`, `/` (placeholder `"test"`)
- `User`: `name`, `surname`, `nickname`, `email`, `password`
- Login/register Livewire forms exist; they hit `User` directly (move behind a repository)

Build next: auth polish → parent verification → kid profile → onboarding → home shell → XP.

---

## UI — copy from `kidzio/`, never redesign

The look is already done. Every screen and component must be a **verbatim port** of `kidzio/*.html` plus `kidzio/assets/css/index.css`.

### Source files

| Need | Copy from |
|---|---|
| Screen markup | `kidzio/{screen}.html` — the `<main>…</main>` (and matching overlays/sheets) |
| CSS | `kidzio/assets/css/index.css` — this is the stylesheet. Do not rewrite it in `resources/css/app.css`. |
| Images | `kidzio/assets/images/` |
| Page JS (only if still needed) | `kidzio/assets/js/` |
| Icons | Phosphor (`ph` / `ph-fill`) as in the HTML |
| Fonts | Baloo 2 + Nunito (template `<head>`), not Instrument Sans |

Put shared `<head>` bits (theme script, Phosphor, fonts, `index.css`, theme-color) in `resources/views/layouts/app.blade.php` so every page matches the template.

### How to build a screen

1. Open the matching `kidzio/{name}.html`.
2. Copy the HTML **class-for-class, node-for-node** into the Livewire Blade view.
3. Keep copy, emojis, chips, structure, and class names (`device-frame`, `appbar`, `btn btn-primary`, `input-wrap`, `k-card`, …).
4. Only swap static wiring for Laravel/Livewire:
   - `href="signup.html"` → `href="{{ route('user-register') }}"` + `wire:navigate`
   - `src="assets/images/icon.png"` → `asset(...)` or Vite after copying the file into `public/`
   - `onsubmit="..."` / dummy click handlers → `wire:submit` / `wire:click`
   - `<input>` → `wire:model`
   - hardcoded names/XP → Livewire/Blade variables **inside the same markup**
5. Strip HTTrack comments and `../unpkg.com/` paths. Use a CDN or vendor copy of Phosphor, same icon classes.

### Allowed vs forbidden

**Allowed:** copy HTML; copy CSS; copy images; bind Livewire; named routes; replace dummy data.

**Forbidden:**
- Restyling or “cleaning up” Tailwind classes
- New layouts, new color palettes, new typography
- Rebuilding a screen from scratch because Livewire exists
- Writing equivalent UI in `resources/css/app.css` instead of using `kidzio/assets/css/index.css`
- Dropping sections that exist in the template (social login, remember me, chips, etc.)

Wrong: a plain `<h1>User Login</h1>` form while `kidzio/login.html` has the real UI.
Right: the `kidzio/login.html` `<main>` in Blade, with `wire:model` / `wire:submit` on the existing inputs and button.

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

PHP class at the top of the Blade file, then markup. Name files `⚡user-login.blade.php` so Livewire 4 picks them up.

The Livewire root **is** the copied `<main class="device-frame …">`. Do not wrap it in an extra unstyled `<div>`.

```php
<?php

use Livewire\Component;

new class extends Component
{
    //
};
?>

<main class="device-frame min-h-screen flex flex-col safe-top">
    {{-- pasted from kidzio/{screen}.html --}}
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
