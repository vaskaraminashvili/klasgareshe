<?php

use App\Enums\DailyGoal;
use App\Enums\SchoolSubject;
use App\Repositories\UserRepository;
use App\Services\UserProfileService;
use App\Services\WeekPlanService;
use Illuminate\Validation\ValidationException;
use Livewire\Component;

new class extends Component
{
    /** @var list<string> */
    public array $selected = [];

    public string $kidName = '';

    public int $age = 6;

    public int $dailyGoalMinutes = 10;

    public int $lessonsTotal = 0;

    public bool $saved = false;

    /**
     * @var array<string, array{label: string, emoji: string, tile: string, ink: string, sub: string, total: int}>
     */
    public array $catalog = [];

    public function title(): string
    {
        return __('parent-zone.subjects_title');
    }

    public function rendering(\Illuminate\View\View $view): void
    {
        $view->title($this->title());
    }

    public function mount(UserRepository $users, WeekPlanService $week): void
    {
        $user = $users->authenticated();
        $this->kidName = $user->name;
        $this->age = $user->age ?? 6;
        $this->dailyGoalMinutes = ($user->daily_goal ?? DailyGoal::Regular)->minutes();

        $totals = [];
        foreach ($week->subjectMastery($user) as $row) {
            $totals[$row->subject->value] = $row->total;
        }

        foreach (SchoolSubject::ordered() as $subject) {
            $this->catalog[$subject->value] = [
                'label' => $subject->label(),
                'emoji' => $subject->emoji(),
                'tile' => $subject->tile(),
                'ink' => $subject->inkClass(),
                'sub' => (string) __('onboarding.categories.topics.'.$subject->value.'_sub'),
                'total' => $totals[$subject->value] ?? 0,
            ];
        }

        $current = is_array($user->favourite_subjects) ? $user->favourite_subjects : [];
        $this->selected = array_values(array_filter(
            $current,
            fn (string $value): bool => array_key_exists($value, $this->catalog),
        ));

        if ($this->selected === []) {
            $this->selected = array_keys($this->catalog);
        }

        $this->refreshLessons();
    }

    public function toggle(string $subject): void
    {
        if (! array_key_exists($subject, $this->catalog)) {
            return;
        }

        if (in_array($subject, $this->selected, true)) {
            $this->selected = array_values(array_filter(
                $this->selected,
                fn (string $item): bool => $item !== $subject,
            ));
            $this->refreshLessons();

            return;
        }

        if (count($this->selected) >= 3) {
            return;
        }

        $this->selected[] = $subject;
        $this->refreshLessons();
    }

    public function applyQuick(string $bundle): void
    {
        $this->selected = match ($bundle) {
            'read' => ['georgian'],
            'stem' => ['math'],
            'history' => ['history'],
            default => array_keys($this->catalog),
        };
        $this->refreshLessons();
    }

    public function move(string $subject, int $delta): void
    {
        $index = array_search($subject, $this->selected, true);

        if ($index === false) {
            return;
        }

        $swap = $index + $delta;

        if ($swap < 0 || $swap >= count($this->selected)) {
            return;
        }

        $items = $this->selected;
        [$items[$index], $items[$swap]] = [$items[$swap], $items[$index]];
        $this->selected = array_values($items);
    }

    public function resetPlan(): void
    {
        $this->selected = array_keys($this->catalog);
        $this->saved = false;
        $this->refreshLessons();
    }

    public function save(UserProfileService $profiles, UserRepository $users): void
    {
        try {
            $profiles->updateFavouriteSubjects($users->authenticated(), $this->selected);
        } catch (ValidationException) {
            $this->addError('subjects', __('parent-zone.subjects_required'));

            return;
        }

        $this->saved = true;
    }

    public function dismissSaved(): void
    {
        $this->saved = false;
    }

    public function isPicked(string $subject): bool
    {
        return in_array($subject, $this->selected, true);
    }

    public function fillClass(): string
    {
        return match (count($this->selected)) {
            1 => 'w-33',
            2 => 'w-70',
            3 => 'w-100',
            default => 'w-0',
        };
    }

    private function refreshLessons(): void
    {
        $this->lessonsTotal = 0;

        foreach ($this->selected as $value) {
            $this->lessonsTotal += $this->catalog[$value]['total'] ?? 0;
        }
    }
};
?>

