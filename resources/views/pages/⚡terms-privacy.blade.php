<?php

use Livewire\Component;

new class extends Component
{
    public string $tab = 'privacy';

    public function title(): string
    {
        return __('legal.terms_page_title');
    }

    public function rendering(\Illuminate\View\View $view): void
    {
        $view->title($this->title());
    }

    public function mount(\Illuminate\Http\Request $request): void
    {
        $requested = $request->query('tab');

        if (is_string($requested) && in_array($requested, ['privacy', 'terms', 'coppa', 'cookies'], true)) {
            $this->tab = $requested;
        }
    }
};
?>

<main class="device-frame min-h-screen flex flex-col safe-top">

    <header class="appbar">
        <a href="{{ route('user-register') }}" class="icon-btn" data-back aria-label="{{ __('legal.back') }}"><i
                class="ph ph-caret-left"></i></a>
        <div class="grow">
            <p class="text-xs text-muted">{{ __('legal.updated', ['date' => __('legal.updated_on')]) }}</p>
            <h1 class="h-display text-lg leading-tight">{{ __('legal.heading_terms') }}</h1>
        </div>
        <button type="button" class="icon-btn" aria-label="{{ __('legal.share') }}"><i
                class="ph ph-share-fat text-xl"></i></button>
    </header>

    <section class="px-5">
        <div class="k-card-lg hero-profile text-center">
            <div class="relative inline-grid place-items-center">
                <div class="size-24 rounded-3xl bg-white/20 backdrop-blur-sm grid place-items-center text-5xl">🛡️</div>
            </div>
            <p class="relative chip bg-white/20 border-0 text-white mt-4">
                <i class="ph-fill ph-shield-check"></i> {{ __('legal.kid_safe') }}
            </p>
            <p class="relative h-display text-2xl mt-2 leading-tight">{{ __('legal.hero_terms_title') }}</p>
            <p class="relative text-xs text-white/90 mt-1 max-w-[270px] mx-auto">{{ __('legal.hero_terms_body') }}</p>

            <div class="relative mt-4 grid grid-cols-3 gap-2">
                <div class="rounded-2xl bg-white/15 backdrop-blur-sm p-3">
                    <p class="h-display text-xl leading-none">{{ __('legal.stat_ads') }}</p>
                    <p class="text-[10px] text-white/85 mt-1">{{ __('legal.stat_ads_label') }}</p>
                </div>
                <div class="rounded-2xl bg-white/15 backdrop-blur-sm p-3">
                    <p class="h-display text-xl leading-none">{{ __('legal.stat_trackers') }}</p>
                    <p class="text-[10px] text-white/85 mt-1">{{ __('legal.stat_trackers_label') }}</p>
                </div>
                <div class="rounded-2xl bg-white/15 backdrop-blur-sm p-3">
                    <p class="h-display text-xl leading-none">{{ __('legal.stat_parent') }}</p>
                    <p class="text-[10px] text-white/85 mt-1">{{ __('legal.stat_parent_label') }}</p>
                </div>
            </div>
        </div>
    </section>

    <section class="px-5 mt-5">
        <p class="section-label">{{ __('legal.certified_label') }}</p>
        <div class="mt-3 grid grid-cols-4 gap-2">
            <div class="k-card p-3 text-center">
                <div class="size-10 mx-auto rounded-xl tile-sky grid place-items-center text-lg">🇺🇸</div>
                <p class="text-[11px] font-extrabold text-ink mt-2 leading-tight">{{ __('legal.badge_coppa') }}</p>
                <p class="text-[10px] text-muted leading-tight">{{ __('legal.badge_coppa_hint') }}</p>
            </div>
            <div class="k-card p-3 text-center">
                <div class="size-10 mx-auto rounded-xl tile-mint grid place-items-center text-lg">🇪🇺</div>
                <p class="text-[11px] font-extrabold text-ink mt-2 leading-tight">{{ __('legal.badge_gdpr') }}</p>
                <p class="text-[10px] text-muted leading-tight">{{ __('legal.badge_gdpr_hint') }}</p>
            </div>
            <div class="k-card p-3 text-center">
                <div class="size-10 mx-auto rounded-xl tile-violet grid place-items-center text-lg">🇬🇪</div>
                <p class="text-[11px] font-extrabold text-ink mt-2 leading-tight">{{ __('legal.badge_locale') }}</p>
                <p class="text-[10px] text-muted leading-tight">{{ __('legal.badge_locale_hint') }}</p>
            </div>
            <div class="k-card p-3 text-center">
                <div class="size-10 mx-auto rounded-xl tile-coral grid place-items-center text-lg">✓</div>
                <p class="text-[11px] font-extrabold text-ink mt-2 leading-tight">{{ __('legal.badge_ads') }}</p>
                <p class="text-[10px] text-muted leading-tight">{{ __('legal.badge_ads_hint') }}</p>
            </div>
        </div>
    </section>

    <section class="px-5 mt-5">
        <div data-swiper-rail-tabs class="swiper rail-swiper" data-tabs role="tablist" aria-label="{{ __('legal.heading_terms') }}">
            <div class="swiper-wrapper">
                <button type="button" class="swiper-slide chip {{ $tab === 'privacy' ? 'chip-primary' : '' }}"
                    data-tab="privacy" role="tab"
                    aria-selected="{{ $tab === 'privacy' ? 'true' : 'false' }}">{{ __('legal.tab_privacy') }}</button>
                <button type="button" class="swiper-slide chip {{ $tab === 'terms' ? 'chip-primary' : '' }}"
                    data-tab="terms" role="tab"
                    aria-selected="{{ $tab === 'terms' ? 'true' : 'false' }}">{{ __('legal.tab_terms') }}</button>
                <button type="button" class="swiper-slide chip {{ $tab === 'coppa' ? 'chip-primary' : '' }}"
                    data-tab="coppa" role="tab"
                    aria-selected="{{ $tab === 'coppa' ? 'true' : 'false' }}">{{ __('legal.tab_coppa') }}</button>
                <button type="button" class="swiper-slide chip {{ $tab === 'cookies' ? 'chip-primary' : '' }}"
                    data-tab="cookies" role="tab"
                    aria-selected="{{ $tab === 'cookies' ? 'true' : 'false' }}">{{ __('legal.tab_cookies') }}</button>
            </div>
        </div>
    </section>

    <section class="px-5 mt-4 {{ $tab === 'privacy' ? '' : 'hidden' }}" data-panel="privacy">
        <p class="section-label">{{ __('legal.collect_label') }}</p>
        <div class="mt-3 space-y-2">
            <div class="setting-row">
                <div class="setting-ico tile-mint"><i class="ph-fill ph-check-circle"></i></div>
                <div class="grow min-w-0">
                    <p class="setting-text font-extrabold text-sm text-ink">{{ __('legal.collect_progress') }}</p>
                    <p class="text-[11px] text-muted">{{ __('legal.collect_progress_hint') }}</p>
                </div>
            </div>
            <div class="setting-row">
                <div class="setting-ico tile-mint"><i class="ph-fill ph-check-circle"></i></div>
                <div class="grow min-w-0">
                    <p class="setting-text font-extrabold text-sm text-ink">{{ __('legal.collect_email') }}</p>
                    <p class="text-[11px] text-muted">{{ __('legal.collect_email_hint') }}</p>
                </div>
            </div>
            <div class="setting-row">
                <div class="setting-ico tile-mint"><i class="ph-fill ph-check-circle"></i></div>
                <div class="grow min-w-0">
                    <p class="setting-text font-extrabold text-sm text-ink">{{ __('legal.collect_profile') }}</p>
                    <p class="text-[11px] text-muted">{{ __('legal.collect_profile_hint') }}</p>
                </div>
            </div>
            <div class="setting-row">
                <div class="setting-ico tile-mint"><i class="ph-fill ph-check-circle"></i></div>
                <div class="grow min-w-0">
                    <p class="setting-text font-extrabold text-sm text-ink">{{ __('legal.collect_play') }}</p>
                    <p class="text-[11px] text-muted">{{ __('legal.collect_play_hint') }}</p>
                </div>
            </div>
        </div>

        <p class="section-label mt-5">{{ __('legal.never_label') }}</p>
        <div class="mt-3 space-y-2">
            <div class="setting-row danger">
                <div class="setting-ico"><i class="ph-fill ph-x-circle"></i></div>
                <div class="grow min-w-0">
                    <p class="setting-text font-extrabold text-sm">{{ __('legal.never_ads') }}</p>
                    <p class="text-[11px] text-muted">{{ __('legal.never_ads_hint') }}</p>
                </div>
            </div>
            <div class="setting-row danger">
                <div class="setting-ico"><i class="ph-fill ph-x-circle"></i></div>
                <div class="grow min-w-0">
                    <p class="setting-text font-extrabold text-sm">{{ __('legal.never_sell') }}</p>
                    <p class="text-[11px] text-muted">{{ __('legal.never_sell_hint') }}</p>
                </div>
            </div>
            <div class="setting-row danger">
                <div class="setting-ico"><i class="ph-fill ph-x-circle"></i></div>
                <div class="grow min-w-0">
                    <p class="setting-text font-extrabold text-sm">{{ __('legal.never_track') }}</p>
                    <p class="text-[11px] text-muted">{{ __('legal.never_track_hint') }}</p>
                </div>
            </div>
        </div>
    </section>

    <section class="px-5 mt-4 {{ $tab === 'terms' ? '' : 'hidden' }}" data-panel="terms">
        <p class="section-label">{{ __('legal.terms_short_label') }}</p>
        <div class="mt-3 space-y-2">
            <div class="setting-row">
                <div class="setting-ico tile-violet"><i class="ph-fill ph-handshake"></i></div>
                <div class="grow min-w-0">
                    <p class="setting-text font-extrabold text-sm text-ink">{{ __('legal.terms_parent') }}</p>
                    <p class="text-[11px] text-muted">{{ __('legal.terms_parent_hint') }}</p>
                </div>
            </div>
            <div class="setting-row">
                <div class="setting-ico tile-sun"><i class="ph-fill ph-gift"></i></div>
                <div class="grow min-w-0">
                    <p class="setting-text font-extrabold text-sm text-ink">{{ __('legal.terms_free') }}</p>
                    <p class="text-[11px] text-muted">{{ __('legal.terms_free_hint') }}</p>
                </div>
            </div>
            <div class="setting-row">
                <div class="setting-ico tile-mint"><i class="ph-fill ph-users-four"></i></div>
                <div class="grow min-w-0">
                    <p class="setting-text font-extrabold text-sm text-ink">{{ __('legal.terms_family') }}</p>
                    <p class="text-[11px] text-muted">{{ __('legal.terms_family_hint') }}</p>
                </div>
            </div>
            <div class="setting-row danger">
                <div class="setting-ico"><i class="ph-fill ph-prohibit"></i></div>
                <div class="grow min-w-0">
                    <p class="setting-text font-extrabold text-sm">{{ __('legal.terms_scrape') }}</p>
                    <p class="text-[11px] text-muted">{{ __('legal.terms_scrape_hint') }}</p>
                </div>
            </div>
        </div>
    </section>

    <section class="px-5 mt-4 {{ $tab === 'coppa' ? '' : 'hidden' }}" data-panel="coppa">
        <p class="section-label">{{ __('legal.coppa_label') }}</p>
        <div class="mt-3 space-y-3">
            <div class="k-card p-4 flex items-start gap-3">
                <div class="size-10 rounded-xl tile-sky grid place-items-center text-lg shrink-0 font-extrabold text-sky-ink">1</div>
                <div class="grow">
                    <p class="font-extrabold text-sm text-ink">{{ __('legal.coppa_1') }}</p>
                    <p class="text-[11px] text-muted mt-1">{{ __('legal.coppa_1_hint') }}</p>
                </div>
            </div>
            <div class="k-card p-4 flex items-start gap-3">
                <div class="size-10 rounded-xl tile-mint grid place-items-center text-lg shrink-0 font-extrabold text-mint-ink">2</div>
                <div class="grow">
                    <p class="font-extrabold text-sm text-ink">{{ __('legal.coppa_2') }}</p>
                    <p class="text-[11px] text-muted mt-1">{{ __('legal.coppa_2_hint') }}</p>
                </div>
            </div>
            <div class="k-card p-4 flex items-start gap-3">
                <div class="size-10 rounded-xl tile-violet grid place-items-center text-lg shrink-0 font-extrabold">3</div>
                <div class="grow">
                    <p class="font-extrabold text-sm text-ink">{{ __('legal.coppa_3') }}</p>
                    <p class="text-[11px] text-muted mt-1">{{ __('legal.coppa_3_hint') }}</p>
                </div>
            </div>
            <div class="k-card p-4 flex items-start gap-3">
                <div class="size-10 rounded-xl tile-coral grid place-items-center text-lg shrink-0 font-extrabold text-coral-ink">4</div>
                <div class="grow">
                    <p class="font-extrabold text-sm text-ink">{{ __('legal.coppa_4') }}</p>
                    <p class="text-[11px] text-muted mt-1">{{ __('legal.coppa_4_hint') }}</p>
                </div>
            </div>
        </div>
    </section>

    <section class="px-5 mt-4 {{ $tab === 'cookies' ? '' : 'hidden' }}" data-panel="cookies">
        <p class="section-label">{{ __('legal.cookies_label') }}</p>
        <div class="mt-3 space-y-2">
            <div class="setting-row">
                <div class="setting-ico tile-mint"><i class="ph-fill ph-check-circle"></i></div>
                <div class="grow min-w-0">
                    <p class="setting-text font-extrabold text-sm text-ink">{{ __('legal.cookies_session') }}</p>
                    <p class="text-[11px] text-muted">{{ __('legal.cookies_session_hint') }}</p>
                </div>
            </div>
            <div class="setting-row">
                <div class="setting-ico tile-sky"><i class="ph-fill ph-check-circle"></i></div>
                <div class="grow min-w-0">
                    <p class="setting-text font-extrabold text-sm text-ink">{{ __('legal.cookies_theme') }}</p>
                    <p class="text-[11px] text-muted">{{ __('legal.cookies_theme_hint') }}</p>
                </div>
            </div>
            <div class="setting-row danger">
                <div class="setting-ico"><i class="ph-fill ph-x-circle"></i></div>
                <div class="grow min-w-0">
                    <p class="setting-text font-extrabold text-sm">{{ __('legal.cookies_no_ads') }}</p>
                    <p class="text-[11px] text-muted">{{ __('legal.cookies_no_ads_hint') }}</p>
                </div>
            </div>
            <div class="setting-row danger">
                <div class="setting-ico"><i class="ph-fill ph-x-circle"></i></div>
                <div class="grow min-w-0">
                    <p class="setting-text font-extrabold text-sm">{{ __('legal.cookies_no_third') }}</p>
                    <p class="text-[11px] text-muted">{{ __('legal.cookies_no_third_hint') }}</p>
                </div>
            </div>
        </div>
        {{-- Manage storage → docs/tasks/T10-settings-screen.md --}}
    </section>

    <section class="px-5 mt-5">
        <p class="section-label">{{ __('legal.rights_quick') }}</p>
        <div class="mt-3 space-y-2">
            {{-- Export PDF → docs/tasks/T09-account-and-data.md --}}
            {{-- Clear cache → docs/tasks/T10-settings-screen.md --}}
            <a href="{{ route('privacy-policy') }}" wire:navigate class="setting-row">
                <div class="setting-ico tile-mint"><i class="ph-fill ph-shield-check"></i></div>
                <div class="grow min-w-0">
                    <p class="setting-text font-extrabold text-sm text-ink">{{ __('legal.rights_policy') }}</p>
                    <p class="text-[11px] text-muted">{{ __('legal.rights_policy_hint') }}</p>
                </div>
                <i class="ph ph-caret-right text-muted"></i>
            </a>
            {{-- Delete account → docs/tasks/T09-account-and-data.md --}}
        </div>
    </section>

    <section class="px-5 mt-5">
        <p class="section-label">{{ __('legal.full_text_label') }}</p>
        <div class="mt-3 space-y-2">

            <details class="k-card p-0 overflow-hidden group">
                <summary class="flex items-center gap-3 p-4 cursor-pointer list-none">
                    <div class="size-10 rounded-xl tile-sky grid place-items-center"><i class="ph-fill ph-shield-check"></i></div>
                    <p class="font-extrabold text-sm grow text-ink">{{ __('legal.acc_privacy') }}</p>
                    <i class="ph ph-caret-down text-muted group-open:rotate-180 transition-transform"></i>
                </summary>
                <div class="px-4 pb-4 text-[12px] text-muted leading-relaxed space-y-2 border-t border-token pt-3">
                    <p>{{ __('legal.acc_privacy_p1') }}</p>
                    <p>{{ __('legal.acc_privacy_p2') }}</p>
                    <p>{{ __('legal.acc_privacy_p3') }}</p>
                    <p>{{ __('legal.acc_privacy_p4') }}</p>
                </div>
            </details>

            <details class="k-card p-0 overflow-hidden group">
                <summary class="flex items-center gap-3 p-4 cursor-pointer list-none">
                    <div class="size-10 rounded-xl tile-violet grid place-items-center"><i class="ph-fill ph-file-text"></i></div>
                    <p class="font-extrabold text-sm grow text-ink">{{ __('legal.acc_terms') }}</p>
                    <i class="ph ph-caret-down text-muted group-open:rotate-180 transition-transform"></i>
                </summary>
                <div class="px-4 pb-4 text-[12px] text-muted leading-relaxed space-y-2 border-t border-token pt-3">
                    <p>{{ __('legal.acc_terms_p1') }}</p>
                    <p>{{ __('legal.acc_terms_p2') }}</p>
                    <p>{{ __('legal.acc_terms_p3') }}</p>
                </div>
            </details>

            <details class="k-card p-0 overflow-hidden group">
                <summary class="flex items-center gap-3 p-4 cursor-pointer list-none">
                    <div class="size-10 rounded-xl tile-mint grid place-items-center"><i class="ph-fill ph-users-three"></i></div>
                    <p class="font-extrabold text-sm grow text-ink">{{ __('legal.acc_consent') }}</p>
                    <i class="ph ph-caret-down text-muted group-open:rotate-180 transition-transform"></i>
                </summary>
                <div class="px-4 pb-4 text-[12px] text-muted leading-relaxed space-y-2 border-t border-token pt-3">
                    <p>{{ __('legal.acc_consent_p1') }}</p>
                    <p>{{ __('legal.acc_consent_p2') }}</p>
                    <p>{{ __('legal.acc_consent_p3') }}</p>
                </div>
            </details>

            <details class="k-card p-0 overflow-hidden group">
                <summary class="flex items-center gap-3 p-4 cursor-pointer list-none">
                    <div class="size-10 rounded-xl tile-sun grid place-items-center">🍪</div>
                    <p class="font-extrabold text-sm grow text-ink">{{ __('legal.acc_cookies') }}</p>
                    <i class="ph ph-caret-down text-muted group-open:rotate-180 transition-transform"></i>
                </summary>
                <div class="px-4 pb-4 text-[12px] text-muted leading-relaxed space-y-2 border-t border-token pt-3">
                    <p>{{ __('legal.acc_cookies_p1') }}</p>
                    <p>{{ __('legal.acc_cookies_p2') }}</p>
                </div>
            </details>

            <details class="k-card p-0 overflow-hidden group">
                <summary class="flex items-center gap-3 p-4 cursor-pointer list-none">
                    <div class="size-10 rounded-xl tile-coral grid place-items-center"><i class="ph-fill ph-trash"></i></div>
                    <p class="font-extrabold text-sm grow text-ink">{{ __('legal.acc_rights') }}</p>
                    <i class="ph ph-caret-down text-muted group-open:rotate-180 transition-transform"></i>
                </summary>
                <div class="px-4 pb-4 text-[12px] text-muted leading-relaxed space-y-2 border-t border-token pt-3">
                    <p><b class="text-ink">{{ __('legal.acc_rights_access') }}</b> {{ __('legal.acc_rights_access_body') }}</p>
                    <p><b class="text-ink">{{ __('legal.acc_rights_optout') }}</b> {{ __('legal.acc_rights_optout_body') }}</p>
                    <p><b class="text-ink">{{ __('legal.acc_rights_delete') }}</b> {{ __('legal.acc_rights_delete_body') }}</p>
                    <p><b class="text-ink">{{ __('legal.acc_rights_contact') }}</b> {{ __('legal.contact_email') }}</p>
                </div>
            </details>

            <details class="k-card p-0 overflow-hidden group">
                <summary class="flex items-center gap-3 p-4 cursor-pointer list-none">
                    <div class="size-10 rounded-xl tile-pink grid place-items-center"><i class="ph-fill ph-pencil-simple"></i></div>
                    <p class="font-extrabold text-sm grow text-ink">{{ __('legal.acc_changes') }}</p>
                    <i class="ph ph-caret-down text-muted group-open:rotate-180 transition-transform"></i>
                </summary>
                <div class="px-4 pb-4 text-[12px] text-muted leading-relaxed space-y-2 border-t border-token pt-3">
                    <p>{{ __('legal.acc_changes_p1') }}</p>
                    <p>{{ __('legal.acc_changes_p2') }}</p>
                </div>
            </details>

        </div>
    </section>

    <section class="px-5 mt-5 mb-10">
        <div class="tip-card rounded-2xl p-4 flex items-start gap-3">
            <div class="mascot shrink-0 size-11 text-xl">🦉</div>
            <div class="grow">
                <p class="font-extrabold text-sm text-ink">{{ __('legal.questions') }}</p>
                <p class="text-xs text-muted">{{ __('legal.questions_body', ['email' => __('legal.contact_email')]) }}</p>
            </div>
            <a href="mailto:{{ __('legal.contact_email') }}" class="chip chip-primary">{{ __('legal.contact') }}</a>
        </div>
        {{-- Export PDF / Contact screen → docs/tasks/T09-account-and-data.md and docs/tasks/T21-sound-voice-appearance.md --}}
        <p class="text-[11px] text-center text-muted mt-5">{{ __('legal.footer', ['date' => __('legal.updated_on')]) }}</p>
    </section>
</main>

@push('scripts')
    <script src="{{ asset('assets/js/terms-privacy.js') }}"></script>
@endpush
