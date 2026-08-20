<?php

use App\Repositories\UserRepository;
use App\Services\ParentVerificationService;
use Illuminate\Support\Js;
use Livewire\Component;

new class extends Component
{
    public string $email = '';

    public function title(): string
    {
        return __('parent-verify.page_title');
    }

    public function rendering(\Illuminate\View\View $view): void
    {
        $view->title($this->title());
    }

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
            $this->addError('code', __('parent-verify.code_invalid'));
            $this->toast(__('parent-verify.code_invalid'));

            return;
        }

        $this->redirectRoute('home', navigate: true);
    }

    public function resend(ParentVerificationService $verification, UserRepository $users): void
    {
        if (! $verification->resend($users->authenticated())) {
            $this->toast(__('parent-verify.wait_resend'));

            return;
        }

        $this->toast(__('parent-verify.link_resent'));
    }

    public function resendCode(ParentVerificationService $verification, UserRepository $users): void
    {
        if (! $verification->resend($users->authenticated())) {
            $this->toast(__('parent-verify.wait_resend'));

            return;
        }

        $this->toast(__('parent-verify.code_resent'));
    }

    public function acknowledge(UserRepository $users): void
    {
        if ($users->authenticated()->hasVerifiedEmail()) {
            $this->redirectRoute('home', navigate: true);

            return;
        }

        $this->toast(__('parent-verify.not_verified_yet'));
    }

    private function toast(string $message): void
    {
        $this->js('toast('.Js::from($message).')');
    }
};
?>

