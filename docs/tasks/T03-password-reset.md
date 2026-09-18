# T03 — Password reset via parent email

**Priority:** P0 · ship blocker
**Status:** done
**Depends on:** —

## Why now

There is no way to recover an account. The login screen's "forgot password" is dead and
`/edit-profile`'s reset row is `href="#"`. One forgotten password today means a permanently lost
account, lost streak, and lost XP — the three things the product is built on.

## Product rule

Reset goes to the **parent email only** (`CLAUDE.md`). The kid never receives or enters an email
address in this flow.

## Scope

- [x] `pages::forgot-password` from `kidzio/forgot-password.html` → `/forgot-password`
- [x] `pages::otp` from `kidzio/otp.html` → `/otp` (6 boxes, not 4 — same length as parent-verify)
- [x] Reset-password step (`pages::reset-password` → `/reset-password`) — login-style password fields +
      `data-pwd-toggle`. No `new-password.html` in the template; same visual language as forgot-password.
- [x] Wire "forgot password" on `⚡user-login.blade.php`
- [x] Wire a reset-password row on `⚡edit-profile.blade.php` (logged-in → same flow, parent email locked)
- [x] Georgian copy in `lang/ka/password-reset.php` + mailable subject/body
- [x] Rate-limit requests and code attempts; expire codes (10 min); single-use
- [x] Invalidate other sessions on successful reset

## Notes on the OTP screen

`kidzio/otp.html` is a 4-digit pad. Parent verification already ships a **6-digit** code
(`ParentVerificationService`). **Shipped as 6**, with six `.otp-box` inputs so layout `app.js`
paste/focus handling works. Shared issue/verify lives in `VerificationCodeService` and is used by
both parent-verify and password reset.

## Code touchpoints

- `app/Services/VerificationCodeService.php` — generate / store / consume / throttle
- `app/Services/ParentVerificationService.php` — now uses the shared helper
- `app/Services/PasswordResetService.php` + `app/Repositories/UserRepository.php`
- `app/Notifications/PasswordResetNotification.php`
- `routes/web.php` — guest **and** logged-in can open the three screens (not behind `guest`)
- `resources/views/pages/⚡forgot-password.blade.php`, `⚡otp.blade.php`, `⚡reset-password.blade.php`

## Done when

- [x] A parent can reset from the login screen, receive a code at the parent email, set a new password,
      and log in.
- [x] Codes expire, are single-use, and are rate-limited.
- [x] Feature tests cover: happy path, expired code, wrong code, throttle, unknown email (no leak),
      other-session purge.
- [x] `composer test` green (132 tests, Pint, PHPStan level 7).

## Out of scope

- Changing the parent email address → **T09**
- Phone-based reset → **T22**
