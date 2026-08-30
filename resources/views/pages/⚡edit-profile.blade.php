<?php

use App\Enums\DailyGoal;
use App\Enums\Gender;
use App\Enums\SchoolGrade;
use App\Enums\SchoolSubject;
use App\Repositories\UserRepository;
use App\Services\BadgeService;
use App\Services\UserProfileService;
use App\Services\UserStatService;
use Illuminate\Validation\Rule;
use Livewire\Attributes\Title;
use Livewire\Component;

new #[Title('პროფილის რედაქტირება · Kidzio')] class extends Component
{
    public string $name = '';

    public string $nickname = '';

    public string $age = '6';

    public string $gender = 'Girl';

    public int $grade = 1;

    public string $favouriteSubject = 'georgian';

    public string $dailyGoal = 'regular';

    public string $avatar = '🐻';

    public string $email = '';

    public bool $emailVerified = false;

    public bool $showOnLeaderboard = true;

    public bool $allowFriendRequests = true;

    public int $xp = 0;

    public int $streak = 0;

    public int $badgeCount = 0;

    /** @var list<string> */
    public array $quickAvatars = [];

    /** @var list<string> */
    public array $allAvatars = [];

    /** @var array<string, string> */
    public array $avatarTiles = [];

    public function mount(
        UserProfileService $profiles,
        UserStatService $stats,
        BadgeService $badges,
        UserRepository $users,
    ): void {
        $user = $users->authenticated();
        $snap = $stats->profileSnapshot($user);

        $this->name = $user->name;
        $this->nickname = $user->nickname;
        $this->age = (string) ($user->age ?? 6);
        $this->gender = ($user->gender ?? Gender::Girl)->value;
        $this->grade = ($user->grade ?? SchoolGrade::First)->value;
        $this->favouriteSubject = $this->favouriteFrom($user->favourite_subjects);
        $this->dailyGoal = ($user->daily_goal ?? DailyGoal::Regular)->value;
        $this->avatar = $profiles->defaultAvatar($user->avatar);
        $this->email = $user->email;
        $this->emailVerified = $user->email_verified_at !== null;
        $this->showOnLeaderboard = (bool) ($user->show_on_leaderboard ?? true);
        $this->allowFriendRequests = (bool) ($user->allow_friend_requests ?? true);
        $this->xp = $snap->xp;
        $this->streak = $snap->streak;
        $this->badgeCount = $badges->earnedCount($user);
        $this->quickAvatars = $profiles->quickAvatars();
        $this->allAvatars = $profiles->avatarChoices();
        $this->avatarTiles = [];

        foreach ($this->allAvatars as $emoji) {
            $this->avatarTiles[$emoji] = $profiles->tileForAvatar($emoji);
        }
    }

    public function selectAvatar(string $avatar): void
    {
        if (in_array($avatar, $this->allAvatars, true)) {
            $this->avatar = $avatar;
        }
    }

    public function selectGender(string $gender): void
    {
        if (Gender::tryFrom($gender) instanceof Gender) {
            $this->gender = $gender;
        }
    }

    public function selectSubject(string $subject): void
    {
        if (SchoolSubject::tryFrom($subject) instanceof SchoolSubject) {
            $this->favouriteSubject = $subject;
        }
    }

    public function selectGoal(string $goal): void
    {
        if (DailyGoal::tryFrom($goal) instanceof DailyGoal) {
            $this->dailyGoal = $goal;
        }
    }

    public function cancel(): void
    {
        $this->redirectRoute('profile', navigate: true);
    }

    public function save(UserProfileService $profiles, UserRepository $users): void
    {
        $this->validate([
            'name' => ['required', 'string', 'max:24'],
            'nickname' => ['required', 'string', 'min:3', 'max:16'],
            'age' => ['required', 'integer', 'min:3', 'max:14'],
            'gender' => ['required', Rule::enum(Gender::class)],
            'grade' => ['required', Rule::enum(SchoolGrade::class)],
            'favouriteSubject' => ['required', Rule::enum(SchoolSubject::class)],
            'dailyGoal' => ['required', Rule::enum(DailyGoal::class)],
            'avatar' => ['required', 'string', Rule::in($profiles->avatarChoices())],
            'showOnLeaderboard' => ['boolean'],
            'allowFriendRequests' => ['boolean'],
        ]);

        $profiles->update($users->authenticated(), [
            'name' => $this->name,
            'nickname' => $this->nickname,
            'age' => (int) $this->age,
            'gender' => Gender::from($this->gender),
            'grade' => SchoolGrade::from((int) $this->grade),
            'favouriteSubject' => $this->favouriteSubject,
            'daily_goal' => DailyGoal::from($this->dailyGoal),
            'avatar' => $this->avatar,
            'show_on_leaderboard' => $this->showOnLeaderboard,
            'allow_friend_requests' => $this->allowFriendRequests,
        ]);

        $this->redirectRoute('profile', navigate: true);
    }

    public function formattedXp(): string
    {
        return number_format($this->xp);
    }

    public function heroMeta(): string
    {
        $grade = SchoolGrade::from($this->grade)->label();
        $subject = SchoolSubject::from($this->favouriteSubject)->label();

        if ($this->age !== '') {
            return (string) __('edit-profile.hero_meta', [
                'age' => $this->age,
                'grade' => $grade,
                'subject' => $subject,
            ]);
        }

        return (string) __('edit-profile.hero_meta_no_age', [
            'grade' => $grade,
            'subject' => $subject,
        ]);
    }

    public function tileFor(string $avatar): string
    {
        return $this->avatarTiles[$avatar] ?? UserProfileService::AVATAR_TILES[0];
    }

    /**
     * @param  list<string>|null  $subjects
     */
    private function favouriteFrom(?array $subjects): string
    {
        if (is_array($subjects) && $subjects !== []) {
            $first = $subjects[0];

            if (is_string($first) && SchoolSubject::tryFrom($first) instanceof SchoolSubject) {
                return $first;
            }
        }

        return SchoolSubject::Georgian->value;
    }
};
?>

