<?php

use App\Enums\PhoneCodeChannel;
use App\Repositories\UserRepository;
use App\Services\KidSetupService;
use App\Services\PhoneLoginService;
use Illuminate\Support\Facades\Auth;
use Livewire\Component;

new class extends Component
{
    public string $tab = 'email';

    public string $email = '';

    public string $password = '';

    public bool $remember = false;

    public string $phone = '';

    public string $phoneCode = '';

    public bool $codeSent = false;

    public string $deliveryNote = '';

    public bool $showFacebookNotice = false;

    public function title(): string
    {
        return __('login.page_title');
    }

    public function rendering(\Illuminate\View\View $view): void
    {
        $view->title($this->title());
    }

    public function showEmail(): void
    {
        $this->tab = 'email';
    }

    public function showPhone(): void
    {
        $this->tab = 'phone';
    }

    public function explainFacebook(): void
    {
        $this->showFacebookNotice = true;
    }

    public function login(KidSetupService $setup, UserRepository $users): void
    {
        $this->validate([
            'email' => 'required|email',
            'password' => 'required',
        ]);

        if (! Auth::attempt(['email' => $this->email, 'password' => $this->password], $this->remember)) {
            $this->addError('email', __('auth.failed'));

            return;
        }

        session()->regenerate();

        $this->redirectRoute($setup->nextRouteName($users->authenticated()), navigate: true);
    }

    public function sendPhoneCode(PhoneLoginService $phones): void
    {
        $this->validate([
            'phone' => 'required|string|max:32',
        ]);

        $channel = $phones->requestCode($this->phone, request()->ip());
        $this->phone = $phones->requestedPhone() ?? $this->phone;
        $this->codeSent = true;
        $this->deliveryNote = match ($channel) {
            PhoneCodeChannel::Mail => __('login.phone_code_sent_mail'),
            PhoneCodeChannel::Log => __('login.phone_code_sent_log'),
        };
    }

    public function verifyPhoneCode(PhoneLoginService $phones, KidSetupService $setup, UserRepository $users): void
    {
        $this->validate([
            'phoneCode' => 'required|digits:6',
        ]);

        $result = $phones->verify($this->phoneCode);

        if (! $result->valid || ($result->user === null && ! $result->needsRegistration)) {
            $this->addError('phoneCode', __('login.phone_code_invalid'));

            return;
        }

        if ($result->needsRegistration || $result->user === null) {
            $this->redirectRoute('user-register', navigate: true);

            return;
        }

        Auth::login($result->user);
        session()->regenerate();

        $this->redirectRoute($setup->nextRouteName($users->authenticated()), navigate: true);
    }
};
?>

