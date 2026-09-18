<?php

use App\Repositories\UserRepository;
use App\Services\ProgressReportService;
use Livewire\Component;

new class extends Component
{
    public function title(): string
    {
        return __('reports.optout_title');
    }

    public function rendering(\Illuminate\View\View $view): void
    {
        $view->title($this->title());
    }

    public function mount(int $user, UserRepository $users, ProgressReportService $reports): void
    {
        $reports->setWeeklyEmail($users->findOrFail($user), false);
    }
};
?>

<main class="device-frame min-h-screen flex flex-col safe-top">
    <header class="appbar">
        <a href="{{ route('user-login') }}" class="icon-btn" aria-label="{{ __('reports.back') }}"><i class="ph ph-caret-left"></i></a>
        <div class="grow">
            <p class="text-xs text-muted">{{ __('reports.parent_zone') }}</p>
            <h1 class="h-display text-lg leading-tight">{{ __('reports.optout_heading') }}</h1>
        </div>
    </header>
    <section class="px-5">
        <div class="k-card-lg hero-friends text-center">
            <div class="relative inline-grid place-items-center">
                <div class="size-24 rounded-3xl bg-white/20 backdrop-blur-sm grid place-items-center text-5xl">✉️</div>
            </div>
            <p class="relative h-display text-2xl mt-4 leading-tight">{{ __('reports.optout_heading') }}</p>
            <p class="relative text-xs text-white/90 mt-2">{{ __('reports.optout_body') }}</p>
        </div>
        <a href="{{ route('user-login') }}" class="btn btn-primary w-full mt-6">{{ __('reports.optout_home') }}</a>
    </section>
</main>
