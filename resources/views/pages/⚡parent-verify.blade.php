<?php

use App\Repositories\UserRepository;
use App\Services\ParentVerificationService;
use Livewire\Attributes\Title;
use Livewire\Component;

new #[Title('Parent verification · Kidzio')] class extends Component
{
    public string $email = '';

    public function mount(ParentVerificationService $verification, UserRepository $users): void
    {
        $user = $users->authenticated();
        $this->email = $user->email;
        $verification->send($user);
    }

    public function verifyCode(string $code, ParentVerificationService $verification, UserRepository $users): void
    {
        $digits = preg_replace('/\D/', '', $code) ?? '';

        if (! $verification->verifyCode($users->authenticated(), $digits)) {
            $this->addError('code', 'That code is invalid or expired.');
            $this->js('toast("That code is invalid or expired.")');

            return;
        }

        $this->redirectRoute('home', navigate: true);
    }

    public function resend(ParentVerificationService $verification, UserRepository $users): void
    {
        if (! $verification->resend($users->authenticated())) {
            $this->js('toast("Please wait before resending.")');

            return;
        }

        $this->js('toast("Link resent")');
    }

    public function resendCode(ParentVerificationService $verification, UserRepository $users): void
    {
        if (! $verification->resend($users->authenticated())) {
            $this->js('toast("Please wait before resending.")');

            return;
        }

        $this->js('toast("Code resent")');
    }

    public function acknowledge(UserRepository $users): void
    {
        if ($users->authenticated()->hasVerifiedEmail()) {
            $this->redirectRoute('home', navigate: true);

            return;
        }

        $this->js('toast("Not verified yet — use the link or code")');
    }
};
?>

