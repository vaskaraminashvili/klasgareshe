<?php

use App\Repositories\UserRepository;
use App\Services\LearnLibraryService;
use Illuminate\View\View;
use Livewire\Attributes\Locked;
use Livewire\Component;

new class extends Component
{
    #[Locked]
    public int $item = 0;

    public string $titleText = '';

    public string $subject = '';

    public string $subjectLabel = '';

    public string $emoji = '';

    public string $tile = '';

    public int $week = 0;

    public int $questions = 0;

    public int $xp = 0;

    public int $minutes = 0;

    public string $state = '';

    public string $sectionHref = '';

    public bool $favourite = false;

    public string $gradeLabel = '';

    public string $difficulty = '';

    public string $gameLabel = '';

    /** @var list<array{n: int, title: string, subtitle: string, state: string, tile: string}> */
    public array $questionsList = [];

    /** @var list<array{slug: string, name: string, emoji: string, medalClass: string, meta: string, href: string, locked: bool}> */
    public array $badges = [];

    public ?int $blockingId = null;

    public string $blockingTitle = '';

    public string $blockingHref = '';

    public int $unlockSteps = 1;

    public int $unlockDone = 0;

    public bool $showPreview = false;

    public function title(): string
    {
        return __('learn.locked_page_title');
    }

    public function rendering(View $view): void
    {
        $view->title($this->title());
    }

    public function mount(int $item, LearnLibraryService $learn, UserRepository $users): void
    {
        $this->item = $item;
        $this->fillFrom($learn, $users);

        if ($this->state !== 'locked') {
            $this->redirectRoute('lesson-details', ['item' => $this->item], navigate: true);
        }
    }

    public function togglePreview(): void
    {
        $this->showPreview = ! $this->showPreview;
    }

    public function unlockPercent(): int
    {
        if ($this->unlockSteps < 1) {
            return 0;
        }

        return (int) round(($this->unlockDone / $this->unlockSteps) * 100);
    }

    private function fillFrom(LearnLibraryService $learn, UserRepository $users): void
    {
        $snap = $learn->pack($users->authenticated(), $this->item);

        if ($snap === null) {
            abort(404);
        }

        $this->titleText = $snap->title;
        $this->subject = $snap->subject;
        $this->subjectLabel = $snap->subjectLabel;
        $this->emoji = $snap->emoji;
        $this->tile = $snap->tile;
        $this->week = $snap->week;
        $this->questions = $snap->questions;
        $this->xp = $snap->xp;
        $this->minutes = $snap->minutes;
        $this->state = $snap->state;
        $this->sectionHref = $snap->sectionHref;
        $this->favourite = $snap->favourite;
        $this->gradeLabel = $snap->gradeLabel;
        $this->difficulty = $snap->difficulty;
        $this->gameLabel = $snap->gameLabel;
        $this->questionsList = $snap->questionsList;
        $this->badges = $snap->badges;
        $this->blockingId = $snap->blockingId;
        $this->blockingTitle = $snap->blockingTitle;
        $this->blockingHref = $snap->blockingHref;
        $this->unlockSteps = $snap->unlockSteps;
        $this->unlockDone = $snap->unlockDone;
    }
};
?>

