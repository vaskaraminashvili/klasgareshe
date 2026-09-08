# Rails / sliders keep breaking — diagnosis playbook

The horizontal rails (Home subjects + achievements, Learn, Badges, Leaderboard, League,
Profile, Ranking filter chips) have broken four separate times, each time for a
different reason and each time reported as "the sliders stopped working".

Read this before touching the rail options — the last three fixes each repaired one
gesture and silently broke another.

---

## 1. Identify which failure this is

Open DevTools, inspect the rail container (`.swiper.subjects-swiper` on Home) and read
its class list. That single string tells you which of the three failures you have.

| Class list on the container | What it means | Go to |
|---|---|---|
| no `swiper-initialized` | Swiper never ran on this element | [A](#a-never-initialised) |
| `swiper-initialized` but nothing moves, not even touch | stale instance bound to a wrapper Livewire replaced | [B](#b-stale-instance-after-a-livewire-morph) |
| `swiper-initialized … swiper-css-mode`, touch works, mouse drag doesn't | native scroll is on; mouse drag isn't a native scroll gesture | [C](#c-cssmode-removes-every-swiper-pointer-gesture) |

Also check the console. In all three failures the console is **clean** — no exception.
A rail that is silently dead is the normal presentation, so "no errors in console" does
not mean "the JS is fine".

---

## 2. How the rails actually work

- **Markup** comes from the `kidzio/` template and is never hand-written:
  `<div class="swiper rail-swiper" data-swiper-rail>` for content rows,
  `data-swiper-rail-tabs` for filter-chip rows.
- **Init** lives in `public/assets/js/app.js`, block *"Auto-init Swiper rails"*. It is
  delegated and idempotent, and re-runs on `livewire:navigated`, `livewire:initialized`
  and `Livewire.hook('morph.updated')`.
- **Swiper itself** is `window.Swiper` / `window.SwiperModules`, exposed by
  `public/assets/js/index.js` — a prebuilt 445 KB webpack bundle from the template
  (search it for `window.Swiper =`). Swiper is **not** an npm dependency; there is
  nothing swiper-related in `package.json`, so `npm install` and Vite are irrelevant
  to this bug.
- **`cssMode: true` is load-bearing.** Rails scroll through native `overflow-x` on
  `.swiper-wrapper` rather than JS transforms, which is the only reason they survive a
  Livewire morph. Do not remove it to "fix" a gesture.
- **`touch-action: pan-x` is load-bearing.** In `public/assets/css/index.css` (~line
  3282). Swiper's own `.swiper-horizontal` sets `touch-action: pan-y`, which blocks the
  native horizontal scroll on touch. Removing this rule kills touch swiping.

### Gesture support matrix (measured in headless Chrome, not assumed)

| Gesture | Handled by | Works |
|---|---|---|
| Touch swipe (phone) | native `overflow-x` | yes |
| Trackpad two-finger horizontal (`deltaX`) | native | yes |
| Shift + wheel | native | yes |
| Plain vertical wheel over a rail | nothing | **no, deliberately** — mapping `deltaY` to horizontal steals page scroll |
| Mouse drag | the drag shim in `app.js` | yes, since the fix below |

---

## 3. The three failures

### A. Never initialised

`window.Swiper` was not defined yet when `initRailSwipers()` ran, or init was never
re-run after a `wire:navigate` body swap.

Already handled: `initRailSwipers` retries on `requestAnimationFrame` for up to 60
frames while `window.Swiper` is missing, and re-runs on the three Livewire events.

Check that the layout still loads `index.js` **before** `app.js`, both with
`data-navigate-once`, in `resources/views/layouts/app.blade.php`.

### B. Stale instance after a Livewire morph

A component re-render replaces `.swiper-wrapper`, so the live Swiper instance points at
a detached node. The container still carries `swiper-initialized`, so a naive
`if (el.swiper) return;` guard skips re-init forever and the rail is dead.

Already handled by `railIsAlive()` in `app.js`, which verifies
`el.swiper.wrapperEl === el.querySelector('.swiper-wrapper')` and that the wrapper is
still connected, then destroys and remounts if not.

### C. cssMode removes every Swiper pointer gesture

**This is the one that hit on 8 Sep 2026 and the one most likely to recur.**

Swiper skips all of its own touch/pointer handlers when `cssMode` is on — literally
`if (swiper.params.cssMode) return;` at the top of `onTouchStart` / `onTouchMove` /
`onTouchEnd`. Scrolling is then entirely the browser's job, and a **mouse drag is not a
native scroll gesture**, so on desktop nothing moves at all.

It looks especially broken because `grabCursor: true` still renders a grab hand,
promising a drag that does nothing.

Fixed by the *"Mouse drag-to-scroll for cssMode rails"* block in `app.js`: a delegated,
mouse-only shim that drives `scrollLeft` directly, with a 6px threshold and a
capture-phase click swallow so `wire:navigate` tiles only fire on a real tap.

---

## 4. Why it keeps regressing

| Commit | Change | What it fixed | What it broke |
|---|---|---|---|
| `e709675` | init on `DOMContentLoaded` only | first working rails | died on every `wire:navigate` |
| `34159e8` | destroy on `livewire:navigating`, re-init on `livewire:navigated` | navigation | still died on in-place morphs |
| `8b5ff11` "fix js" | `cssMode: true` + `railIsAlive` + `morph.updated` hook | morphs, for good | mouse drag, silently |
| (this fix) | mouse drag-to-scroll shim | mouse drag | — |

**Rule of thumb:** any change to the rail options must be re-checked against the whole
gesture matrix above, not just the gesture being fixed. Each of these looked correct in
code review and each shipped a dead rail.

---

## 5. Diagnosing it in five minutes

These are runtime gesture bugs. Reading the code will not tell you which one you have —
drive a real browser. The two things that cost the most time are getting a session and
getting the coordinates right, so both are written out here.

### Mint a session cookie

Every rail page is behind `auth:web` + `RedirectToKidSetup`, so an unauthenticated
request just redirects to `/login`.

Herd's `php.bat` is broken in a plain shell (it prepends a missing `dump-loader.php`).
Use the raw binary and clear the prepend:

```powershell
& "C:\Users\vaska\.config\herd\bin\php84\php.exe" -d auto_prepend_file= _probe.php
```

```php
<?php // _probe.php — throwaway, delete after use
require __DIR__.'/vendor/autoload.php';
$app = require_once __DIR__.'/bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use App\Models\User;
use Illuminate\Cookie\CookieValuePrefix;
use Illuminate\Support\Str;

// A user past onboarding AND parent verification. Note the column is
// email_verified_at — there is no parent_verified_at column.
$user = User::query()
    ->whereNotNull('onboarding_completed_at')
    ->whereNotNull('email_verified_at')
    ->first() ?? User::query()->first();

$session = app('session.store');
$session->setId(Str::random(40));
$session->start();
$session->put(app('auth')->guard('web')->getName(), $user->id);
$session->put('password_hash_web', $user->getAuthPassword());
$session->save();

$name = config('session.cookie');
$encrypter = app('encrypter');
echo 'COOKIE='.$name.'='.$encrypter->encrypt(
    CookieValuePrefix::create($name, $encrypter->getKey()).$session->getId(), false
).PHP_EOL;
```

Feed it to `Network.setCookie` (domain `klasgareshe.test`) or
`curl -H "Cookie: <name>=<value>"`.

### Drive Chrome over CDP

No Playwright needed — Node 25 has a global `WebSocket`, and Chrome is at
`C:\Program Files\Google\Chrome\Application\chrome.exe`. Launch with
`--headless=new --remote-debugging-port=9333 --user-data-dir=<temp>`, read
`http://127.0.0.1:9333/json/version` for the socket URL, then `Target.attachToTarget`
with `flatten: true`.

What to assert:

```js
// state
const w = document.querySelector('.swiper[data-swiper-rail] .swiper-wrapper');
({ cls: w.parentElement.className, scrollW: w.scrollWidth, clientW: w.clientWidth });
// scrollW > clientW means the rail *can* scroll — if it can and doesn't, it's failure C
```

Then dispatch a drag with `Input.dispatchMouseEvent`
(`mousePressed` → 12 × `mouseMoved` with `buttons: 1` → `mouseReleased`) and read
`w.scrollLeft`.

**Gotcha that produced a false negative:** scroll the rail into view first
(`w.scrollIntoView({ block: 'center' })`) before reading its bounding rect. The Home
subject rail sits below a 900px viewport, so synthetic clicks at its "centre" land on
empty space and every gesture reads as broken even after the fix works.

Regressions worth asserting after any rail change:

1. Plain tap on a slide still produces a click with the right `href`.
2. Tap with ~3px of jitter still produces a click (must stay under the drag threshold).
3. A real drag scrolls **and** swallows the trailing click, so the page does not navigate.
4. The tap immediately after a drag navigates again (no sticky click suppression).
5. Touch swipe still scrolls.
6. On `/badges`, tapping a filter chip still applies `chip-primary`, and dragging the
   chip row scrolls without selecting a chip.
