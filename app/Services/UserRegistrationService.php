<?php

namespace App\Services;

use App\Enums\Gender;
use App\Enums\OnboardingStep;
use App\Models\User;
use App\Repositories\UserRepository;
use Illuminate\Support\Str;

class UserRegistrationService
{
    public function __construct(private UserRepository $users) {}

    public function register(string $name, string $email, string $password, int $age, string $gender): User
    {
        return $this->users->create([
            'name' => $name,
            'surname' => '',
            'nickname' => $this->uniqueNickname($name),
            'email' => $email,
            'password' => $password,
            'age' => $age,
            'gender' => Gender::from($gender),
            'onboarding_step' => OnboardingStep::Age,
        ]);
    }

    private function uniqueNickname(string $name): string
    {
        $base = Str::slug($name);
        $base = $base !== '' ? $base : 'kid';

        if (Str::length($base) < 3) {
            $base = Str::padRight($base, 3, '0');
        }

        $base = Str::substr($base, 0, 16);

        $nickname = $base;
        $suffix = 1;

        while ($this->users->nicknameExists($nickname)) {
            $nickname = $base.(string) $suffix;
            $suffix++;
        }

        return $nickname;
    }
}
