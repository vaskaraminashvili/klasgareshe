<?php

use App\Enums\ParentPinAttempt;
use App\Repositories\UserRepository;
use App\Services\ParentZoneService;
use App\Services\UserProfileService;
use Illuminate\Support\Js;
use Livewire\Component;

new class extends Component
{
    public string $pin = '';

    public string $setupFirst = '';

    public string $gateMode = 'unlock';

    public bool $unlocked = false;

    public bool $hasPin = false;

    public string $hint = '';

    public string $kidName = '';

    public int $age = 0;

    public string $gradeLabel = '';

    public int $weekXp = 0;

    public int $weekActiveDays = 0;

    public int $weekLessons = 0;

    public string $weekRangeLabel = '';

    public string $email = '';

    public bool $emailVerified = false;

    public bool $allowFriendRequests = true;

    public bool $hideFromRanking = false;

    /** @var list<string> */
    public array $subjectLabels = [];

    public string $pinChangedLabel = '';

    public int $dailyGoalMinutes = 10;

    public function title(): string
    {
        return __('parent-zone.page_title');
    }

    public function rendering(\Illuminate\View\View $view): void
    {
        $view->title($this->title());
    }

    public function mount(ParentZoneService $zone, UserRepository $users): void
    {
        $user = $users->authenticated();
        $this->hasPin = $zone->hasPin($user);
        $this->unlocked = $this->hasPin && $zone->isUnlocked();

        if ($this->unlocked) {
            $zone->touch();
            $this->fillDashboard($zone, $user);

            return;
        }

        $this->gateMode = $this->hasPin ? 'unlock' : 'setup';
        $this->hint = '';
    }

    public function press(string $key, ParentZoneService $zone, UserRepository $users): void
    {
        if ($this->unlocked) {
            return;
        }

        if ($zone->lockoutSecondsRemaining($users->authenticated()) > 0) {
            $this->hint = __('parent-zone.pin_locked', [
                'seconds' => $zone->lockoutSecondsRemaining($users->authenticated()),
            ]);

            return;
        }

        if ($key === 'back') {
            $this->pin = mb_substr($this->pin, 0, -1);
            $this->hint = '';

            return;
        }

        if (! ctype_digit($key) || mb_strlen($this->pin) >= 4) {
            return;
        }

        $this->pin .= $key;
        $this->hint = '';

        if (mb_strlen($this->pin) === 4) {
            $this->submitPin($zone, $users);
        }
    }

    public function lockZone(ParentZoneService $zone): void
    {
        $zone->lock();
        $this->unlocked = false;
        $this->pin = '';
        $this->setupFirst = '';
        $this->gateMode = 'unlock';
        $this->hasPin = true;
        $this->hint = '';
    }

    public function forgotPin(ParentZoneService $zone, UserRepository $users): void
    {
        if (! $zone->requestResetCode($users->authenticated(), request()->ip())) {
            $this->hint = __('parent-zone.pin_wait_resend');
            $this->toast($this->hint);

            return;
        }

        $this->toast(__('parent-zone.code_sent'));
        $this->redirectRoute('parent-pin-otp', navigate: true);
    }

    public function updatedAllowFriendRequests(): void
    {
        $this->persistPrivacy();
    }

    public function updatedHideFromRanking(): void
    {
        $this->persistPrivacy();
    }

    private function submitPin(ParentZoneService $zone, UserRepository $users): void
    {
        $user = $users->authenticated();

        if ($this->gateMode === 'setup') {
            $this->setupFirst = $this->pin;
            $this->pin = '';
            $this->gateMode = 'confirm';

            return;
        }

        if ($this->gateMode === 'confirm') {
            $result = $zone->createPin($user, $this->setupFirst, $this->pin);
            $this->pin = '';
            $this->setupFirst = '';

            if ($result !== ParentPinAttempt::Created) {
                $this->gateMode = 'setup';
                $this->hint = __('parent-zone.pin_mismatch');

                return;
            }

            $this->hasPin = true;
            $this->unlocked = true;
            $this->fillDashboard($zone, $users->findOrFail($user->id));

            return;
        }

        $result = $zone->attemptUnlock($user, $this->pin);
        $this->pin = '';

        if ($result === ParentPinAttempt::Unlocked) {
            $this->unlocked = true;
            $this->fillDashboard($zone, $user);

            return;
        }

        if ($result === ParentPinAttempt::LockedOut) {
            $this->hint = __('parent-zone.pin_locked', [
                'seconds' => $zone->lockoutSecondsRemaining($user),
            ]);

            return;
        }

        $this->hint = __('parent-zone.pin_wrong');
    }

    private function persistPrivacy(): void
    {
        if (! $this->unlocked) {
            return;
        }

        app(UserProfileService::class)->updatePrivacy(
            app(UserRepository::class)->authenticated(),
            ! $this->hideFromRanking,
            $this->allowFriendRequests,
        );
    }

    private function fillDashboard(ParentZoneService $zone, \App\Models\User $user): void
    {
        $dash = $zone->dashboard($user);
        $this->kidName = $dash->kidName;
        $this->age = $dash->age;
        $this->gradeLabel = $dash->gradeLabel;
        $this->weekXp = $dash->weekXp;
        $this->weekActiveDays = $dash->weekActiveDays;
        $this->weekLessons = $dash->weekLessons;
        $this->weekRangeLabel = $dash->weekRangeLabel;
        $this->email = $dash->email;
        $this->emailVerified = $dash->emailVerified;
        $this->allowFriendRequests = $dash->allowFriendRequests;
        $this->hideFromRanking = ! $dash->showOnLeaderboard;
        $this->subjectLabels = $dash->subjectLabels;
        $this->pinChangedLabel = $dash->pinChangedLabel;
        $this->dailyGoalMinutes = $dash->dailyGoalMinutes;
        $this->hasPin = $dash->hasPin;
    }

    public function subjectsHint(): string
    {
        return $this->subjectLabels === []
            ? (string) __('parent-zone.subjects_none')
            : implode(' · ', $this->subjectLabels);
    }

    public function gateTitle(): string
    {
        return match ($this->gateMode) {
            'setup' => (string) __('parent-zone.gate_setup_title'),
            'confirm' => (string) __('parent-zone.gate_confirm_hero'),
            default => (string) __('parent-zone.gate_title'),
        };
    }

    public function gateHero(): string
    {
        return match ($this->gateMode) {
            'setup' => (string) __('parent-zone.gate_setup_hero'),
            'confirm' => (string) __('parent-zone.gate_confirm_hero'),
            default => (string) __('parent-zone.gate_hero'),
        };
    }

    public function gateBody(): string
    {
        return match ($this->gateMode) {
            'setup' => (string) __('parent-zone.gate_setup_body'),
            'confirm' => (string) __('parent-zone.gate_confirm_body'),
            default => (string) __('parent-zone.gate_body'),
        };
    }

    private function toast(string $message): void
    {
        $this->js('toast('.Js::from($message).')');
    }
};
?>

