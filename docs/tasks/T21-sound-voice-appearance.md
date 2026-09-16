# T21 — Sound, voice, appearance & support

**Priority:** P2 · depth
**Status:** not started
**Depends on:** T10 (settings rows), T12/T19 (game screens)

## Why now

The leftovers that T10 hid and the accessibility features that matter most to the youngest users.
Audio is genuinely important for 6-year-olds reading Georgian for the first time — it is P2 only
because the app works without it.

## Scope — audio

- [ ] Sound effects (correct / incorrect / level up) + Settings switch
- [ ] Background music + switch
- [ ] Voice reader for questions ("hear aloud" appears on every game screen in the template)
- [ ] Georgian letter sounds; animal sounds if the Kidzio extras ship (see T14/T19 decision)
- [ ] Audio assets served from `public/assets/` and preloaded sensibly on slow connections

## Scope — appearance & accessibility

- [ ] Accent colors: violet, pink, mint, sky, sun — persisted like the existing theme toggle
- [ ] Text size small / medium / large
- [ ] Both wired into the Settings rows hidden in T10

## Scope — language & region

- [ ] Language picker (`kidzio/app-language.html` → `pages::app-language`) with search. Georgian is
      the product default; only list a locale once its `lang/` files and question content exist.
- [ ] Country picker (`kidzio/country.html`) — may already ship with T18's country filter

## Scope — support & about

- [ ] `pages::help-faq` from `kidzio/help-faq.html`
- [ ] `pages::contact-us` from `kidzio/contact-us.html` (real delivery, not a dead form)
- [ ] `pages::about` from `kidzio/about.html` — version
- [ ] `pages::clear-cache` from `kidzio/clear-cache.html` + offline lesson cache controls (T15)
- [ ] Rate the app

## Scope — sharing

- [ ] Share backend for profile, badge, mission, weekly report (all toast-only markup today)
- [ ] Share links must not expose a kid's data publicly — decide the surface before building

## Code touchpoints

- `public/assets/js/app.js` (accent, text size, audio), `resources/views/layouts/app.blade.php`
- `resources/views/pages/⚡settings.blade.php` + the new pages
- `lang/ka/*`

## Done when

Every Settings row hidden in T10 is either live or deleted, questions can be heard aloud, and no
share button is a no-op.
