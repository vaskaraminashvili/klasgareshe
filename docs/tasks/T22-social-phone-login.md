# T22 — Social & phone login

**Priority:** P2 · depth
**Status:** done — Facebook is the only open item and it is explicitly deferred (see that checkbox).
**Depends on:** T03 (reset flow), T05 (policy), T09 (account model)

## Why now

Last deliberately. The login and signup screens show Google / Apple / Facebook buttons and a phone
tab, and they are the template blocks we must not delete — but email + password already works, and
every added identity provider multiplies the account-recovery and parental-consent edge cases. Doing
this before the parent zone and deletion flow exist would mean redoing it.

## Scope

- [x] Google OAuth
- [x] Apple Sign In (required by Apple if other social login ships on iOS). A private-relay email can still reset a password through the parent-email code.
- [ ] Facebook — confirm it is wanted before building; three providers is a lot of surface.
      **Deferred, not built.** It was not confirmed as wanted. The login button stays, shows a Georgian explanation that Facebook is not enabled, and does not sign anyone in.
- [x] Phone login tab: number entry + SMS code, reusing the T03 code service. With no SMS gateway (`SMS_DRIVER` empty or `log`), the code goes through the mail channel (`MAIL_MAILER=log` locally) and the screen says SMS was not sent. Any other `SMS_DRIVER` value fails clearly.
- [x] Linking: a social identity attaches to the existing parent account rather than creating a
      duplicate kid profile for the same email
- [x] Parent verification still required — a verified Google or Apple email satisfies parent-email verification. Consent still applies. An unverified Google email does not.
- [x] Onboarding must still run for a social signup, and Home stays blocked until it completes
- [x] Account deletion (T09) removes linked identities

## Risks to resolve first

- Provider accounts belong to the parent, but the app's display name and age belong to the kid —
  do not auto-fill the kid's name from the provider profile. **Resolved:** the provider name is never written to the kid's name, nickname, or age.
- Apple's private relay emails break "send to parent email" assumptions; test reset with one.
  **Resolved:** `SocialPhoneLoginTest` resets a password on an `@privaterelay.appleid.com` account that was created with no password.

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
