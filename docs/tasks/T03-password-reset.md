# T03 — Password reset via parent email

**Priority:** P0 · ship blocker
**Status:** not started
**Depends on:** —

## Why now

There is no way to recover an account. The login screen's "forgot password" is dead and
`/edit-profile`'s reset row is `href="#"`. One forgotten password today means a permanently lost
account, lost streak, and lost XP — the three things the product is built on.

## Product rule

Reset goes to the **parent email only** (`CLAUDE.md`). The kid never receives or enters an email
address in this flow.

## Scope

- [ ] `pages::forgot-password` from `kidzio/forgot-password.html` → `/forgot-password`
- [ ] `pages::otp` from `kidzio/otp.html` → verify code screen
- [ ] Reset-password step (new password + confirm), reusing the password field markup and
      `data-pwd-toggle` from the login port
- [ ] Wire "forgot password" on `⚡user-login.blade.php`
- [ ] Wire the reset row on `⚡edit-profile.blade.php` (logged-in kid → same flow, parent email)
- [ ] Georgian copy in `lang/ka/auth.php` (or a new `password.php`) + mailable subject/body
- [ ] Rate-limit requests and code attempts; expire codes (10 min is fine); single-use
- [ ] Invalidate other sessions on successful reset

## Notes on the OTP screen

`kidzio/otp.html` is a 4-digit pad. Parent verification already ships a **6-digit** code
(`ParentVerificationService`). Pick one length and use it in both places — 6, and adjust the ported
markup's box count. Reuse `ParentVerificationService`'s code generation rather than writing a second
code scheme.

## Code touchpoints

- `app/Services/ParentVerificationService.php` — extract shared code issue/verify
- New `app/Services/PasswordResetService.php` + `app/Repositories/UserRepository.php`
- `routes/web.php` — guest group
- `resources/views/pages/⚡forgot-password.blade.php`, `⚡otp.blade.php`
- Mail: a Georgian mailable; same transport as parent-verify

## Done when

- A parent can reset from the login screen, receive a code at the parent email, set a new password,
  and log in.
- Codes expire, are single-use, and are rate-limited.
- Feature tests cover: happy path, expired code, wrong code, throttle.

## Out of scope

- Changing the parent email address → **T09**
- Phone-based reset → **T22**
