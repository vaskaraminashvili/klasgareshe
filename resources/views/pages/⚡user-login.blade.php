<?php

use Livewire\Component;

new class extends Component {};
?>

<div>
    <h1>User Login</h1>
    <livewire:user-login-form />

    <br />
    <a href="{{ route('user-register') }}" wire:navigate>Register</a>
</div>
