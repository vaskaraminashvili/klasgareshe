<?php

use App\Services\UserRegistrationService;
use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\Title;
use Livewire\Component;

new #[Title('Sign up · Kidzio')] class extends Component
{
    public string $name = '';

    public string $age = '6';

    public string $gender = 'Girl';

    public string $email = '';

    public string $password = '';

    public bool $agreed = false;

    public function register(UserRegistrationService $registration): void
    {
        $this->validate([
            'name' => 'required|string|max:255',
            'age' => 'required|integer|min:3|max:14',
            'gender' => 'required|in:Girl,Boy,Other',
            'email' => 'required|email|unique:users,email',
            'password' => 'required|min:8|max:20',
            'agreed' => 'accepted',
        ]);

        $user = $registration->register($this->name, $this->email, $this->password);

        Auth::login($user);
        session()->regenerate();

        $this->redirectRoute('home', navigate: true);
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
        <h1 class="h-display text-3xl">Create account</h1>
        <p class="text-sm mt-1" style="color:var(--color-k-muted)">Fun learning starts in under a minute.</p>
    </section>

    <form class="px-6 mt-5 space-y-3" wire:submit="register">
        <div class="input-wrap">
            <i class="ph ph-smiley i-left"></i>
            <input type="text" class="input has-left" placeholder="Kid's name" wire:model="name" />
        </div>
        @error('name')
            <p class="text-sm" style="color:var(--color-k-coral)">{{ $message }}</p>
        @enderror
        <div class="grid grid-cols-2 gap-3">
            <div class="input-wrap"><i class="ph ph-cake i-left"></i><input type="number" min="3" max="14" class="input has-left" placeholder="Age" wire:model="age" /></div>
            <select class="input" wire:model="gender"><option>Girl</option><option>Boy</option><option>Other</option></select>
        </div>
        @error('age')
            <p class="text-sm" style="color:var(--color-k-coral)">{{ $message }}</p>
        @enderror
        @error('gender')
            <p class="text-sm" style="color:var(--color-k-coral)">{{ $message }}</p>
        @enderror
        <div class="input-wrap">
            <i class="ph ph-envelope-simple i-left"></i>
            <input type="email" class="input has-left" placeholder="Parent email" required wire:model="email" />
        </div>
        @error('email')
            <p class="text-sm" style="color:var(--color-k-coral)">{{ $message }}</p>
        @enderror
        <div class="input-wrap">
            <i class="ph ph-lock i-left"></i>
            <input id="np" type="password" class="input has-left has-right" placeholder="Create password" required
                wire:model="password" />
            <button type="button" class="i-right" data-pwd-toggle="np"><i class="ph ph-eye"></i></button>
        </div>
        @error('password')
            <p class="text-sm" style="color:var(--color-k-coral)">{{ $message }}</p>
        @enderror

        <label class="flex items-start gap-2 text-xs pt-1" style="color:var(--color-k-muted)">
            <input type="checkbox" class="mt-1 size-4 accent-[var(--color-k-primary)]" required wire:model="agreed" />
            <span>I'm the parent/guardian and I agree to the <a class="font-bold" style="color:var(--color-k-primary)" href="#">Terms</a> & <a class="font-bold" style="color:var(--color-k-primary)" href="#">Privacy</a>.</span>
        </label>
        @error('agreed')
            <p class="text-sm" style="color:var(--color-k-coral)">{{ $message }}</p>
        @enderror

        <button class="btn btn-primary w-full mt-2">Create account</button>
    </form>

    <p class="mt-auto pb-8 pt-8 text-center text-sm safe-bottom" style="color:var(--color-k-muted)">
        Already have one? <a href="{{ route('user-login') }}" class="font-extrabold" style="color:var(--color-k-primary)"
            wire:navigate>Log in</a>
    </p>
</main>
