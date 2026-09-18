<?php

namespace Database\Factories;

use App\Models\User;
use App\Models\UserPlaySession;
use Carbon\CarbonImmutable;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<UserPlaySession>
 */
class UserPlaySessionFactory extends Factory
{
    protected $model = UserPlaySession::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $started = now()->subMinutes(10);

        return [
            'user_id' => User::factory(),
            'started_at' => $started,
            'last_heartbeat_at' => $started->copy()->addMinutes(10),
            'ended_at' => $started->copy()->addMinutes(10),
            'seconds' => 600,
        ];
    }

    public function open(): static
    {
        return $this->state(fn (array $attributes) => [
            'ended_at' => null,
            'last_heartbeat_at' => now(),
        ]);
    }

    public function lasting(int $seconds, ?string $endedAt = null): static
    {
        $end = $endedAt !== null ? CarbonImmutable::parse($endedAt) : CarbonImmutable::now();
        $start = $end->subSeconds($seconds);

        return $this->state(fn (array $attributes) => [
            'started_at' => $start,
            'last_heartbeat_at' => $end,
            'ended_at' => $end,
            'seconds' => $seconds,
        ]);
    }
}
