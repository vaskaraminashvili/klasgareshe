<?php

use App\Enums\ParentPinAttempt;
use App\Repositories\UserRepository;
use App\Services\ParentZoneService;
use Illuminate\Support\Js;
use Livewire\Component;

new class extends Component
{
    public int $step = 1;

    public string $pin = '';

    public string $current = '';

    public string $newPin = '';

    public bool $skipCurrent = false;

    public string $hint = '';

    public string $strength = 'weak';

    /** @var array{unique: bool, noseq: bool, norepeat: bool} */
    public array $rules = ['unique' => false, 'noseq' => false, 'norepeat' => false];

    public function title(): string
    {
        return __('parent-zone.change_pin_title');
    }

    public function rendering(\Illuminate\View\View $view): void
    {
        $view->title($this->title());
    }

    public function mount(ParentZoneService $zone): void
    {
        $this->skipCurrent = $zone->canSetPinWithoutCurrent();
        $this->step = $this->skipCurrent ? 2 : 1;
    }

    public function press(string $key, ParentZoneService $zone): void
    {
        if ($key === 'back') {
            $this->pin = mb_substr($this->pin, 0, -1);
            $this->syncStrength($zone);
            $this->hint = '';

            return;
        }

        if (! ctype_digit($key) || mb_strlen($this->pin) >= 4) {
            return;
        }

        $this->pin .= $key;
        $this->hint = '';
        $this->syncStrength($zone);
    }

    public function next(ParentZoneService $zone, UserRepository $users): void
    {
        if (mb_strlen($this->pin) !== 4) {
            return;
        }

        if ($this->step === 1) {
            $this->current = $this->pin;
            $this->pin = '';
            $this->step = 2;
            $this->syncStrength($zone);

            return;
        }

        if ($this->step === 2) {
            $this->newPin = $this->pin;
            $this->pin = '';
            $this->step = 3;

            return;
        }

        $user = $users->authenticated();
        $result = $zone->changePin($user, $this->current, $this->newPin, $this->pin);

        if ($result === ParentPinAttempt::Created) {
            $this->redirectRoute('parent-controls', navigate: true);

            return;
        }

        if ($result === ParentPinAttempt::LockedOut) {
            $this->hint = __('parent-zone.pin_locked', [
                'seconds' => $zone->lockoutSecondsRemaining($user),
            ]);
            $this->resetToCurrent();

            return;
        }

        if ($result === ParentPinAttempt::Invalid) {
            $this->hint = __('parent-zone.pin_wrong');
            $this->resetToCurrent();

            return;
        }

        $this->hint = __('parent-zone.pin_mismatch');
        $this->pin = '';
        $this->step = 2;
        $this->newPin = '';
    }

    public function forgotPin(ParentZoneService $zone, UserRepository $users): void
    {
        if (! $zone->requestResetCode($users->authenticated(), request()->ip())) {
            $this->toast(__('parent-zone.pin_wait_resend'));

            return;
        }

        $this->toast(__('parent-zone.code_sent'));
        $this->redirectRoute('parent-pin-otp', navigate: true);
    }

    public function stepLabel(): string
    {
        $total = $this->skipCurrent ? 2 : 3;
        $current = $this->skipCurrent ? $this->step - 1 : $this->step;

        return (string) __('parent-zone.step_of', [
            'current' => $current,
            'total' => $total,
            'label' => $this->heroTitle(),
        ]);
    }

    public function heroTitle(): string
    {
        return match ($this->step) {
            2 => (string) __('parent-zone.step_new'),
            3 => (string) __('parent-zone.step_confirm'),
            default => (string) __('parent-zone.step_current'),
        };
    }

    public function heroSub(): string
    {
        if ($this->skipCurrent && $this->step === 2) {
            return (string) __('parent-zone.hero_reset');
        }

        return match ($this->step) {
            2 => (string) __('parent-zone.hero_new'),
            3 => (string) __('parent-zone.hero_confirm'),
            default => (string) __('parent-zone.hero_current'),
        };
    }

    public function nextLabel(): string
    {
        return $this->step === 3
            ? (string) __('parent-zone.save_pin')
            : (string) __('parent-zone.continue');
    }

    public function strengthLabel(): string
    {
        return (string) __('parent-zone.strength_'.$this->strength);
    }

    private function resetToCurrent(): void
    {
        $this->pin = '';
        $this->current = '';
        $this->newPin = '';
        $this->step = $this->skipCurrent ? 2 : 1;
    }

    private function syncStrength(ParentZoneService $zone): void
    {
        if ($this->step !== 2) {
            return;
        }

        $this->strength = $zone->pinStrength($this->pin);
        $this->rules = $zone->pinRules($this->pin);
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
            <p class="text-xs text-muted">{{ __('parent-zone.security_eyebrow') }}</p>
            <h1 class="h-display text-lg leading-tight">{{ __('parent-zone.change_heading') }}</h1>
        </div>
        <button type="button" class="icon-btn" data-theme-toggle aria-label="{{ __('parent-zone.toggle_theme') }}"><i class="ph ph-moon text-xl"></i></button>
    </header>

    <section class="px-5">
        <div class="flex items-center gap-2">
            @if (! $skipCurrent)
                <span class="h-1.5 rounded-full {{ $step >= 1 ? 'bg-[var(--color-k-primary)]' : 'bg-[var(--color-k-border)]' }} grow transition-all" data-step="1"></span>
            @endif
            <span class="h-1.5 rounded-full {{ $step >= 2 ? 'bg-[var(--color-k-primary)]' : 'bg-[var(--color-k-border)]' }} grow transition-all" data-step="2"></span>
            <span class="h-1.5 rounded-full {{ $step >= 3 ? 'bg-[var(--color-k-primary)]' : 'bg-[var(--color-k-border)]' }} grow transition-all" data-step="3"></span>
        </div>
        <p class="text-[11px] text-muted mt-2" id="stepLabel">{{ $this->stepLabel() }}</p>
    </section>

    <section class="px-5 mt-4">
        <div class="k-card-lg hero-profile text-center">
            <div class="relative inline-grid place-items-center">
                <div class="size-24 rounded-3xl bg-white/20 backdrop-blur-sm grid place-items-center text-5xl">🔐</div>
            </div>
            <p class="relative chip bg-white/20 border-0 text-white mt-4">
                <i class="ph-fill ph-lock-key"></i> {{ __('parent-zone.chip_pin') }}
            </p>
            <p class="relative h-display text-2xl mt-2 leading-tight" id="heroTitle">{{ $this->heroTitle() }}</p>
            <p class="relative text-xs text-white/90 mt-1 max-w-[280px] mx-auto" id="heroSub">{{ $this->heroSub() }}</p>
        </div>
    </section>

    <section class="px-8 mt-6">
        <div class="flex justify-center gap-3" id="pinGroup">
            @for ($i = 0; $i < 4; $i++)
                <div class="input text-center text-2xl font-extrabold h-16 w-14 grid place-items-center" aria-label="{{ __('parent-zone.digit', ['n' => $i + 1]) }}">{{ mb_strlen($pin) > $i ? '•' : '' }}</div>
            @endfor
        </div>
        <p id="pinHint" class="text-center text-xs mt-4{{ $hint !== '' ? '' : ' text-muted' }}" style="{{ $hint !== '' ? 'color:var(--color-k-coral)' : '' }}">{{ $hint }}</p>

        <div id="strengthWrap" class="mt-4{{ $step === 2 ? '' : ' hidden' }}">
            <div class="flex items-center gap-2">
                <span class="text-[11px] text-muted shrink-0">{{ __('parent-zone.strength') }}</span>
                <div class="progress grow"><span id="strengthFill" class="{{ $strength === 'strong' ? 'w-100' : ($strength === 'ok' ? 'w-60' : (mb_strlen($pin) > 0 ? 'w-30' : 'w-0')) }} transition-all duration-300"></span></div>
                <span id="strengthLabel" class="text-[11px] font-extrabold text-muted shrink-0">{{ $this->strengthLabel() }}</span>
            </div>
            <div class="mt-2 flex flex-wrap gap-2 text-[10px]" id="strengthRules">
                <span class="chip {{ $rules['unique'] ? 'chip-mint' : 'chip-coral' }}" data-rule="unique"><i class="ph {{ $rules['unique'] ? 'ph-check' : 'ph-x' }}"></i> {{ __('parent-zone.rule_unique') }}</span>
                <span class="chip {{ $rules['noseq'] ? 'chip-mint' : 'chip-coral' }}" data-rule="noseq"><i class="ph {{ $rules['noseq'] ? 'ph-check' : 'ph-x' }}"></i> {{ __('parent-zone.rule_noseq') }}</span>
                <span class="chip {{ $rules['norepeat'] ? 'chip-mint' : 'chip-coral' }}" data-rule="norepeat"><i class="ph {{ $rules['norepeat'] ? 'ph-check' : 'ph-x' }}"></i> {{ __('parent-zone.rule_norepeat') }}</span>
            </div>
        </div>
    </section>

    <section class="px-8 mt-5">
        <div class="grid grid-cols-3 gap-2 max-w-[320px] mx-auto" id="pinPad">
            <button type="button" class="h-14 rounded-2xl bg-[var(--color-k-surface)] border border-token text-xl font-extrabold text-ink hover:bg-[var(--color-k-bg)] active:scale-95 transition-all" wire:click="press('1')">1</button>
            <button type="button" class="h-14 rounded-2xl bg-[var(--color-k-surface)] border border-token text-xl font-extrabold text-ink hover:bg-[var(--color-k-bg)] active:scale-95 transition-all" wire:click="press('2')">2</button>
            <button type="button" class="h-14 rounded-2xl bg-[var(--color-k-surface)] border border-token text-xl font-extrabold text-ink hover:bg-[var(--color-k-bg)] active:scale-95 transition-all" wire:click="press('3')">3</button>
            <button type="button" class="h-14 rounded-2xl bg-[var(--color-k-surface)] border border-token text-xl font-extrabold text-ink hover:bg-[var(--color-k-bg)] active:scale-95 transition-all" wire:click="press('4')">4</button>
            <button type="button" class="h-14 rounded-2xl bg-[var(--color-k-surface)] border border-token text-xl font-extrabold text-ink hover:bg-[var(--color-k-bg)] active:scale-95 transition-all" wire:click="press('5')">5</button>
            <button type="button" class="h-14 rounded-2xl bg-[var(--color-k-surface)] border border-token text-xl font-extrabold text-ink hover:bg-[var(--color-k-bg)] active:scale-95 transition-all" wire:click="press('6')">6</button>
            <button type="button" class="h-14 rounded-2xl bg-[var(--color-k-surface)] border border-token text-xl font-extrabold text-ink hover:bg-[var(--color-k-bg)] active:scale-95 transition-all" wire:click="press('7')">7</button>
            <button type="button" class="h-14 rounded-2xl bg-[var(--color-k-surface)] border border-token text-xl font-extrabold text-ink hover:bg-[var(--color-k-bg)] active:scale-95 transition-all" wire:click="press('8')">8</button>
            <button type="button" class="h-14 rounded-2xl bg-[var(--color-k-surface)] border border-token text-xl font-extrabold text-ink hover:bg-[var(--color-k-bg)] active:scale-95 transition-all" wire:click="press('9')">9</button>
            <button type="button" class="h-14 rounded-2xl opacity-0 pointer-events-none" aria-hidden="true">·</button>
            <button type="button" class="h-14 rounded-2xl bg-[var(--color-k-surface)] border border-token text-xl font-extrabold text-ink hover:bg-[var(--color-k-bg)] active:scale-95 transition-all" wire:click="press('0')">0</button>
            <button type="button" class="h-14 rounded-2xl bg-[var(--color-k-surface)] border border-token text-xl font-extrabold text-ink hover:bg-[var(--color-k-bg)] active:scale-95 transition-all" wire:click="press('back')" aria-label="{{ __('parent-zone.backspace') }}"><i class="ph ph-backspace text-xl"></i></button>
        </div>
    </section>

    <section class="px-5 mt-5 mb-10">
        <div class="space-y-2">
            <button type="button" id="nextBtn" class="btn btn-primary w-full" wire:click="next" @disabled(mb_strlen($pin) !== 4)>
                <i class="ph-fill {{ $step === 3 ? 'ph-check' : 'ph-arrow-right' }}"></i> <span id="nextLabel">{{ $this->nextLabel() }}</span>
            </button>
            <a href="{{ route('parent-controls') }}" wire:navigate class="btn btn-ghost w-full">{{ __('parent-zone.cancel') }}</a>
        </div>
        <p class="text-center text-[11px] text-muted mt-3">
            {{ __('parent-zone.forgot_via_email') }} <button type="button" class="text-primary-ink font-extrabold" wire:click="forgotPin">{{ __('parent-zone.reset_via_email') }}</button>
        </p>
    </section>
</main>
