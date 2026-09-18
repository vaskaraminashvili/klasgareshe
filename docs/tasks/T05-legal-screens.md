# T05 — Terms & Privacy screens

**Priority:** P0 · ship blocker
**Status:** done
**Depends on:** —

## Why now

Signup requires a parent to tick a consent checkbox whose Terms and Privacy links go to `#`. The
onboarding copy references COPPA / GDPR-K. Collecting a child's name, age, and a parent's email
against an unreadable agreement is not something to fix later — and both screens are already
designed in the template.

## Scope

- [x] `pages::terms-privacy` from `kidzio/terms-privacy.html` → `/terms`
- [x] `pages::privacy-policy` from `kidzio/privacy-policy.html` → `/privacy`
- [x] Both reachable **without auth** (public routes, not guest-only) — a parent must be able to read before signing up; a logged-in parent can still open them
- [x] Wire the signup consent checkbox links (`⚡user-register.blade.php`)
- [x] Georgian document text in `lang/ka/legal.php` — technical description, flagged for human legal review
- [x] Link both from Settings when **T10** lands (noted in `docs/tasks/T10-settings-screen.md`)

## Content the documents must actually state

Not lorem ipsum. Minimum, given what the app already does:

- What is collected: kid name, nickname, age, class, gender, avatar, parent email, play history, XP
- Who the account belongs to (parent) and that the kid is a profile on it
- Parent verification and why
- Leaderboard visibility, nickname visibility to friends, and how to opt out (see **T04**)
- Data deletion path and contact address (see **T09**) — in-app delete is not shipped; copy points to `privacy@kidzio.app`
- No third-party ad tracking claim — only make claims that are true today

If the final legal wording needs a human, ship the screens with the true technical description and
flag the file for review rather than shipping `#`.

## Code touchpoints

- `routes/web.php` — guest-accessible routes
- `resources/views/pages/⚡terms-privacy.blade.php`, `⚡privacy-policy.blade.php`
- `resources/views/pages/⚡user-register.blade.php`
- `lang/ka/legal.php`

## Done when

- Both screens render the ported Kidzio layout with real Georgian text.
- Signup consent links open them, logged out.
- Nothing in the app claims a protection the code does not implement.

## Out of scope

- Help / FAQ / Contact / About → **T21**
- Cookie or analytics consent — no analytics shipped yet
