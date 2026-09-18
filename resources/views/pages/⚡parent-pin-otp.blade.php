<?php

use App\Repositories\UserRepository;
use App\Services\ParentZoneService;
use Illuminate\Support\Js;
use Livewire\Component;

new class extends Component
{
    public string $maskedEmail = '';

    public function title(): string
    {
        return __('parent-zone.otp_title');
    }

    public function rendering(\Illuminate\View\View $view): void
    {
        $view->title($this->title());
    }

    public function mount(ParentZoneService $zone, UserRepository $users): void
    {
        if (! $zone->hasResetChallenge()) {
            $this->redirectRoute('parent-controls', navigate: true);

            return;
        }

        $this->maskedEmail = $zone->maskedEmail($users->authenticated());
    }

    public function verifyCode(string $code, ParentZoneService $zone, UserRepository $users): void
    {
        $digits = preg_replace('/\D/', '', $code) ?? '';

        if (! $zone->verifyResetCode($users->authenticated(), $digits)) {
            $this->addError('code', __('parent-zone.code_invalid'));
            $this->toast(__('parent-zone.code_invalid'));

            return;
        }

        $this->redirectRoute('change-pin', navigate: true);
    }

    public function resend(ParentZoneService $zone, UserRepository $users): void
    {
        if (! $zone->requestResetCode($users->authenticated(), request()->ip())) {
            $this->toast(__('parent-zone.pin_wait_resend'));

            return;
        }

        $this->toast(__('parent-zone.code_resent'));
    }

    private function toast(string $message): void
    {
        $this->js('toast('.Js::from($message).')');
    }
};
?>

<main class="device-frame min-h-screen flex flex-col safe-top">

    <header class="appbar">
        <a href="{{ route('parent-controls') }}" class="icon-btn" data-back aria-label="{{ __('parent-zone.back') }}"><i class="ph ph-caret-left"></i></a>
        <div class="grow">
            <p class="text-xs text-muted">{{ __('parent-zone.otp_eyebrow') }}</p>
            <h1 class="h-display text-lg leading-tight">{{ __('parent-zone.otp_heading') }}</h1>
        </div>
        <button type="button" class="icon-btn" data-theme-toggle aria-label="{{ __('parent-zone.toggle_theme') }}"><i class="ph ph-moon text-xl"></i></button>
    </header>

    <section class="px-6 pt-2 text-center">
        <div class="mx-auto size-24 rounded-3xl tile-violet grid place-items-center mb-4">
            <i class="ph-fill ph-chat-circle-dots text-5xl"></i>
        </div>
        <h2 class="h-display text-2xl text-ink">{{ __('parent-zone.enter_code') }}</h2>
        <p class="text-sm mt-1 text-muted">{{ __('parent-zone.sent_to') }} <span class="font-extrabold text-ink">{{ $maskedEmail }}</span></p>
    </section>

    <form class="px-6 mt-6" wire:submit.prevent>
        <div class="flex justify-center gap-2" id="otp" role="group" aria-label="{{ __('parent-zone.enter_code') }}" wire:ignore>
            <input type="text" inputmode="numeric" maxlength="1" class="otp-box input text-center h-16 w-12 text-2xl font-extrabold" autocomplete="one-time-code" aria-label="{{ __('parent-zone.digit', ['n' => 1]) }}"/>
            <input type="text" inputmode="numeric" maxlength="1" class="otp-box input text-center h-16 w-12 text-2xl font-extrabold" aria-label="{{ __('parent-zone.digit', ['n' => 2]) }}"/>
            <input type="text" inputmode="numeric" maxlength="1" class="otp-box input text-center h-16 w-12 text-2xl font-extrabold" aria-label="{{ __('parent-zone.digit', ['n' => 3]) }}"/>
            <input type="text" inputmode="numeric" maxlength="1" class="otp-box input text-center h-16 w-12 text-2xl font-extrabold" aria-label="{{ __('parent-zone.digit', ['n' => 4]) }}"/>
            <input type="text" inputmode="numeric" maxlength="1" class="otp-box input text-center h-16 w-12 text-2xl font-extrabold" aria-label="{{ __('parent-zone.digit', ['n' => 5]) }}"/>
            <input type="text" inputmode="numeric" maxlength="1" class="otp-box input text-center h-16 w-12 text-2xl font-extrabold" aria-label="{{ __('parent-zone.digit', ['n' => 6]) }}"/>
        </div>
        <p id="otpHint" class="text-center text-xs mt-3 text-muted" data-hint-empty="{{ __('parent-zone.otp_tip') }}" data-hint-ready="{{ __('parent-zone.otp_ready') }}">{{ __('parent-zone.otp_tip') }}</p>
        @error('code')
            <p class="text-sm text-center mt-2" style="color:var(--color-k-coral)">{{ $message }}</p>
        @enderror

        <button type="button" id="verifyBtn" class="btn btn-primary w-full mt-5" disabled>
            <i class="ph-fill ph-check-circle"></i> {{ __('parent-zone.verify') }}
        </button>
        <button type="button" class="btn btn-ghost w-full mt-2" wire:click="resend">
            <i class="ph ph-arrow-clockwise"></i> {{ __('parent-zone.resend') }}
        </button>

        <p class="text-center text-[11px] text-muted mt-5">
            <a href="{{ route('parent-controls') }}" class="chip chip-primary" wire:navigate>{{ __('parent-zone.back_to_gate') }}</a>
        </p>
    </form>
</main>
