<?php

use App\Enums\ReminderTime;
use App\Repositories\UserRepository;
use App\Services\KidSetupService;
use App\Services\ParentVerificationService;
use Illuminate\Validation\Rule;
use Livewire\Component;

new class extends Component
{
    public bool $streak = true;

    public bool $newLessons = true;

    public bool $rewards = false;

    public bool $dailyMission = true;

    public bool $friendActivity = false;

    public bool $quietHours = true;

    public string $reminderTime = 'evening';

    public string $kidName = '';

    public function title(): string
    {
        return __('onboarding.notifications.page_title');
    }

    public function rendering(\Illuminate\View\View $view): void
    {
        $view->title($this->title());
    }

    public function mount(KidSetupService $setup, UserRepository $users): void
    {
        $user = $users->authenticated();
        $prefs = $setup->defaultNotificationPreferences($user);

        $this->streak = $prefs['streak'];
        $this->newLessons = $prefs['new_lessons'];
        $this->rewards = $prefs['rewards'];
        $this->dailyMission = $prefs['daily_mission'];
        $this->friendActivity = $prefs['friend_activity'];
        $this->quietHours = $prefs['quiet_hours'];
        $this->reminderTime = $setup->defaultReminderTime($user)->value;
        $this->kidName = $user->name;
    }

    public function selectTime(string $reminderTime): void
    {
        $this->reminderTime = $reminderTime;
    }

    public function allow(KidSetupService $setup, UserRepository $users, ParentVerificationService $verification): void
    {
        $this->completeSetup($setup, $users, $verification, [
            'streak' => $this->streak,
            'new_lessons' => $this->newLessons,
            'rewards' => $this->rewards,
            'daily_mission' => $this->dailyMission,
            'friend_activity' => $this->friendActivity,
            'quiet_hours' => $this->quietHours,
        ]);
    }

    public function skip(KidSetupService $setup, UserRepository $users, ParentVerificationService $verification): void
    {
        $this->completeSetup($setup, $users, $verification, $setup->skippedNotificationPreferences());
    }

    /**
     * @param  array<string, bool>  $preferences
     */
    private function completeSetup(
        KidSetupService $setup,
        UserRepository $users,
        ParentVerificationService $verification,
        array $preferences,
    ): void {
        $this->validate([
            'reminderTime' => ['required', Rule::enum(ReminderTime::class)],
        ]);

        $user = $users->authenticated();
        $setup->completeNotifications(
            $user,
            $setup->normalizeNotificationPreferences($preferences),
            ReminderTime::from($this->reminderTime),
        );
        $verification->send($user);

        $this->redirectRoute('parent-verify', navigate: true);
    }
};
?>

