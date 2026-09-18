# T10 — Settings screen

**Priority:** P1 · core product
**Status:** not started
**Depends on:** T02, T05, T06

## Why now

Four places link to `settings.html` and there is no Settings page. Onboarding already collects
notification preferences, a daily goal, and subject choices — and after onboarding the kid can never
change most of them. Settings is also where T05's legal screens and T09's account rows become
reachable.

## Scope

- [ ] `pages::settings` from `kidzio/settings.html` → `/settings`
- [ ] Sections, using the ported markup:
  - **Appearance** — dark mode (already works via layout JS; surface the toggle here). Accent color
        and text size are **T21**; hide those rows for now.
  - **Sound** — deferred to **T21**; hide.
  - **Notifications** — streak reminders, new lessons, rewards & rankings, reminder time. These
        columns already exist from onboarding; make them editable. Delivery is **T16**; the copy must
        not promise push that doesn't send yet.
  - **Learning** — daily goal, favourite subjects, difficulty (difficulty is **T19**; hide).
  - **Privacy & safety** — show on leaderboard, allow friend requests (both now enforced, T04) +
        link to Privacy (`route('privacy-policy')` / `/privacy`) and Terms (`route('terms-privacy')` /
        `/terms`). Those screens shipped in **T05**; do not invent new legal copy here.
  - **Language & region** — Georgian only today. Show it as a locked value with the current locale
        rather than an empty picker; a real picker is **T21**.
  - **Parent zone** — link into `/parent-controls` behind the PIN.
  - **Account** — parent email, delete account (**T09**).
  - **Support & about** — **T21**; hide.
- [ ] Settings search over the visible rows
- [ ] Every editable row writes through `UserProfileService` — the same service `/edit-profile` uses,
      so the two screens cannot drift
- [ ] Georgian copy in `lang/ka/settings.php`
- [ ] Unblock the `settings.html` links neutralized in T02

## Rule

Do not render a row whose backend does not exist. A settings screen full of switches that change
nothing is worse than a short one.

## Code touchpoints

- `resources/views/pages/⚡settings.blade.php`
- `app/Services/UserProfileService.php`
- `resources/views/pages/⚡home.blade.php`, `⚡profile.blade.php` — restore the links
- `lang/ka/settings.php`

## Done when

- Every switch and value on Settings persists and is reflected on the screen it affects.
- Onboarding choices are all editable from Settings or Edit profile.
- No hidden-away preference remains uneditable after onboarding.

## Out of scope

Accent colors, text size, sounds, voice reader, language picker, help/FAQ/about → **T21**.
