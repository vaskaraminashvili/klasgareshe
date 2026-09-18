<?php

use App\Repositories\UserRepository;
use App\Services\UserProfileService;
use App\Services\UserStatService;
use Livewire\Component;

new class extends Component
{
    public string $name = 'Luna';

    public string $greeting = '';

    public string $avatar = '🐻';

    public string $avatarTile = 'tile-sun';

    public function mount(UserRepository $users, UserStatService $stats, UserProfileService $profiles): void
    {
        $user = $users->authenticated();

        $this->name = $user->name;
        $this->greeting = $this->greetingForHour(now()->hour);
        $this->avatar = $stats->avatarFor($user);
        $this->avatarTile = $profiles->tileForAvatar($this->avatar);
    }

    private function greetingForHour(int $hour): string
    {
        return match (true) {
            $hour < 12 => __('header.greet_morning'),
            $hour < 17 => __('header.greet_afternoon'),
            $hour < 21 => __('header.greet_evening'),
            default => __('header.greet_bedtime'),
        };
    }
};
?>

<!-- =============== TOP APPBAR =============== -->
<header class="px-5 pt-4 safe-top flex items-center gap-3">
    {{-- Online dot removed with the rest of the fake presence data; restore it when
         presence is real (docs/tasks/T18-ranking-depth.md). --}}
    <a href="{{ route('profile') }}" class="size-11 rounded-2xl grid place-items-center text-2xl {{ $avatarTile }} relative"
        aria-label="{{ __('header.open_profile') }}" wire:navigate>
        {{ $avatar }}
    </a>
    <div class="grow leading-tight">
        <p class="text-xs text-muted flex items-center gap-1"><span class="live-dot"> </span><span></span> <span
                id="greetLine">{{ $greeting }}</span></p>
        <p class="h-display text-lg">{{ __('header.hi_name', ['name' => $name]) }}</p>
    </div>
    <button id="searchIconBtn" type="button" class="icon-btn" aria-label="{{ __('header.search') }}"><i
            class="ph ph-magnifying-glass text-xl"></i></button>
    {{-- Bell + unread badge dropped: the sheet it opened had no real notifications behind it
         and the badge was a literal "3". Re-port both from kidzio/home.html together with
         the sheet in ⚡home.blade.php (docs/tasks/T16-notifications.md). --}}
    <button class="icon-btn" data-theme-toggle aria-label="{{ __('header.toggle_theme') }}">
        <i class="ph ph-moon text-xl"></i>
    </button>
</header>