<main class="device-frame min-h-screen flex flex-col safe-top">

    <header class="appbar">
        <a href="{{ $sectionHref }}" class="icon-btn" data-back aria-label="{{ __('learn.back') }}"><i
                class="ph ph-caret-left"></i></a>
        <div class="grow">
            <p class="text-xs text-muted">{{ __('learn.subject_week', ['subject' => $subjectLabel, 'week' => $week]) }}</p>
            <h1 class="h-display text-lg leading-tight">{{ __('learn.locked_lesson') }}</h1>
        </div>
        <button type="button" wire:click="togglePreview" class="icon-btn" aria-label="{{ __('learn.preview') }}"><i
                class="ph ph-eye text-xl"></i></button>
        <button class="icon-btn" data-theme-toggle aria-label="{{ __('learn.toggle_theme') }}"><i
                class="ph ph-moon text-xl"></i></button>
    </header>

    <!-- =============== HERO =============== -->
    <section class="px-5">
        <div class="k-card-lg hero-learn text-center">
            <div class="relative inline-grid place-items-center">
                <div class="size-28 rounded-3xl bg-white/20 backdrop-blur-sm grid place-items-center text-6xl opacity-80">{{ $emoji }}</div>
                <div class="absolute -bottom-1 -right-1 size-12 rounded-full bg-white grid place-items-center shadow-lg">
                    <i class="ph-fill ph-lock-simple text-2xl text-primary-ink"></i>
                </div>
            </div>
            <p class="relative chip bg-white/20 border-0 text-white mt-5">
                <i class="ph-fill ph-lock-simple"></i> {{ __('learn.locked_steps_away', ['n' => max(1, $unlockSteps - $unlockDone)]) }}
            </p>
            <p class="relative h-display text-2xl mt-2 leading-tight">{{ $titleText }}</p>
            <p class="relative text-xs text-white/90 mt-1 max-w-[300px] mx-auto">
                @if ($blockingTitle !== '')
                    {{ __('learn.finish_to_unlock', ['title' => $blockingTitle]) }}
                @else
                    {{ __('learn.finish_previous_tip') }}
                @endif
            </p>

            <div class="relative mt-4 grid grid-cols-3 gap-2">
                <div class="rounded-2xl bg-white/15 backdrop-blur-sm p-3">
                    <p class="h-display text-xl leading-none text-white">{{ $unlockDone }}/{{ $unlockSteps }}</p>
                    <p class="text-[10px] text-white/85 mt-1">{{ __('learn.steps_done') }}</p>
                </div>
                <div class="rounded-2xl bg-white/15 backdrop-blur-sm p-3">
                    <p class="h-display text-xl leading-none text-white">{{ $minutes }}</p>
                    <p class="text-[10px] text-white/85 mt-1">{{ __('learn.minutes') }}</p>
                </div>
                <div class="rounded-2xl bg-white/15 backdrop-blur-sm p-3">
                    <p class="h-display text-xl leading-none text-white">{{ __('learn.plus_xp', ['xp' => $xp]) }}</p>
                    <p class="text-[10px] text-white/85 mt-1">{{ __('learn.xp_reward') }}</p>
                </div>
            </div>

            <div class="relative mt-4 flex items-center gap-3">
                <div class="progress on-gradient grow"><span class="transition-all duration-700"
                        style="width: {{ $this->unlockPercent() }}%"></span></div>
                <span class="text-sm font-extrabold shrink-0 text-white">{{ $this->unlockPercent() }}%</span>
            </div>
            <p class="relative text-[11px] text-white/85 mt-1">{{ __('learn.unlock_progress') }}</p>
        </div>
    </section>

    <!-- =============== REQUIREMENTS =============== -->
    <section class="px-5 mt-5">
        <p class="section-label">{{ __('learn.what_you_need') }}</p>
        <div class="mt-3 space-y-2">
            {{-- XP 500 gate / xpSheet — lock is the previous pack only. --}}
            @if ($blockingTitle !== '')
                <a href="{{ $blockingHref }}" wire:navigate class="setting-row">
                    <div class="setting-ico tile-violet"><i class="ph-fill ph-play-circle"></i></div>
                    <div class="grow min-w-0">
                        <div class="flex items-center gap-2">
                            <p class="setting-text font-extrabold text-sm text-ink">{{ __('learn.complete_pack', ['title' => $blockingTitle]) }}</p>
                            <span class="chip chip-primary">{{ __('learn.now') }}</span>
                        </div>
                        <p class="text-[11px] text-muted">{{ __('learn.play_to_unlock', ['minutes' => $minutes]) }}</p>
                    </div>
                    <i class="ph ph-caret-right text-muted shrink-0"></i>
                </a>
            @endif
        </div>
    </section>

    <!-- =============== WHAT YOU'LL LEARN =============== -->
    <section class="px-5 mt-5">
        <p class="section-label">{{ __('learn.what_youll_preview') }}</p>
        <div class="mt-3 grid grid-cols-2 gap-3">
            @foreach (array_slice($questionsList, 0, 4) as $step)
                <div class="k-card p-3">
                    <div class="size-9 rounded-xl {{ $step['tile'] }} grid place-items-center text-lg">{{ $step['n'] }}</div>
                    <p class="font-extrabold text-sm text-ink mt-2">{{ \Illuminate\Support\Str::limit($step['title'], 32) }}</p>
                    <p class="text-[11px] text-muted">{{ $step['subtitle'] }}</p>
                </div>
            @endforeach
        </div>
    </section>

    <!-- =============== REWARDS =============== -->
    @if ($badges !== [])
        <section class="px-5 mt-5">
            <p class="section-label">{{ __('learn.reward_when_unlocked') }}</p>
            <div class="k-card p-4 mt-3 flex items-center gap-3">
                <div class="size-12 rounded-2xl tile-mint grid place-items-center text-2xl shrink-0">{{ $badges[0]['emoji'] }}</div>
                <div class="grow min-w-0">
                    <p class="font-extrabold text-sm text-ink">{{ $badges[0]['name'] }}</p>
                    <p class="text-[11px] text-muted">{{ $badges[0]['meta'] }}</p>
                </div>
                <span class="chip chip-mint">{{ __('learn.badge_unlock') }}</span>
            </div>
        </section>
    @endif

    <!-- =============== TIP =============== -->
    <section class="px-5 mt-5">
        <div class="tip-card rounded-2xl p-4 flex items-start gap-3">
            <div class="mascot shrink-0 size-11 text-xl">🦉</div>
            <div class="grow">
                <p class="font-extrabold text-sm text-ink">{{ __('learn.almost_there') }}</p>
                <p class="text-xs text-muted">{{ __('learn.finish_previous_tip') }}</p>
            </div>
            {{-- Notify — T16. --}}
        </div>
    </section>

    <div class="mt-auto px-5 pb-8 pt-6 safe-bottom space-y-2">
        <a href="{{ $blockingHref }}" wire:navigate class="btn btn-primary w-full">
            <i class="ph-fill ph-play"></i> {{ __('learn.play_previous') }}
        </a>
        <button type="button" wire:click="togglePreview" class="btn btn-secondary w-full">
            <i class="ph ph-eye"></i> {{ __('learn.sneak_peek') }}
        </button>
        <a href="{{ $sectionHref }}" wire:navigate class="btn btn-ghost w-full">{{ __('learn.back_to_chapter') }}</a>
    </div>

    <!-- =============== PREVIEW BOTTOM SHEET =============== -->
    @if ($showPreview)
        <div class="fixed inset-0 z-50" role="dialog" aria-modal="true" aria-labelledby="previewTitle">
            <button type="button" wire:click="togglePreview"
                class="absolute inset-0 size-full bg-black/50 backdrop-blur-sm" aria-label="{{ __('learn.close') }}"></button>
            <div
                class="absolute left-0 right-0 bottom-0 mx-auto max-w-[430px] bg-surface rounded-t-3xl border-t border-token shadow-2xl safe-bottom">
                <div class="flex justify-center pt-3">
                    <span class="block w-10 h-1.5 rounded-full bg-[var(--color-k-border)]"></span>
                </div>
                <div class="px-5 pt-4 pb-6">
                    <div class="flex items-start gap-3">
                        <div class="size-14 rounded-2xl {{ $tile }} grid place-items-center text-3xl shrink-0">{{ $emoji }}</div>
                        <div class="grow min-w-0">
                            <p id="previewTitle" class="h-display text-xl leading-tight text-ink">{{ $titleText }}</p>
                            <p class="text-xs text-muted mt-0.5">{{ $questions }} · {{ __('learn.mins_n', ['n' => $minutes]) }} · {{ $gradeLabel }}</p>
                        </div>
                        <button type="button" wire:click="togglePreview" class="icon-btn shrink-0"
                            aria-label="{{ __('learn.close') }}"><i class="ph ph-x"></i></button>
                    </div>

                    <p class="section-label mt-5">{{ __('learn.sample_questions') }}</p>
                    <div class="mt-2 space-y-2">
                        @foreach (array_slice($questionsList, 0, 3) as $step)
                            <div class="setting-row blur-sm pointer-events-none">
                                <div class="setting-ico {{ $step['tile'] }}"><i class="ph-fill ph-question"></i></div>
                                <div class="grow min-w-0">
                                    <p class="setting-text font-extrabold text-sm text-ink">{{ $step['title'] }}</p>
                                    <p class="text-[11px] text-muted">{{ $step['subtitle'] }}</p>
                                </div>
                                <span class="chip chip-primary">{{ $difficulty }}</span>
                            </div>
                        @endforeach
                    </div>
                    <p class="text-[11px] text-center text-muted mt-3"><i class="ph ph-lock-simple"></i>
                        {{ __('learn.unlock_to_see') }}</p>

                    <p class="section-label mt-5">{{ __('learn.skills') }}</p>
                    <div class="mt-2 flex flex-wrap gap-2">
                        <span class="chip chip-primary">{{ $subjectLabel }}</span>
                        <span class="chip chip-mint">{{ $gameLabel }}</span>
                        <span class="chip chip-sun">{{ $difficulty }}</span>
                    </div>

                    <div class="mt-5 grid grid-cols-2 gap-2">
                        <a href="{{ $sectionHref }}" wire:navigate class="btn btn-ghost"><i
                                class="ph ph-book-open"></i> {{ __('learn.current_lesson') }}</a>
                        <a href="{{ $blockingHref }}" wire:navigate class="btn btn-primary"><i
                                class="ph-fill ph-play"></i> {{ __('learn.keep_going') }}</a>
                    </div>
                </div>
            </div>
        </div>
    @endif
</main>