<main class="device-frame min-h-screen flex flex-col safe-top">

    <div id="pinGate" class="fixed inset-0 z-50{{ $unlocked ? ' hidden' : '' }}" role="dialog" aria-modal="true" aria-labelledby="pinTitle">
        <div class="absolute inset-0 bg-[var(--color-k-bg)]"></div>
        <div id="pinPanel" class="relative h-full flex flex-col overflow-y-auto{{ $unlocked ? ' opacity-0 translate-y-3' : '' }} transition-all duration-400">
            <header class="appbar safe-top">
                <a href="{{ route('profile') }}" class="icon-btn" data-back aria-label="{{ __('parent-zone.back') }}"><i class="ph ph-caret-left text-xl"></i></a>
                <div class="grow">
                    <p class="text-xs text-muted">{{ __('parent-zone.gate_eyebrow') }}</p>
                    <h1 id="pinTitle" class="h-display text-lg leading-tight">{{ $this->gateTitle() }}</h1>
                </div>
                <button type="button" class="icon-btn" data-theme-toggle aria-label="{{ __('parent-zone.toggle_theme') }}"><i class="ph ph-moon text-xl"></i></button>
            </header>

            <section class="px-5 mt-2 text-center">
                <div class="mx-auto size-24 rounded-3xl tile-violet grid place-items-center text-5xl mb-3">🔐</div>
                <h2 class="h-display text-2xl text-ink">{{ $this->gateHero() }}</h2>
                <p class="text-sm text-muted mt-1 max-w-[280px] mx-auto">{{ $this->gateBody() }}</p>
            </section>

            <section class="px-8 mt-6">
                <div class="flex justify-center gap-3" id="pinGroup">
                    @for ($i = 0; $i < 4; $i++)
                        <div class="pin-box input text-center text-2xl font-extrabold h-16 w-14 grid place-items-center" aria-label="{{ __('parent-zone.digit', ['n' => $i + 1]) }}">{{ mb_strlen($pin) > $i ? '•' : '' }}</div>
                    @endfor
                </div>
                <p id="pinHint" class="text-center text-xs mt-4{{ $hint !== '' ? '' : ' text-muted' }}" style="{{ $hint !== '' ? 'color:var(--color-k-coral)' : '' }}">{{ $hint }}</p>
            </section>

            <section class="px-8 mt-6">
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

            <section class="px-8 mt-auto pb-8 safe-bottom text-center">
                @if ($hasPin)
                    <button type="button" class="chip" wire:click="forgotPin"><i class="ph ph-key"></i> {{ __('parent-zone.forgot_pin') }}</button>
                @endif
            </section>
        </div>
    </div>

    @if ($unlocked)
        <header class="appbar">
            <a href="{{ route('profile') }}" class="icon-btn" data-back aria-label="{{ __('parent-zone.back') }}"><i class="ph ph-caret-left"></i></a>
            <div class="grow">
                <p class="text-xs text-muted">{{ __('parent-zone.eyebrow') }}</p>
                <h1 class="h-display text-lg leading-tight">{{ __('parent-zone.heading') }}</h1>
            </div>
            <button id="lockBtn" type="button" class="icon-btn" wire:click="lockZone" aria-label="{{ __('parent-zone.lock_aria') }}"><i class="ph ph-lock-simple-open text-xl"></i></button>
            <button type="button" class="icon-btn" data-theme-toggle aria-label="{{ __('parent-zone.toggle_theme') }}"><i class="ph ph-moon text-xl"></i></button>
        </header>

        <section class="px-5">
            <div class="k-card-lg hero-profile text-center">
                <div class="relative inline-grid place-items-center">
                    <div class="size-24 rounded-3xl bg-white/20 backdrop-blur-sm grid place-items-center text-5xl">🛡️</div>
                </div>
                <p class="relative chip bg-white/20 border-0 text-white mt-4">
                    <i class="ph-fill ph-users-three"></i> {{ __('parent-zone.dashboard_chip') }}
                </p>
                <p class="relative h-display text-2xl mt-2 leading-tight">{{ __('parent-zone.week_at_a_glance', ['name' => $kidName]) }}</p>
                <p class="relative text-xs text-white/90 mt-1">{{ $weekRangeLabel }} · {{ __('parent-zone.week_days_active', ['days' => $weekActiveDays]) }}</p>

                <div class="relative mt-4 grid grid-cols-3 gap-2">
                    <div class="rounded-2xl bg-white/15 backdrop-blur-sm p-3">
                        <p class="h-display text-xl leading-none">{{ number_format($weekXp) }}</p>
                        <p class="text-[10px] text-white/85 mt-1">{{ __('parent-zone.xp_earned') }}</p>
                    </div>
                    <div class="rounded-2xl bg-white/15 backdrop-blur-sm p-3">
                        <p class="h-display text-xl leading-none">{{ $weekActiveDays }}</p>
                        <p class="text-[10px] text-white/85 mt-1">{{ __('parent-zone.active_days') }}</p>
                    </div>
                    <div class="rounded-2xl bg-white/15 backdrop-blur-sm p-3">
                        <p class="h-display text-xl leading-none">{{ $weekLessons }}</p>
                        <p class="text-[10px] text-white/85 mt-1">{{ __('parent-zone.lessons_done') }}</p>
                    </div>
                </div>

                <div class="relative mt-4 flex items-center gap-2 flex-wrap">
                    <span class="chip bg-white/20 border-0 text-white"><span class="live-dot"></span> {{ __('parent-zone.week_status') }}</span>
                    {{-- Full report → docs/tasks/T08-parent-reports.md --}}
                </div>
            </div>
        </section>

        {{-- This-week minutes chart + daily goal hit count → docs/tasks/T07-screen-time-bedtime.md --}}

        {{-- Time & limits (screen time, bedtime, break reminders) → docs/tasks/T07-screen-time-bedtime.md --}}

        <section class="px-5 mt-5">
            <p class="section-label">{{ __('parent-zone.learning') }}</p>
            <div class="mt-3 space-y-2">
                {{-- Weekly report → docs/tasks/T08-parent-reports.md --}}
                <a href="{{ route('monthly-goals') }}" wire:navigate class="setting-row">
                    <div class="setting-ico tile-coral"><i class="ph-fill ph-target"></i></div>
                    <div class="grow min-w-0">
                        <p class="setting-text font-extrabold text-sm text-ink">{{ __('parent-zone.monthly_goals') }}</p>
                        <p class="text-[11px] text-muted">{{ __('parent-zone.monthly_goals_hint') }}</p>
                    </div>
                    <i class="ph ph-caret-right text-muted"></i>
                </a>
                <a href="{{ route('preferred-subjects') }}" wire:navigate class="setting-row">
                    <div class="setting-ico tile-mint"><i class="ph-fill ph-books"></i></div>
                    <div class="grow min-w-0">
                        <p class="setting-text font-extrabold text-sm text-ink">{{ __('parent-zone.preferred_subjects') }}</p>
                        <p class="text-[11px] text-muted">{{ $this->subjectsHint() }}</p>
                    </div>
                    <i class="ph ph-caret-right text-muted"></i>
                </a>
                <div class="setting-row">
                    <div class="setting-ico tile-pink"><i class="ph-fill ph-baby"></i></div>
                    <div class="grow min-w-0">
                        <p class="setting-text font-extrabold text-sm text-ink">{{ __('parent-zone.age_filter') }}</p>
                        <p class="text-[11px] text-muted">{{ __('parent-zone.age_filter_hint', ['grade' => $gradeLabel]) }}</p>
                    </div>
                    <span class="chip chip-mint">{{ __('parent-zone.age_filter_on') }}</span>
                </div>
            </div>
        </section>

        <section class="px-5 mt-5">
            <p class="section-label">{{ __('parent-zone.privacy_safety') }}</p>
            <div class="mt-3 space-y-2">
                <a href="{{ route('privacy-policy') }}" wire:navigate class="setting-row">
                    <div class="setting-ico tile-violet"><i class="ph-fill ph-shield-check"></i></div>
                    <div class="grow min-w-0">
                        <p class="setting-text font-extrabold text-sm text-ink">{{ __('parent-zone.privacy_policy') }}</p>
                        <p class="text-[11px] text-muted">{{ __('parent-zone.privacy_policy_hint') }}</p>
                    </div>
                    <span class="chip chip-mint">{{ __('parent-zone.privacy_chip') }}</span>
                </a>
                <label class="setting-row cursor-pointer">
                    <div class="setting-ico tile-sky"><i class="ph-fill ph-users-four"></i></div>
                    <div class="grow min-w-0">
                        <p class="setting-text font-extrabold text-sm text-ink">{{ __('parent-zone.allow_friends') }}</p>
                        <p class="text-[11px] text-muted">{{ __('parent-zone.allow_friends_hint') }}</p>
                    </div>
                    <span class="ks-switch">
                        <input type="checkbox" wire:model.live="allowFriendRequests"/>
                        <span class="track"></span>
                        <span class="thumb"></span>
                    </span>
                </label>
                <label class="setting-row cursor-pointer">
                    <div class="setting-ico tile-coral"><i class="ph-fill ph-eye-slash"></i></div>
                    <div class="grow min-w-0">
                        <p class="setting-text font-extrabold text-sm text-ink">{{ __('parent-zone.hide_ranking') }}</p>
                        <p class="text-[11px] text-muted">{{ __('parent-zone.hide_ranking_hint') }}</p>
                    </div>
                    <span class="ks-switch">
                        <input type="checkbox" wire:model.live="hideFromRanking"/>
                        <span class="track"></span>
                        <span class="thumb"></span>
                    </span>
                </label>
                {{-- Export all data → docs/tasks/T09-account-and-data.md --}}
            </div>
        </section>

        <section class="px-5 mt-5">
            <p class="section-label">{{ __('parent-zone.account') }}</p>
            <div class="mt-3 space-y-2">
                <a href="{{ route('edit-profile') }}" wire:navigate class="setting-row">
                    <div class="setting-ico tile-mint"><i class="ph-fill ph-user-circle"></i></div>
                    <div class="grow min-w-0">
                        <p class="setting-text font-extrabold text-sm text-ink">{{ __('parent-zone.kid_profile') }}</p>
                        <p class="text-[11px] text-muted">{{ __('parent-zone.kid_profile_meta', ['name' => $kidName, 'age' => $age, 'grade' => $gradeLabel]) }}</p>
                    </div>
                    <i class="ph ph-caret-right text-muted"></i>
                </a>
                <div class="setting-row">
                    <div class="setting-ico tile-sky"><i class="ph-fill ph-envelope-simple"></i></div>
                    <div class="grow min-w-0">
                        <p class="setting-text font-extrabold text-sm text-ink">{{ __('parent-zone.parent_email') }}</p>
                        <p class="text-[11px] text-muted">{{ $email }} · {{ $emailVerified ? __('parent-zone.email_verified') : __('parent-zone.email_unverified') }}</p>
                    </div>
                    {{-- Change parent email → docs/tasks/T09-account-and-data.md --}}
                </div>
                <a href="{{ route('change-pin') }}" wire:navigate class="setting-row">
                    <div class="setting-ico tile-violet"><i class="ph-fill ph-lock-key"></i></div>
                    <div class="grow min-w-0">
                        <p class="setting-text font-extrabold text-sm text-ink">{{ __('parent-zone.change_pin') }}</p>
                        <p class="text-[11px] text-muted">{{ $pinChangedLabel }}</p>
                    </div>
                    <i class="ph ph-caret-right text-muted"></i>
                </a>
                {{-- Delete account → docs/tasks/T09-account-and-data.md --}}
            </div>
        </section>

        <section class="px-5 mt-5 mb-10">
            <div class="tip-card rounded-2xl p-4 flex items-start gap-3">
                <div class="mascot shrink-0 size-11 text-xl">🦉</div>
                <div class="grow">
                    <p class="font-extrabold text-sm text-ink">{{ __('parent-zone.tip_title') }}</p>
                    <p class="text-xs text-muted">{{ __('parent-zone.tip_body') }}</p>
                </div>
                <button type="button" id="lockNowBtn" class="chip chip-primary" wire:click="lockZone"><i class="ph ph-lock"></i> {{ __('parent-zone.lock_now') }}</button>
            </div>
        </section>
    @endif
</main>
