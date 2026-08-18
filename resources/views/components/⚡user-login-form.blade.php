<?php

use App\Models\User;
use Illuminate\Support\Facades\Hash;
use Livewire\Component;

new class extends Component
{
    public $email = '';

    public $password = '';

    public function login()
    {
        $this->validate([
            'email' => 'required|email',
            'password' => 'required',
        ]);

        $user = User::where('email', $this->email)->first();
        if (! $user) {
            $this->addError('email', 'User not found');

            return;
        }

        if (! Hash::check($this->password, $user->password)) {
            $this->addError('password', 'Invalid password');

            return;
        }

        auth()->login($user);

        return redirect()->route('user');
    }
};
?>

<div>
    <form wire:submit="login">
        <div>
            <label for="email">Email</label>
            <input type="email" wire:model="email" />
            @error('email')
                <span class="text-red-500">{{ $message }}</span>
            @enderror
        </div>
        <div>
            <label for="password">Password</label>
            <input type="password" wire:model="password" />
            @error('password')
                <span class="text-red-500">{{ $message }}</span>
            @enderror
        </div>
    </form>
    <button wire:click="login">Login</button>
</div>
