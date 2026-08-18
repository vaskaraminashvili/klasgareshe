<?php

use Livewire\Component;

new class extends Component
{
    //
};
?>

<div>
    <h1>User Register</h1>
    <br />
    <livewire:user-register-form />
    <br />
    <a href="{{ route('user-login') }}" wire:navigate>Login</a>
</div>
