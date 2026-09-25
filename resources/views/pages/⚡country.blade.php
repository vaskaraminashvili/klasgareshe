<?php

use App\Repositories\UserRepository;
use App\Services\CountryService;
use Livewire\Component;

new class extends Component
{
    public ?string $selected = null;

    public string $selectedName = '';

    public string $selectedEmoji = '🌎';

    public ?int $yourRank = null;

    public int $learnersHere = 0;

    public int $countryCount = 0;

    /** @var list<array{code: string, emoji: string, name: string, continent: string, keywords: string, learners: int, weekXp: int}> */
    public array $climbers = [];

    /** @var list<array{code: string, emoji: string, name: string, continent: string, keywords: string, learners: int, weekXp: int}> */
    public array $countries = [];

    public function title(): string
    {
        return __('country.page_title');
    }

    public function rendering(\Illuminate\View\View $view): void
    {
        $view->title($this->title());
    }

    public function mount(CountryService $countries, UserRepository $users): void
    {
        $this->applyPage($countries->page($users->authenticated()));
    }

    public function choose(string $code, CountryService $countries, UserRepository $users): void
    {
        $countries->choose($users->authenticated(), $code === 'world' ? null : $code);
        $this->applyPage($countries->page($users->authenticated()));
    }

    /**
     * @param  array{
     *     selected: ?string,
     *     selectedName: string,
     *     selectedEmoji: string,
     *     yourRank: ?int,
     *     learnersHere: int,
     *     countryCount: int,
     *     climbers: list<array{code: string, emoji: string, name: string, continent: string, keywords: string, learners: int, weekXp: int}>,
     *     countries: list<array{code: string, emoji: string, name: string, continent: string, keywords: string, learners: int, weekXp: int}>
     * }  $page
     */
    private function applyPage(array $page): void
    {
        $this->selected = $page['selected'];
        $this->selectedName = $page['selectedName'];
        $this->selectedEmoji = $page['selectedEmoji'];
        $this->yourRank = $page['yourRank'];
        $this->learnersHere = $page['learnersHere'];
        $this->countryCount = $page['countryCount'];
        $this->climbers = $page['climbers'];
        $this->countries = $page['countries'];
    }
};
?>

