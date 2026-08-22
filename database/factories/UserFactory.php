<?php

namespace Database\Factories;

use App\Enums\AgeGroup;
use App\Enums\DailyGoal;
use App\Enums\Gender;
use App\Enums\OnboardingStep;
use App\Enums\ReminderTime;
use App\Enums\SchoolGrade;
use App\Models\User;
use App\Models\UserActivityDay;
use App\Models\UserStat;
use App\Services\LeagueSeasonService;
use Carbon\CarbonImmutable;
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
            'grade' => SchoolGrade::First,
            'favourite_subjects' => ['georgian', 'math', 'history'],
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

    /**
     * @param  array<string, mixed>  $attributes
     */
    public function withStats(array $attributes = []): static
    {
        return $this->afterCreating(function (User $user) use ($attributes): void {
            UserStat::query()->updateOrCreate(
                ['user_id' => $user->id],
                array_merge(UserStat::defaults(), $attributes),
            );
        });
    }

    /**
     * Fully set up kid with veteran XP, streak, recent activity days, and league membership.
     *
     * @param  array<string, mixed>  $statAttributes
     */
    public function veteran(array $statAttributes = []): static
    {
        return $this->fullySetUp()->afterCreating(function (User $user) use ($statAttributes): void {
            $defaults = UserStat::factory()->veteran()->make()->only([
                'xp',
                'current_streak',
                'longest_streak',
                'last_played_on',
                'league',
            ]);

            $stats = array_merge($defaults, $statAttributes);

            UserStat::query()->updateOrCreate(
                ['user_id' => $user->id],
                array_merge(UserStat::defaults(), $stats),
            );

            $this->seedRecentActivity($user, (int) $stats['xp'], (int) $stats['current_streak']);
            $this->seedLeagueWeekXp($user, fake()->numberBetween(40, 280));
        });
    }

    /**
     * High-XP kid for the top of the global leaderboard.
     *
     * @param  array<string, mixed>  $statAttributes
     */
    public function leaderboardTop(array $statAttributes = []): static
    {
        return $this->fullySetUp()->afterCreating(function (User $user) use ($statAttributes): void {
            $defaults = UserStat::factory()->leaderboardTop()->make()->only([
                'xp',
                'current_streak',
                'longest_streak',
                'last_played_on',
                'league',
            ]);

            $stats = array_merge($defaults, $statAttributes);

            UserStat::query()->updateOrCreate(
                ['user_id' => $user->id],
                array_merge(UserStat::defaults(), $stats),
            );

            $this->seedRecentActivity($user, (int) $stats['xp'], (int) $stats['current_streak']);
            $this->seedLeagueWeekXp($user, fake()->numberBetween(180, 420));
        });
    }

    private function seedRecentActivity(User $user, int $lifetimeXp, int $streak): void
    {
        $days = max(3, min(14, $streak > 0 ? $streak : fake()->numberBetween(4, 7)));
        $pool = min(
            $lifetimeXp,
            fake()->numberBetween(60, max(60, (int) round($lifetimeXp * 0.4))),
        );
        $today = CarbonImmutable::now()->startOfDay();

        for ($i = 0; $i < $days; $i++) {
            $date = $today->subDays($i)->toDateString();
            $isLast = $i === $days - 1;
            $left = $days - $i;
            $chunk = $isLast
                ? max(8, $pool)
                : fake()->numberBetween(8, max(8, intdiv($pool, $left)));
            $chunk = min($chunk, max(8, $pool));
            $pool = max(0, $pool - $chunk);

            UserActivityDay::query()->updateOrCreate(
                [
                    'user_id' => $user->id,
                    'played_on' => $date,
                ],
                ['xp_earned' => $chunk],
            );
        }
    }

    private function seedLeagueWeekXp(User $user, int $weekXp): void
    {
        $member = app(LeagueSeasonService::class)->ensureMembership($user);

        if ($weekXp > 0) {
            $member->update(['week_xp' => $weekXp]);
        }
    }
}
