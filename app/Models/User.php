<?php

namespace App\Models;

use App\Enums\AgeGroup;
use App\Enums\DailyGoal;
use App\Enums\Gender;
use App\Enums\OnboardingStep;
use App\Enums\ReminderTime;
use App\Enums\SchoolGrade;
use Database\Factories\UserFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Hidden;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Support\Carbon;
use Illuminate\Support\Str;
use NotificationChannels\WebPush\HasPushSubscriptions;

/**
 * @property int $id
 * @property string $name
 * @property string $surname
 * @property string $nickname
 * @property string|null $avatar
 * @property string $email
 * @property int|null $age
 * @property Gender|null $gender
 * @property AgeGroup|null $age_group
 * @property SchoolGrade|null $grade
 * @property list<string>|null $favourite_subjects
 * @property DailyGoal|null $daily_goal
 * @property OnboardingStep|null $onboarding_step
 * @property Carbon|null $onboarding_completed_at
 * @property array<string, mixed>|null $notification_preferences
 * @property ReminderTime|null $reminder_time
 * @property bool $show_on_leaderboard
 * @property bool $allow_friend_requests
 * @property Carbon|null $email_verified_at
 * @property string|null $pending_parent_email
 * @property string|null $pending_parent_email_token
 * @property Carbon|null $pending_parent_email_sent_at
 * @property Carbon|null $deletion_requested_at
 * @property string $password
 * @property string|null $parent_pin
 * @property Carbon|null $parent_pin_set_at
 * @property int|null $daily_limit_minutes
 * @property int $screen_time_extra_minutes
 * @property Carbon|null $screen_time_extra_on
 * @property bool $break_reminders
 * @property bool $warn_before_limit
 * @property bool $bedtime_enabled
 * @property string|null $bedtime_start
 * @property string|null $bedtime_end
 * @property list<int>|null $bedtime_days
 * @property string $timezone
 * @property string|null $remember_token
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * @property Carbon|null $deleted_at
 */
#[Fillable([
    'name',
    'surname',
    'nickname',
    'avatar',
    'email',
    'password',
    'age',
    'gender',
    'age_group',
    'grade',
    'favourite_subjects',
    'daily_goal',
    'onboarding_step',
    'onboarding_completed_at',
    'notification_preferences',
    'reminder_time',
    'show_on_leaderboard',
    'allow_friend_requests',
    'pending_parent_email',
    'pending_parent_email_token',
    'pending_parent_email_sent_at',
    'deletion_requested_at',
    'parent_pin',
    'parent_pin_set_at',
    'daily_limit_minutes',
    'screen_time_extra_minutes',
    'screen_time_extra_on',
    'break_reminders',
    'warn_before_limit',
    'bedtime_enabled',
    'bedtime_start',
    'bedtime_end',
    'bedtime_days',
    'timezone',
])]
#[Hidden(['password', 'parent_pin', 'remember_token', 'pending_parent_email_token'])]
class User extends Authenticatable
{
    /** @use HasFactory<UserFactory> */
    use HasFactory, HasPushSubscriptions, Notifiable, SoftDeletes;

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'pending_parent_email_sent_at' => 'datetime',
            'deletion_requested_at' => 'datetime',
            'onboarding_completed_at' => 'datetime',
            'password' => 'hashed',
            'parent_pin' => 'hashed',
            'parent_pin_set_at' => 'datetime',
            'daily_limit_minutes' => 'integer',
            'screen_time_extra_minutes' => 'integer',
            'screen_time_extra_on' => 'date',
            'break_reminders' => 'boolean',
            'warn_before_limit' => 'boolean',
            'bedtime_enabled' => 'boolean',
            'bedtime_days' => 'array',
            'age' => 'integer',
            'gender' => Gender::class,
            'age_group' => AgeGroup::class,
            'grade' => SchoolGrade::class,
            'favourite_subjects' => 'array',
            'daily_goal' => DailyGoal::class,
            'onboarding_step' => OnboardingStep::class,
            'notification_preferences' => 'array',
            'reminder_time' => ReminderTime::class,
            'show_on_leaderboard' => 'boolean',
            'allow_friend_requests' => 'boolean',
        ];
    }

    /**
     * Kids whose parent left them on the worldwide leaderboard.
     *
     * New accounts default to visible (`users.show_on_leaderboard` is true). Opting out
     * hides the kid from public ranking reads only — friends and league cohorts stay.
     *
     * @param  Builder<User>  $query
     * @return Builder<User>
     */
    public function scopeVisibleOnLeaderboard(Builder $query): Builder
    {
        return $query->where('show_on_leaderboard', true);
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
