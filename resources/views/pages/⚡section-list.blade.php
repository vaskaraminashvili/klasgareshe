<?php

use App\Enums\SchoolSubject;
use App\Repositories\UserRepository;
use App\Services\LearnLibraryService;
use Illuminate\View\View;
use Livewire\Attributes\Locked;
use Livewire\Attributes\Url;
use Livewire\Component;

new class extends Component
{
    #[Locked]
    public string $subject = '';

    #[Url]
    public int $week = 0;

    public string $subjectLabel = '';

    public string $emoji = '';

    public int $weekDone = 0;

    public int $weekTotal = 0;

    public int $percent = 0;

    public int $xpEarned = 0;

    public int $minutesLeft = 0;

    public bool $favourite = false;

    /** @var array{title: string, subtitle: string, href: string}|null */
    public ?array $continue = null;

    /** @var list<array{week: int, label: string, name: string, status: string, statusLabel: string}> */
    public array $weeks = [];

    /** @var list<array{id: int, title: string, subtitle: string, state: string, href: string, minutes: int, xp: int, stars: string, chip: string, chipClass: string, icon: string, percent: int}> */
    public array $lessons = [];

    /** @var list<array{slug: string, name: string, emoji: string, medalClass: string, meta: string, href: string, locked: bool}> */
    public array $badges = [];

    public function title(): string
    {
        return __('learn.sections_page_title');
    }

    public function rendering(View $view): void
    {
        $view->title($this->title());
    }

    public function mount(string $subject, LearnLibraryService $learn, UserRepository $users): void
    {
        if (SchoolSubject::tryFrom($subject) === null) {
            abort(404);
        }

        $this->subject = $subject;
        $this->fillFrom($learn, $users);
    }

    public function selectWeek(int $week, LearnLibraryService $learn, UserRepository $users): void
    {
        $this->week = $week;
        $this->fillFrom($learn, $users);
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
        $subject = SchoolSubject::from($this->subject);
        $snap = $learn->section(
            $users->authenticated(),
            $subject,
            $this->week > 0 ? $this->week : null,
        );

        $this->subjectLabel = $snap->subjectLabel;
        $this->emoji = $snap->emoji;
        $this->week = $snap->week;
        $this->weekDone = $snap->weekDone;
        $this->weekTotal = $snap->weekTotal;
        $this->percent = $snap->percent;
        $this->xpEarned = $snap->xpEarned;
        $this->minutesLeft = $snap->minutesLeft;
        $this->favourite = $snap->favourite;
        $this->continue = $snap->continue;
        $this->weeks = $snap->weeks;
        $this->lessons = $snap->lessons;
        $this->badges = $snap->badges;
    }

    public function lessonClass(string $state): string
    {
        return match ($state) {
            'done' => 'is-done',
            'playable' => 'is-current',
            default => 'is-locked',
        };
    }

    public function weekClass(string $status): string
    {
        return match ($status) {
            'current' => 'is-current',
            'done' => 'is-done',
            'locked' => 'is-locked',
            default => '',
        };
    }

    public function badgesEarned(): int
    {
        $count = 0;

        foreach ($this->badges as $badge) {
            if (! $badge['locked']) {
                $count++;
            }
        }

        return $count;
    }
};
?>

