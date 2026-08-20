<?php

namespace App\Models;

use App\Enums\AgeGroup;
use App\Enums\DailyGoal;
use App\Enums\Gender;
use App\Enums\OnboardingStep;
use App\Enums\ReminderTime;
use Database\Factories\UserFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Hidden;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Support\Carbon;
use Illuminate\Support\Str;

/**
 * @property int $id
 * @property string $name
 * @property string $surname
 * @property string $nickname
 * @property string $email
 * @property int|null $age
 * @property Gender|null $gender
 * @property AgeGroup|null $age_group
 * @property list<string>|null $favourite_subjects
 * @property DailyGoal|null $daily_goal
 * @property OnboardingStep|null $onboarding_step
 * @property Carbon|null $onboarding_completed_at
 * @property array<string, mixed>|null $notification_preferences
 * @property ReminderTime|null $reminder_time
 * @property Carbon|null $email_verified_at
 * @property string $password
 * @property string|null $remember_token
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 */
#[Fillable([
    'name',
    'surname',
    'nickname',
    'email',
    'password',
    'age',
    'gender',
    'age_group',
    'favourite_subjects',
    'daily_goal',
    'onboarding_step',
    'onboarding_completed_at',
    'notification_preferences',
    'reminder_time',
])]
#[Hidden(['password', 'remember_token'])]
class User extends Authenticatable
{
    /** @use HasFactory<UserFactory> */
    use HasFactory, Notifiable;

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'onboarding_completed_at' => 'datetime',
            'password' => 'hashed',
            'age' => 'integer',
            'gender' => Gender::class,
            'age_group' => AgeGroup::class,
            'favourite_subjects' => 'array',
            'daily_goal' => DailyGoal::class,
            'onboarding_step' => OnboardingStep::class,
            'notification_preferences' => 'array',
            'reminder_time' => ReminderTime::class,
        ];
    }

    /**
     * Get the user's initials
     */
    public function initials(): string
    {
        $initials = Str::initials($this->name, true);

        return Str::length($initials) > 1
            ? Str::substr($initials, 0, 1).Str::substr($initials, -1)
            : $initials;
    }
}
