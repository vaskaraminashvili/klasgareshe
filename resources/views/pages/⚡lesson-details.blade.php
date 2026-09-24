<?php

use App\Enums\SchoolSubject;
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

    public int $index = 1;

    public int $weekTotal = 1;

    public int $questions = 0;

    public int $correct = 0;

    public int $xp = 0;

    public int $minutes = 0;

    public int $percent = 0;

    public string $state = '';

    public string $playHref = '';

    public string $sectionHref = '';

    public bool $favourite = false;

    public string $gradeLabel = '';

    public string $difficulty = '';

    public string $gameLabel = '';

    /** @var array{id: int, title: string, href: string, done: bool}|null */
    public ?array $previous = null;

    /** @var array{id: int, title: string, href: string, minutes: int, xp: int, locked: bool}|null */
    public ?array $next = null;

    /** @var list<array{n: int, title: string, subtitle: string, state: string, tile: string}> */
    public array $questionsList = [];

    /** @var list<array{slug: string, name: string, emoji: string, medalClass: string, meta: string, href: string, locked: bool}> */
    public array $badges = [];

    public function title(): string
    {
        return __('learn.lesson_page_title', ['title' => $this->titleText !== '' ? $this->titleText : __('learn.library')]);
    }

    public function rendering(View $view): void
    {
        $view->title($this->title());
    }

    public function mount(int $item, LearnLibraryService $learn, UserRepository $users): void
    {
        $this->item = $item;
        $this->fillFrom($learn, $users);

        if ($this->state === 'locked') {
            $this->redirectRoute('lesson-locked', ['item' => $this->item], navigate: true);
        }
    }

    public function toggleFavourite(LearnLibraryService $learn, UserRepository $users): void
    {
        $subject = SchoolSubject::tryFrom($this->subject);

        if ($subject === null) {
            return;
        }

        $learn->toggleFavourite($users->authenticated(), $subject);
        $this->fillFrom($learn, $users);
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
        $this->index = $snap->index;
        $this->weekTotal = $snap->weekTotal;
        $this->questions = $snap->questions;
        $this->correct = $snap->correct;
        $this->xp = $snap->xp;
        $this->minutes = $snap->minutes;
        $this->percent = $snap->percent;
        $this->state = $snap->state;
        $this->playHref = $snap->playHref;
        $this->sectionHref = $snap->sectionHref;
        $this->favourite = $snap->favourite;
        $this->gradeLabel = $snap->gradeLabel;
        $this->difficulty = $snap->difficulty;
        $this->gameLabel = $snap->gameLabel;
        $this->previous = $snap->previous;
        $this->next = $snap->next;
        $this->questionsList = $snap->questionsList;
        $this->badges = $snap->badges;
    }
};
?>

