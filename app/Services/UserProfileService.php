<?php

namespace App\Services;

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

    public function __construct(private UserRepository $users) {}

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
}
