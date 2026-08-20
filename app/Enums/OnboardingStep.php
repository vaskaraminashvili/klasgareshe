<?php

namespace App\Enums;

enum OnboardingStep: string
{
    case Age = 'age';
    case Categories = 'categories';
    case Goals = 'goals';
    case Notifications = 'notifications';
    case Verify = 'verify';
    case Done = 'done';

    public function routeName(): string
    {
        return match ($this) {
            self::Age => 'onboarding-age',
            self::Categories => 'onboarding-categories',
            self::Goals => 'onboarding-goals',
            self::Notifications => 'onboarding-notifications',
            self::Verify => 'parent-verify',
            self::Done => 'home',
        };
    }

    public function order(): int
    {
        return match ($this) {
            self::Age => 1,
            self::Categories => 2,
            self::Goals => 3,
            self::Notifications => 4,
            self::Verify => 5,
            self::Done => 6,
        };
    }

    public function furthest(self $other): self
    {
        return $this->order() >= $other->order() ? $this : $other;
    }
}
