# T22 — Social & phone login

**Priority:** P2 · depth
**Status:** not started
**Depends on:** T03 (reset flow), T05 (policy), T09 (account model)

## Why now

Last deliberately. The login and signup screens show Google / Apple / Facebook buttons and a phone
tab, and they are the template blocks we must not delete — but email + password already works, and
every added identity provider multiplies the account-recovery and parental-consent edge cases. Doing
this before the parent zone and deletion flow exist would mean redoing it.

## Scope

- [ ] Google OAuth
- [ ] Apple Sign In (required by Apple if other social login ships on iOS)
- [ ] Facebook — confirm it is wanted before building; three providers is a lot of surface
- [ ] Phone login tab: number entry + SMS code, reusing the T03 code service
- [ ] Linking: a social identity attaches to the existing parent account rather than creating a
      duplicate kid profile for the same email
- [ ] Parent verification still required — a verified Google email is the **parent's** email, so
      decide whether it satisfies parent verification (proposed: yes for the email, no for consent —
      the consent checkbox still applies)
- [ ] Onboarding must still run for a social signup, and Home stays blocked until it completes
- [ ] Account deletion (T09) removes linked identities

## Risks to resolve first

- Provider accounts belong to the parent, but the app's display name and age belong to the kid —
  do not auto-fill the kid's name from the provider profile.
- Apple's private relay emails break "send to parent email" assumptions; test reset with one.

## Code touchpoints

- Socialite + provider config in `config/services.php`
- Migration: `social_identities` (provider, provider_id, user_id, unique pair)
- `app/Services/UserRegistrationService.php`, `ParentVerificationService.php`
- `resources/views/pages/⚡user-login.blade.php`, `⚡user-register.blade.php` (buttons already there)

## Done when

Each shipped provider signs in, links to one account, runs onboarding and parent verification, and
password reset still works for accounts that have no password.

## Out of scope

SSO for schools or classroom accounts — a different product.