<main class="device-frame min-h-screen flex flex-col">

    <!-- =============== APPBAR =============== -->
    <header class="appbar safe-top">
        <a href="{{ route('learn-categories') }}" class="icon-btn" data-back aria-label="{{ __('learn.back') }}"><i
                class="ph ph-caret-left"></i></a>
        <div class="grow">
            <p class="text-xs text-muted">{{ __('learn.subject_week', ['subject' => $subjectLabel, 'week' => $week]) }}</p>
            <h1 class="h-display text-lg leading-tight">{{ __('learn.week_name', ['n' => $week]) }}</h1>
        </div>
        <button class="icon-btn" type="button" wire:click="toggleFavourite" aria-label="{{ __('learn.favourite') }}"><i
                class="{{ $favourite ? 'ph-fill ph-heart text-[var(--color-k-coral)]' : 'ph ph-heart' }} text-xl"></i></button>
        <button class="icon-btn" data-theme-toggle aria-label="{{ __('learn.toggle_theme') }}"><i
                class="ph ph-moon text-xl"></i></button>
    </header>

    <!-- =============== CHAPTER HERO =============== -->
    <section class="px-5">
        <div class="k-card-lg hero-chapter">
            <div class="relative flex items-center gap-3">
                <div class="text-5xl">{{ $emoji }}</div>
                <div class="grow">
                    <span class="chip bg-white/20 border-0 text-white">
                        <i class="ph-fill ph-book-open"></i> {{ __('learn.week_n', ['n' => $week]) }}
                    </span>
                    <p class="h-display text-xl mt-1 leading-tight">{{ __('learn.week_name', ['n' => $week]) }}</p>
                    <p class="text-xs text-white/90">{{ __('learn.week_hero_sub', ['subject' => $subjectLabel]) }}</p>
                </div>
            </div>

            <div class="relative mt-4 flex items-center gap-3">
                <div class="progress on-gradient grow"><span style="width: {{ $percent }}%"></span></div>
                <span class="text-sm font-extrabold shrink-0">{{ __('learn.progress_n_of_n', ['done' => $weekDone, 'total' => $weekTotal]) }}</span>
            </div>
            <p class="relative text-[11px] text-white/85 mt-1">{{ __('learn.percent_remaining', ['pct' => $percent, 'n' => max(0, $weekTotal - $weekDone)]) }}</p>

            <div class="relative mt-4 grid grid-cols-3 gap-2">
                <div class="hero-metric">
                    <p class="hm-v">+{{ $xpEarned }}</p>
                    <p class="hm-l">{{ __('learn.xp_earned') }}</p>
                </div>
                <div class="hero-metric">
                    <p class="hm-v">{{ __('learn.minutes_left', ['n' => $minutesLeft]) }}</p>
                    <p class="hm-l">{{ __('learn.time_left') }}</p>
                </div>
                <div class="hero-metric">
                    <p class="hm-v">{{ $this->badgesEarned() }}/{{ max(1, count($badges)) }}</p>
                    <p class="hm-l">{{ __('learn.badges_short') }}</p>
                </div>
            </div>
        </div>
    </section>

    <!-- =============== CONTINUE CTA =============== -->
    @if ($continue)
        <section class="px-5 mt-4">
            <a href="{{ $continue['href'] }}" wire:navigate class="k-card-lg card-hero-success relative overflow-hidden block">
                <span class="watermark-emoji" aria-hidden="true">▶️</span>
                <div class="relative flex items-center gap-3">
                    <div class="text-4xl">▶️</div>
                    <div class="grow">
                        <p class="text-xs uppercase font-extrabold tracking-wider opacity-90">{{ __('learn.continue_pack') }}</p>
                        <p class="h-display text-lg leading-tight">{{ $continue['title'] }}</p>
                        <p class="text-xs text-white/90">{{ $continue['subtitle'] }}</p>
                    </div>
                    <span class="cta-soft shrink-0">{{ __('learn.play') }}</span>
                </div>
            </a>
        </section>
    @endif

    <!-- =============== CHAPTER JUMP =============== -->
    <section class="mt-5">
        <div class="section-head px-5">
            <h2 class="h-display text-lg">{{ __('learn.chapters') }}</h2>
            <a href="{{ route('learn-categories') }}" wire:navigate
                class="link">{{ __('learn.all_subject', ['subject' => $subjectLabel]) }}</a>
        </div>
        <div data-swiper-rail class="swiper rail-swiper">
            <div class="swiper-wrapper">
                @foreach ($weeks as $chip)
                    <button type="button" wire:click="selectWeek({{ $chip['week'] }})"
                        class="swiper-slide chapter-chip {{ $this->weekClass($chip['status']) }}">
                        <span class="cc-num">{{ $chip['label'] }}</span>
                        <span class="cc-name">{{ $chip['name'] }}</span>
                        <span
                            class="text-[10px] {{ $chip['status'] === 'current' ? 'text-primary-ink' : 'text-muted' }} font-extrabold">{{ $chip['statusLabel'] }}</span>
                    </button>
                @endforeach
            </div>
        </div>
    </section>

    <!-- =============== LESSON LIST (flat cards, no connecting bar) =============== -->
    <section class="px-5 mt-5">
        <div class="section-head">
            <h2 class="h-display text-lg">{{ __('learn.lessons') }}</h2>
            <span
                class="link cursor-default">{{ __('learn.lessons_head', ['total' => $weekTotal, 'done' => $weekDone]) }}</span>
        </div>

        <div class="space-y-3">
            @foreach ($lessons as $lesson)
                <a href="{{ $lesson['href'] }}" wire:navigate
                    class="lesson-item {{ $this->lessonClass($lesson['state']) }}">
                    <div class="lesson-node"><i class="{{ $lesson['icon'] }}"></i></div>
                    <div class="lesson-body">
                        <p class="lesson-title">{{ $lesson['title'] }}</p>
                        <p class="lesson-sub">{{ $lesson['subtitle'] }}</p>
                        <div class="lesson-meta">
                            <span><i class="ph ph-clock"></i> {{ __('learn.mins_n', ['n' => $lesson['minutes']]) }}</span>
                            @if ($lesson['stars'] !== '')
                                <span><i class="ph ph-star"></i> {{ $lesson['stars'] }}</span>
                            @endif
                            <span><i class="ph ph-sparkle"></i> {{ __('learn.plus_xp', ['xp' => $lesson['xp']]) }}</span>
                        </div>
                    </div>
                    <span class="{{ $lesson['chipClass'] }} shrink-0">{{ $lesson['chip'] }}</span>
                </a>
            @endforeach
        </div>
    </section>

    {{-- Chapter boss quiz — not a v1 pack type. --}}

    <!-- =============== SECTION REWARDS =============== -->
    @if ($badges !== [])
        <section class="px-5 mt-5">
            <div class="section-head">
                <h2 class="h-display text-lg">{{ __('learn.chapter_rewards') }}</h2>
                <span class="link cursor-default">{{ __('learn.earn_all') }}</span>
            </div>
            <div class="grid grid-cols-3 gap-3">
                @foreach ($badges as $badge)
                    <a href="{{ $badge['href'] }}" wire:navigate
                        class="k-card p-3 text-center {{ $badge['locked'] ? 'locked' : '' }}">
                        <div class="badge-medal {{ $badge['medalClass'] }} mx-auto">{{ $badge['emoji'] }}</div>
                        <p class="text-xs font-extrabold mt-2">{{ $badge['name'] }}</p>
                        <p class="text-[10px] text-muted">{{ $badge['meta'] }}</p>
                    </a>
                @endforeach
            </div>
        </section>
    @endif

    <!-- =============== CHAPTER TIP =============== -->
    <section class="px-5 mt-5 mb-5">
        <div class="tip-card rounded-2xl p-4 flex items-start gap-3">
            <div class="mascot shrink-0 size-11 text-xl">🦉</div>
            <div class="grow">
                <p class="font-extrabold text-sm text-ink">{{ __('learn.chapter_tip') }}</p>
                <p class="text-xs text-muted">{{ __('learn.chapter_tip_body') }}</p>
            </div>
            <a href="{{ route('settings') }}" wire:navigate class="chip chip-primary">{{ __('learn.help') }}</a>
        </div>
    </section>

    <livewire:bottom-nav-bar />
</main>
