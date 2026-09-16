# T15 — Splash, walkthrough, PWA

**Priority:** P2 · depth
**Status:** not started
**Depends on:** —

## Why now

First-run polish and installability. It matters for how the app feels on a phone home screen, but no
existing user is blocked by it — which is why it sits below the parent zone.

## Scope

- [ ] `pages::splash` from `kidzio/index.html` → `/welcome` (guest). Keep `home` as `/`; back
      buttons already target `home`.
- [ ] Walkthrough 3 slides from `kidzio/walkthrough-1/2/3.html` — play, streak, rewards — with Skip
- [ ] Shown once per device/account; never blocks a returning kid
- [ ] PWA manifest: name, icons, theme color (the layout already sets `theme-color`), standalone
      display, Georgian `lang`
- [ ] Service worker: shell caching, offline fallback screen
- [ ] Wire the `data-install` prompt (neutralized in T02) to the real install event, hidden when
      already installed or unsupported
- [ ] Offline: cache the current week's packs so a pack started offline can be played and synced.
      If sync is too large for this task, cache read-only screens and say so explicitly rather than
      letting a kid lose XP.

## Code touchpoints

- `public/manifest.json`, service worker, `resources/views/layouts/app.blade.php`
- `resources/views/pages/⚡splash.blade.php`, `⚡walkthrough.blade.php`
- `routes/web.php` guest group

## Done when

Installable on Android and iOS, opens standalone, first run shows splash → walkthrough → signup, and
the offline behaviour is either working or honestly unavailable.

## Out of scope

Offline lesson downloads as a settings feature and cache clearing → **T21**.
