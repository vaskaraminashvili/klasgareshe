<?php

use App\Repositories\UserRepository;
use App\Services\BadgeService;
use Illuminate\View\View;
use Livewire\Attributes\Locked;
use Livewire\Component;

new class extends Component
{
    #[Locked]
    public string $slug = '';

    public string $name = '';

    public string $blurb = '';

    public string $emoji = '';

    public string $medalClass = '';

    public string $rarityLabel = '';

    public int $xpBonus = 100;

    public int $holderPercent = 1;

    public function title(): string
    {
        return __('badges.unlock_page_title');
    }

    public function rendering(View $view): void
    {
        $view->title($this->title());
    }

    public function mount(BadgeService $badges, UserRepository $users, string $slug): void
    {
        $user = $users->authenticated();
        $view = $badges->unlockView($user, $slug);

        if ($view === null) {
            $this->redirectRoute('badges', navigate: true);

            return;
        }

        $badges->markSeen($user, $slug);

        $this->slug = $view->slug;
        $this->name = $view->name;
        $this->blurb = $view->blurb;
        $this->emoji = $view->emoji;
        $this->medalClass = $view->medalClass;
        $this->rarityLabel = $view->rarityLabel;
        $this->xpBonus = $view->xpBonus;
        $this->holderPercent = $view->holderPercent;
    }
};
?>

@push('styles')
    <style>
        html,
        body {
            background: #2A1769 !important;
        }

        .device-frame {
            background: linear-gradient(180deg, #2A1769 0%, #4B2FD6 55%, #6A49F0 100%) !important;
        }

        @keyframes spin {
            0% {
                transform: rotate(-4deg)
            }

            50% {
                transform: rotate(4deg)
            }

            100% {
                transform: rotate(-4deg)
            }
        }
    </style>
@endpush

<main class="device-frame min-h-screen flex flex-col text-white relative overflow-hidden">
    <div class="absolute inset-0 confetti-bg opacity-25"></div>
    <div class="absolute -top-20 left-1/2 -translate-x-1/2 w-[420px] h-[420px] rounded-full bg-[#FFD66B]/25 blur-3xl">
    </div>

    <header class="relative z-10 px-5 pt-4 safe-top flex items-center justify-between">
        <a href="{{ route('badges') }}" wire:navigate class="icon-btn"
            style="background:rgba(255,255,255,.16);color:white;border:1px solid rgba(255,255,255,.28);"><i
                class="ph ph-caret-left"></i></a>
        <p class="font-extrabold text-white">{{ __('badges.new_badge') }}</p>
        <button type="button" class="icon-btn"
            style="background:rgba(255,255,255,.16);color:white;border:1px solid rgba(255,255,255,.28);"><i
                class="ph ph-share-fat"></i></button>
    </header>

    <section class="relative z-10 text-center px-6 pt-8">
        <span class="chip" style="background:rgba(255,214,107,.22);color:#FFE27A;border-color:rgba(255,214,107,.45);">
            <i class="ph-fill ph-sparkle"></i> {{ __('badges.badge_unlocked') }}
        </span>
        <div class="relative inline-grid place-items-center mt-5">
            <div class="absolute size-72 rounded-full bg-[#FFD66B]/30 blur-2xl"></div>
            <div class="relative badge-medal {{ $medalClass }}"
                style="width:180px;height:180px;font-size:84px;animation:spin 10s linear infinite;">{{ $emoji }}</div>
        </div>
        <h1 class="h-display text-4xl mt-8 leading-tight"
            style="color:#ffffff;text-shadow:0 2px 20px rgba(0,0,0,.25);">{{ $name }}!</h1>
        <p class="mt-3 max-w-[300px] mx-auto" style="color:rgba(255,255,255,.92);">{{ $blurb }}</p>
    </section>

    <section class="relative z-10 px-6 mt-8">
        <div class="k-card-lg grid grid-cols-3 gap-3 text-center"
            style="background:#ffffff;border-color:transparent;color:#1B1240;box-shadow:0 20px 50px -20px rgba(0,0,0,.45);">
            <div>
                <p class="text-xs font-bold" style="color:#5B5178">{{ __('badges.xp_bonus') }}</p>
                <p class="h-display text-xl mt-1" style="color:#4B2FD6">+{{ $xpBonus }}</p>
            </div>
            <div class="border-x" style="border-color:#EDE7F6">
                <p class="text-xs font-bold" style="color:#5B5178">{{ __('badges.rarity_label') }}</p>
                <p class="h-display text-xl mt-1" style="color:#1B1240">{{ $rarityLabel }}</p>
            </div>
            <div>
                <p class="text-xs font-bold" style="color:#5B5178">{{ __('badges.holders') }}</p>
                <p class="h-display text-xl mt-1" style="color:#1B1240">{{ $holderPercent }}%</p>
            </div>
        </div>
    </section>

    <div class="mt-auto relative z-10 px-6 pb-8 pt-10 safe-bottom space-y-3">
        <button type="button" class="btn w-full"
            style="background:#FFE27A;color:#4B2FD6;box-shadow:0 10px 24px -10px rgba(255,214,107,.6), inset 0 -3px 0 rgba(0,0,0,.12);">
            <i class="ph-fill ph-share-fat"></i> {{ __('badges.share_friends') }}
        </button>
        <a href="{{ route('badges') }}" wire:navigate class="btn w-full"
            style="background:rgba(255,255,255,.14);color:#ffffff;border:1px solid rgba(255,255,255,.35);">{{ __('badges.view_all') }}</a>
    </div>
</main>