<main class="device-frame min-h-screen flex flex-col safe-top">

    <header class="appbar">
        <a href="{{ route('settings') }}" class="icon-btn" data-back aria-label="{{ __('country.back') }}"><i class="ph ph-caret-left"></i></a>
        <div class="grow">
            <p class="text-xs text-muted">{{ __('country.eyebrow') }}</p>
            <h1 class="h-display text-lg leading-tight">{{ __('country.heading') }}</h1>
        </div>
        <button class="icon-btn" data-theme-toggle aria-label="Toggle theme"><i class="ph ph-moon text-xl"></i></button>
    </header>

    <section class="px-5">
        <div class="k-card-lg hero-knowledge text-center">
            <div class="relative inline-grid place-items-center">
                <div class="size-24 rounded-3xl bg-white/20 backdrop-blur-sm grid place-items-center text-5xl">🌎</div>
            </div>
            <p class="relative chip bg-white/20 border-0 text-white mt-4">{{ __('country.ranking_region') }}</p>
            <p class="relative h-display text-2xl mt-2 leading-tight">{{ $selectedEmoji }} {{ $selectedName }}</p>
            <p class="relative text-xs text-white/90 mt-1">{{ __('country.compete', ['count' => number_format($learnersHere)]) }}</p>

            <div class="relative mt-4 grid grid-cols-3 gap-2">
                <div class="rounded-2xl bg-white/15 backdrop-blur-sm p-3">
                    <p class="h-display text-xl leading-none">{{ $yourRank === null ? '—' : '#'.$yourRank }}</p>
                    <p class="text-[10px] text-white/85 mt-1">{{ __('country.your_rank') }}</p>
                </div>
                <div class="rounded-2xl bg-white/15 backdrop-blur-sm p-3">
                    <p class="h-display text-xl leading-none">{{ number_format($learnersHere) }}</p>
                    <p class="text-[10px] text-white/85 mt-1">{{ __('country.learners') }}</p>
                </div>
                <div class="rounded-2xl bg-white/15 backdrop-blur-sm p-3">
                    <p class="h-display text-xl leading-none">{{ $countryCount }}</p>
                    <p class="text-[10px] text-white/85 mt-1">{{ __('country.countries') }}</p>
                </div>
            </div>
        </div>
    </section>

    <section class="px-5 mt-4">
        <div class="input-wrap">
            <i class="ph ph-magnifying-glass i-left"></i>
            <input id="countrySearch" class="input has-left" placeholder="{{ __('country.search_placeholder') }}" aria-label="{{ __('country.search_aria') }}" autocomplete="off"/>
            <button type="button" id="countryClear" class="i-right hidden" aria-label="{{ __('country.clear') }}">
                <i class="ph ph-x-circle text-muted"></i>
            </button>
        </div>
    </section>

    <section class="px-5 mt-3">
        <div data-swiper-rail-tabs class="swiper rail-swiper" role="tablist" aria-label="{{ __('country.filter_aria') }}">
            <div class="swiper-wrapper">
                <button type="button" class="swiper-slide chip chip-primary" data-continent="all" aria-selected="true">{{ __('country.continents.all') }}</button>
                <button type="button" class="swiper-slide chip" data-continent="americas">{{ __('country.continents.americas') }}</button>
                <button type="button" class="swiper-slide chip" data-continent="europe">{{ __('country.continents.europe') }}</button>
                <button type="button" class="swiper-slide chip" data-continent="asia">{{ __('country.continents.asia') }}</button>
                <button type="button" class="swiper-slide chip" data-continent="oceania">{{ __('country.continents.oceania') }}</button>
                <button type="button" class="swiper-slide chip" data-continent="africa">{{ __('country.continents.africa') }}</button>
            </div>
        </div>
    </section>

    <section id="noResults" class="px-5 mt-6 hidden">
        <div class="k-card text-center p-6">
            <div class="size-16 mx-auto rounded-2xl tile-sky grid place-items-center text-3xl">🔍</div>
            <p class="h-display text-lg mt-3 text-ink">{{ __('country.no_matches') }}</p>
            <p class="text-xs text-muted mt-1">{{ __('country.no_matches_hint') }}</p>
        </div>
    </section>

    @if ($climbers !== [])
        <section data-search-section data-continent-wrap="all" class="px-5 mt-4">
            <div class="flex items-end justify-between">
                <p class="section-label" data-section-label><span data-label-text>{{ __('country.climbers') }}</span></p>
                <span class="text-[11px] text-muted">{{ __('country.climbers_hint') }}</span>
            </div>
            <div class="mt-3 grid grid-cols-3 gap-2">
                @foreach ($climbers as $row)
                    <button type="button" class="k-card p-3 text-center" wire:click="choose('{{ $row['code'] }}')" data-country data-keywords="{{ $row['keywords'] }}" data-continent="{{ $row['continent'] }}">
                        <div class="text-3xl">{{ $row['emoji'] }}</div>
                        <p class="font-extrabold text-xs text-ink mt-2 leading-tight">{{ $row['name'] }}</p>
                        <p class="text-[10px] text-mint-ink font-extrabold mt-0.5">{{ __('country.week_xp', ['xp' => number_format($row['weekXp'])]) }}</p>
                    </button>
                @endforeach
            </div>
        </section>
    @endif

    <section data-search-section data-continent-wrap="all" class="px-5 mt-5">
        <p class="section-label" data-section-label><span data-label-text>{{ __('country.current') }}</span></p>
        @if ($selected === null)
            <button type="button" class="setting-row w-full text-left mt-3" wire:click="choose('world')" data-country data-keywords="worldwide global world earth" data-continent="all">
                <div class="setting-ico tile-sky text-2xl">🌎</div>
                <div class="grow min-w-0">
                    <p class="setting-text font-extrabold text-sm text-ink">{{ __('country.worldwide') }}</p>
                    <p class="text-[11px] text-muted">{{ __('country.learners_count', ['count' => number_format($learnersHere)]) }}</p>
                </div>
                <span class="chip chip-mint">{{ __('country.active') }}</span>
            </button>
        @else
            @foreach ($countries as $row)
                @if ($row['code'] === $selected)
                    <button type="button" class="setting-row w-full text-left mt-3" wire:click="choose('{{ $row['code'] }}')" data-country data-keywords="{{ $row['keywords'] }}" data-continent="{{ $row['continent'] }}">
                        <div class="setting-ico tile-sky text-2xl">{{ $row['emoji'] }}</div>
                        <div class="grow min-w-0">
                            <p class="setting-text font-extrabold text-sm text-ink">{{ $row['name'] }}</p>
                            <p class="text-[11px] text-muted">
                                @if ($yourRank !== null)
                                    {{ __('country.rank_line', ['count' => number_format($row['learners']), 'rank' => $yourRank]) }}
                                @else
                                    {{ __('country.unranked', ['count' => number_format($row['learners'])]) }}
                                @endif
                            </p>
                        </div>
                        <span class="chip chip-mint">{{ __('country.active') }}</span>
                    </button>
                @endif
            @endforeach
        @endif
    </section>

    <section data-search-section class="px-5 mt-5 mb-5">
        <p class="section-label" data-section-label><span data-label-text>{{ __('country.all') }}</span></p>
        <div class="mt-3 space-y-2">
            <button type="button" class="setting-row w-full text-left" wire:click="choose('world')" data-country data-keywords="worldwide global world earth" data-continent="all">
                <div class="setting-ico tile-sky text-2xl">🌎</div>
                <div class="grow min-w-0">
                    <p class="setting-text font-extrabold text-sm text-ink">{{ __('country.worldwide') }}</p>
                    <p class="text-[11px] text-muted">{{ __('country.learners_count', ['count' => number_format($learnersHere)]) }}</p>
                </div>
                @if ($selected === null)
                    <span class="chip chip-mint">{{ __('country.active') }}</span>
                @else
                    <i class="ph ph-caret-right text-muted"></i>
                @endif
            </button>
            @foreach ($countries as $row)
                <button type="button" class="setting-row w-full text-left" wire:click="choose('{{ $row['code'] }}')" data-country data-keywords="{{ $row['keywords'] }}" data-continent="{{ $row['continent'] }}">
                    <div class="setting-ico tile-mint text-2xl">{{ $row['emoji'] }}</div>
                    <div class="grow min-w-0">
                        <p class="setting-text font-extrabold text-sm text-ink">{{ $row['name'] }}</p>
                        <p class="text-[11px] text-muted">{{ __('country.learners_count', ['count' => number_format($row['learners'])]) }}</p>
                    </div>
                    <div class="flex items-center gap-1 shrink-0">
                        @if ($row['weekXp'] > 0)
                            <span class="text-[10px] text-mint-ink font-extrabold">{{ __('country.week_xp', ['xp' => number_format($row['weekXp'])]) }}</span>
                        @else
                            <span class="text-[10px] text-muted font-extrabold">{{ __('country.week_flat') }}</span>
                        @endif
                        @if ($selected === $row['code'])
                            <span class="chip chip-mint">{{ __('country.active') }}</span>
                        @else
                            <i class="ph ph-caret-right text-muted"></i>
                        @endif
                    </div>
                </button>
            @endforeach
        </div>
    </section>
</main>

@push('scripts')
    <script src="{{ asset('assets/js/country.js') }}"></script>
@endpush
