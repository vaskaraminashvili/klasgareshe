<?php

namespace Database\Factories;

use App\Enums\LeagueWeekStatus;
use App\Models\LeagueWeek;
use Carbon\CarbonImmutable;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<LeagueWeek>
 */
class LeagueWeekFactory extends Factory
{
    protected $model = LeagueWeek::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $start = now()->startOfWeek(CarbonImmutable::MONDAY);

        return [
            'starts_on' => $start->toDateString(),
            'ends_on' => $start->addDays(6)->toDateString(),
            'status' => LeagueWeekStatus::Open,
        ];
    }
}
