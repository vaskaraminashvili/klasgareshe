<?php

namespace Tests\Feature;

use App\Enums\GameType;
use App\Enums\PlanProgressStatus;
use App\Enums\SchoolGrade;
use App\Enums\SchoolSubject;
use App\Models\Game;
use App\Models\Question;
use App\Models\User;
use App\Models\UserPlanProgress;
use App\Models\WeekPlanItem;
use App\Services\UserStatService;
use Carbon\CarbonImmutable;
use Database\Seeders\BadgeSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class MonthlyGoalsTest extends TestCase
{
    use RefreshDatabase;

    public function test_monthly_goals_page_loads_for_setup_user(): void
    {
        $this->withoutVite();
        $this->travelTo(CarbonImmutable::parse('2026-08-20 12:00:00'));
        $this->seed(BadgeSeeder::class);

        $user = User::factory()->fullySetUp()->withStats()->create();

        Livewire::actingAs($user)
            ->test('pages::monthly-goals')
            ->assertSet('monthLabel', __('monthly-goals.months.august'))
            ->assertSet('goalsTotal', 4)
            ->assertSet('xpEarned', 0)
            ->assertSee(__('monthly-goals.monthly_goals'), false)
            ->assertSee(__('monthly-goals.goal_lessons', ['target' => 12]), false);
    }

    public function test_monthly_goals_shows_xp_after_record_play(): void
    {
        $this->withoutVite();
        $this->travelTo(CarbonImmutable::parse('2026-08-20 12:00:00'));
        $this->seed(BadgeSeeder::class);

        $user = User::factory()->fullySetUp()->withStats()->create();

        app(UserStatService::class)->recordPlay($user, 120);

        Livewire::actingAs($user)
            ->test('pages::monthly-goals')
            ->assertSet('xpEarned', 120)
            ->assertSet('xpPerDay', 6)
            ->assertSee('120', false);
    }

    public function test_monthly_goals_counts_completed_lessons_this_month(): void
    {
        $this->withoutVite();
        $this->travelTo(CarbonImmutable::parse('2026-08-20 12:00:00'));
        $this->seed(BadgeSeeder::class);
        $this->seedWeek();

        $user = User::factory()->fullySetUp()->withStats()->create();

        $item = WeekPlanItem::query()
            ->where('grade', SchoolGrade::First)
            ->where('subject', SchoolSubject::Georgian)
            ->where('weekday', 1)
            ->firstOrFail();

        UserPlanProgress::query()->create([
            'user_id' => $user->id,
            'week_plan_item_id' => $item->id,
            'status' => PlanProgressStatus::Completed,
            'correct_count' => 1,
            'completed_at' => CarbonImmutable::parse('2026-08-15 10:00:00'),
        ]);

        Livewire::actingAs($user)
            ->test('pages::monthly-goals')
            ->assertSet('goals.0.key', 'lessons')
            ->assertSet('goals.0.current', 1)
            ->assertSee(__('monthly-goals.goal_progress', [
                'current' => '1',
                'target' => '12',
                'status' => __('monthly-goals.status_keep_going'),
            ]), false);
    }

    public function test_profile_links_to_monthly_goals_with_live_chip(): void
    {
        $this->withoutVite();
        $this->travelTo(CarbonImmutable::parse('2026-08-20 12:00:00'));
        $this->seed(BadgeSeeder::class);
        $this->seedWeek();

        $user = User::factory()->fullySetUp()->withStats([
            'current_streak' => 20,
        ])->create();

        Livewire::actingAs($user)
            ->test('pages::profile')
            ->assertSet('monthlyGoalsHit', 1)
            ->assertSet('monthlyGoalsTotal', 4)
            ->assertSeeHtml(route('monthly-goals'));
    }

    private function seedWeek(SchoolGrade $grade = SchoolGrade::First, int $weekdays = 2, int $perPack = 1): void
    {
        Game::factory()->create([
            'slug' => GameType::MultipleChoice,
            'user_id' => null,
        ]);

        foreach (SchoolSubject::ordered() as $subject) {
            for ($day = 1; $day <= $weekdays; $day++) {
                $item = WeekPlanItem::factory()->create([
                    'grade' => $grade,
                    'week_number' => 1,
                    'weekday' => $day,
                    'subject' => $subject,
                    'level' => $day,
                    'title' => $subject->value.'-d'.$day,
                    'questions_per_round' => $perPack,
                ]);

                $questions = Question::factory()->count($perPack)->create([
                    'subject' => $subject->favourite(),
                    'grade' => $grade->value,
                ]);

                $sync = [];

                foreach ($questions as $index => $question) {
                    $sync[$question->id] = ['sort_order' => $index];
                }

                $item->questions()->sync($sync);
            }
        }
    }
}