<main class="device-frame min-h-screen flex flex-col safe-top">

  <header class="appbar">
    <a href="{{ route('profile') }}" id="backLink" class="icon-btn" data-back aria-label="{{ __('edit-profile.back') }}"><i class="ph ph-caret-left"></i></a>
    <div class="grow">
      <p class="text-xs text-muted">{{ __('edit-profile.eyebrow') }}</p>
      <h1 class="h-display text-lg leading-tight">{{ __('edit-profile.heading') }}</h1>
    </div>
    <button type="button" class="icon-btn" data-theme-toggle aria-label="{{ __('edit-profile.toggle_theme') }}"><i class="ph ph-moon text-xl"></i></button>
  </header>

  <!-- =============== HERO PREVIEW =============== -->
  <section class="px-5">
    <div class="k-card-lg hero-profile text-center">
      <div class="relative inline-grid place-items-center">
        <div class="size-28 rounded-3xl bg-white/20 backdrop-blur-sm grid place-items-center text-6xl" id="heroAvatar">{{ $avatar }}</div>
        <button type="button" class="absolute -bottom-1 -right-1 size-10 rounded-full tile-sun grid place-items-center shadow-lg" data-sheet="avatarSheet" aria-label="{{ __('edit-profile.change_avatar') }}">
          <i class="ph-fill ph-camera text-lg text-sun-ink"></i>
        </button>
      </div>
      <p class="relative chip bg-white/20 border-0 text-white mt-4">{{ __('edit-profile.live_preview') }}</p>
      <p class="relative h-display text-2xl mt-2 leading-tight" id="heroName">{{ $name !== '' ? $name : '—' }}</p>
      <p class="relative text-[11px] text-white/80 -mt-0.5" id="heroNickname">{{ __('edit-profile.nickname_at', ['nickname' => $nickname !== '' ? $nickname : '—']) }}</p>
      <p class="relative text-xs text-white/90 mt-1" id="heroMeta">{{ $this->heroMeta() }}</p>

      <div class="relative mt-4 grid grid-cols-3 gap-2">
        <div class="rounded-2xl bg-white/15 backdrop-blur-sm p-3">
          <p class="h-display text-xl leading-none">{{ $this->formattedXp() }}</p>
          <p class="text-[10px] text-white/85 mt-1">{{ __('edit-profile.total_xp') }}</p>
        </div>
        <div class="rounded-2xl bg-white/15 backdrop-blur-sm p-3">
          <p class="h-display text-xl leading-none">{{ $streak }}</p>
          <p class="text-[10px] text-white/85 mt-1">{{ __('edit-profile.day_streak') }}</p>
        </div>
        <div class="rounded-2xl bg-white/15 backdrop-blur-sm p-3">
          <p class="h-display text-xl leading-none">{{ $badgeCount }}</p>
          <p class="text-[10px] text-white/85 mt-1">{{ __('edit-profile.badges') }}</p>
        </div>
      </div>
    </div>
  </section>

  <!-- =============== AVATAR PICKER =============== -->
  <section class="px-5 mt-5">
    <div class="flex items-end justify-between">
      <p class="section-label">{{ __('edit-profile.pick_avatar') }}</p>
      <button type="button" class="chip" data-sheet="avatarSheet">
        <i class="ph ph-grid-four"></i> {{ __('edit-profile.all_avatars') }}
      </button>
    </div>
    <div class="mt-3 grid grid-cols-6 gap-2" id="avatarPicker">
      @foreach ($quickAvatars as $emoji)
        <button type="button" wire:click="selectAvatar('{{ $emoji }}')" class="aspect-square rounded-2xl {{ $this->tileFor($emoji) }} grid place-items-center text-2xl pick-card{{ $avatar === $emoji ? ' is-selected' : '' }}" data-avatar="{{ $emoji }}">{{ $emoji }}</button>
      @endforeach
    </div>
  </section>

  <form id="profileForm" class="px-5 mt-5 space-y-5" wire:submit.prevent="save">

    <!-- =============== BASICS =============== -->
    <div>
      <p class="section-label">{{ __('edit-profile.basics') }}</p>
      <div class="mt-3 space-y-3">
        <div>
          <label for="kidName" class="text-[11px] font-extrabold text-muted uppercase tracking-wide">{{ __('edit-profile.kids_name') }}</label>
          <div class="input-wrap mt-1">
            <i class="ph ph-smiley i-left"></i>
            <input id="kidName" type="text" class="input has-left" wire:model.live="name" maxlength="24" required autocomplete="off"/>
          </div>
          @error('name')
            <p class="text-sm" style="color:var(--color-k-coral)">{{ $message }}</p>
          @enderror
          <p id="nameHint" class="text-[10px] text-muted mt-1"><i class="ph ph-info"></i> {{ __('edit-profile.name_hint') }}</p>
        </div>

        <div>
          <label for="nickname" class="text-[11px] font-extrabold text-muted uppercase tracking-wide">{{ __('edit-profile.display_name') }} <span class="text-muted font-normal normal-case">{{ __('edit-profile.display_name_hint') }}</span></label>
          <div class="input-wrap mt-1">
            <i class="ph ph-sparkle i-left"></i>
            <input id="nickname" type="text" class="input has-left" wire:model.live="nickname" maxlength="16" autocomplete="off"/>
          </div>
          @error('nickname')
            <p class="text-sm" style="color:var(--color-k-coral)">{{ $message }}</p>
          @enderror
        </div>

        <div class="grid grid-cols-2 gap-3">
          <div>
            <label for="age" class="text-[11px] font-extrabold text-muted uppercase tracking-wide">{{ __('edit-profile.age') }}</label>
            <div class="input-wrap mt-1">
              <i class="ph ph-cake i-left"></i>
              <input id="age" type="number" min="3" max="14" class="input has-left" wire:model.live="age"/>
            </div>
            @error('age')
              <p class="text-sm" style="color:var(--color-k-coral)">{{ $message }}</p>
            @enderror
          </div>
          <div>
            <label for="birthday" class="text-[11px] font-extrabold text-muted uppercase tracking-wide">{{ __('edit-profile.birthday') }}</label>
            <div class="input-wrap mt-1">
              <i class="ph ph-calendar i-left"></i>
              <input id="birthday" type="date" class="input has-left" disabled/>
            </div>
          </div>
        </div>
      </div>
    </div>

    <!-- =============== GENDER =============== -->
    <div>
      <p class="section-label">{{ __('edit-profile.gender_section') }}</p>
      <div class="mt-3 grid grid-cols-3 gap-2" id="pronounPicker">
        <button type="button" wire:click="selectGender('Girl')" class="pick-card !p-2 !gap-0 flex-col text-center{{ $gender === 'Girl' ? ' is-selected' : '' }}" data-pronoun="she">
          <span class="text-xl">👧</span>
          <span class="pc-name text-xs mt-1">{{ __('edit-profile.gender.Girl') }}</span>
        </button>
        <button type="button" wire:click="selectGender('Boy')" class="pick-card !p-2 !gap-0 flex-col text-center{{ $gender === 'Boy' ? ' is-selected' : '' }}" data-pronoun="he">
          <span class="text-xl">👦</span>
          <span class="pc-name text-xs mt-1">{{ __('edit-profile.gender.Boy') }}</span>
        </button>
        <button type="button" wire:click="selectGender('Other')" class="pick-card !p-2 !gap-0 flex-col text-center{{ $gender === 'Other' ? ' is-selected' : '' }}" data-pronoun="they">
          <span class="text-xl">🧒</span>
          <span class="pc-name text-xs mt-1">{{ __('edit-profile.gender.Other') }}</span>
        </button>
      </div>
      @error('gender')
        <p class="text-sm" style="color:var(--color-k-coral)">{{ $message }}</p>
      @enderror
    </div>

    <!-- =============== SCHOOL =============== -->
    <div>
      <p class="section-label">{{ __('edit-profile.school_goal') }}</p>
      <div class="mt-3 space-y-3">
        <div>
          <label for="grade" class="text-[11px] font-extrabold text-muted uppercase tracking-wide">{{ __('edit-profile.grade_level') }}</label>
          <div class="input-wrap mt-1">
            <i class="ph ph-graduation-cap i-left"></i>
            <select id="grade" class="input has-left" wire:model.live="grade">
              @foreach (\App\Enums\SchoolGrade::cases() as $option)
                <option value="{{ $option->value }}">{{ $option->label() }}</option>
              @endforeach
            </select>
          </div>
          @error('grade')
            <p class="text-sm" style="color:var(--color-k-coral)">{{ $message }}</p>
          @enderror
        </div>

        <div>
          <label class="text-[11px] font-extrabold text-muted uppercase tracking-wide">{{ __('edit-profile.favorite_subject') }}</label>
          <div class="mt-2 grid grid-cols-3 gap-2" id="subjectPicker">
            @foreach (\App\Enums\SchoolSubject::ordered() as $subject)
              <button type="button" wire:click="selectSubject('{{ $subject->value }}')" class="pick-card !p-2 !gap-0 flex-col text-center{{ $favouriteSubject === $subject->value ? ' is-selected' : '' }}" data-subject="{{ $subject->value }}">
                <span class="text-xl">{{ $subject->emoji() }}</span>
                <span class="pc-name text-xs mt-1">{{ $subject->label() }}</span>
              </button>
            @endforeach
          </div>
          @error('favouriteSubject')
            <p class="text-sm" style="color:var(--color-k-coral)">{{ $message }}</p>
          @enderror
        </div>

        <div>
          <label class="text-[11px] font-extrabold text-muted uppercase tracking-wide">{{ __('edit-profile.daily_goal') }}</label>
          <div class="mt-2 grid grid-cols-4 gap-2" id="goalPicker">
            @foreach (\App\Enums\DailyGoal::cases() as $goal)
              <button type="button" wire:click="selectGoal('{{ $goal->value }}')" class="pick-card !p-2 !gap-0 flex-col text-center{{ $dailyGoal === $goal->value ? ' is-selected' : '' }}" data-goal="{{ $goal->minutes() }}">
                <span class="h-display text-base">{{ $goal->minutes() }}</span>
                <span class="pc-name text-[10px] mt-0.5">{{ __('edit-profile.minutes') }}</span>
              </button>
            @endforeach
          </div>
          @error('dailyGoal')
            <p class="text-sm" style="color:var(--color-k-coral)">{{ $message }}</p>
          @enderror
        </div>
      </div>
    </div>

    <!-- =============== PARENT =============== -->
    <div>
      <p class="section-label">{{ __('edit-profile.parent_contact') }}</p>
      <div class="mt-3 space-y-3">
        <div>
          <label for="parentEmail" class="text-[11px] font-extrabold text-muted uppercase tracking-wide">{{ __('edit-profile.parent_email') }}</label>
          <div class="input-wrap mt-1">
            <i class="ph ph-envelope-simple i-left"></i>
            <input id="parentEmail" type="email" class="input has-left" value="{{ $email }}" readonly autocomplete="email"/>
          </div>
          <p id="emailHint" class="text-[10px] text-muted mt-1">
            @if ($emailVerified)
              <i class="ph-fill ph-check-circle text-mint-ink"></i>
              <span>{{ __('edit-profile.email_verified') }}</span>
            @else
              <i class="ph ph-info"></i>
              <span>{{ __('edit-profile.email_unverified') }}</span>
            @endif
          </p>
        </div>
        <a href="#" class="setting-row">
          <div class="setting-ico tile-violet"><i class="ph-fill ph-shield-check"></i></div>
          <div class="grow min-w-0">
            <p class="setting-text font-extrabold text-sm text-ink">{{ __('edit-profile.parent_zone') }}</p>
            <p class="text-[11px] text-muted">{{ __('edit-profile.parent_zone_hint') }}</p>
          </div>
          <i class="ph ph-caret-right text-muted"></i>
        </a>
      </div>
    </div>

    <!-- =============== PRIVACY =============== -->
    <div>
      <p class="section-label">{{ __('edit-profile.privacy') }}</p>
      <div class="mt-3 space-y-2">
        <label class="setting-row cursor-pointer">
          <div class="setting-ico tile-mint"><i class="ph-fill ph-eye"></i></div>
          <div class="grow min-w-0">
            <p class="setting-text font-extrabold text-sm text-ink">{{ __('edit-profile.show_on_ranking') }}</p>
            <p class="text-[11px] text-muted">{{ __('edit-profile.show_on_ranking_hint') }}</p>
          </div>
          <span class="ks-switch">
            <input type="checkbox" wire:model="showOnLeaderboard"/>
            <span class="track"></span>
            <span class="thumb"></span>
          </span>
        </label>
        <label class="setting-row cursor-pointer">
          <div class="setting-ico tile-sky"><i class="ph-fill ph-users-three"></i></div>
          <div class="grow min-w-0">
            <p class="setting-text font-extrabold text-sm text-ink">{{ __('edit-profile.allow_friend_requests') }}</p>
            <p class="text-[11px] text-muted">{{ __('edit-profile.allow_friend_requests_hint') }}</p>
          </div>
          <span class="ks-switch">
            <input type="checkbox" wire:model="allowFriendRequests"/>
            <span class="track"></span>
            <span class="thumb"></span>
          </span>
        </label>
      </div>
    </div>

    <!-- =============== DANGER =============== -->
    <div class="pb-32">
      <p class="section-label">{{ __('edit-profile.danger_zone') }}</p>
      <div class="mt-3 space-y-2">
        <button type="button" id="resetBtn" class="setting-row w-full text-left">
          <div class="setting-ico tile-sun"><i class="ph-fill ph-arrows-clockwise"></i></div>
          <div class="grow min-w-0">
            <p class="setting-text font-extrabold text-sm text-ink">{{ __('edit-profile.reset_progress') }}</p>
            <p class="text-[11px] text-muted">{{ __('edit-profile.reset_progress_hint') }}</p>
          </div>
          <i class="ph ph-caret-right text-muted"></i>
        </button>
        <button type="button" id="deleteBtn" class="setting-row danger w-full text-left">
          <div class="setting-ico"><i class="ph-fill ph-trash"></i></div>
          <div class="grow min-w-0">
            <p class="setting-text font-extrabold text-sm">{{ __('edit-profile.delete_profile') }}</p>
            <p class="text-[11px] text-muted">{{ __('edit-profile.delete_profile_hint') }}</p>
          </div>
          <i class="ph ph-caret-right text-muted"></i>
        </button>
      </div>
    </div>
  </form>

  <!-- =============== STICKY SAVE BAR =============== -->
  <div class="fixed bottom-0 left-0 right-0 z-40 pointer-events-none">
    <div class="mx-auto max-w-[430px] pointer-events-auto">
      <div class="bg-surface border-t border-token safe-bottom px-5 pt-3 pb-3 flex items-center gap-2">
        <button type="button" id="cancelBtn" class="btn btn-ghost grow" wire:click="cancel">{{ __('edit-profile.cancel') }}</button>
        <button type="button" id="saveBtn" class="btn btn-primary grow" wire:click="save">
          <i class="ph-fill ph-check"></i> {{ __('edit-profile.save') }}
        </button>
      </div>
    </div>
  </div>

  <!-- =============== AVATAR BOTTOM SHEET =============== -->
  <div id="avatarSheet" class="hidden fixed inset-0 z-50" role="dialog" aria-modal="true" aria-labelledby="avatarTitle">
    <button type="button" data-sheet="avatarSheet" class="absolute inset-0 size-full bg-black/50 backdrop-blur-sm" aria-label="{{ __('edit-profile.close') }}"></button>
    <div class="absolute left-0 right-0 bottom-0 mx-auto max-w-[430px] bg-surface rounded-t-3xl border-t border-token shadow-2xl safe-bottom">
      <div class="flex justify-center pt-3">
        <span class="block w-10 h-1.5 rounded-full bg-[var(--color-k-border)]"></span>
      </div>
      <div class="px-5 pt-4 pb-6">
        <div class="flex items-start gap-3">
          <div class="size-12 rounded-2xl tile-pink grid place-items-center text-2xl shrink-0">🎨</div>
          <div class="grow min-w-0">
            <p id="avatarTitle" class="h-display text-xl leading-tight text-ink">{{ __('edit-profile.choose_avatar') }}</p>
            <p class="text-xs text-muted mt-1">{{ __('edit-profile.choose_avatar_hint') }}</p>
          </div>
          <button type="button" class="icon-btn shrink-0" data-sheet="avatarSheet" aria-label="{{ __('edit-profile.close') }}">
            <i class="ph ph-x"></i>
          </button>
        </div>
        <div class="mt-4 grid grid-cols-6 gap-2" id="avatarAll">
          @foreach ($allAvatars as $emoji)
            <button type="button" wire:click="selectAvatar('{{ $emoji }}')" class="aspect-square rounded-2xl {{ $this->tileFor($emoji) }} grid place-items-center text-2xl pick-card{{ $avatar === $emoji ? ' is-selected' : '' }}" data-avatar-all="{{ $emoji }}">{{ $emoji }}</button>
          @endforeach
        </div>
        <button type="button" data-sheet="avatarSheet" class="btn btn-primary w-full mt-5">{{ __('edit-profile.done') }}</button>
      </div>
    </div>
  </div>
</main>