<main class="device-frame min-h-screen flex flex-col safe-top">

  <header class="appbar">
    <a href="{{ route('onboarding-notifications') }}" class="icon-btn" data-back aria-label="Back"><i class="ph ph-caret-left"></i></a>
    <div class="grow">
      <p class="text-xs text-muted">Step 2 of 3</p>
      <h1 class="h-display text-lg leading-tight">Parent verification</h1>
    </div>
    <button class="icon-btn" data-theme-toggle aria-label="Toggle theme"><i class="ph ph-moon text-xl"></i></button>
  </header>

  <!-- =============== PROGRESS =============== -->
  <section class="px-5">
    <div class="flex items-center gap-2">
      <span class="h-1.5 rounded-full bg-[var(--color-k-primary)] grow"></span>
      <span class="h-1.5 rounded-full bg-[var(--color-k-primary)] grow"></span>
      <span class="h-1.5 rounded-full bg-[var(--color-k-border)] grow"></span>
    </div>
    <p class="text-[11px] text-muted mt-2">Sign up → <span class="font-extrabold text-ink">Verify</span> → Create kid profile</p>
  </section>

  <!-- =============== HERO =============== -->
  <section class="px-5 mt-4">
    <div class="k-card-lg hero-profile text-center">
      <div class="relative inline-grid place-items-center">
        <div class="size-24 rounded-3xl bg-white/20 backdrop-blur-sm grid place-items-center text-5xl">
          <i class="ph-fill ph-shield-check text-white"></i>
        </div>
      </div>
      <p class="relative chip bg-white/20 border-0 text-white mt-4">
        <i class="ph-fill ph-lock-key"></i> COPPA · PARENT ONLY
      </p>
      <p class="relative h-display text-2xl mt-2 leading-tight">Just to keep kids safe</p>
      <p class="relative text-xs text-white/90 mt-1 max-w-[280px] mx-auto">
        We sent a link and a 6-digit code to <span class="font-extrabold">{{ $email }}</span>
      </p>
      <div class="relative mt-4 flex items-center justify-center gap-2 flex-wrap">
        <span class="chip bg-white/20 border-0 text-white"><i class="ph-fill ph-envelope-simple"></i> Email link</span>
        <span class="chip bg-white/20 border-0 text-white"><i class="ph-fill ph-password"></i> 6-digit code</span>
      </div>
    </div>
  </section>

  <!-- =============== METHOD TABS =============== -->
  <section class="px-5 mt-5">
    <p class="section-label">Choose how to verify</p>
    <div class="mt-3 grid grid-cols-2 gap-2" id="methodPicker">
      <button type="button" class="pick-card !p-3 flex-col text-center is-selected" data-method="link">
        <span class="text-3xl">📩</span>
        <span class="pc-name mt-1">Email link</span>
        <span class="pc-sub">Fastest</span>
      </button>
      <button type="button" class="pick-card !p-3 flex-col text-center" data-method="code">
        <span class="text-3xl">🔢</span>
        <span class="pc-name mt-1">Enter code</span>
        <span class="pc-sub">6 digits</span>
      </button>
    </div>
  </section>

  <!-- =============== LINK PANEL =============== -->
  <section class="px-5 mt-5" data-panel="link">
    <div class="k-card p-4 flex items-start gap-3">
      <div class="size-10 rounded-xl tile-mint grid place-items-center text-lg shrink-0">📩</div>
      <div class="grow min-w-0">
        <p class="font-extrabold text-sm text-ink">Check your inbox</p>
        <p class="text-[11px] text-muted mt-0.5">Sent to <span class="font-extrabold text-ink">{{ $email }}</span> just now</p>
      </div>
      <span class="chip chip-mint"><span class="live-dot"></span> Sent</span>
    </div>

    <p class="section-label mt-5">Quick open</p>
    <div class="mt-3 grid grid-cols-4 gap-2">
      <button type="button" class="k-card p-3 text-center" onclick="toast('Opening Gmail…')">
        <div class="size-10 mx-auto rounded-xl tile-coral grid place-items-center text-xl">✉️</div>
        <p class="text-[11px] font-extrabold text-ink mt-2 leading-tight">Gmail</p>
      </button>
      <button type="button" class="k-card p-3 text-center" onclick="toast('Opening Outlook…')">
        <div class="size-10 mx-auto rounded-xl tile-sky grid place-items-center text-xl">📧</div>
        <p class="text-[11px] font-extrabold text-ink mt-2 leading-tight">Outlook</p>
      </button>
      <button type="button" class="k-card p-3 text-center" onclick="toast('Opening Apple Mail…')">
        <div class="size-10 mx-auto rounded-xl tile-violet grid place-items-center text-xl">🍎</div>
        <p class="text-[11px] font-extrabold text-ink mt-2 leading-tight">Apple Mail</p>
      </button>
      <button type="button" class="k-card p-3 text-center" onclick="toast('Opening Yahoo…')">
        <div class="size-10 mx-auto rounded-xl tile-sun grid place-items-center text-xl">💌</div>
        <p class="text-[11px] font-extrabold text-ink mt-2 leading-tight">Yahoo</p>
      </button>
    </div>

    <div class="mt-5 space-y-2">
      <button type="button" class="btn btn-primary w-full" wire:click="acknowledge">
        <i class="ph-fill ph-check-circle"></i> I've verified
      </button>
      <button type="button" id="resendLink" class="btn btn-ghost w-full" wire:click="resend">
        <i class="ph ph-arrow-clockwise"></i> Resend link <span id="resendTimer" class="text-muted"></span>
      </button>
    </div>
  </section>

  <!-- =============== CODE PANEL =============== -->
  <section class="px-5 mt-5 hidden" data-panel="code">
    <p class="section-label">Enter the 6-digit code</p>
    <div class="mt-3 flex items-center justify-between gap-2" id="otpGroup" wire:ignore>
      <input type="text" inputmode="numeric" maxlength="1" class="otp-box input text-center text-2xl font-extrabold" aria-label="Digit 1"/>
      <input type="text" inputmode="numeric" maxlength="1" class="otp-box input text-center text-2xl font-extrabold" aria-label="Digit 2"/>
      <input type="text" inputmode="numeric" maxlength="1" class="otp-box input text-center text-2xl font-extrabold" aria-label="Digit 3"/>
      <input type="text" inputmode="numeric" maxlength="1" class="otp-box input text-center text-2xl font-extrabold" aria-label="Digit 4"/>
      <input type="text" inputmode="numeric" maxlength="1" class="otp-box input text-center text-2xl font-extrabold" aria-label="Digit 5"/>
      <input type="text" inputmode="numeric" maxlength="1" class="otp-box input text-center text-2xl font-extrabold" aria-label="Digit 6"/>
    </div>
    <p id="otpHint" class="text-[11px] text-muted text-center mt-3">Tip: paste the code and it auto-fills.</p>
    @error('code')
      <p class="text-sm text-center mt-2" style="color:var(--color-k-coral)">{{ $message }}</p>
    @enderror

    <div class="mt-5 space-y-2">
      <button type="button" id="verifyBtn" class="btn btn-primary w-full" disabled>
        <i class="ph-fill ph-check-circle"></i> Verify &amp; continue
      </button>
      <button type="button" class="btn btn-ghost w-full" wire:click="resendCode">
        <i class="ph ph-arrow-clockwise"></i> Resend code
      </button>
    </div>
  </section>

  <!-- =============== HOW IT WORKS =============== -->
  <section class="px-5 mt-6">
    <p class="section-label">How it works</p>
    <div class="mt-3 space-y-2">
      <div class="k-card p-4 flex items-start gap-3">
        <div class="size-10 rounded-xl tile-sky grid place-items-center text-base shrink-0 font-extrabold text-sky-ink">1</div>
        <div class="grow">
          <p class="font-extrabold text-sm text-ink">Open the email we sent</p>
          <p class="text-[11px] text-muted mt-0.5">Arrives within 30 seconds · check spam if missing</p>
        </div>
      </div>
      <div class="k-card p-4 flex items-start gap-3">
        <div class="size-10 rounded-xl tile-mint grid place-items-center text-base shrink-0 font-extrabold text-mint-ink">2</div>
        <div class="grow">
          <p class="font-extrabold text-sm text-ink">Tap the magic link or copy the code</p>
          <p class="text-[11px] text-muted mt-0.5">Link expires in 10 minutes for safety</p>
        </div>
      </div>
      <div class="k-card p-4 flex items-start gap-3">
        <div class="size-10 rounded-xl tile-violet grid place-items-center text-base shrink-0 font-extrabold">3</div>
        <div class="grow">
          <p class="font-extrabold text-sm text-ink">Come back here to continue</p>
          <p class="text-[11px] text-muted mt-0.5">Your kid's profile is set up next</p>
        </div>
      </div>
    </div>
  </section>

  <!-- =============== WHY WE ASK =============== -->
  <section class="px-5 mt-5">
    <p class="section-label">Why we ask</p>
    <div class="mt-3 grid grid-cols-3 gap-2">
      <div class="k-card p-3 text-center">
        <div class="size-10 mx-auto rounded-xl tile-sky grid place-items-center text-base">🇺🇸</div>
        <p class="text-[11px] font-extrabold text-ink mt-2 leading-tight">COPPA</p>
        <p class="text-[10px] text-muted leading-tight">US law for under-13</p>
      </div>
      <div class="k-card p-3 text-center">
        <div class="size-10 mx-auto rounded-xl tile-mint grid place-items-center text-base">🇪🇺</div>
        <p class="text-[11px] font-extrabold text-ink mt-2 leading-tight">GDPR-K</p>
        <p class="text-[10px] text-muted leading-tight">EU child privacy</p>
      </div>
      <div class="k-card p-3 text-center">
        <div class="size-10 mx-auto rounded-xl tile-violet grid place-items-center text-base">🛡️</div>
        <p class="text-[11px] font-extrabold text-ink mt-2 leading-tight">No ads</p>
        <p class="text-[10px] text-muted leading-tight">Ever · promise</p>
      </div>
    </div>
  </section>

  <!-- =============== FOOTER =============== -->
  <section class="px-5 mt-5 mb-10">
    <div class="tip-card rounded-2xl p-4 flex items-start gap-3">
      <div class="mascot shrink-0 size-11 text-xl">🦉</div>
      <div class="grow">
        <p class="font-extrabold text-sm text-ink">Privacy by design</p>
        <p class="text-xs text-muted">Email is used only for verification &amp; weekly reports. Never shared, never sold.</p>
      </div>
    </div>
    <div class="mt-4 flex items-center justify-center gap-2 text-[11px] text-muted flex-wrap">
      <span>Wrong email?</span>
      <a href="#" class="chip chip-primary"><i class="ph ph-pencil-simple"></i> Change it</a>
      <span>·</span>
      <a href="#" class="chip"><i class="ph ph-question"></i> Get help</a>
    </div>
  </section>
</main>
