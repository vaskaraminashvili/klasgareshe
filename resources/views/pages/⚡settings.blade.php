<?php

use App\Enums\DailyGoal;
use App\Enums\ReminderTime;
use App\Enums\SchoolGrade;
use App\Enums\SchoolSubject;
use App\Repositories\UserRepository;
use App\Services\KidSetupService;
use App\Services\ParentZoneService;
use App\Services\ProgressReportService;
use App\Services\ScreenTimeService;
use App\Services\UserProfileService;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rule;
use Livewire\Attributes\Title;
use Livewire\Component;

new #[Title('პარამეტრები · Kidzio')] class extends Component
{
    public string $query = '';

    public string $name = '';

    public string $email = '';

    public string $age = '';

    public string $gradeLabel = '';

    public string $avatar = '🐻';

    public string $avatarTile = 'tile-sun';

    public bool $streak = true;

    public bool $newLessons = true;

    public bool $rewards = false;

    public string $reminderTime = 'evening';

    public string $dailyGoal = 'regular';

    /** @var list<string> */
    public array $subjects = [];

    public bool $showOnLeaderboard = true;

    public bool $allowFriendRequests = true;

    public bool $hasPin = false;

    public int $weekXp = 0;

    public string $screenTimeChip = '';

    public string $bedtimeChip = '';

    public function mount(
        UserProfileService $profiles,
        KidSetupService $setup,
        ParentZoneService $zone,
        ScreenTimeService $time,
        ProgressReportService $reports,
        UserRepository $users,
    ): void {
        $user = $users->authenticated();
        $prefs = $setup->defaultNotificationPreferences($user);
        $clock = $time->snapshot($user);
        $left = $clock->remainingMinutes();

        $this->name = $user->name;
        $this->email = $user->email;
        $this->age = $user->age !== null ? (string) $user->age : '';
        $this->gradeLabel = ($user->grade ?? SchoolGrade::First)->label();
        $this->avatar = $profiles->defaultAvatar($user->avatar);
        $this->avatarTile = $profiles->tileForAvatar($this->avatar);
        $this->streak = $prefs['streak'];
        $this->newLessons = $prefs['new_lessons'];
        $this->rewards = $prefs['rewards'];
        $this->reminderTime = $setup->defaultReminderTime($user)->value;
        $this->dailyGoal = ($user->daily_goal ?? DailyGoal::Regular)->value;
        $this->subjects = $setup->selectedSubjects($user);
        $this->showOnLeaderboard = (bool) ($user->show_on_leaderboard ?? true);
        $this->allowFriendRequests = (bool) ($user->allow_friend_requests ?? true);
        $this->hasPin = $zone->hasPin($user);
        $this->weekXp = $reports->weekSnapshot($user)->figures->xp;
        $this->screenTimeChip = $clock->limitMinutes === null
            ? (string) __('profile.screen_time_off')
            : (string) __('profile.screen_time_left', ['minutes' => $left ?? 0]);
        $this->bedtimeChip = $clock->bedtimeEnabled
            ? $clock->bedtimeStart.' — '.$clock->bedtimeEnd
            : (string) __('settings.bedtime_off');
    }

    public function updatedStreak(UserProfileService $profiles, UserRepository $users): void
    {
        $this->saveNotifications($profiles, $users);
    }

    public function updatedNewLessons(UserProfileService $profiles, UserRepository $users): void
    {
        $this->saveNotifications($profiles, $users);
    }

    public function updatedRewards(UserProfileService $profiles, UserRepository $users): void
    {
        $this->saveNotifications($profiles, $users);
    }

    public function updatedShowOnLeaderboard(UserProfileService $profiles, UserRepository $users): void
    {
        $this->savePrivacy($profiles, $users);
    }

    public function updatedAllowFriendRequests(UserProfileService $profiles, UserRepository $users): void
    {
        $this->savePrivacy($profiles, $users);
    }

    public function selectGoal(string $goal, UserProfileService $profiles, UserRepository $users): void
    {
        $this->dailyGoal = $goal;
        $this->validate([
            'dailyGoal' => ['required', Rule::enum(DailyGoal::class)],
        ]);

        $profiles->updateDailyGoal($users->authenticated(), DailyGoal::from($this->dailyGoal));
    }

    public function selectReminderTime(string $time, UserProfileService $profiles, UserRepository $users): void
    {
        $this->reminderTime = $time;
        $this->validate([
            'reminderTime' => ['required', Rule::enum(ReminderTime::class)],
        ]);

        $this->saveNotifications($profiles, $users);
    }

    public function toggleSubject(string $subject, UserProfileService $profiles, UserRepository $users): void
    {
        if (! SchoolSubject::tryFrom($subject) instanceof SchoolSubject) {
            return;
        }

        $next = $this->subjects;

        if (in_array($subject, $next, true)) {
            $next = array_values(array_filter(
                $next,
                static fn (string $item): bool => $item !== $subject,
            ));
        } else {
            $next[] = $subject;
        }

        $profiles->updateFavouriteSubjects($users->authenticated(), $next);
        $this->subjects = $next;
        $this->resetErrorBag('subjects');
    }

    public function logout(): void
    {
        Auth::logout();

        session()->invalidate();
        session()->regenerateToken();

        $this->redirectRoute('user-login');
    }

    public function matches(string ...$parts): bool
    {
        $needle = trim(mb_strtolower($this->query));

        if ($needle === '') {
            return true;
        }

        return str_contains(mb_strtolower(implode(' ', $parts)), $needle);
    }

    public function anyRowVisible(): bool
    {
        foreach ($this->searchHaystacks() as $haystack) {
            if ($this->matches($haystack)) {
                return true;
            }
        }

        return false;
    }

    public function userMeta(): string
    {
        $parts = array_values(array_filter([
            $this->age,
            $this->gradeLabel,
            $this->email,
        ], static fn (string $part): bool => $part !== ''));

        return implode(' · ', $parts);
    }

    public function dailyGoalChip(): string
    {
        return DailyGoal::from($this->dailyGoal)->timeLabel();
    }

    public function reminderChip(): string
    {
        return ReminderTime::from($this->reminderTime)->clockLabel();
    }

    public function subjectsLine(): string
    {
        $labels = [];

        foreach (SchoolSubject::ordered() as $subject) {
            if (in_array($subject->value, $this->subjects, true)) {
                $labels[] = $subject->label();
            }
        }

        return implode(' · ', $labels);
    }

    public function isPicked(string $subject): bool
    {
        return in_array($subject, $this->subjects, true);
    }

    /**
     * @return list<string>
     */
    private function searchHaystacks(): array
    {
        return [
            $this->name.' '.$this->email.' '.__('settings.edit'),
            __('settings.dark_mode').' '.__('settings.dark_mode_hint'),
            __('settings.streak').' '.__('settings.streak_hint'),
            __('settings.lessons').' '.__('settings.lessons_hint'),
            __('settings.rewards').' '.__('settings.rewards_hint'),
            __('settings.reminder_time').' '.__('settings.reminder_time_hint'),
            __('settings.daily_goal').' '.__('settings.daily_goal_hint'),
            __('settings.favourite_subjects').' '.$this->subjectsLine(),
            __('settings.parent_controls').' '.__('settings.parent_controls_hint'),
            __('settings.weekly_report').' '.__('settings.weekly_report_hint'),
            __('settings.screen_time').' '.__('settings.screen_time_hint'),
            __('settings.bedtime_lock').' '.__('settings.bedtime_lock_hint'),
            __('settings.show_on_leaderboard').' '.__('settings.show_on_leaderboard_hint'),
            __('settings.friend_requests').' '.__('settings.friend_requests_hint'),
            __('settings.privacy_policy').' '.__('settings.privacy_policy_hint'),
            __('settings.terms').' '.__('settings.terms_hint'),
            __('settings.app_language').' '.__('settings.language_value'),
            __('settings.parent_email').' '.$this->email,
            __('settings.delete_account').' '.__('settings.delete_account_hint'),
            __('settings.log_out'),
        ];
    }

    private function saveNotifications(UserProfileService $profiles, UserRepository $users): void
    {
        $this->validate([
            'reminderTime' => ['required', Rule::enum(ReminderTime::class)],
        ]);

        $profiles->updateNotifications($users->authenticated(), [
            'streak' => $this->streak,
            'new_lessons' => $this->newLessons,
            'rewards' => $this->rewards,
        ], ReminderTime::from($this->reminderTime));
    }

    private function savePrivacy(UserProfileService $profiles, UserRepository $users): void
    {
        $profiles->updatePrivacy(
            $users->authenticated(),
            $this->showOnLeaderboard,
            $this->allowFriendRequests,
        );
    }
};
?>

