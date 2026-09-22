<?php

use App\Repositories\UserRepository;
use App\Repositories\UserStatRepository;
use App\Services\AccountService;
use App\Services\ProgressReportService;
use Illuminate\Support\Js;
use Livewire\Component;

new class extends Component
{
    public string $email = '';

    public string $pendingEmail = '';

    public bool $emailVerified = false;

    public string $verifiedLabel = '';

    public int $weekXp = 0;

    public int $weekActiveDays = 0;

    public int $streak = 0;

    public bool $weeklyEmail = true;

    public string $newEmail = '';

    public string $confirmEmail = '';

    public string $currentPin = '';

    public function title(): string
    {
        return __('account.page_title');
    }

    public function rendering(\Illuminate\View\View $view): void
    {
        $view->title($this->title());
    }

    public function mount(UserRepository $users, ProgressReportService $reports, UserStatRepository $stats): void
    {
        $this->hydrateFrom($users->authenticated(), $reports, $stats);
    }

    public function getReadyProperty(): bool
    {
        return filter_var($this->newEmail, FILTER_VALIDATE_EMAIL) !== false
            && $this->newEmail === $this->confirmEmail
            && strlen($this->currentPin) === 4;
    }

    public function submit(AccountService $accounts, UserRepository $users, ProgressReportService $reports, UserStatRepository $stats): void
    {
        $accounts->requestEmailChange(
            $users->authenticated(),
            $this->newEmail,
            $this->confirmEmail,
            $this->currentPin,
            request()->ip(),
        );

        $this->newEmail = '';
        $this->confirmEmail = '';
        $this->currentPin = '';
        $this->hydrateFrom($users->findOrFail($users->authenticated()->id), $reports, $stats);
        $this->toast(__('account.link_sent'));
    }

    public function verifyCode(string $code, AccountService $accounts, UserRepository $users, ProgressReportService $reports, UserStatRepository $stats): void
    {
        $digits = preg_replace('/\D/', '', $code) ?? '';

        if (! $accounts->verifyPendingEmailCode($users->authenticated(), $digits)) {
            $this->addError('code', __('account.code_invalid'));
            $this->toast(__('account.code_invalid'));

            return;
        }

        $this->hydrateFrom($users->findOrFail($users->authenticated()->id), $reports, $stats);
        $this->toast(__('account.email_updated'));
    }

    public function resend(AccountService $accounts, UserRepository $users): void
    {
        if (! $accounts->resendPendingEmail($users->authenticated(), request()->ip())) {
            $this->toast(__('account.wait_resend'));

            return;
        }

        $this->toast(__('account.link_sent'));
    }

    public function updatedWeeklyEmail(AccountService $accounts, UserRepository $users): void
    {
        $accounts->setWeeklyEmail($users->authenticated(), $this->weeklyEmail);
        $this->toast(__('account.pref_saved'));
    }

    public function copyEmail(): void
    {
        $this->js('navigator.clipboard.writeText('.Js::from($this->email).').then(function(){ toast('.Js::from(__('account.copied')).'); })');
    }

    private function hydrateFrom(\App\Models\User $user, ProgressReportService $reports, UserStatRepository $stats): void
    {
        $week = $reports->weekSnapshot($user);
        $stat = $stats->firstOrCreateFor($user);

        $this->email = $user->email;
        $this->pendingEmail = (string) ($user->pending_parent_email ?? '');
        $this->emailVerified = $user->email_verified_at !== null;
        $this->verifiedLabel = $user->email_verified_at instanceof \Illuminate\Support\Carbon
            ? (string) __('account.verified_on', ['date' => $user->email_verified_at->timezone($user->timezone)->translatedFormat('j M Y')])
            : (string) __('account.not_verified_yet');
        $this->weekXp = $week->figures->xp;
        $this->weekActiveDays = $week->figures->activeDays;
        $this->streak = $stat->current_streak;
        $this->weeklyEmail = $reports->wantsWeeklyEmail($user);
    }

    private function toast(string $message): void
    {
        $this->js('toast('.Js::from($message).')');
    }
};
?>

