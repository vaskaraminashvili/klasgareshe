<?php

use App\Repositories\UserRepository;
use App\Services\PasswordResetService;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Js;
use Livewire\Component;

new class extends Component
{
    public string $email = '';

    public bool $emailLocked = false;

    public function title(): string
    {
        return __('password-reset.page_title');
    }

    public function rendering(\Illuminate\View\View $view): void
    {
        $view->title($this->title());
    }

    public function mount(UserRepository $users): void
    {
        if (! Auth::check()) {
            return;
        }

        $this->email = $users->authenticated()->email;
        $this->emailLocked = true;
    }

    public function send(PasswordResetService $resets): void
    {
        $this->validate([
            'email' => 'required|email',
        ]);

        $sent = $resets->request($this->email, request()->ip());

        if (! $sent) {
            $this->toast(__('password-reset.wait_resend'));

            return;
        }

        $this->toast(__('password-reset.sent_toast'));
        $this->redirectRoute('otp', navigate: true);
    }

    private function toast(string $message): void
    {
        $this->js('toast('.Js::from($message).')');
    }
};
?>

<main class="device-frame min-h-screen flex flex-col safe-top">
      <header class="appbar">
        <a href="{{ route('user-login') }}" class="icon-btn" data-back><i class="ph ph-caret-left"></i></a>
        <h1 class="h-display text-lg grow">{{ __('password-reset.heading') }}</h1>
      </header>

      <section class="px-6 pt-4">
        <div class="mx-auto size-32 rounded-[28px] tile-coral grid place-items-center mb-4">
          <i class="ph-fill ph-key text-5xl text-white"></i>
        </div>
        <h2 class="h-display text-2xl">{{ __('password-reset.reset_heading') }}</h2>
        <p class="text-sm mt-1" style="color: var(--color-k-muted)">{{ __('password-reset.subtitle') }}</p>
      </section>

      <form
        class="px-6 mt-6 space-y-3"
        wire:submit="send"
      >
        <div class="input-wrap">
          <i class="ph ph-envelope-simple i-left"></i>
          <input type="email" class="input has-left" placeholder="{{ __('password-reset.email_placeholder') }}" required wire:model="email" @disabled($emailLocked) />
        </div>
        @error('email')
            <p class="text-sm" style="color:var(--color-k-coral)">{{ $message }}</p>
        @enderror
        <button class="btn btn-primary w-full">{{ __('password-reset.send') }}</button>
      </form>

      <div class="px-6 mt-6">
        <div class="k-card flex items-start gap-3 text-xs" style="color: var(--color-k-muted)">
          <i class="ph-fill ph-info text-xl" style="color: var(--color-k-primary)"></i>
          <span>{{ __('password-reset.safety_note') }}</span>
        </div>
      </div>

      <p class="mt-auto pb-8 pt-8 text-center text-sm safe-bottom" style="color: var(--color-k-muted)">{{ __('password-reset.remembered') }} <a href="{{ route('user-login') }}" class="font-extrabold" style="color: var(--color-k-primary)" wire:navigate>{{ __('password-reset.log_in') }}</a></p>
    </main>
