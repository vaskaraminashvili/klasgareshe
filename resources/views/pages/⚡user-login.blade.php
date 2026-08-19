<?php

use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\Title;
use Livewire\Component;

new #[Title('Login · Kidzio')] class extends Component
{
    public string $email = '';

    public string $password = '';

    public bool $remember = false;

    public function login(): void
    {
        $this->validate([
            'email' => 'required|email',
            'password' => 'required',
        ]);

        if (! Auth::attempt(['email' => $this->email, 'password' => $this->password], $this->remember)) {
            $this->addError('email', 'These credentials do not match our records.');

            return;
        }

        session()->regenerate();

        $this->redirectRoute('home', navigate: true);
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
        <h1 class="h-display text-3xl">Welcome back!</h1>
        <p class="text-sm mt-1" style="color:var(--color-k-muted)">Sign in to continue your learning journey.</p>
    </section>

    <form class="px-6 mt-6 space-y-3" wire:submit="login">
        <div class="input-wrap">
            <i class="ph ph-envelope-simple i-left"></i>
            <input type="email" class="input has-left" placeholder="Email or phone" required wire:model="email" />
        </div>
        @error('email')
            <p class="text-sm" style="color:var(--color-k-coral)">{{ $message }}</p>
        @enderror
        <div class="input-wrap">
            <i class="ph ph-lock i-left"></i>
            <input id="pwd" type="password" class="input has-left has-right" placeholder="Password" required
                wire:model="password" />
            <button type="button" class="i-right" data-pwd-toggle="pwd"><i class="ph ph-eye"></i></button>
        </div>
        @error('password')
            <p class="text-sm" style="color:var(--color-k-coral)">{{ $message }}</p>
        @enderror

        <div class="flex items-center justify-between text-sm pt-1">
            <label class="flex items-center gap-2"><input type="checkbox"
                    class="size-4 accent-[var(--color-k-primary)]" wire:model="remember" /><span>Remember me</span></label>
            <a href="#" class="font-bold" style="color:var(--color-k-primary)">Forgot?</a>
        </div>

        <button class="btn btn-primary w-full mt-2">Log in</button>
    </form>

    <div class="px-6 mt-6">
        <div class="flex items-center gap-3 text-xs" style="color:var(--color-k-muted)">
            <span class="flex-1 h-px bg-[color:var(--color-k-border)]"></span>
            <span>or continue with</span>
            <span class="flex-1 h-px bg-[color:var(--color-k-border)]"></span>
        </div>
        <div class="grid grid-cols-3 gap-3 mt-4">
            <button type="button" class="btn btn-secondary" aria-label="Continue with Google"><i
                    class="ph ph-google-logo text-2xl"></i></button>
            <button type="button" class="btn btn-secondary" aria-label="Continue with Apple"><i
                    class="ph ph-apple-logo text-2xl"></i></button>
            <button type="button" class="btn btn-secondary" aria-label="Continue with Facebook"><i
                    class="ph ph-facebook-logo text-2xl"></i></button>
        </div>
    </div>

    <p class="mt-auto pb-8 pt-8 text-center text-sm safe-bottom" style="color:var(--color-k-muted)">
        New here? <a href="{{ route('user-register') }}" class="font-extrabold" style="color:var(--color-k-primary)"
            wire:navigate>Create account</a>
    </p>
</main>