<main class="device-frame min-h-screen flex flex-col safe-top">

  <header class="appbar">
    <a href="{{ route('parent-controls') }}" class="icon-btn" data-back aria-label="{{ __('account.back') }}"><i class="ph ph-caret-left"></i></a>
    <div class="grow">
      <p class="text-xs text-muted">{{ __('account.eyebrow') }}</p>
      <h1 class="h-display text-lg leading-tight">{{ __('account.heading') }}</h1>
    </div>
    <button class="icon-btn" data-theme-toggle aria-label="{{ __('account.toggle_theme') }}"><i class="ph ph-moon text-xl"></i></button>
  </header>

  <section class="px-5">
    <div class="k-card-lg hero-friends text-center">
      <div class="relative inline-grid place-items-center">
        <div class="size-24 rounded-3xl bg-white/20 backdrop-blur-sm grid place-items-center text-5xl">✉️</div>
      </div>
      <p class="relative chip bg-white/20 border-0 text-white mt-4">
        <i class="ph-fill ph-shield-check"></i> {{ $emailVerified ? __('account.verified') : __('account.unverified') }}
      </p>
      <p class="relative h-display text-2xl mt-2 leading-tight">{{ $email }}</p>
      <p class="relative text-xs text-white/90 mt-1">{{ __('account.hero_hint') }}</p>

      <div class="relative mt-4 grid grid-cols-3 gap-2">
        <div class="rounded-2xl bg-white/15 backdrop-blur-sm p-3">
          <p class="h-display text-xl leading-none">{{ $weekXp }}</p>
          <p class="text-[10px] text-white/85 mt-1">{{ __('account.stat_xp') }}</p>
        </div>
        <div class="rounded-2xl bg-white/15 backdrop-blur-sm p-3">
          <p class="h-display text-xl leading-none">{{ $weekActiveDays }}</p>
          <p class="text-[10px] text-white/85 mt-1">{{ __('account.stat_days') }}</p>
        </div>
        <div class="rounded-2xl bg-white/15 backdrop-blur-sm p-3">
          <p class="h-display text-xl leading-none">{{ $streak }}</p>
          <p class="text-[10px] text-white/85 mt-1">{{ __('account.stat_streak') }}</p>
        </div>
      </div>
    </div>
  </section>

  <section class="px-5 mt-5">
    <p class="section-label">{{ __('account.current_email') }}</p>
    <div class="k-card p-4 mt-3 flex items-center gap-3">
      <div class="size-11 rounded-2xl tile-mint grid place-items-center text-xl shrink-0">
        <i class="ph-fill ph-check-circle text-mint-ink"></i>
      </div>
      <div class="grow min-w-0">
        <p class="font-extrabold text-sm text-ink">{{ $email }}</p>
        <p class="text-[11px] text-muted">{{ $verifiedLabel }}</p>
      </div>
      <button type="button" class="chip chip-primary" wire:click="copyEmail" aria-label="{{ __('account.copy_email') }}"><i class="ph ph-copy"></i></button>
    </div>
  </section>

  <section class="px-5 mt-5">
    <p class="section-label">{{ __('account.change_email') }}</p>
    @if ($pendingEmail !== '')
      <div class="k-card p-4 mt-3">
        <p class="chip chip-sun">{{ __('account.pending_chip') }}</p>
        <p class="text-sm text-muted mt-2">{{ __('account.pending_body', ['email' => $pendingEmail]) }}</p>
        <p class="section-label mt-4">{{ __('account.enter_code') }}</p>
        <div class="mt-3 flex items-center justify-between gap-2" id="otp" wire:ignore>
          <input type="text" inputmode="numeric" maxlength="1" class="otp-box input text-center text-2xl font-extrabold" aria-label="{{ __('account.digit', ['n' => 1]) }}"/>
          <input type="text" inputmode="numeric" maxlength="1" class="otp-box input text-center text-2xl font-extrabold" aria-label="{{ __('account.digit', ['n' => 2]) }}"/>
          <input type="text" inputmode="numeric" maxlength="1" class="otp-box input text-center text-2xl font-extrabold" aria-label="{{ __('account.digit', ['n' => 3]) }}"/>
          <input type="text" inputmode="numeric" maxlength="1" class="otp-box input text-center text-2xl font-extrabold" aria-label="{{ __('account.digit', ['n' => 4]) }}"/>
          <input type="text" inputmode="numeric" maxlength="1" class="otp-box input text-center text-2xl font-extrabold" aria-label="{{ __('account.digit', ['n' => 5]) }}"/>
          <input type="text" inputmode="numeric" maxlength="1" class="otp-box input text-center text-2xl font-extrabold" aria-label="{{ __('account.digit', ['n' => 6]) }}"/>
        </div>
        <p id="otpHint" class="text-[11px] text-muted text-center mt-3" data-hint-empty="{{ __('account.otp_tip') }}" data-hint-ready="{{ __('account.otp_ready') }}">{{ __('account.otp_tip') }}</p>
        @error('code')
          <p class="text-sm text-center mt-2" style="color:var(--color-k-coral)">{{ $message }}</p>
        @enderror
        <div class="mt-5 space-y-2">
          <button type="button" id="verifyBtn" class="btn btn-primary w-full" disabled>
            <i class="ph-fill ph-check-circle"></i> {{ __('account.verify') }}
          </button>
          <button type="button" class="btn btn-ghost w-full" wire:click="resend">
            <i class="ph ph-arrow-clockwise"></i> {{ __('account.resend') }}
          </button>
        </div>
      </div>
    @else
      <form class="mt-3 space-y-3" wire:submit="submit">
        <div>
          <label for="newEmail" class="text-[11px] font-extrabold text-muted uppercase tracking-wide">{{ __('account.new_email') }}</label>
          <div class="input-wrap mt-1">
            <i class="ph ph-envelope-simple i-left"></i>
            <input id="newEmail" type="email" class="input has-left" placeholder="{{ __('account.new_email_placeholder') }}" autocomplete="email" wire:model.live="newEmail"/>
          </div>
          <p class="text-[10px] text-muted mt-1"><i class="ph ph-info"></i> {{ __('account.new_email_hint') }}</p>
          @error('newEmail')
            <p class="text-sm" style="color:var(--color-k-coral)">{{ $message }}</p>
          @enderror
        </div>

        <div>
          <label for="confirmEmail" class="text-[11px] font-extrabold text-muted uppercase tracking-wide">{{ __('account.confirm_email') }}</label>
          <div class="input-wrap mt-1">
            <i class="ph ph-envelope-simple i-left"></i>
            <input id="confirmEmail" type="email" class="input has-left" placeholder="{{ __('account.confirm_email_placeholder') }}" autocomplete="email" wire:model.live="confirmEmail"/>
          </div>
          <p class="text-[10px] text-muted mt-1"><i class="ph ph-info"></i> {{ __('account.confirm_email_hint') }}</p>
          @error('confirmEmail')
            <p class="text-sm" style="color:var(--color-k-coral)">{{ $message }}</p>
          @enderror
        </div>

        <div>
          <label for="currentPin" class="text-[11px] font-extrabold text-muted uppercase tracking-wide">{{ __('account.parent_pin') }}</label>
          <div class="input-wrap mt-1">
            <i class="ph ph-lock-key i-left"></i>
            <input id="currentPin" type="password" inputmode="numeric" maxlength="4" class="input has-left" placeholder="{{ __('account.pin_placeholder') }}" wire:model.live="currentPin"/>
          </div>
          <p class="text-[10px] text-muted mt-1"><i class="ph ph-lock"></i> {{ __('account.pin_hint') }}</p>
          @error('currentPin')
            <p class="text-sm" style="color:var(--color-k-coral)">{{ $message }}</p>
          @enderror
        </div>

        <button type="submit" class="btn btn-primary w-full" @unless($this->ready) disabled @endunless>
          <i class="ph-fill ph-paper-plane-tilt"></i> {{ __('account.send_link') }}
        </button>
      </form>
    @endif
  </section>

  <section class="px-5 mt-5">
    <p class="section-label">{{ __('account.prefs') }}</p>
    <div class="mt-3 space-y-2">
      <label class="setting-row cursor-pointer">
        <div class="setting-ico tile-sky"><i class="ph-fill ph-chart-line-up"></i></div>
        <div class="grow min-w-0">
          <p class="setting-text font-extrabold text-sm text-ink">{{ __('account.pref_weekly') }}</p>
          <p class="text-[11px] text-muted">{{ __('account.pref_weekly_hint') }}</p>
        </div>
        <span class="ks-switch"><input type="checkbox" wire:model.live="weeklyEmail"/><span class="track"></span><span class="thumb"></span></span>
      </label>
      {{-- Badge / streak / product-update emails → docs/tasks/T16-notifications.md --}}
    </div>
  </section>

  <section class="px-5 mt-5 mb-10">
    <div class="tip-card rounded-2xl p-4 flex items-start gap-3">
      <div class="mascot shrink-0 size-11 text-xl">🦉</div>
      <div class="grow">
        <p class="font-extrabold text-sm text-ink">{{ __('account.tip_title') }}</p>
        <p class="text-xs text-muted">{{ __('account.tip_body') }}</p>
      </div>
      <a href="{{ route('privacy-policy') }}" wire:navigate class="chip chip-primary">{{ __('account.policy') }}</a>
    </div>
  </section>
</main>
