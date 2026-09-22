<?php

use App\Repositories\UserRepository;
use App\Services\AccountService;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Js;
use Livewire\Component;

new class extends Component
{
    public string $email = '';

    public string $kidName = '';

    public bool $codeSent = false;

    public function title(): string
    {
        return __('account.delete_title');
    }

    public function rendering(\Illuminate\View\View $view): void
    {
        $view->title($this->title());
    }

    public function mount(UserRepository $users): void
    {
        $user = $users->authenticated();
        $this->email = $user->email;
        $this->kidName = $user->name;
    }

    public function sendCode(AccountService $accounts, UserRepository $users): void
    {
        if (! $accounts->sendDeletionCode($users->authenticated(), request()->ip())) {
            $this->toast(__('account.wait_resend'));

            return;
        }

        $this->codeSent = true;
        $this->toast(__('account.delete_code_sent'));
    }

    public function verifyCode(string $code, AccountService $accounts, UserRepository $users): void
    {
        $digits = preg_replace('/\D/', '', $code) ?? '';
        $user = $users->authenticated();

        if (! $accounts->verifyDeletionCode($user, $digits)) {
            $this->addError('code', __('account.code_invalid'));
            $this->toast(__('account.code_invalid'));

            return;
        }

        $accounts->requestDeletion($user);
        Auth::logout();
        session()->invalidate();
        session()->regenerateToken();
        $this->redirectRoute('user-login', navigate: true);
    }

    public function resend(AccountService $accounts, UserRepository $users): void
    {
        $this->sendCode($accounts, $users);
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
      <p class="text-xs text-muted">{{ __('account.delete_eyebrow') }}</p>
      <h1 class="h-display text-lg leading-tight">{{ __('account.delete_heading') }}</h1>
    </div>
    <button class="icon-btn" data-theme-toggle aria-label="{{ __('account.toggle_theme') }}"><i class="ph ph-moon text-xl"></i></button>
  </header>

  <section class="px-5">
    <div class="k-card-lg hero-friends text-center">
      <div class="relative inline-grid place-items-center">
        <div class="size-24 rounded-3xl bg-white/20 backdrop-blur-sm grid place-items-center text-5xl">🗑️</div>
      </div>
      <p class="relative chip bg-white/20 border-0 text-white mt-4">{{ $kidName }}</p>
      <p class="relative h-display text-2xl mt-2 leading-tight">{{ __('account.delete_hero') }}</p>
      <p class="relative text-xs text-white/90 mt-1">{{ __('account.delete_hero_body') }}</p>
    </div>
  </section>

  <section class="px-5 mt-5">
    <p class="section-label">{{ __('account.delete_what') }}</p>
    <div class="mt-3 space-y-2">
      <div class="setting-row">
        <div class="setting-ico tile-violet"><i class="ph-fill ph-user"></i></div>
        <p class="setting-text font-extrabold text-sm text-ink grow">{{ __('account.delete_item_profile') }}</p>
      </div>
      <div class="setting-row">
        <div class="setting-ico tile-sun"><i class="ph-fill ph-star"></i></div>
        <p class="setting-text font-extrabold text-sm text-ink grow">{{ __('account.delete_item_xp') }}</p>
      </div>
      <div class="setting-row">
        <div class="setting-ico tile-mint"><i class="ph-fill ph-medal"></i></div>
        <p class="setting-text font-extrabold text-sm text-ink grow">{{ __('account.delete_item_badges') }}</p>
      </div>
      <div class="setting-row">
        <div class="setting-ico tile-sky"><i class="ph-fill ph-users-three"></i></div>
        <p class="setting-text font-extrabold text-sm text-ink grow">{{ __('account.delete_item_friends') }}</p>
      </div>
      <div class="setting-row">
        <div class="setting-ico tile-coral"><i class="ph-fill ph-book-open"></i></div>
        <p class="setting-text font-extrabold text-sm text-ink grow">{{ __('account.delete_item_history') }}</p>
      </div>
      <div class="setting-row">
        <div class="setting-ico tile-violet"><i class="ph-fill ph-trophy"></i></div>
        <p class="setting-text font-extrabold text-sm text-ink grow">{{ __('account.delete_item_league') }}</p>
      </div>
    </div>
    <p class="text-[11px] text-muted text-center mt-3">{{ __('account.delete_grace') }}</p>
  </section>

  <section class="px-5 mt-5 mb-10">
    @unless ($codeSent)
      <button type="button" class="btn btn-primary w-full" wire:click="sendCode">
        <i class="ph-fill ph-paper-plane-tilt"></i> {{ __('account.delete_send') }}
      </button>
    @else
      <p class="text-sm text-center text-muted">{{ __('account.delete_sent_to') }} <span class="font-extrabold text-ink">{{ $email }}</span></p>
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
      <button type="button" id="verifyBtn" class="btn btn-primary w-full mt-5" disabled>
        <i class="ph-fill ph-trash"></i> {{ __('account.delete_confirm') }}
      </button>
      <button type="button" class="btn btn-ghost w-full mt-2" wire:click="resend">
        <i class="ph ph-arrow-clockwise"></i> {{ __('account.resend') }}
      </button>
    @endunless
  </section>
</main>
