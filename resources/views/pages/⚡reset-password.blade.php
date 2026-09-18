<?php

use App\Services\KidSetupService;
use App\Services\PasswordResetService;
use Illuminate\Support\Facades\Auth;
use Livewire\Component;

new class extends Component
{
    public string $password = '';

    public string $password_confirmation = '';

    public function title(): string
    {
        return __('password-reset.new_page_title');
    }

    public function rendering(\Illuminate\View\View $view): void
    {
        $view->title($this->title());
    }

    public function mount(PasswordResetService $resets): void
    {
        if (! $resets->hasVerifiedChallenge()) {
            $this->redirectRoute($resets->requestedEmail() === null ? 'forgot-password' : 'otp', navigate: true);
        }
    }

    public function save(PasswordResetService $resets, KidSetupService $setup): void
    {
        $this->validate([
            'password' => 'required|min:8|max:20|confirmed',
        ]);

        $user = $resets->applyNewPassword($this->password);

        if ($user === null) {
            $this->redirectRoute('forgot-password', navigate: true);

            return;
        }

        Auth::login($user);
        session()->regenerate();
        $resets->forgetOtherSessions($user, session()->getId());

        $this->redirectRoute($setup->nextRouteName($user), navigate: true);
    }
};
?>

<main class="device-frame min-h-screen flex flex-col safe-top">
      <header class="appbar">
        <a href="{{ route('otp') }}" class="icon-btn" data-back><i class="ph ph-caret-left"></i></a>
        <h1 class="h-display text-lg grow">{{ __('password-reset.new_heading') }}</h1>
      </header>

      <section class="px-6 pt-4">
        <div class="mx-auto size-32 rounded-[28px] tile-mint grid place-items-center mb-4">
          <i class="ph-fill ph-lock-key text-5xl text-white"></i>
        </div>
        <h2 class="h-display text-2xl">{{ __('password-reset.new_heading') }}</h2>
        <p class="text-sm mt-1" style="color: var(--color-k-muted)">{{ __('password-reset.new_subtitle') }}</p>
      </section>

      <form class="px-6 mt-6 space-y-3" wire:submit="save">
        <div class="input-wrap">
          <i class="ph ph-lock i-left"></i>
          <input id="pwd" type="password" class="input has-left has-right" placeholder="{{ __('password-reset.new_password') }}" required wire:model="password" />
          <button type="button" class="i-right" data-pwd-toggle="pwd"><i class="ph ph-eye"></i></button>
        </div>
        @error('password')
            <p class="text-sm" style="color:var(--color-k-coral)">{{ $message }}</p>
        @enderror
        <div class="input-wrap">
          <i class="ph ph-lock i-left"></i>
          <input id="pwd2" type="password" class="input has-left has-right" placeholder="{{ __('password-reset.confirm_password') }}" required wire:model="password_confirmation" />
          <button type="button" class="i-right" data-pwd-toggle="pwd2"><i class="ph ph-eye"></i></button>
        </div>
        <button class="btn btn-primary w-full">{{ __('password-reset.save_password') }}</button>
      </form>

      <div class="px-6 mt-6">
        <div class="k-card flex items-start gap-3 text-xs" style="color: var(--color-k-muted)">
          <i class="ph-fill ph-info text-xl" style="color: var(--color-k-primary)"></i>
          <span>{{ __('password-reset.new_safety_note') }}</span>
        </div>
      </div>
    </main>
