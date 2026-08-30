<?php

namespace Tests\Feature;

use App\Enums\GameType;
use App\Enums\League;
use App\Enums\PlanProgressStatus;
use App\Enums\SchoolGrade;
use App\Enums\SchoolSubject;
use App\Models\Game;
use App\Models\Question;
use App\Models\User;
use App\Models\UserPlanProgress;
use App\Models\WeekPlanItem;
use App\Repositories\UserStatRepository;
use Carbon\CarbonImmutable;
use Database\Seeders\BadgeSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class ProfileTest extends TestCase
{
    use RefreshDatabase;

    public function test_profile_shows_live_hero_stats_and_week_activity(): void
    {
        $this->withoutVite();
        $this->travelTo(CarbonImmutable::parse('2026-08-20 12:00:00'));
        $this->seed(BadgeSeeder::class);
        $this->seedWeek();

        $user = User::factory()->fullySetUp()->withStats([
            'xp' => 1240,
            'current_streak' => 7,
            'longest_streak' => 12,
            'league' => League::Gold,
        ])->create([
            'name' => 'ნინო',
            'age' => 6,
            'grade' => SchoolGrade::First,
        ]);

        $days = app(UserStatRepository::class);

        foreach (['2026-08-17' => 100, '2026-08-18' => 200, '2026-08-19' => 300, '2026-08-20' => 340] as $date => $xp) {
            $days->addDayXp($user, $date, $xp);
        }

        $georgian = WeekPlanItem::query()
            ->where('grade', SchoolGrade::First)
            ->where('subject', SchoolSubject::Georgian)
            ->where('weekday', 1)
            ->firstOrFail();

        UserPlanProgress::query()->create([
            'user_id' => $user->id,
            'week_plan_item_id' => $georgian->id,
            'status' => PlanProgressStatus::Completed,
            'correct_count' => 1,
            'completed_at' => CarbonImmutable::parse('2026-08-20 10:00:00'),
        ]);

        Livewire::actingAs($user)
            ->test('pages::profile')
            ->assertSet('displayName', 'ნინო')
            ->assertSet('xp', 1240)
            ->assertSet('streak', 7)
            ->assertSet('leagueLabel', League::Gold->label())
            ->assertSet('weekActiveDays', 4)
            ->assertSet('weekXp', 940)
            ->assertSet('weekLessons', 1)
            ->assertSet('level', 5)
            ->assertSet('mastery.0.label', SchoolSubject::Georgian->label())
            ->assertSet('mastery.0.percent', 50)
            ->assertSet('mastery.1.percent', 0)
            ->assertSet('mastery.2.percent', 0)
            ->assertSee('ნინო', false)
            ->assertSee(SchoolSubject::Math->label(), false)
            ->assertSee(SchoolSubject::History->label(), false)
            ->assertDontSee('Luna Parker', false);
    }

    public function test_new_profile_starts_empty(): void
    {
        $this->withoutVite();
        $this->seed(BadgeSeeder::class);
        $this->seedWeek();

        $user = User::factory()->fullySetUp()->withStats()->create([
            'name' => 'გიორგი',
        ]);

        Livewire::actingAs($user)
            ->test('pages::profile')
            ->assertSet('displayName', 'გიორგი')
            ->assertSet('xp', 0)
            ->assertSet('streak', 0)
            ->assertSet('weekXp', 0)
            ->assertSet('weekLessons', 0)
            ->assertSet('weekActiveDays', 0)
            ->assertSet('mastery.0.percent', 0)
            ->assertSee(__('profile.no_recent_achievements'), false);
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
