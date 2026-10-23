<?php

use App\Exceptions\AuthProviderException;
use App\Services\KidSetupService;
use App\Services\PhoneLoginService;
use App\Services\SocialAuthService;
use App\Services\UserRegistrationService;
use Illuminate\Support\Facades\Auth;
use Livewire\Component;

new class extends Component
{
    public string $name = '';

    public string $age = '6';

    public string $gender = 'Girl';

    public string $email = '';

    public string $password = '';

    public bool $agreed = false;

    public bool $socialPending = false;

    public bool $phonePending = false;

    public function mount(SocialAuthService $social, PhoneLoginService $phones): void
    {
        $pending = $social->pending();

        if ($pending !== null) {
            $this->socialPending = true;
            $this->email = $pending->email;
        }

        if ($phones->verifiedPhone() !== null) {
            $this->phonePending = true;
        }
    }

    public function title(): string
    {
        return __('register.page_title');
    }

    public function rendering(\Illuminate\View\View $view): void
    {
        $view->title($this->title());
    }

    public function register(
        UserRegistrationService $registration,
        SocialAuthService $social,
        PhoneLoginService $phones,
        KidSetupService $setup,
    ): void {
        $pending = $social->pending();

        if ($pending !== null) {
            $this->socialPending = true;
            $this->email = $pending->email;
        }

        $this->phonePending = $phones->verifiedPhone() !== null;
        $optionalPassword = $this->socialPending || $this->phonePending;

        $rules = [
            'name' => 'required|string|max:255',
            'age' => 'required|integer|min:3|max:14',
            'gender' => 'required|in:Girl,Boy,Other',
            'email' => $pending !== null ? 'required|email' : 'required|email|unique:users,email',
            'agreed' => 'accepted',
        ];

        if ($optionalPassword && $this->password === '') {
            $rules['password'] = 'nullable|string|max:20';
        } elseif ($optionalPassword) {
            $rules['password'] = 'string|min:8|max:20';
        } else {
            $rules['password'] = 'required|string|min:8|max:20';
        }

        $this->validate($rules);

        $password = $this->password === '' ? null : $this->password;

        try {
            if ($pending !== null) {
                $user = $social->registerPending($this->name, $password, (int) $this->age, $this->gender);
            } elseif ($this->phonePending) {
                $user = $phones->registerPending($this->name, $this->email, $password, (int) $this->age, $this->gender);
            } else {
                $user = $registration->register(
                    $this->name,
                    $this->email,
                    $password ?? '',
                    (int) $this->age,
                    $this->gender,
                );
            }
        } catch (AuthProviderException $exception) {
            $this->addError('email', $exception->getMessage());

            return;
        }

        Auth::login($user);
        session()->regenerate();

        $this->redirectRoute($setup->nextRouteName($user), navigate: true);
    }
};
?>

<main class="device-frame min-h-screen flex flex-col safe-top">
    <header class="appbar">
        <a href="{{ route('user-login') }}" class="icon-btn" data-back><i class="ph ph-caret-left"></i></a>
        <span class="grow"></span>
        <button class="icon-btn" data-theme-toggle><i class="ph ph-moon"></i></button>
    </header>

    <section class="px-6 pt-2">
        <h1 class="h-display text-3xl">{{ __('register.heading') }}</h1>
        <p class="text-sm mt-1" style="color:var(--color-k-muted)">{{ __('register.subtitle') }}</p>
    </section>

    <form class="px-6 mt-5 space-y-3" wire:submit="register">
        @if ($phonePending)
            <p class="text-xs" style="color:var(--color-k-muted)">{{ __('register.phone_pending_note') }}</p>
        @endif
        <div class="input-wrap">
            <i class="ph ph-smiley i-left"></i>
            <input type="text" class="input has-left" placeholder="{{ __('register.kids_name') }}" wire:model="name" />
        </div>
        @error('name')
            <p class="text-sm" style="color:var(--color-k-coral)">{{ $message }}</p>
        @enderror
        <div class="grid grid-cols-2 gap-3">
            <div class="input-wrap"><i class="ph ph-cake i-left"></i><input type="number" min="3" max="14" class="input has-left" placeholder="{{ __('register.age') }}" wire:model="age" /></div>
            <select class="input" wire:model="gender">
                <option value="Girl">{{ __('register.gender_girl') }}</option>
                <option value="Boy">{{ __('register.gender_boy') }}</option>
                <option value="Other">{{ __('register.gender_other') }}</option>
            </select>
        </div>
        @error('age')
            <p class="text-sm" style="color:var(--color-k-coral)">{{ $message }}</p>
        @enderror
        @error('gender')
            <p class="text-sm" style="color:var(--color-k-coral)">{{ $message }}</p>
        @enderror
        <div class="input-wrap">
            <i class="ph ph-envelope-simple i-left"></i>
            <input type="email" class="input has-left" placeholder="{{ __('register.parent_email') }}" @unless($socialPending) required @endunless @if($socialPending) readonly @endif wire:model="email" />
        </div>
        @if ($socialPending)
            <p class="text-xs" style="color:var(--color-k-muted)">{{ __('register.social_email_locked') }}</p>
        @endif
        @error('email')
            <p class="text-sm" style="color:var(--color-k-coral)">{{ $message }}</p>
        @enderror
        <div class="input-wrap">
            <i class="ph ph-lock i-left"></i>
            <input id="np" type="password" class="input has-left has-right" placeholder="{{ __('register.create_password') }}" @unless($socialPending || $phonePending) required @endunless
                wire:model="password" />
            <button type="button" class="i-right" data-pwd-toggle="np"><i class="ph ph-eye"></i></button>
        </div>
        @error('password')
            <p class="text-sm" style="color:var(--color-k-coral)">{{ $message }}</p>
        @enderror
        @if ($socialPending || $phonePending)
            <p class="text-xs" style="color:var(--color-k-muted)">{{ __('register.password_optional') }}</p>
        @endif

        <label class="flex items-start gap-2 text-xs pt-1" style="color:var(--color-k-muted)">
            <input type="checkbox" class="mt-1 size-4 accent-[var(--color-k-primary)]" required wire:model="agreed" />
            <span>{{ __('register.consent_start') }} <a class="font-bold" style="color:var(--color-k-primary)" href="{{ route('terms-privacy', ['tab' => 'terms']) }}" wire:navigate>{{ __('register.terms') }}</a>{{ __('register.consent_and') }}<a class="font-bold" style="color:var(--color-k-primary)" href="{{ route('privacy-policy') }}" wire:navigate>{{ __('register.privacy') }}</a>.</span>
        </label>
        @error('agreed')
            <p class="text-sm" style="color:var(--color-k-coral)">{{ $message }}</p>
        @enderror

        <button class="btn btn-primary w-full mt-2">{{ __('register.create_account') }}</button>
    </form>

    <p class="mt-auto pb-8 pt-8 text-center text-sm safe-bottom" style="color:var(--color-k-muted)">
        {{ __('register.already_have') }} <a href="{{ route('user-login') }}" class="font-extrabold" style="color:var(--color-k-primary)"
            wire:navigate>{{ __('register.log_in') }}</a>
    </p>
</main>