<main class="device-frame min-h-screen flex flex-col safe-top">
    <header class="appbar">
        <a href="{{ route('home') }}" class="icon-btn" data-back><i class="ph ph-caret-left"></i></a>
        <span class="grow"></span>
        <button class="icon-btn" data-theme-toggle><i class="ph ph-moon"></i></button>
    </header>

    <section class="px-6 pt-4">
        <img src="{{ asset('assets/images/icon.png') }}" alt="Kidzio" class="mb-4 size-18 rounded-2xl" />
        <h1 class="h-display text-3xl">{{ __('login.welcome_back') }}</h1>
        <p class="text-sm mt-1" style="color:var(--color-k-muted)">{{ __('login.subtitle') }}</p>
    </section>

    <div class="px-6 mt-5 flex gap-2">
        <button type="button" class="chip {{ $tab === 'email' ? 'chip-primary' : '' }}" wire:click="showEmail">{{ __('login.tab_email') }}</button>
        <button type="button" class="chip {{ $tab === 'phone' ? 'chip-primary' : '' }}" wire:click="showPhone">{{ __('login.tab_phone') }}</button>
    </div>

    @if (session('auth_error'))
        <p class="px-6 mt-4 text-sm" style="color:var(--color-k-coral)">{{ session('auth_error') }}</p>
    @endif

    @if ($tab === 'email')
        <form class="px-6 mt-6 space-y-3" wire:submit="login">
            <div class="input-wrap">
                <i class="ph ph-envelope-simple i-left"></i>
                <input type="email" class="input has-left" placeholder="{{ __('login.email_or_phone') }}" required wire:model="email" />
            </div>
            @error('email')
                <p class="text-sm" style="color:var(--color-k-coral)">{{ $message }}</p>
            @enderror
            <div class="input-wrap">
                <i class="ph ph-lock i-left"></i>
                <input id="pwd" type="password" class="input has-left has-right" placeholder="{{ __('login.password') }}" required
                    wire:model="password" />
                <button type="button" class="i-right" data-pwd-toggle="pwd"><i class="ph ph-eye"></i></button>
            </div>
            @error('password')
                <p class="text-sm" style="color:var(--color-k-coral)">{{ $message }}</p>
            @enderror

            <div class="flex items-center justify-between text-sm pt-1">
                <label class="flex items-center gap-2"><input type="checkbox"
                        class="size-4 accent-[var(--color-k-primary)]" wire:model="remember" /><span>{{ __('login.remember_me') }}</span></label>
                <a href="{{ route('forgot-password') }}" class="font-bold" style="color:var(--color-k-primary)" wire:navigate>{{ __('login.forgot') }}</a>
            </div>

            <button class="btn btn-primary w-full mt-2">{{ __('login.log_in') }}</button>
        </form>
    @else
        <form class="px-6 mt-6 space-y-3" wire:submit="sendPhoneCode">
            <div class="input-wrap">
                <i class="ph ph-phone i-left"></i>
                <input type="tel" class="input has-left" placeholder="{{ __('login.phone_label') }}" required wire:model="phone" />
            </div>
            @error('phone')
                <p class="text-sm" style="color:var(--color-k-coral)">{{ $message }}</p>
            @enderror
            <button class="btn btn-primary w-full mt-2">{{ __('login.phone_send') }}</button>
        </form>
        @if ($deliveryNote !== '')
            <p class="px-6 mt-3 text-sm" style="color:var(--color-k-muted)">{{ $deliveryNote }}</p>
        @endif
        @if ($codeSent)
            <form class="px-6 mt-3 space-y-3" wire:submit="verifyPhoneCode">
                <div class="input-wrap">
                    <i class="ph ph-password i-left"></i>
                    <input type="text" inputmode="numeric" maxlength="6" class="input has-left" placeholder="{{ __('login.phone_code') }}" required wire:model="phoneCode" />
                </div>
                @error('phoneCode')
                    <p class="text-sm" style="color:var(--color-k-coral)">{{ $message }}</p>
                @enderror
                <button class="btn btn-primary w-full mt-2">{{ __('login.phone_verify') }}</button>
            </form>
        @endif
    @endif

    <div class="px-6 mt-6">
        <div class="flex items-center gap-3 text-xs" style="color:var(--color-k-muted)">
            <span class="flex-1 h-px bg-[color:var(--color-k-border)]"></span>
            <span>{{ __('login.or_continue_with') }}</span>
            <span class="flex-1 h-px bg-[color:var(--color-k-border)]"></span>
        </div>
        <div class="grid grid-cols-3 gap-3 mt-4">
            <a href="{{ route('social.redirect', ['provider' => 'google']) }}" class="btn btn-secondary" aria-label="{{ __('login.continue_google') }}"><i
                    class="ph ph-google-logo text-2xl"></i></a>
            <a href="{{ route('social.redirect', ['provider' => 'apple']) }}" class="btn btn-secondary" aria-label="{{ __('login.continue_apple') }}"><i
                    class="ph ph-apple-logo text-2xl"></i></a>
            <button type="button" class="btn btn-secondary" aria-label="{{ __('login.continue_facebook') }}" wire:click="explainFacebook"><i
                    class="ph ph-facebook-logo text-2xl"></i></button>
        </div>
        @if ($showFacebookNotice)
            <p class="text-sm mt-3 text-center" style="color:var(--color-k-coral)">{{ __('login.facebook_unavailable') }}</p>
        @endif
    </div>

    <p class="mt-auto pb-8 pt-8 text-center text-sm safe-bottom" style="color:var(--color-k-muted)">
        {{ __('login.new_here') }} <a href="{{ route('user-register') }}" class="font-extrabold" style="color:var(--color-k-primary)"
            wire:navigate>{{ __('login.create_account') }}</a>
    </p>
</main>