<main class="device-frame min-h-screen flex flex-col">

  <!-- =============== APPBAR =============== -->
  <header class="appbar safe-top">
    <a href="{{ route('profile') }}" class="icon-btn" data-back aria-label="{{ __('settings.back') }}"><i class="ph ph-caret-left"></i></a>
    <div class="grow">
      <p class="text-xs text-muted">{{ __('settings.eyebrow') }}</p>
      <h1 class="h-display text-lg leading-tight">{{ __('settings.heading') }}</h1>
    </div>
    <button type="button" class="icon-btn" aria-label="{{ __('settings.search_aria') }}" onclick="document.getElementById('settingsSearch')?.focus()"><i class="ph ph-magnifying-glass text-xl"></i></button>
  </header>

  <!-- =============== QUICK USER STRIP =============== -->
  @if ($this->matches($name, $email, __('settings.edit')))
  <section class="px-5">
    <a href="{{ route('edit-profile') }}" wire:navigate class="k-card p-3 flex items-center gap-3">
      <div class="size-12 rounded-2xl {{ $avatarTile }} grid place-items-center text-2xl shrink-0">{{ $avatar }}</div>
      <div class="grow min-w-0">
        <p class="font-extrabold text-sm text-ink">{{ $name }}</p>
        <p class="text-[11px] text-muted">{{ $this->userMeta() }}</p>
      </div>
      <span class="chip chip-primary">{{ __('settings.edit') }}</span>
    </a>
  </section>
  @endif

  <!-- =============== SEARCH =============== -->
  <section class="px-5 mt-3">
    <div class="input-wrap">
      <i class="ph ph-magnifying-glass i-left"></i>
      <input id="settingsSearch" class="input has-left" placeholder="{{ __('settings.search_placeholder') }}" aria-label="{{ __('settings.search_aria') }}" wire:model.live.debounce.200ms="query"/>
    </div>
  </section>

  @if ($query !== '' && ! $this->anyRowVisible())
    <p class="text-sm text-center text-muted mt-8 px-5">{{ __('settings.search_empty') }}</p>
  @endif

  <!-- =============== APPEARANCE =============== -->
  @if ($this->matches(__('settings.appearance'), __('settings.dark_mode'), __('settings.dark_mode_hint')))
  <section class="px-5 mt-5">
    <p class="section-label">{{ __('settings.appearance') }}</p>
    <div class="mt-3 space-y-2">
      <label class="setting-row cursor-pointer" wire:ignore>
        <div class="setting-ico tile-violet"><i class="ph-fill ph-moon"></i></div>
        <div class="grow min-w-0">
          <p class="setting-text font-extrabold text-sm text-ink">{{ __('settings.dark_mode') }}</p>
          <p class="text-[11px] text-muted">{{ __('settings.dark_mode_hint') }}</p>
        </div>
        <span class="ks-switch">
          <input type="checkbox" onchange="toggleTheme()" data-theme-switch/>
          <span class="track"></span>
          <span class="thumb"></span>
        </span>
      </label>
      {{-- Theme color + text size → docs/tasks/T21-sound-voice-appearance.md --}}
    </div>
  </section>
  @endif

  {{-- Sound & music → docs/tasks/T21-sound-voice-appearance.md --}}

  <!-- =============== NOTIFICATIONS =============== -->
  @if ($this->matches(__('settings.notifications'), __('settings.streak'), __('settings.lessons'), __('settings.rewards'), __('settings.reminder_time'), __('settings.streak_hint'), __('settings.lessons_hint'), __('settings.rewards_hint')))
  <section class="px-5 mt-5">
    <p class="section-label">{{ __('settings.notifications') }}</p>
    <div class="mt-3 space-y-2">
      @if ($this->matches(__('settings.streak'), __('settings.streak_hint')))
      <label class="setting-row cursor-pointer">
        <div class="setting-ico tile-sun"><i class="ph-fill ph-fire"></i></div>
        <div class="grow min-w-0">
          <p class="setting-text font-extrabold text-sm text-ink">{{ __('settings.streak') }}</p>
          <p class="text-[11px] text-muted">{{ __('settings.streak_hint') }}</p>
        </div>
        <span class="ks-switch">
          <input type="checkbox" wire:model.live="streak"/>
          <span class="track"></span>
          <span class="thumb"></span>
        </span>
      </label>
      @endif
      @if ($this->matches(__('settings.lessons'), __('settings.lessons_hint')))
      <label class="setting-row cursor-pointer">
        <div class="setting-ico tile-mint"><i class="ph-fill ph-book-open"></i></div>
        <div class="grow min-w-0">
          <p class="setting-text font-extrabold text-sm text-ink">{{ __('settings.lessons') }}</p>
          <p class="text-[11px] text-muted">{{ __('settings.lessons_hint') }}</p>
        </div>
        <span class="ks-switch">
          <input type="checkbox" wire:model.live="newLessons"/>
          <span class="track"></span>
          <span class="thumb"></span>
        </span>
      </label>
      @endif
      @if ($this->matches(__('settings.rewards'), __('settings.rewards_hint')))
      <label class="setting-row cursor-pointer">
        <div class="setting-ico tile-coral"><i class="ph-fill ph-trophy"></i></div>
        <div class="grow min-w-0">
          <p class="setting-text font-extrabold text-sm text-ink">{{ __('settings.rewards') }}</p>
          <p class="text-[11px] text-muted">{{ __('settings.rewards_hint') }}</p>
        </div>
        <span class="ks-switch">
          <input type="checkbox" wire:model.live="rewards"/>
          <span class="track"></span>
          <span class="thumb"></span>
        </span>
      </label>
      @endif
      @if ($this->matches(__('settings.reminder_time'), __('settings.reminder_time_hint')))
      <button type="button" class="setting-row w-full text-left" data-sheet="reminderSheet">
        <div class="setting-ico tile-violet"><i class="ph-fill ph-sliders"></i></div>
        <div class="grow min-w-0">
          <p class="setting-text font-extrabold text-sm text-ink">{{ __('settings.reminder_time') }}</p>
          <p class="text-[11px] text-muted">{{ __('settings.reminder_time_hint') }}</p>
        </div>
        <span class="chip chip-primary">{{ $this->reminderChip() }}</span>
        <i class="ph ph-caret-right text-muted"></i>
      </button>
      @endif
    </div>
  </section>
  @endif

  <!-- =============== LEARNING =============== -->
  @if ($this->matches(__('settings.learning'), __('settings.daily_goal'), __('settings.favourite_subjects'), $this->subjectsLine()))
  <section class="px-5 mt-5">
    <p class="section-label">{{ __('settings.learning') }}</p>
    <div class="mt-3 space-y-2">
      @if ($this->matches(__('settings.daily_goal'), __('settings.daily_goal_hint')))
      <button type="button" class="setting-row w-full text-left" data-sheet="goalSheet">
        <div class="setting-ico tile-sun">⚡</div>
        <div class="grow min-w-0">
          <p class="setting-text font-extrabold text-sm text-ink">{{ __('settings.daily_goal') }}</p>
          <p class="text-[11px] text-muted">{{ __('settings.daily_goal_hint') }}</p>
        </div>
        <span class="chip chip-primary">{{ $this->dailyGoalChip() }}</span>
        <i class="ph ph-caret-right text-muted"></i>
      </button>
      @endif
      @if ($this->matches(__('settings.favourite_subjects'), $this->subjectsLine()))
      <button type="button" class="setting-row w-full text-left" data-sheet="subjectsSheet">
        <div class="setting-ico tile-mint">📚</div>
        <div class="grow min-w-0">
          <p class="setting-text font-extrabold text-sm text-ink">{{ __('settings.favourite_subjects') }}</p>
          <p class="text-[11px] text-muted">{{ $this->subjectsLine() !== '' ? $this->subjectsLine() : __('settings.favourite_subjects_hint') }}</p>
        </div>
        <span class="chip">{{ count($subjects) }}</span>
        <i class="ph ph-caret-right text-muted"></i>
      </button>
      @error('subjects')
        <p class="text-sm" style="color:var(--color-k-coral)">{{ $message }}</p>
      @enderror
      @endif
      {{-- Age group lives on edit-profile. Difficulty → docs/tasks/T19-minigames-batch-2.md --}}
    </div>
  </section>
  @endif

  <!-- =============== PARENT ZONE =============== -->
  @if ($this->matches(__('settings.parent_zone'), __('settings.parent_controls'), __('settings.weekly_report'), __('settings.screen_time'), __('settings.bedtime_lock')))
  <section class="px-5 mt-5">
    <p class="section-label">{{ __('settings.parent_zone') }}</p>
    <div class="mt-3 space-y-2">
      @if ($this->matches(__('settings.parent_controls'), __('settings.parent_controls_hint')))
      <a href="{{ route('parent-controls') }}" wire:navigate class="setting-row">
        <div class="setting-ico tile-sky"><i class="ph-fill ph-shield-check"></i></div>
        <div class="grow min-w-0">
          <p class="setting-text font-extrabold text-sm text-ink">{{ __('settings.parent_controls') }}</p>
          <p class="text-[11px] text-muted">{{ __('settings.parent_controls_hint') }}</p>
        </div>
        <span class="chip {{ $hasPin ? 'chip-mint' : '' }}">{{ $hasPin ? __('settings.pin_on') : __('settings.pin_off') }}</span>
        <i class="ph ph-caret-right text-muted"></i>
      </a>
      @endif
      @if ($this->matches(__('settings.weekly_report'), __('settings.weekly_report_hint')))
      <a href="{{ route('weekly-report') }}" wire:navigate class="setting-row">
        <div class="setting-ico tile-violet"><i class="ph-fill ph-chart-bar"></i></div>
        <div class="grow min-w-0">
          <p class="setting-text font-extrabold text-sm text-ink">{{ __('settings.weekly_report') }}</p>
          <p class="text-[11px] text-muted">{{ __('settings.weekly_report_hint') }}</p>
        </div>
        <span class="chip chip-mint">{{ $weekXp }} XP</span>
      </a>
      @endif
      @if ($this->matches(__('settings.screen_time'), __('settings.screen_time_hint')))
      <a href="{{ route('screen-time') }}" wire:navigate class="setting-row">
        <div class="setting-ico tile-pink"><i class="ph-fill ph-timer"></i></div>
        <div class="grow min-w-0">
          <p class="setting-text font-extrabold text-sm text-ink">{{ __('settings.screen_time') }}</p>
          <p class="text-[11px] text-muted">{{ __('settings.screen_time_hint') }}</p>
        </div>
        <span class="chip">{{ $screenTimeChip }}</span>
        <i class="ph ph-caret-right text-muted"></i>
      </a>
      @endif
      @if ($this->matches(__('settings.bedtime_lock'), __('settings.bedtime_lock_hint')))
      <a href="{{ route('bedtime-lock') }}" wire:navigate class="setting-row">
        <div class="setting-ico tile-sun"><i class="ph-fill ph-bed"></i></div>
        <div class="grow min-w-0">
          <p class="setting-text font-extrabold text-sm text-ink">{{ __('settings.bedtime_lock') }}</p>
          <p class="text-[11px] text-muted">{{ __('settings.bedtime_lock_hint') }}</p>
        </div>
        <span class="chip">{{ $bedtimeChip }}</span>
        <i class="ph ph-caret-right text-muted"></i>
      </a>
      @endif
    </div>
  </section>
  @endif

  <!-- =============== PRIVACY & SAFETY =============== -->
  @if ($this->matches(__('settings.privacy'), __('settings.show_on_leaderboard'), __('settings.friend_requests'), __('settings.privacy_policy'), __('settings.terms')))
  <section class="px-5 mt-5">
    <p class="section-label">{{ __('settings.privacy') }}</p>
    <div class="mt-3 space-y-2">
      @if ($this->matches(__('settings.show_on_leaderboard'), __('settings.show_on_leaderboard_hint')))
      <label class="setting-row cursor-pointer">
        <div class="setting-ico tile-mint"><i class="ph-fill ph-users-three"></i></div>
        <div class="grow min-w-0">
          <p class="setting-text font-extrabold text-sm text-ink">{{ __('settings.show_on_leaderboard') }}</p>
          <p class="text-[11px] text-muted">{{ __('settings.show_on_leaderboard_hint') }}</p>
        </div>
        <span class="ks-switch">
          <input type="checkbox" wire:model.live="showOnLeaderboard"/>
          <span class="track"></span>
          <span class="thumb"></span>
        </span>
      </label>
      @endif
      @if ($this->matches(__('settings.friend_requests'), __('settings.friend_requests_hint')))
      <label class="setting-row cursor-pointer">
        <div class="setting-ico tile-violet"><i class="ph-fill ph-user-plus"></i></div>
        <div class="grow min-w-0">
          <p class="setting-text font-extrabold text-sm text-ink">{{ __('settings.friend_requests') }}</p>
          <p class="text-[11px] text-muted">{{ __('settings.friend_requests_hint') }}</p>
        </div>
        <span class="ks-switch">
          <input type="checkbox" wire:model.live="allowFriendRequests"/>
          <span class="track"></span>
          <span class="thumb"></span>
        </span>
      </label>
      @endif
      @if ($this->matches(__('settings.privacy_policy'), __('settings.privacy_policy_hint')))
      <a href="{{ route('privacy-policy') }}" wire:navigate class="setting-row">
        <div class="setting-ico tile-coral"><i class="ph-fill ph-lock-key"></i></div>
        <div class="grow min-w-0">
          <p class="setting-text font-extrabold text-sm text-ink">{{ __('settings.privacy_policy') }}</p>
          <p class="text-[11px] text-muted">{{ __('settings.privacy_policy_hint') }}</p>
        </div>
        <i class="ph ph-caret-right text-muted"></i>
      </a>
      @endif
      @if ($this->matches(__('settings.terms'), __('settings.terms_hint')))
      <a href="{{ route('terms-privacy') }}" wire:navigate class="setting-row">
        <div class="setting-ico tile-violet"><i class="ph-fill ph-file-text"></i></div>
        <div class="grow min-w-0">
          <p class="setting-text font-extrabold text-sm text-ink">{{ __('settings.terms') }}</p>
          <p class="text-[11px] text-muted">{{ __('settings.terms_hint') }}</p>
        </div>
        <i class="ph ph-caret-right text-muted"></i>
      </a>
      @endif
    </div>
  </section>
  @endif

  {{-- Storage & data (offline / cache) → docs/tasks/T21-sound-voice-appearance.md. Export is on parent reports. --}}

  <!-- =============== LANGUAGE =============== -->
  @if ($this->matches(__('settings.language'), __('settings.app_language'), __('settings.language_value'), __('settings.language_locked')))
  <section class="px-5 mt-5">
    <p class="section-label">{{ __('settings.language') }}</p>
    <div class="mt-3 space-y-2">
      <div class="setting-row">
        <div class="setting-ico tile-sun">🌐</div>
        <div class="grow min-w-0">
          <p class="setting-text font-extrabold text-sm text-ink">{{ __('settings.app_language') }}</p>
          <p class="text-[11px] text-muted">{{ __('settings.language_locked') }}</p>
        </div>
        <span class="chip">{{ __('settings.language_value') }}</span>
      </div>
      {{-- Country picker → docs/tasks/T21-sound-voice-appearance.md --}}
    </div>
  </section>
  @endif

  {{-- Support & about / PWA install → docs/tasks/T21-sound-voice-appearance.md and T15 --}}

  <!-- =============== ABOUT / ACCOUNT =============== -->
  @if ($this->matches(__('settings.account'), __('settings.parent_email'), $email, __('settings.delete_account'), __('settings.log_out')))
  <section class="px-5 mt-5 mb-5">
    <p class="section-label">{{ __('settings.account') }}</p>
    <div class="mt-3 space-y-2">
      @if ($this->matches(__('settings.parent_email'), $email, __('settings.parent_email_hint')))
      <a href="{{ route('parent-email') }}" wire:navigate class="setting-row">
        <div class="setting-ico tile-violet"><i class="ph-fill ph-envelope-simple"></i></div>
        <div class="grow min-w-0">
          <p class="setting-text font-extrabold text-sm text-ink">{{ __('settings.parent_email') }}</p>
          <p class="text-[11px] text-muted">{{ $email }}</p>
        </div>
        <i class="ph ph-caret-right text-muted"></i>
      </a>
      @endif
      @if ($this->matches(__('settings.delete_account'), __('settings.delete_account_hint')))
      <a href="{{ route('delete-account') }}" wire:navigate class="setting-row danger">
        <div class="setting-ico"><i class="ph-fill ph-trash"></i></div>
        <div class="grow min-w-0">
          <p class="setting-text font-extrabold text-sm">{{ __('settings.delete_account') }}</p>
          <p class="text-[11px] text-muted">{{ __('settings.delete_account_hint') }}</p>
        </div>
        <i class="ph ph-caret-right text-muted"></i>
      </a>
      @endif
      @if ($this->matches(__('settings.log_out')))
      <button type="button" class="setting-row danger w-full text-left" wire:click="logout">
        <div class="setting-ico"><i class="ph-fill ph-sign-out"></i></div>
        <p class="setting-text font-extrabold text-sm grow">{{ __('settings.log_out') }}</p>
      </button>
      @endif
    </div>
    <p class="text-[11px] text-center text-muted mt-5">{{ __('settings.footer_version') }}</p>
  </section>
  @endif

  <livewire:bottom-nav-bar />

  <!-- =============== GOAL SHEET =============== -->
  <div id="goalSheet" class="hidden fixed inset-0 z-50" role="dialog" aria-modal="true" aria-labelledby="goalTitle">
    <button type="button" data-sheet="goalSheet" class="absolute inset-0 size-full bg-black/50 backdrop-blur-sm" aria-label="{{ __('settings.close') }}"></button>
    <div class="absolute left-0 right-0 bottom-0 mx-auto max-w-[430px] bg-surface rounded-t-3xl border-t border-token shadow-2xl safe-bottom">
      <div class="flex justify-center pt-3">
        <span class="block w-10 h-1.5 rounded-full bg-[var(--color-k-border)]"></span>
      </div>
      <div class="px-5 pt-4 pb-6">
        <div class="flex items-start gap-3">
          <div class="size-12 rounded-2xl tile-sun grid place-items-center text-2xl shrink-0">⚡</div>
          <div class="grow min-w-0">
            <p id="goalTitle" class="h-display text-xl leading-tight text-ink">{{ __('settings.pick_goal') }}</p>
            <p class="text-xs text-muted mt-1">{{ __('settings.pick_goal_hint') }}</p>
          </div>
          <button type="button" class="icon-btn shrink-0" data-sheet="goalSheet" aria-label="{{ __('settings.close') }}">
            <i class="ph ph-x"></i>
          </button>
        </div>
        <div class="mt-4 grid grid-cols-4 gap-2">
          @foreach (\App\Enums\DailyGoal::cases() as $goal)
            <button type="button" wire:click="selectGoal('{{ $goal->value }}')" class="pick-card !p-2 !gap-0 flex-col text-center{{ $dailyGoal === $goal->value ? ' is-selected' : '' }}">
              <span class="h-display text-base">{{ $goal->minutes() }}</span>
              <span class="pc-name text-[10px] mt-0.5">{{ __('settings.minutes') }}</span>
            </button>
          @endforeach
        </div>
        <button type="button" data-sheet="goalSheet" class="btn btn-primary w-full mt-5">{{ __('settings.done') }}</button>
      </div>
    </div>
  </div>

  <!-- =============== SUBJECTS SHEET =============== -->
  <div id="subjectsSheet" class="hidden fixed inset-0 z-50" role="dialog" aria-modal="true" aria-labelledby="subjectsTitle">
    <button type="button" data-sheet="subjectsSheet" class="absolute inset-0 size-full bg-black/50 backdrop-blur-sm" aria-label="{{ __('settings.close') }}"></button>
    <div class="absolute left-0 right-0 bottom-0 mx-auto max-w-[430px] bg-surface rounded-t-3xl border-t border-token shadow-2xl safe-bottom">
      <div class="flex justify-center pt-3">
        <span class="block w-10 h-1.5 rounded-full bg-[var(--color-k-border)]"></span>
      </div>
      <div class="px-5 pt-4 pb-6">
        <div class="flex items-start gap-3">
          <div class="size-12 rounded-2xl tile-mint grid place-items-center text-2xl shrink-0">📚</div>
          <div class="grow min-w-0">
            <p id="subjectsTitle" class="h-display text-xl leading-tight text-ink">{{ __('settings.pick_subjects') }}</p>
            <p class="text-xs text-muted mt-1">{{ __('settings.pick_subjects_hint') }}</p>
          </div>
          <button type="button" class="icon-btn shrink-0" data-sheet="subjectsSheet" aria-label="{{ __('settings.close') }}">
            <i class="ph ph-x"></i>
          </button>
        </div>
        <div class="mt-4 grid grid-cols-3 gap-2">
          @foreach (\App\Enums\SchoolSubject::ordered() as $subject)
            <button type="button" wire:click="toggleSubject('{{ $subject->value }}')" class="pick-card !p-2 !gap-0 flex-col text-center{{ $this->isPicked($subject->value) ? ' is-selected' : '' }}">
              <span class="text-xl">{{ $subject->emoji() }}</span>
              <span class="pc-name text-xs mt-1">{{ $subject->label() }}</span>
            </button>
          @endforeach
        </div>
        <button type="button" data-sheet="subjectsSheet" class="btn btn-primary w-full mt-5">{{ __('settings.done') }}</button>
      </div>
    </div>
  </div>

  <!-- =============== REMINDER SHEET =============== -->
  <div id="reminderSheet" class="hidden fixed inset-0 z-50" role="dialog" aria-modal="true" aria-labelledby="reminderTitle">
    <button type="button" data-sheet="reminderSheet" class="absolute inset-0 size-full bg-black/50 backdrop-blur-sm" aria-label="{{ __('settings.close') }}"></button>
    <div class="absolute left-0 right-0 bottom-0 mx-auto max-w-[430px] bg-surface rounded-t-3xl border-t border-token shadow-2xl safe-bottom">
      <div class="flex justify-center pt-3">
        <span class="block w-10 h-1.5 rounded-full bg-[var(--color-k-border)]"></span>
      </div>
      <div class="px-5 pt-4 pb-6">
        <div class="flex items-start gap-3">
          <div class="size-12 rounded-2xl tile-violet grid place-items-center text-2xl shrink-0"><i class="ph-fill ph-sliders"></i></div>
          <div class="grow min-w-0">
            <p id="reminderTitle" class="h-display text-xl leading-tight text-ink">{{ __('settings.pick_reminder') }}</p>
            <p class="text-xs text-muted mt-1">{{ __('settings.pick_reminder_hint') }}</p>
          </div>
          <button type="button" class="icon-btn shrink-0" data-sheet="reminderSheet" aria-label="{{ __('settings.close') }}">
            <i class="ph ph-x"></i>
          </button>
        </div>
        <div class="mt-4 grid grid-cols-2 gap-3">
          <button type="button" class="pick-card{{ $reminderTime === 'morning' ? ' is-selected' : '' }}" wire:click="selectReminderTime('morning')">
            <span class="pc-emoji tile-sun">🌅</span>
            <span class="pc-body">
              <span class="pc-name">{{ __('onboarding.notifications.morning') }}</span>
              <span class="pc-sub">{{ __('onboarding.notifications.morning_sub') }}</span>
            </span>
            <span class="pc-check"></span>
          </button>
          <button type="button" class="pick-card{{ $reminderTime === 'afternoon' ? ' is-selected' : '' }}" wire:click="selectReminderTime('afternoon')">
            <span class="pc-emoji tile-mint">🏡</span>
            <span class="pc-body">
              <span class="pc-name">{{ __('onboarding.notifications.afternoon') }}</span>
              <span class="pc-sub">{{ __('onboarding.notifications.afternoon_sub') }}</span>
            </span>
            <span class="pc-check"></span>
          </button>
          <button type="button" class="pick-card{{ $reminderTime === 'evening' ? ' is-selected' : '' }}" wire:click="selectReminderTime('evening')">
            <span class="pc-emoji tile-coral">🌆</span>
            <span class="pc-body">
              <span class="pc-name">{{ __('onboarding.notifications.evening') }}</span>
              <span class="pc-sub">{{ __('onboarding.notifications.evening_sub') }}</span>
            </span>
            <span class="pc-check"></span>
          </button>
          <button type="button" class="pick-card{{ $reminderTime === 'bedtime' ? ' is-selected' : '' }}" wire:click="selectReminderTime('bedtime')">
            <span class="pc-emoji tile-violet">🌙</span>
            <span class="pc-body">
              <span class="pc-name">{{ __('onboarding.notifications.bedtime') }}</span>
              <span class="pc-sub">{{ __('onboarding.notifications.bedtime_sub') }}</span>
            </span>
            <span class="pc-check"></span>
          </button>
        </div>
        <button type="button" data-sheet="reminderSheet" class="btn btn-primary w-full mt-5">{{ __('settings.done') }}</button>
      </div>
    </div>
  </div>
</main>