<main class="device-frame min-h-screen flex flex-col safe-top">

  <!-- =============== APPBAR =============== -->
  <header class="appbar">
    <a href="{{ route('onboarding-goals') }}" class="icon-btn" data-back aria-label="{{ __('onboarding.back') }}"><i class="ph ph-caret-left"></i></a>
    <div class="grow">
      <div class="progress"><span class="w-100"></span></div>
    </div>
    <span class="text-xs font-extrabold text-muted">{{ __('onboarding.progress', ['current' => 4, 'total' => 4]) }}</span>
  </header>

  <!-- =============== HEADING =============== -->
  <section class="px-6 pt-4">
    <div class="flex items-center gap-2 mb-3">
      <span class="chip chip-primary">
        <i class="ph-fill ph-sparkle"></i> {{ __('onboarding.notifications.final_step') }}
      </span>
      <span class="chip chip-mint ml-auto">
        <span class="live-dot"></span> {{ __('onboarding.notifications.almost_done') }}
      </span>
    </div>
    <h1 class="h-display text-2xl leading-tight">{{ __('onboarding.notifications.heading') }}</h1>
    <p class="text-sm mt-1 text-muted">{{ __('onboarding.notifications.subtitle') }}</p>
  </section>

  <!-- =============== HERO BELL =============== -->
  <section class="px-6 mt-5">
    <div class="k-card-lg hero-friends text-center">
      <div class="relative inline-grid place-items-center">
        <div class="size-24 rounded-3xl bg-white/20 backdrop-blur-sm grid place-items-center">
          <i class="ph-fill ph-bell-ringing text-6xl text-white"></i>
        </div>
        <span class="absolute -top-1 -right-1 size-8 rounded-full bg-[#FF5B73] text-white grid place-items-center font-extrabold text-sm border-2 border-white animate-pulse">3</span>
      </div>

      <!-- Preview notification card (looks like a real push) -->
      <div class="relative mt-5 mx-auto max-w-[280px] rounded-2xl bg-white/95 px-3 py-2.5 text-left shadow-lg">
        <div class="flex items-center gap-2">
          <div class="size-7 rounded-lg tile-sun grid place-items-center text-xs">🔥</div>
          <p class="text-[10px] font-extrabold text-[#5B5178] uppercase tracking-wide">{{ __('onboarding.notifications.kidzio_now') }}</p>
        </div>
        <p class="text-[12px] font-extrabold text-[#1B1240] mt-1.5 leading-tight">{{ __('onboarding.notifications.nudge_title', ['name' => $kidName]) }}</p>
        <p class="text-[11px] text-[#5B5178] leading-snug mt-0.5">{{ __('onboarding.notifications.nudge_body') }}</p>
      </div>

      <p class="relative text-xs text-white/90 mt-3">{{ __('onboarding.notifications.nudge_caption') }}</p>
    </div>
  </section>

  <!-- =============== TOGGLES =============== -->
  <section class="px-6 mt-5">
    <p class="section-label">{{ __('onboarding.notifications.daily_reminders') }}</p>
    <div class="mt-3 space-y-2">

      <label class="setting-row cursor-pointer">
        <div class="setting-ico tile-sun"><i class="ph-fill ph-fire"></i></div>
        <div class="grow min-w-0">
          <p class="setting-text font-extrabold text-sm text-ink">{{ __('onboarding.notifications.streak_title') }}</p>
          <p class="text-[11px] text-muted">{{ __('onboarding.notifications.streak_sub') }}</p>
        </div>
        <span class="ks-switch">
          <input type="checkbox" wire:model="streak"/>
          <span class="track"></span>
          <span class="thumb"></span>
        </span>
      </label>

      <label class="setting-row cursor-pointer">
        <div class="setting-ico tile-mint"><i class="ph-fill ph-book-open"></i></div>
        <div class="grow min-w-0">
          <p class="setting-text font-extrabold text-sm text-ink">{{ __('onboarding.notifications.lessons_title') }}</p>
          <p class="text-[11px] text-muted">{{ __('onboarding.notifications.lessons_sub') }}</p>
        </div>
        <span class="ks-switch">
          <input type="checkbox" wire:model="newLessons"/>
          <span class="track"></span>
          <span class="thumb"></span>
        </span>
      </label>

      <label class="setting-row cursor-pointer">
        <div class="setting-ico tile-coral"><i class="ph-fill ph-trophy"></i></div>
        <div class="grow min-w-0">
          <p class="setting-text font-extrabold text-sm text-ink">{{ __('onboarding.notifications.rewards_title') }}</p>
          <p class="text-[11px] text-muted">{{ __('onboarding.notifications.rewards_sub') }}</p>
        </div>
        <span class="ks-switch">
          <input type="checkbox" wire:model="rewards"/>
          <span class="track"></span>
          <span class="thumb"></span>
        </span>
      </label>

      <label class="setting-row cursor-pointer">
        <div class="setting-ico tile-violet"><i class="ph-fill ph-target"></i></div>
        <div class="grow min-w-0">
          <p class="setting-text font-extrabold text-sm text-ink">{{ __('onboarding.notifications.mission_title') }}</p>
          <p class="text-[11px] text-muted">{{ __('onboarding.notifications.mission_sub') }}</p>
        </div>
        <span class="ks-switch">
          <input type="checkbox" wire:model="dailyMission"/>
          <span class="track"></span>
          <span class="thumb"></span>
        </span>
      </label>

      <label class="setting-row cursor-pointer">
        <div class="setting-ico tile-sky"><i class="ph-fill ph-users-three"></i></div>
        <div class="grow min-w-0">
          <p class="setting-text font-extrabold text-sm text-ink">{{ __('onboarding.notifications.friends_title') }}</p>
          <p class="text-[11px] text-muted">{{ __('onboarding.notifications.friends_sub') }}</p>
        </div>
        <span class="ks-switch">
          <input type="checkbox" wire:model="friendActivity"/>
          <span class="track"></span>
          <span class="thumb"></span>
        </span>
      </label>
    </div>
  </section>

  <!-- =============== REMINDER TIME =============== -->
  <section class="px-6 mt-5">
    <p class="section-label">{{ __('onboarding.notifications.best_time') }}</p>
    <div class="mt-3 grid grid-cols-2 gap-3">
      <button type="button" class="pick-card{{ $reminderTime === 'morning' ? ' is-selected' : '' }}" wire:click="selectTime('morning')" data-time>
        <span class="pc-emoji tile-sun">🌅</span>
        <span class="pc-body">
          <span class="pc-name">{{ __('onboarding.notifications.morning') }}</span>
          <span class="pc-sub">{{ __('onboarding.notifications.morning_sub') }}</span>
        </span>
        <span class="pc-check"></span>
      </button>
      <button type="button" class="pick-card{{ $reminderTime === 'afternoon' ? ' is-selected' : '' }}" wire:click="selectTime('afternoon')" data-time>
        <span class="pc-emoji tile-mint">🏡</span>
        <span class="pc-body">
          <span class="pc-name">{{ __('onboarding.notifications.afternoon') }}</span>
          <span class="pc-sub">{{ __('onboarding.notifications.afternoon_sub') }}</span>
        </span>
        <span class="pc-check"></span>
      </button>
      <button type="button" class="pick-card{{ $reminderTime === 'evening' ? ' is-selected' : '' }}" wire:click="selectTime('evening')" data-time>
        <span class="pc-emoji tile-coral">🌆</span>
        <span class="pc-body">
          <span class="pc-name">{{ __('onboarding.notifications.evening') }}</span>
          <span class="pc-sub">{{ __('onboarding.notifications.evening_sub') }}</span>
        </span>
        <span class="pc-check"></span>
      </button>
      <button type="button" class="pick-card{{ $reminderTime === 'bedtime' ? ' is-selected' : '' }}" wire:click="selectTime('bedtime')" data-time>
        <span class="pc-emoji tile-violet">🌙</span>
        <span class="pc-body">
          <span class="pc-name">{{ __('onboarding.notifications.bedtime') }}</span>
          <span class="pc-sub">{{ __('onboarding.notifications.bedtime_sub') }}</span>
        </span>
        <span class="pc-check"></span>
      </button>
    </div>
  </section>

  <!-- =============== QUIET HOURS =============== -->
  <section class="px-6 mt-5">
    <p class="section-label">{{ __('onboarding.notifications.quiet_hours') }}</p>
    <div class="mt-3">
      <label class="setting-row cursor-pointer">
        <div class="setting-ico tile-sky"><i class="ph-fill ph-moon"></i></div>
        <div class="grow min-w-0">
          <p class="setting-text font-extrabold text-sm text-ink">{{ __('onboarding.notifications.silent_title') }}</p>
          <p class="text-[11px] text-muted">{{ __('onboarding.notifications.silent_sub') }}</p>
        </div>
        <span class="ks-switch">
          <input type="checkbox" wire:model="quietHours"/>
          <span class="track"></span>
          <span class="thumb"></span>
        </span>
      </label>
    </div>
  </section>

  <!-- =============== TRUST TIP =============== -->
  <section class="px-6 mt-5">
    <div class="tip-card rounded-2xl p-3 flex items-start gap-3">
      <div class="mascot shrink-0 size-10 text-base">🦉</div>
      <div class="grow">
        <p class="font-extrabold text-sm text-ink">{{ __('onboarding.notifications.tip_title') }}</p>
        <p class="text-[11px] text-muted">{{ __('onboarding.notifications.tip_body') }}</p>
      </div>
    </div>
  </section>

  <!-- =============== CTA =============== -->
  <div class="mt-auto px-6 pb-8 pt-6 safe-bottom space-y-3">
    <button type="button" class="btn btn-primary w-full" wire:click="allow">
      <i class="ph-fill ph-bell"></i> {{ __('onboarding.notifications.allow') }}
    </button>
    <button type="button" class="btn btn-ghost w-full" wire:click="skip">{{ __('onboarding.notifications.maybe_later') }}</button>
    <p class="text-[11px] text-center text-muted">{{ __('onboarding.notifications.change_later') }}</p>
  </div>
</main>
