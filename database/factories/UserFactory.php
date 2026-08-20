<?php

namespace Database\Factories;

use App\Enums\AgeGroup;
use App\Enums\DailyGoal;
use App\Enums\Gender;
use App\Enums\OnboardingStep;
use App\Enums\ReminderTime;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

/**
 * @extends Factory<User>
 */
class UserFactory extends Factory
{
    /**
     * The current password being used by the factory.
     */
    protected static ?string $password;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'name' => fake()->firstName(),
            'surname' => fake()->lastName(),
            'nickname' => fake()->unique()->numerify('kid####'),
            'email' => fake()->unique()->safeEmail(),
            'email_verified_at' => null,
            'password' => static::$password ??= Hash::make('password'),
            'remember_token' => Str::random(10),
            'age' => 6,
            'gender' => Gender::Girl,
            'onboarding_step' => OnboardingStep::Age,
        ];
    }

    /**
     * Indicate that the model's email address should be unverified.
     */
    public function unverified(): static
    {
        return $this->state(fn (array $attributes) => [
            'email_verified_at' => null,
        ]);
    }

    public function onboarded(): static
    {
        return $this->state(fn (array $attributes) => [
            'age_group' => AgeGroup::Kindergarten,
            'favourite_subjects' => ['alphabet', 'math', 'animals'],
            'daily_goal' => DailyGoal::Regular,
            'onboarding_step' => OnboardingStep::Verify,
            'onboarding_completed_at' => now(),
            'notification_preferences' => [
                'streak' => false,
                'new_lessons' => false,
                'rewards' => false,
                'daily_mission' => false,
                'friend_activity' => false,
                'quiet_hours' => true,
            ],
            'reminder_time' => ReminderTime::Evening,
        ]);
    }

    public function fullySetUp(): static
    {
        return $this->onboarded()->state(fn (array $attributes) => [
            'email_verified_at' => now(),
            'onboarding_step' => OnboardingStep::Done,
        ]);
    }
}