<main class="device-frame min-h-screen flex flex-col safe-top">

  <header class="appbar">
    <a href="{{ route('onboarding-notifications') }}" class="icon-btn" data-back aria-label="{{ __('parent-verify.back') }}"><i class="ph ph-caret-left"></i></a>
    <div class="grow">
      <p class="text-xs text-muted">{{ __('parent-verify.step_of', ['current' => 2, 'total' => 3]) }}</p>
      <h1 class="h-display text-lg leading-tight">{{ __('parent-verify.heading') }}</h1>
    </div>
    <button class="icon-btn" data-theme-toggle aria-label="{{ __('parent-verify.toggle_theme') }}"><i class="ph ph-moon text-xl"></i></button>
  </header>

  <!-- =============== PROGRESS =============== -->
  <section class="px-5">
    <div class="flex items-center gap-2">
      <span class="h-1.5 rounded-full bg-[var(--color-k-primary)] grow"></span>
      <span class="h-1.5 rounded-full bg-[var(--color-k-primary)] grow"></span>
      <span class="h-1.5 rounded-full bg-[var(--color-k-border)] grow"></span>
    </div>
    <p class="text-[11px] text-muted mt-2">{{ __('parent-verify.sign_up') }} → <span class="font-extrabold text-ink">{{ __('parent-verify.verify') }}</span> → {{ __('parent-verify.create_kid_profile') }}</p>
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
        <i class="ph-fill ph-lock-key"></i> {{ __('parent-verify.coppa_badge') }}
      </p>
      <p class="relative h-display text-2xl mt-2 leading-tight">{{ __('parent-verify.hero_title') }}</p>
      <p class="relative text-xs text-white/90 mt-1 max-w-[280px] mx-auto">
        {{ __('parent-verify.sent_to') }} <span class="font-extrabold">{{ $email }}</span>
      </p>
      <div class="relative mt-4 flex items-center justify-center gap-2 flex-wrap">
        <span class="chip bg-white/20 border-0 text-white"><i class="ph-fill ph-envelope-simple"></i> {{ __('parent-verify.email_link') }}</span>
        <span class="chip bg-white/20 border-0 text-white"><i class="ph-fill ph-password"></i> {{ __('parent-verify.six_digit_code') }}</span>
      </div>
    </div>
  </section>

  <!-- =============== METHOD TABS =============== -->
  <section class="px-5 mt-5">
    <p class="section-label">{{ __('parent-verify.choose_method') }}</p>
    <div class="mt-3 grid grid-cols-2 gap-2" id="methodPicker">
      <button type="button" class="pick-card !p-3 flex-col text-center is-selected" data-method="link">
        <span class="text-3xl">📩</span>
        <span class="pc-name mt-1">{{ __('parent-verify.email_link') }}</span>
        <span class="pc-sub">{{ __('parent-verify.fastest') }}</span>
      </button>
      <button type="button" class="pick-card !p-3 flex-col text-center" data-method="code">
        <span class="text-3xl">🔢</span>
        <span class="pc-name mt-1">{{ __('parent-verify.enter_code') }}</span>
        <span class="pc-sub">{{ __('parent-verify.six_digits') }}</span>
      </button>
    </div>
  </section>

  <!-- =============== LINK PANEL =============== -->
  <section class="px-5 mt-5" data-panel="link">
    <div class="k-card p-4 flex items-start gap-3">
      <div class="size-10 rounded-xl tile-mint grid place-items-center text-lg shrink-0">📩</div>
      <div class="grow min-w-0">
        <p class="font-extrabold text-sm text-ink">{{ __('parent-verify.check_inbox') }}</p>
        <p class="text-[11px] text-muted mt-0.5">{{ __('parent-verify.sent_just_now', ['email' => $email]) }}</p>
      </div>
      <span class="chip chip-mint"><span class="live-dot"></span> {{ __('parent-verify.sent') }}</span>
    </div>

    <p class="section-label mt-5">{{ __('parent-verify.quick_open') }}</p>
    <div class="mt-3 grid grid-cols-4 gap-2">
      <button type="button" class="k-card p-3 text-center" onclick="toast(@js(__('parent-verify.opening_gmail')))">
        <div class="size-10 mx-auto rounded-xl tile-coral grid place-items-center text-xl">✉️</div>
        <p class="text-[11px] font-extrabold text-ink mt-2 leading-tight">Gmail</p>
      </button>
      <button type="button" class="k-card p-3 text-center" onclick="toast(@js(__('parent-verify.opening_outlook')))">
        <div class="size-10 mx-auto rounded-xl tile-sky grid place-items-center text-xl">📧</div>
        <p class="text-[11px] font-extrabold text-ink mt-2 leading-tight">Outlook</p>
      </button>
      <button type="button" class="k-card p-3 text-center" onclick="toast(@js(__('parent-verify.opening_apple_mail')))">
        <div class="size-10 mx-auto rounded-xl tile-violet grid place-items-center text-xl">🍎</div>
        <p class="text-[11px] font-extrabold text-ink mt-2 leading-tight">Apple Mail</p>
      </button>
      <button type="button" class="k-card p-3 text-center" onclick="toast(@js(__('parent-verify.opening_yahoo')))">
        <div class="size-10 mx-auto rounded-xl tile-sun grid place-items-center text-xl">💌</div>
        <p class="text-[11px] font-extrabold text-ink mt-2 leading-tight">Yahoo</p>
      </button>
    </div>

    <div class="mt-5 space-y-2">
      <button type="button" class="btn btn-primary w-full" wire:click="acknowledge">
        <i class="ph-fill ph-check-circle"></i> {{ __('parent-verify.ive_verified') }}
      </button>
      <button type="button" id="resendLink" class="btn btn-ghost w-full" wire:click="resend">
        <i class="ph ph-arrow-clockwise"></i> {{ __('parent-verify.resend_link') }} <span id="resendTimer" class="text-muted"></span>
      </button>
    </div>
  </section>

  <!-- =============== CODE PANEL =============== -->
  <section class="px-5 mt-5 hidden" data-panel="code">
    <p class="section-label">{{ __('parent-verify.enter_six_digit') }}</p>
    <div class="mt-3 flex items-center justify-between gap-2" id="otpGroup" wire:ignore>
      <input type="text" inputmode="numeric" maxlength="1" class="otp-box input text-center text-2xl font-extrabold" aria-label="{{ __('parent-verify.digit', ['n' => 1]) }}"/>
      <input type="text" inputmode="numeric" maxlength="1" class="otp-box input text-center text-2xl font-extrabold" aria-label="{{ __('parent-verify.digit', ['n' => 2]) }}"/>
      <input type="text" inputmode="numeric" maxlength="1" class="otp-box input text-center text-2xl font-extrabold" aria-label="{{ __('parent-verify.digit', ['n' => 3]) }}"/>
      <input type="text" inputmode="numeric" maxlength="1" class="otp-box input text-center text-2xl font-extrabold" aria-label="{{ __('parent-verify.digit', ['n' => 4]) }}"/>
      <input type="text" inputmode="numeric" maxlength="1" class="otp-box input text-center text-2xl font-extrabold" aria-label="{{ __('parent-verify.digit', ['n' => 5]) }}"/>
      <input type="text" inputmode="numeric" maxlength="1" class="otp-box input text-center text-2xl font-extrabold" aria-label="{{ __('parent-verify.digit', ['n' => 6]) }}"/>
    </div>
    <p id="otpHint" class="text-[11px] text-muted text-center mt-3" data-hint-empty="{{ __('parent-verify.otp_tip') }}" data-hint-ready="{{ __('parent-verify.otp_ready') }}">{{ __('parent-verify.otp_tip') }}</p>
    @error('code')
      <p class="text-sm text-center mt-2" style="color:var(--color-k-coral)">{{ $message }}</p>
    @enderror

    <div class="mt-5 space-y-2">
      <button type="button" id="verifyBtn" class="btn btn-primary w-full" disabled>
        <i class="ph-fill ph-check-circle"></i> {{ __('parent-verify.verify_continue') }}
      </button>
      <button type="button" class="btn btn-ghost w-full" wire:click="resendCode">
        <i class="ph ph-arrow-clockwise"></i> {{ __('parent-verify.resend_code') }}
      </button>
    </div>
  </section>

  <!-- =============== HOW IT WORKS =============== -->
  <section class="px-5 mt-6">
    <p class="section-label">{{ __('parent-verify.how_it_works') }}</p>
    <div class="mt-3 space-y-2">
      <div class="k-card p-4 flex items-start gap-3">
        <div class="size-10 rounded-xl tile-sky grid place-items-center text-base shrink-0 font-extrabold text-sky-ink">1</div>
        <div class="grow">
          <p class="font-extrabold text-sm text-ink">{{ __('parent-verify.step1_title') }}</p>
          <p class="text-[11px] text-muted mt-0.5">{{ __('parent-verify.step1_sub') }}</p>
        </div>
      </div>
      <div class="k-card p-4 flex items-start gap-3">
        <div class="size-10 rounded-xl tile-mint grid place-items-center text-base shrink-0 font-extrabold text-mint-ink">2</div>
        <div class="grow">
          <p class="font-extrabold text-sm text-ink">{{ __('parent-verify.step2_title') }}</p>
          <p class="text-[11px] text-muted mt-0.5">{{ __('parent-verify.step2_sub') }}</p>
        </div>
      </div>
      <div class="k-card p-4 flex items-start gap-3">
        <div class="size-10 rounded-xl tile-violet grid place-items-center text-base shrink-0 font-extrabold">3</div>
        <div class="grow">
          <p class="font-extrabold text-sm text-ink">{{ __('parent-verify.step3_title') }}</p>
          <p class="text-[11px] text-muted mt-0.5">{{ __('parent-verify.step3_sub') }}</p>
        </div>
      </div>
    </div>
  </section>

  <!-- =============== WHY WE ASK =============== -->
  <section class="px-5 mt-5">
    <p class="section-label">{{ __('parent-verify.why_we_ask') }}</p>
    <div class="mt-3 grid grid-cols-3 gap-2">
      <div class="k-card p-3 text-center">
        <div class="size-10 mx-auto rounded-xl tile-sky grid place-items-center text-base">🇺🇸</div>
        <p class="text-[11px] font-extrabold text-ink mt-2 leading-tight">{{ __('parent-verify.coppa') }}</p>
        <p class="text-[10px] text-muted leading-tight">{{ __('parent-verify.coppa_sub') }}</p>
      </div>
      <div class="k-card p-3 text-center">
        <div class="size-10 mx-auto rounded-xl tile-mint grid place-items-center text-base">🇪🇺</div>
        <p class="text-[11px] font-extrabold text-ink mt-2 leading-tight">{{ __('parent-verify.gdprk') }}</p>
        <p class="text-[10px] text-muted leading-tight">{{ __('parent-verify.gdprk_sub') }}</p>
      </div>
      <div class="k-card p-3 text-center">
        <div class="size-10 mx-auto rounded-xl tile-violet grid place-items-center text-base">🛡️</div>
        <p class="text-[11px] font-extrabold text-ink mt-2 leading-tight">{{ __('parent-verify.no_ads') }}</p>
        <p class="text-[10px] text-muted leading-tight">{{ __('parent-verify.no_ads_sub') }}</p>
      </div>
    </div>
  </section>

  <!-- =============== FOOTER =============== -->
  <section class="px-5 mt-5 mb-10">
    <div class="tip-card rounded-2xl p-4 flex items-start gap-3">
      <div class="mascot shrink-0 size-11 text-xl">🦉</div>
      <div class="grow">
        <p class="font-extrabold text-sm text-ink">{{ __('parent-verify.privacy_title') }}</p>
        <p class="text-xs text-muted">{{ __('parent-verify.privacy_body') }}</p>
      </div>
    </div>
    <div class="mt-4 flex items-center justify-center gap-2 text-[11px] text-muted flex-wrap">
      <span>{{ __('parent-verify.wrong_email') }}</span>
      <a href="#" class="chip chip-primary"><i class="ph ph-pencil-simple"></i> {{ __('parent-verify.change_it') }}</a>
      <span>·</span>
      <a href="#" class="chip"><i class="ph ph-question"></i> {{ __('parent-verify.get_help') }}</a>
    </div>
  </section>
</main>
