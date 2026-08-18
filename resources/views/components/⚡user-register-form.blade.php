<?php

use App\Models\User;
use Illuminate\Support\Facades\Hash;
use Livewire\Component;

new class extends Component
{
    public $name = '';

    public $email = '';

    public $surname = '';

    public $nickname = '';

    public $password = '';

    public function register()
    {
        $this->validate([
            'name' => 'required',
            'surname' => 'required',
            'email' => 'required|email|unique:users,email',
            'nickname' => 'required|unique:users,nickname|min:3|max:20',
            'password' => 'required|min:8|max:20',
        ]);

        $user = User::create([
            'name' => $this->name,
            'surname' => $this->surname,
            'email' => $this->email,
            'nickname' => $this->nickname,
            'password' => Hash::make($this->password),
        ]);

        auth()->login($user);

        return redirect()->route('user');
    }
};
?>

<div>
    <form wire:submit="register">
        <div>
            <label for="name">Name</label>
            <input type="text" wire:model="name" />
            @error('name')
                <span class="text-red-500">{{ $message }}</span>
            @enderror
        </div>
        <div>
            <label for="surname">Surname</label>
            <input type="text" wire:model="surname" />
            @error('surname')
                <span class="text-red-500">{{ $message }}</span>
            @enderror
        </div>
        <div>
            <label for="email">Email</label>
            <input type="email" wire:model="email" />
            @error('email')
                <span class="text-red-500">{{ $message }}</span>
            @enderror
        </div>
        <div>
            <label for="nickname">NickName</label>
            <input type="text" wire:model="nickname" />
            @error('nickname')
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
        <button wire:click="register">Register</button>
    </form>
</div>
