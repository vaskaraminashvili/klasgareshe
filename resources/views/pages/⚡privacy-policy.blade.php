<?php

use Livewire\Component;

new class extends Component
{
    public function title(): string
    {
        return __('legal.privacy_page_title');
    }

    public function rendering(\Illuminate\View\View $view): void
    {
        $view->title($this->title());
    }
};
?>

<main class="device-frame min-h-screen flex flex-col safe-top">

    <header class="appbar">
        <a href="{{ route('terms-privacy') }}" class="icon-btn" data-back aria-label="{{ __('legal.back') }}"><i
                class="ph ph-caret-left"></i></a>
        <div class="grow">
            <p class="text-xs text-muted">{{ __('legal.updated_short', ['date' => __('legal.updated_on')]) }}</p>
            <h1 class="h-display text-lg leading-tight">{{ __('legal.heading_privacy') }}</h1>
        </div>
        <button type="button" class="icon-btn" data-theme-toggle aria-label="{{ __('legal.toggle_theme') }}"><i
                class="ph ph-moon text-xl"></i></button>
    </header>

    <section class="px-5">
        <div class="k-card-lg hero-profile text-center">
            <div class="relative inline-grid place-items-center">
                <div class="size-24 rounded-3xl bg-white/20 backdrop-blur-sm grid place-items-center text-5xl">🛡️</div>
            </div>
            <p class="relative chip bg-white/20 border-0 text-white mt-4">{{ __('legal.kid_safe') }}</p>
            <p class="relative h-display text-2xl mt-2 leading-tight">{{ __('legal.hero_privacy_title') }}</p>
            <p class="relative text-xs text-white/90 mt-1 max-w-[260px] mx-auto">{{ __('legal.hero_privacy_body') }}</p>
        </div>
    </section>

    <section class="px-5 mt-5">
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
    </section>

    <section class="px-5 mt-5">
        <p class="section-label">{{ __('legal.never_label') }}</p>
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

    <section class="px-5 mt-5">
        <p class="section-label">{{ __('legal.rights_label') }}</p>
        <div class="mt-3 space-y-2">
            {{-- Export PDF → docs/tasks/T09-account-and-data.md --}}
            {{-- Clear cache → docs/tasks/T10-settings-screen.md --}}
            {{-- Delete account → docs/tasks/T09-account-and-data.md --}}
            <a href="{{ route('terms-privacy') }}" wire:navigate class="setting-row">
                <div class="setting-ico tile-mint"><i class="ph-fill ph-shield-check"></i></div>
                <div class="grow min-w-0">
                    <p class="setting-text font-extrabold text-sm text-ink">{{ __('legal.heading_terms') }}</p>
                    <p class="text-[11px] text-muted">{{ __('legal.hero_terms_body') }}</p>
                </div>
                <i class="ph ph-caret-right text-muted"></i>
            </a>
        </div>
    </section>

    <section class="px-5 mt-5">
        <p class="section-label">{{ __('legal.full_policy_label') }}</p>
        <div class="mt-3 space-y-2">

            <details class="k-card p-0 overflow-hidden group">
                <summary class="flex items-center gap-3 p-4 cursor-pointer list-none">
                    <div class="size-10 rounded-xl tile-sky grid place-items-center"><i class="ph-fill ph-info"></i></div>
                    <p class="font-extrabold text-sm grow text-ink">{{ __('legal.acc_data') }}</p>
                    <i class="ph ph-caret-down text-muted group-open:rotate-180 transition-transform"></i>
                </summary>
                <div class="px-4 pb-4 text-[12px] text-muted leading-relaxed space-y-2 border-t border-token pt-3">
                    <p>{{ __('legal.acc_data_p1') }}</p>
                    <p>{{ __('legal.acc_privacy_p2') }}</p>
                    <p>{{ __('legal.acc_data_p2') }}</p>
                    <p>{{ __('legal.acc_rights_optout_body') }}</p>
                </div>
            </details>

            <details class="k-card p-0 overflow-hidden group">
                <summary class="flex items-center gap-3 p-4 cursor-pointer list-none">
                    <div class="size-10 rounded-xl tile-mint grid place-items-center"><i class="ph-fill ph-users-three"></i></div>
                    <p class="font-extrabold text-sm grow text-ink">{{ __('legal.acc_consent_privacy') }}</p>
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
                    <div class="size-10 rounded-xl tile-violet grid place-items-center"><i class="ph-fill ph-globe"></i></div>
                    <p class="font-extrabold text-sm grow text-ink">{{ __('legal.acc_gdpr') }}</p>
                    <i class="ph ph-caret-down text-muted group-open:rotate-180 transition-transform"></i>
                </summary>
                <div class="px-4 pb-4 text-[12px] text-muted leading-relaxed space-y-2 border-t border-token pt-3">
                    <p>{{ __('legal.acc_gdpr_p1') }}</p>
                    <p>{{ __('legal.acc_gdpr_p2') }}</p>
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
        <p class="text-[11px] text-center text-muted mt-5">{{ __('legal.footer_short') }}</p>
    </section>
</main>