<main class="device-frame min-h-screen flex flex-col safe-top">

    <header class="appbar">
        <a href="{{ $sectionHref }}" class="icon-btn" data-back aria-label="{{ __('learn.back') }}"><i
                class="ph ph-caret-left"></i></a>
        <div class="grow">
            <p class="text-xs text-muted">{{ __('learn.subject_week', ['subject' => $subjectLabel, 'week' => $week]) }}</p>
            <h1 class="h-display text-lg leading-tight">{{ __('learn.lesson_n_of_n', ['n' => $index, 'total' => $weekTotal]) }}</h1>
        </div>
        <button class="icon-btn" type="button" wire:click="toggleFavourite" aria-label="{{ __('learn.favourite') }}"><i
                class="{{ $favourite ? 'ph-fill ph-heart text-[var(--color-k-coral)]' : 'ph ph-heart' }}"></i></button>
        <button class="icon-btn" type="button" aria-label="{{ __('learn.share') }}"><i class="ph ph-share-fat"></i></button>
    </header>

    <!-- =============== HERO =============== -->
    <section class="px-5">
        <div class="k-card-lg hero-learn text-center">
            <div class="relative inline-grid place-items-center">
                <div class="size-24 rounded-3xl bg-white/20 backdrop-blur-sm grid place-items-center text-5xl">{{ $emoji }}</div>
            </div>
            <p class="relative chip bg-white/20 border-0 text-white mt-4">
                <i class="ph-fill ph-number-square-zero"></i> {{ $subjectLabel }} · {{ $difficulty }}
            </p>
            <p class="relative h-display text-2xl mt-2 leading-tight">{{ $titleText }}</p>
            <p class="relative text-xs text-white/90 mt-1 max-w-[280px] mx-auto">{{ $gameLabel }}</p>

            <div class="relative mt-4 grid grid-cols-3 gap-2">
                <div class="rounded-2xl bg-white/15 backdrop-blur-sm p-3">
                    <p class="h-display text-xl leading-none">{{ $minutes }}</p>
                    <p class="text-[10px] text-white/85 mt-1">{{ __('learn.minutes') }}</p>
                </div>
                <div class="rounded-2xl bg-white/15 backdrop-blur-sm p-3">
                    <p class="h-display text-xl leading-none">{{ __('learn.plus_xp', ['xp' => $xp]) }}</p>
                    <p class="text-[10px] text-white/85 mt-1">{{ __('learn.xp_reward') }}</p>
                </div>
                <div class="rounded-2xl bg-white/15 backdrop-blur-sm p-3">
                    <p class="h-display text-xl leading-none">{{ $questions }}</p>
                    <p class="text-[10px] text-white/85 mt-1">{{ __('learn.activities') }}</p>
                </div>
            </div>

            <div class="relative mt-4 flex items-center gap-3">
                <div class="progress on-gradient grow"><span style="width: {{ $percent }}%"></span></div>
                <span class="text-sm font-extrabold shrink-0">{{ __('learn.progress_n_of_n', ['done' => $correct, 'total' => $questions]) }}</span>
            </div>
            <p class="relative text-[11px] text-white/85 mt-1">{{ __('learn.percent_keep_going', ['pct' => $percent]) }}</p>
        </div>
    </section>

    <!-- =============== QUICK META =============== -->
    <section class="px-5 mt-4">
        <div class="k-card p-0 overflow-hidden">
            <div class="grid grid-cols-3 text-center">
                {{-- Kid rating 4.9 — no ratings backend. --}}
                <div class="p-3">
                    <div class="size-8 mx-auto rounded-xl tile-mint grid place-items-center text-base">🧒</div>
                    <p class="text-[11px] font-extrabold text-ink mt-1 leading-tight">{{ $gradeLabel }}</p>
                    <p class="text-[10px] text-muted">{{ __('learn.best_fit') }}</p>
                </div>
                <div class="p-3 border-l border-token">
                    <div class="size-8 mx-auto rounded-xl {{ $tile }} grid place-items-center text-base">{{ $emoji }}</div>
                    <p class="text-[11px] font-extrabold text-ink mt-1 leading-tight">{{ $subjectLabel }}</p>
                    <p class="text-[10px] text-muted">{{ __('learn.subjects') }}</p>
                </div>
                <div class="p-3 border-l border-token">
                    <div class="size-8 mx-auto rounded-xl tile-sun grid place-items-center text-base">🎯</div>
                    <p class="text-[11px] font-extrabold text-ink mt-1 leading-tight">{{ $difficulty }}</p>
                    <p class="text-[10px] text-muted">{{ __('learn.difficulty_label') }}</p>
                </div>
            </div>
        </div>
    </section>

    {{-- What you'll learn dummy outcomes — T02. Activities below are the live questions. --}}

    <!-- =============== SKILLS =============== -->
    <section class="px-5 mt-5">
        <p class="section-label">{{ __('learn.skills') }}</p>
        <div class="mt-3 flex flex-wrap gap-2">
            <span class="chip chip-primary">{{ $subjectLabel }}</span>
            <span class="chip chip-mint">{{ $gameLabel }}</span>
            <span class="chip chip-sun">{{ $difficulty }}</span>
        </div>
    </section>

    <!-- =============== ACTIVITIES =============== -->
    <section class="px-5 mt-5">
        <div class="flex items-end justify-between">
            <p class="section-label">{{ __('learn.activities_n', ['n' => $questions]) }}</p>
            <span class="text-[11px] text-muted">{{ __('learn.about_1_min') }}</span>
        </div>
        <div class="mt-3 space-y-2">
            @foreach ($questionsList as $step)
                <div class="setting-row">
                    <div class="setting-ico {{ $step['tile'] }} font-extrabold">{{ $step['n'] }}</div>
                    <div class="grow min-w-0">
                        <p class="setting-text font-extrabold text-sm text-ink">{{ $step['title'] }}</p>
                        <p class="text-[11px] text-muted">{{ $step['subtitle'] }}</p>
                    </div>
                    @if ($step['state'] === 'done')
                        <span class="chip chip-mint">{{ __('learn.activity_done') }}</span>
                    @elseif ($step['state'] === 'now')
                        <span class="chip chip-primary">{{ __('learn.activity_now') }}</span>
                    @else
                        <i class="ph ph-lock-simple text-muted"></i>
                    @endif
                </div>
            @endforeach
        </div>
    </section>

    {{-- Vocab 11–20 grid — dummy English numbers; T02. --}}

    <!-- =============== REWARDS =============== -->
    <section class="px-5 mt-5">
        <p class="section-label">{{ __('learn.youll_earn') }}</p>
        <div class="mt-3 grid grid-cols-3 gap-2">
            <div class="k-card p-3 text-center">
                <div class="size-10 mx-auto rounded-2xl tile-sun grid place-items-center text-lg">⚡</div>
                <p class="font-extrabold text-sm text-ink mt-2">{{ __('learn.plus_xp', ['xp' => $xp]) }}</p>
                <p class="text-[10px] text-muted">{{ __('learn.level_faster') }}</p>
            </div>
            <div class="k-card p-3 text-center">
                <div class="size-10 mx-auto rounded-2xl tile-mint grid place-items-center text-lg">{{ $badges[0]['emoji'] ?? '🏅' }}</div>
                <p class="font-extrabold text-sm text-ink mt-2">{{ $badges[0]['name'] ?? __('learn.badge_unlock') }}</p>
                <p class="text-[10px] text-muted">{{ __('learn.badge_unlock') }}</p>
            </div>
            <div class="k-card p-3 text-center">
                <div class="size-10 mx-auto rounded-2xl tile-coral grid place-items-center text-lg">🔥</div>
                <p class="font-extrabold text-sm text-ink mt-2">{{ __('learn.streak_plus') }}</p>
                <p class="text-[10px] text-muted">{{ __('learn.keep_alive') }}</p>
            </div>
        </div>
    </section>

    <!-- =============== PREREQUISITES =============== -->
    @if ($previous)
        <section class="px-5 mt-5">
            <p class="section-label">{{ __('learn.before_you_start') }}</p>
            <a href="{{ $previous['href'] }}" wire:navigate class="setting-row mt-3">
                <div class="setting-ico {{ $previous['done'] ? 'tile-mint' : 'tile-violet' }}"><i
                        class="ph-fill {{ $previous['done'] ? 'ph-check-circle' : 'ph-play-circle' }}"></i></div>
                <div class="grow min-w-0">
                    <p class="setting-text font-extrabold text-sm text-ink">{{ $previous['title'] }}</p>
                    <p class="text-[11px] text-muted">{{ $previous['done'] ? __('learn.completed_score') : __('learn.ready_to_play') }}</p>
                </div>
                @if ($previous['done'])
                    <span class="chip chip-mint">✓</span>
                @else
                    <i class="ph ph-caret-right text-muted"></i>
                @endif
            </a>
        </section>
    @endif

    <!-- =============== UP NEXT =============== -->
    @if ($next)
        <section class="px-5 mt-5">
            <p class="section-label">{{ __('learn.up_next') }}</p>
            <div class="mt-3 space-y-2">
                <a href="{{ $next['href'] }}" wire:navigate class="setting-row">
                    <div class="setting-ico tile-sky text-2xl">{{ $emoji }}</div>
                    <div class="grow min-w-0">
                        <p class="setting-text font-extrabold text-sm text-ink">{{ $next['title'] }}</p>
                        <p class="text-[11px] text-muted">{{ __('learn.next_meta', ['minutes' => $next['minutes'], 'xp' => $next['xp']]) }}</p>
                    </div>
                    @if ($next['locked'])
                        <i class="ph ph-lock-simple text-muted"></i>
                    @else
                        <i class="ph ph-caret-right text-muted"></i>
                    @endif
                </a>
            </div>
        </section>
    @endif

    <!-- =============== PARENT NOTE =============== -->
    <section class="px-5 mt-5">
        <div class="tip-card rounded-2xl p-4 flex items-start gap-3">
            <div class="mascot shrink-0 size-11 text-xl">👨‍👩‍👧</div>
            <div class="grow">
                <p class="font-extrabold text-sm text-ink">{{ __('learn.parent_tip_lesson') }}</p>
                <p class="text-xs text-muted">{{ __('learn.parent_tip_lesson_body') }}</p>
            </div>
        </div>
    </section>

    <!-- spacer for sticky CTA -->
    <div class="h-28"></div>

    <!-- =============== STICKY CTA =============== -->
    <div class="fixed bottom-0 left-0 right-0 z-40 pointer-events-none">
        <div class="mx-auto max-w-[430px] pointer-events-auto">
            <div class="bg-surface border-t border-token safe-bottom px-5 pt-3 pb-3 flex items-center gap-2">
                {{-- Preview audio — T21. --}}
                @if ($state === 'playable')
                    <a href="{{ $playHref }}" wire:navigate class="btn btn-primary grow">
                        <i class="ph-fill ph-play"></i> {{ __('learn.continue_lesson') }}
                    </a>
                @else
                    <a href="{{ $sectionHref }}" wire:navigate class="btn btn-primary grow">
                        <i class="ph-fill ph-book-open"></i> {{ __('learn.back_to_chapter') }}
                    </a>
                @endif
            </div>
        </div>
    </div>
</main>
