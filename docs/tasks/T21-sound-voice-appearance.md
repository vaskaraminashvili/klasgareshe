# T21 — Sound, voice, appearance & support

**Priority:** P2 · depth
**Status:** done
**Depends on:** T10 (settings rows), T12/T19 (game screens)

## Why now

The leftovers that T10 hid and the accessibility features that matter most to the youngest users.
Audio is genuinely important for 6-year-olds reading Georgian for the first time — it is P2 only
because the app works without it.

## Scope — audio

- [x] Sound effects (correct / incorrect / level up) + Settings switch
- [x] Background music + switch
- [x] Voice reader for questions ("hear aloud" appears on every game screen in the template)
- [x] Georgian letter sounds (browser `speechSynthesis`, `ka-GE`; no recorded voice files)
- [ ] Animal sounds — moved out. T19 left animal, body, and habitat games out of v1, so there is no screen that should play them.
- [x] Audio assets served from `public/assets/` and preloaded sensibly on slow connections

Letter and question audio uses the browser `speechSynthesis` path with `lang=ka-GE`. There are no
recorded Georgian voice files in the repo. Animal sounds stay out: T19 left animal / body / habitat
games out of v1. Voice search stays out for the same reason — the browser recognizer has no Georgian
model. The home mic is still removed.

Sound effects and the music loop are short generated WAVs in `public/assets/audio/default/` and
`public/assets/audio/fun/`. Only the three short cues are preloaded. Music loads when its switch is
on. The shop `sound-fun` pack, once equipped, selects the `fun` set.

## Scope — appearance & accessibility

- [x] Accent colors: violet, pink, mint, sky, sun — persisted like the existing theme toggle
- [x] Text size small / medium / large
- [x] Both wired into the Settings rows hidden in T10

Accent and text size live in `localStorage` (`accent`, `textSize`) and are applied before first
paint, same as dark mode. They are device preferences, not account fields.

## Scope — language & region

- [x] Language picker (`kidzio/app-language.html` → `pages::app-language`) with search. Georgian is
      the product default; only list a locale once its `lang/` files and question content exist.
- [x] Country picker (`kidzio/country.html`) — already shipped with T18 (`pages::country`). Settings
      links to it. Not rebuilt.

`lang/en` is fallback-only. The picker lists Georgian and nothing else. Search still has an empty
state.

## Scope — support & about

- [x] `pages::help-faq` from `kidzio/help-faq.html`
- [x] `pages::contact-us` from `kidzio/contact-us.html` (real delivery, not a dead form)
- [x] `pages::about` from `kidzio/about.html` — version
- [x] `pages::clear-cache` from `kidzio/clear-cache.html` (browser cache and preloaded sounds)
- [x] Rate the app

Contact stores a `support_messages` row and mails `config('kidzio.support_email')`. The message
does not attach a child's progress. Live chat and social tiles from the template have no
destination, so they are not on the page.

About shows `config('kidzio.version')` and live counts (subjects, live games, badges, locales).
Template blocks that were fake — 120K kids, 4.9 stars, 12 languages, testimonials, team,
certifications, launch date, build size — are not shown.

Clear cache releases preloaded sounds and deletes the origin's Cache Storage. It does not wipe
theme, accent, text size, or account progress. The offline-lessons switch is on screen and
disabled.

- [ ] Offline lesson cache controls — moved to **T15**. There is no PWA lesson cache to measure
      or clear, so the control is visible, disabled, and sized at 0 rather than a fake megabyte
      figure.

Rate the app: there is no store listing and no star count. The Settings row opens
`/contact-us?topic=rate`, which stores a real message.

Install app (PWA) stays with **T15**.

## Scope — sharing

- [x] Share backend for profile, badge, mission, weekly report (all toast-only markup today)
- [x] Share links must not expose a kid's data publicly — decide the surface before building

Decision: there is no public child page and no parent-only token. Every share control uses one
payload from `ShareService`: a Georgian invite plus the public login URL. Weekly and full reports
still open their sheet, but WhatsApp, SMS, and copy use that same payload. The on-screen report
stays behind the parent PIN. A kid's name, XP, and scores are not in the outbound text.

## Code touchpoints

- `public/assets/js/app.js` (accent, text size, audio), `resources/views/layouts/app.blade.php`
- `resources/views/pages/⚡settings.blade.php` + the new pages
- `lang/ka/*`

## Done when

Every Settings row hidden in T10 is either live or deleted, questions can be heard aloud, and no
share button is a no-op.

Settings rows from T10 are live except the PWA install row (T15) and the offline-lesson cache
control (disabled, tracked above). Hear-aloud is wired on every live game. Share buttons call the
generic invite.