<main class="device-frame min-h-screen flex flex-col safe-top">

    <header class="appbar">
        <a href="{{ route('parent-controls') }}" class="icon-btn" data-back aria-label="{{ __('parent-zone.back') }}"><i class="ph ph-caret-left"></i></a>
        <div class="grow">
            <p class="text-xs text-muted">{{ __('parent-zone.subjects_eyebrow') }}</p>
            <h1 class="h-display text-lg leading-tight">{{ __('parent-zone.subjects_heading') }}</h1>
        </div>
        <button type="button" id="resetBtn" class="chip" wire:click="resetPlan">{{ __('parent-zone.reset') }}</button>
        <button type="button" class="icon-btn" data-theme-toggle aria-label="{{ __('parent-zone.toggle_theme') }}"><i class="ph ph-moon text-xl"></i></button>
    </header>

    <section class="px-5">
        <div class="k-card-lg hero-learn text-center">
            <div class="relative inline-grid place-items-center">
                <div class="size-24 rounded-3xl bg-white/20 backdrop-blur-sm grid place-items-center text-5xl">📚</div>
            </div>
            <p class="relative chip bg-white/20 border-0 text-white mt-4">
                <i class="ph-fill ph-sparkle"></i> {{ __('parent-zone.subjects_chip', ['name' => $kidName]) }}
            </p>
            <p class="relative h-display text-2xl mt-2 leading-tight">{{ __('parent-zone.subjects_hero') }}</p>
            <p class="relative text-xs text-white/90 mt-1 max-w-[280px] mx-auto">{{ __('parent-zone.subjects_hero_body') }}</p>

            <div class="relative mt-4 grid grid-cols-3 gap-2">
                <div class="rounded-2xl bg-white/15 backdrop-blur-sm p-3">
                    <p class="h-display text-xl leading-none"><span id="selVal">{{ count($selected) }}</span>/3</p>
                    <p class="text-[10px] text-white/85 mt-1">{{ __('parent-zone.selected') }}</p>
                </div>
                <div class="rounded-2xl bg-white/15 backdrop-blur-sm p-3">
                    <p class="h-display text-xl leading-none" id="lessonsVal">{{ $lessonsTotal }}</p>
                    <p class="text-[10px] text-white/85 mt-1">{{ __('parent-zone.lessons') }}</p>
                </div>
                <div class="rounded-2xl bg-white/15 backdrop-blur-sm p-3">
                    <p class="h-display text-xl leading-none"><span id="minsVal">{{ $dailyGoalMinutes }}</span>m</p>
                    <p class="text-[10px] text-white/85 mt-1">{{ __('parent-zone.daily_goal') }}</p>
                </div>
            </div>

            <div class="relative mt-4">
                <div class="progress on-gradient"><span id="heroFill" class="{{ $this->fillClass() }} transition-all duration-500"></span></div>
                <p class="text-[11px] text-white/85 mt-2" id="heroHint">{{ __('parent-zone.pick_hint', ['count' => count($selected)]) }}</p>
            </div>
        </div>
    </section>

    <section class="px-5 mt-4">
        <p class="text-[11px] text-muted mb-2 font-extrabold tracking-wider">{{ __('parent-zone.suggested') }}</p>
        <div data-swiper-rail class="swiper rail-swiper">
            <div class="swiper-wrapper">
                <button type="button" class="swiper-slide chip{{ $selected === array_keys($catalog) ? ' chip-primary' : '' }}" wire:click="applyQuick('all')">{{ __('parent-zone.quick_all') }}</button>
                <button type="button" class="swiper-slide chip{{ $selected === ['georgian'] ? ' chip-primary' : '' }}" wire:click="applyQuick('read')">{{ __('parent-zone.quick_read') }}</button>
                <button type="button" class="swiper-slide chip{{ $selected === ['math'] ? ' chip-primary' : '' }}" wire:click="applyQuick('stem')">{{ __('parent-zone.quick_stem') }}</button>
                <button type="button" class="swiper-slide chip{{ $selected === ['history'] ? ' chip-primary' : '' }}" wire:click="applyQuick('history')">{{ __('parent-zone.quick_history') }}</button>
            </div>
        </div>
    </section>

    <section class="px-5 mt-5">
        <div class="section-head">
            <h2 class="h-display text-lg">{{ __('parent-zone.all_subjects') }}</h2>
            <span class="link cursor-default" id="pickCount">{{ __('parent-zone.pick_up_to') }}</span>
        </div>

        <div class="mt-3 grid grid-cols-2 gap-3" id="subjectGrid">
            @foreach ($catalog as $value => $subject)
                <button type="button" class="tile {{ $subject['tile'] }} pick-card text-left{{ $this->isPicked($value) ? ' is-selected' : '' }}" wire:click="toggle('{{ $value }}')">
                    <div class="flex items-start justify-between">
                        <span class="tile-meta {{ $subject['ink'] }}">{{ $subject['total'] }} {{ __('parent-zone.lessons') }}</span>
                        <span class="check-badge {{ $this->isPicked($value) ? '' : 'hidden' }} size-7 rounded-full bg-[var(--color-k-primary)] text-white grid place-items-center text-sm"><i class="ph-fill ph-check"></i></span>
                    </div>
                    <h3 class="mt-4 {{ $subject['ink'] }}">{{ $subject['label'] }}</h3>
                    <p class="text-xs mt-1 {{ $subject['ink'] }} opacity-80">{{ $subject['sub'] }}</p>
                    <span class="tile-emoji">{{ $subject['emoji'] }}</span>
                </button>
            @endforeach
        </div>
    </section>

    <section class="px-5 mt-5">
        <div class="section-head">
            <p class="section-label">{{ __('parent-zone.your_plan') }}</p>
            <span class="link cursor-default" id="planMeta">{{ __('parent-zone.reorder') }}</span>
        </div>
        <div id="planBox" class="k-card p-4 mt-3">
            @if ($selected === [])
                <p class="text-sm text-muted text-center" id="planEmpty">{{ __('parent-zone.plan_empty') }}</p>
            @else
                <div id="planRows" class="space-y-2">
                    @foreach ($selected as $index => $value)
                        <div class="setting-row">
                            <div class="setting-ico {{ $catalog[$value]['tile'] }}">{{ $catalog[$value]['emoji'] }}</div>
                            <div class="grow min-w-0">
                                <p class="setting-text font-extrabold text-sm text-ink">{{ $catalog[$value]['label'] }}</p>
                                <p class="text-[11px] text-muted">{{ __('parent-zone.priority', ['n' => $index + 1]) }}</p>
                            </div>
                            <button type="button" class="icon-btn" wire:click="move('{{ $value }}', -1)" aria-label="{{ __('parent-zone.move_up') }}" @disabled($index === 0)><i class="ph ph-caret-up"></i></button>
                            <button type="button" class="icon-btn" wire:click="move('{{ $value }}', 1)" aria-label="{{ __('parent-zone.move_down') }}" @disabled($index === count($selected) - 1)><i class="ph ph-caret-down"></i></button>
                        </div>
                    @endforeach
                </div>
            @endif
        </div>
    </section>

    {{-- Starting difficulty → docs/tasks/T19-minigames-batch-2.md --}}

    <section class="px-5 mt-5 mb-28">
        <div class="tip-card rounded-2xl p-4 flex items-start gap-3">
            <div class="mascot shrink-0 size-11 text-xl">🦉</div>
            <div class="grow">
                <p class="font-extrabold text-sm text-ink">{{ __('parent-zone.tip_title') }}</p>
                <p class="text-xs text-muted">{{ __('parent-zone.tip_subjects') }}</p>
            </div>
        </div>
        @error('subjects')
            <p class="text-sm text-center mt-3" style="color:var(--color-k-coral)">{{ $message }}</p>
        @enderror
    </section>

    <div class="fixed bottom-0 left-0 right-0 z-40 pointer-events-none">
        <div class="mx-auto max-w-[430px] pointer-events-auto">
            <div class="bg-surface border-t border-token safe-bottom px-5 pt-3 pb-3 flex items-center gap-2">
                <a href="{{ route('parent-controls') }}" wire:navigate class="btn btn-ghost grow">{{ __('parent-zone.cancel') }}</a>
                <button type="button" id="saveBtn" class="btn btn-primary grow" wire:click="save" @disabled($selected === [])>
                    <i class="ph-fill ph-check"></i> {{ __('parent-zone.save_count', ['count' => count($selected)]) }}
                </button>
            </div>
        </div>
    </div>

    <div id="savedSheet" class="{{ $saved ? '' : 'hidden' }} fixed inset-0 z-50" role="dialog" aria-modal="true" aria-labelledby="savedTitle">
        <button type="button" id="savedBackdrop" class="absolute inset-0 size-full bg-black/50 backdrop-blur-sm" wire:click="dismissSaved" aria-label="{{ __('parent-zone.cancel') }}"></button>
        <div id="savedPanel" class="absolute left-0 right-0 bottom-0 mx-auto max-w-[430px] bg-surface rounded-t-3xl border-t border-token shadow-2xl safe-bottom">
            <div class="flex justify-center pt-3">
                <span class="block w-10 h-1.5 rounded-full bg-[var(--color-k-border)]"></span>
            </div>
            <div class="px-5 pt-4 pb-6 text-center">
                <div class="mx-auto size-20 rounded-full tile-mint grid place-items-center text-4xl">✅</div>
                <p id="savedTitle" class="h-display text-2xl mt-3 text-ink">{{ __('parent-zone.saved') }}</p>
                <p id="savedSub" class="text-sm text-muted mt-1">{{ __('parent-zone.subjects_hero_body') }}</p>

                <div id="savedRows" class="mt-5 text-left space-y-2">
                    @foreach ($selected as $value)
                        <div class="setting-row">
                            <div class="setting-ico {{ $catalog[$value]['tile'] }}">{{ $catalog[$value]['emoji'] }}</div>
                            <p class="setting-text font-extrabold text-sm grow text-ink">{{ $catalog[$value]['label'] }}</p>
                        </div>
                    @endforeach
                </div>

                <div class="mt-5 grid grid-cols-2 gap-2">
                    <button type="button" id="savedStay" class="btn btn-ghost" wire:click="dismissSaved">{{ __('parent-zone.edit_more') }}</button>
                    <a href="{{ route('parent-controls') }}" wire:navigate class="btn btn-primary"><i class="ph-fill ph-arrow-right"></i> {{ __('parent-zone.save') }}</a>
                </div>
            </div>
        </div>
    </div>
</main>
