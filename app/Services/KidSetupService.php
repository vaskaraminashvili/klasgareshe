<?php

namespace App\Services;

use App\Enums\AgeGroup;
use App\Enums\DailyGoal;
use App\Enums\FavouriteSubject;
use App\Enums\OnboardingStep;
use App\Enums\ReminderTime;
use App\Models\User;
use App\Repositories\UserRepository;

class KidSetupService
{
    public function __construct(private UserRepository $users) {}

    public function nextRouteName(User $user): string
    {
        return $this->currentStep($user)->routeName();
    }

    public function currentStep(User $user): OnboardingStep
    {
        if ($user->onboarding_completed_at === null) {
            return $user->onboarding_step ?? OnboardingStep::Age;
        }

        if ($user->email_verified_at === null) {
            return OnboardingStep::Verify;
        }

        return OnboardingStep::Done;
    }

    public function isFullySetUp(User $user): bool
    {
        return $this->currentStep($user) === OnboardingStep::Done;
    }

    /**
     * @return list<string>
     */
    public function allowedRouteNames(User $user): array
    {
        $step = $this->currentStep($user);

        if ($step === OnboardingStep::Done) {
            return [
                'home',
                'profile',
                'game-multiple-choice',
                'xp-progress',
                'leaderboard',
                'ranking-weekly',
                'league',
            ];
        }

        $routes = [];

        foreach (OnboardingStep::cases() as $candidate) {
            if ($candidate === OnboardingStep::Done) {
                continue;
            }

            if ($candidate->order() <= $step->order()) {
                $routes[] = $candidate->routeName();
            }
        }

        return $routes;
    }

    public function saveAgeGroup(User $user, AgeGroup $ageGroup): void
    {
        $this->users->update($user, [
            'age_group' => $ageGroup,
            'onboarding_step' => $this->advance($user, OnboardingStep::Categories),
        ]);
    }

    /**
     * @param  list<string>  $selected
     */
    public function saveSubjects(User $user, array $selected): void
    {
        $this->users->update($user, [
            'favourite_subjects' => $this->coreSubjectsFrom($selected),
            'onboarding_step' => $this->advance($user, OnboardingStep::Goals),
        ]);
    }

    public function saveDailyGoal(User $user, DailyGoal $goal): void
    {
        $this->users->update($user, [
            'daily_goal' => $goal,
            'onboarding_step' => $this->advance($user, OnboardingStep::Notifications),
        ]);
    }

    /**
     * @param  array<string, bool>  $preferences
     */
    public function completeNotifications(User $user, array $preferences, ReminderTime $reminderTime): void
    {
        $this->users->update($user, [
            'notification_preferences' => $preferences,
            'reminder_time' => $reminderTime,
            'onboarding_step' => $this->advance($user, OnboardingStep::Verify),
            'onboarding_completed_at' => $user->onboarding_completed_at ?? now(),
        ]);
    }

    /**
     * @param  list<string>  $selected
     * @return list<string>
     */
    public function coreSubjectsFrom(array $selected): array
    {
        $subjects = [];

        foreach ($selected as $value) {
            $key = FavouriteSubject::ALIASES[strtolower($value)] ?? strtolower($value);
            $subject = FavouriteSubject::tryFrom($key);

            if ($subject instanceof FavouriteSubject) {
                $subjects[$subject->value] = $subject->value;
            }
        }

        return array_values($subjects);
    }

    public function defaultAgeGroup(User $user): AgeGroup
    {
        if ($user->age_group instanceof AgeGroup) {
            return $user->age_group;
        }

        if (is_int($user->age)) {
            return AgeGroup::fromAge($user->age);
        }

        return AgeGroup::Kindergarten;
    }

    public function defaultDailyGoal(User $user): DailyGoal
    {
        return $user->daily_goal ?? DailyGoal::Regular;
    }

    public function defaultReminderTime(User $user): ReminderTime
    {
        return $user->reminder_time ?? ReminderTime::Evening;
    }

    /**
     * @return array<string, bool>
     */
    public function defaultNotificationPreferences(User $user): array
    {
        $saved = $user->notification_preferences;

        if (is_array($saved) && $saved !== []) {
            return $this->normalizeNotificationPreferences($saved);
        }

        return $this->allowNotificationPreferences();
    }

    /**
     * @return array<string, bool>
     */
    public function allowNotificationPreferences(): array
    {
        return [
            'streak' => true,
            'new_lessons' => true,
            'rewards' => false,
            'daily_mission' => true,
            'friend_activity' => false,
            'quiet_hours' => true,
        ];
    }

    /**
     * @return array<string, bool>
     */
    public function skippedNotificationPreferences(): array
    {
        return [
            'streak' => false,
            'new_lessons' => false,
            'rewards' => false,
            'daily_mission' => false,
            'friend_activity' => false,
            'quiet_hours' => true,
        ];
    }

    /**
     * @param  array<string, mixed>  $preferences
     * @return array<string, bool>
     */
    public function normalizeNotificationPreferences(array $preferences): array
    {
        $defaults = $this->allowNotificationPreferences();

        foreach ($defaults as $key => $default) {
            $defaults[$key] = array_key_exists($key, $preferences)
                ? (bool) $preferences[$key]
                : $default;
        }

        return $defaults;
    }

    /**
     * @return list<string>
     */
    public function selectedSubjects(User $user): array
    {
        if (is_array($user->favourite_subjects)) {
            return $user->favourite_subjects;
        }

        return ['alphabet', 'math', 'animals'];
    }

    private function advance(User $user, OnboardingStep $next): OnboardingStep
    {
        $current = $user->onboarding_step ?? OnboardingStep::Age;

        return $current->furthest($next);
    }
}
