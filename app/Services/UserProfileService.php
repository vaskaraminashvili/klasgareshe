<?php

namespace App\Services;

use App\Enums\DailyGoal;
use App\Enums\PlayDifficulty;
use App\Enums\ReminderTime;
use App\Enums\SchoolSubject;
use App\Models\User;
use App\Repositories\UserRepository;
use Illuminate\Validation\ValidationException;

class UserProfileService
{
    /** @var list<string> */
    private const AVATARS = [
        '🐻', '🐰', '🦊', '🐨', '🐼', '🦄',
        '🐯', '🐸', '🐵', '🐧', '🦉', '🐙',
        '🦁', '🐢', '🦀', '🐬', '🦋', '🐞',
        '🦒', '🦥', '🐶', '🐱', '🐴', '🦩',
    ];

    /** @var list<string> */
    public const AVATAR_TILES = [
        'tile-sun',
        'tile-mint',
        'tile-coral',
        'tile-sky',
        'tile-violet',
        'tile-pink',
    ];

    public function __construct(
        private UserRepository $users,
        private KidSetupService $setup,
    ) {}

    /**
     * @param  array<string, mixed>  $data
     */
    public function update(User $user, array $data): User
    {
        $nickname = trim($data['nickname']);

        if ($this->users->nicknameExists($nickname, $user->id)) {
            throw ValidationException::withMessages([
                'nickname' => (string) __('edit-profile.errors.nickname_taken'),
            ]);
        }

        $avatar = $data['avatar'];

        if (! in_array($avatar, $this->avatarChoices(), true)) {
            $avatar = $this->avatarChoices()[0];
        }

        return $this->users->update($user, [
            'name' => trim($data['name']),
            'nickname' => $nickname,
            'age' => $data['age'],
            'gender' => $data['gender'],
            'grade' => $data['grade'],
            'favourite_subjects' => $this->reorderSubjects($user, $data['favouriteSubject']),
            'daily_goal' => $data['daily_goal'],
            'avatar' => $avatar,
            'show_on_leaderboard' => $data['show_on_leaderboard'],
            'allow_friend_requests' => $data['allow_friend_requests'],
        ]);
    }

    /**
     * @return list<string>
     */
    public function avatarChoices(): array
    {
        return self::AVATARS;
    }

    /**
     * @return list<string>
     */
    public function quickAvatars(): array
    {
        return array_slice($this->avatarChoices(), 0, 6);
    }

    public function defaultAvatar(?string $avatar = null): string
    {
        if (is_string($avatar) && in_array($avatar, $this->avatarChoices(), true)) {
            return $avatar;
        }

        return $this->avatarChoices()[0];
    }

    public function tileForAvatar(string $avatar): string
    {
        $index = array_search($avatar, $this->avatarChoices(), true);

        if ($index === false) {
            return self::AVATAR_TILES[0];
        }

        return self::AVATAR_TILES[$index % count(self::AVATAR_TILES)];
    }

    /**
     * @return list<string>
     */
    private function reorderSubjects(User $user, string $favouriteValue): array
    {
        $favourite = SchoolSubject::from($favouriteValue);
        $orderedValues = array_map(
            static fn (SchoolSubject $subject): string => $subject->value,
            SchoolSubject::ordered(),
        );

        $current = is_array($user->favourite_subjects) ? $user->favourite_subjects : [];
        $schoolCurrent = array_values(array_filter(
            $current,
            static fn (string $value): bool => in_array($value, $orderedValues, true),
        ));
        $hasAll = count(array_unique($schoolCurrent)) === count($orderedValues);

        if ($hasAll) {
            $rest = array_values(array_filter(
                $schoolCurrent,
                static fn (string $value): bool => $value !== $favourite->value,
            ));

            return array_values(array_unique([$favourite->value, ...$rest]));
        }

        $missing = array_values(array_filter(
            $orderedValues,
            static fn (string $value): bool => $value !== $favourite->value,
        ));

        return [$favourite->value, ...$missing];
    }

    public function updatePrivacy(User $user, bool $showOnLeaderboard, bool $allowFriendRequests): User
    {
        return $this->users->update($user, [
            'show_on_leaderboard' => $showOnLeaderboard,
            'allow_friend_requests' => $allowFriendRequests,
        ]);
    }

    public function toggleFavouriteSubject(User $user, SchoolSubject $subject): bool
    {
        $current = is_array($user->favourite_subjects) ? $user->favourite_subjects : [];
        $value = $subject->value;

        if (in_array($value, $current, true)) {
            $current = array_values(array_filter(
                $current,
                static fn (string $item): bool => $item !== $value,
            ));
            $this->users->update($user, ['favourite_subjects' => $current]);

            return false;
        }

        $current[] = $value;
        $this->users->update($user, [
            'favourite_subjects' => array_values(array_unique($current)),
        ]);

        return true;
    }

    /**
     * @return list<string>
     */
    public function favouriteSubjectValues(User $user): array
    {
        return is_array($user->favourite_subjects) ? $user->favourite_subjects : [];
    }

    /**
     * @param  list<string>  $subjects
     */
    public function updateFavouriteSubjects(User $user, array $subjects): User
    {
        $allowed = array_map(
            static fn (SchoolSubject $subject): string => $subject->value,
            SchoolSubject::ordered(),
        );
        $clean = [];

        foreach ($subjects as $value) {
            if (in_array($value, $allowed, true) && ! in_array($value, $clean, true)) {
                $clean[] = $value;
            }
        }

        if ($clean === []) {
            throw ValidationException::withMessages([
                'subjects' => (string) __('parent-zone.subjects_required'),
            ]);
        }

        return $this->users->update($user, [
            'favourite_subjects' => $clean,
        ]);
    }

    public function updateDailyGoal(User $user, DailyGoal $goal): User
    {
        return $this->users->update($user, [
            'daily_goal' => $goal,
        ]);
    }

    public function updatePlayDifficulty(User $user, PlayDifficulty $difficulty): User
    {
        return $this->users->update($user, [
            'play_difficulty' => $difficulty,
        ]);
    }

    /**
     * @param  array<string, bool>  $preferences
     */
    public function updateNotifications(User $user, array $preferences, ReminderTime $reminderTime): User
    {
        $current = is_array($user->notification_preferences) ? $user->notification_preferences : [];

        return $this->users->update($user, [
            'notification_preferences' => $this->setup->normalizeNotificationPreferences(
                array_merge($current, $preferences),
            ),
            'reminder_time' => $reminderTime,
        ]);
    }
}
