<?php

namespace Tests\Feature;

use App\Enums\GameType;
use App\Enums\SchoolGrade;
use App\Enums\SchoolSubject;
use App\Models\Game;
use App\Models\Question;
use App\Models\User;
use App\Models\UserPlanProgress;
use App\Models\UserStat;
use App\Models\WeekPlanItem;
use App\Services\WeekPlanService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class WeekPlanTest extends TestCase
{
    use RefreshDatabase;

    public function test_home_shows_zero_of_three_and_next_pack_titles(): void
    {
        $this->withoutVite();
        $this->seedWeek();

        $user = User::factory()->fullySetUp()->withStats()->create();

        Livewire::actingAs($user)
            ->test('pages::home')
            ->assertSee(__('home.progress_n_of', ['done' => 0, 'total' => 3]), false)
            ->assertDontSee(__('home.progress_2_of_3'), false)
            ->assertSee('georgian-d1', false)
            ->assertSee('math-d1', false)
            ->assertSee('history-d1', false);
    }

    public function test_quiz_without_item_resolves_to_the_first_monday_pack(): void
    {
        $this->withoutVite();
        $this->seedWeek();

        $user = User::factory()->fullySetUp()->withStats()->create();
        $first = app(WeekPlanService::class)->firstIncomplete($user);

        $this->assertNotNull($first);
        $this->assertSame(1, $first->weekday);
        $this->assertSame(SchoolSubject::Georgian, $first->subject);

        Livewire::actingAs($user)
            ->test('pages::game-multiple-choice')
            ->assertRedirect(route('game-multiple-choice', ['item' => $first->id]));
    }

    public function test_finishing_a_pack_awards_xp_and_unlocks_the_next_weekday(): void
    {
        $this->withoutVite();
        $this->seedWeek();

        $user = User::factory()->fullySetUp()->withStats()->create();
        $service = app(WeekPlanService::class);
        $mondayMath = $service->nextIncompleteForSubject($user, SchoolSubject::Math);

        $this->assertNotNull($mondayMath);
        $this->assertSame(1, $mondayMath->weekday);

        $component = Livewire::actingAs($user)
            ->test('pages::game-multiple-choice', ['item' => $mondayMath->id]);

        $question = Question::query()->findOrFail($component->get('questionIds')[0]);

        $component->call('pick', $question->correctKey())
            ->call('next')
            ->assertRedirect(route('home'));

        $this->assertSame(8, UserStat::query()->where('user_id', $user->id)->first()?->xp);
        $this->assertTrue(
            UserPlanProgress::query()
                ->where('user_id', $user->id)
                ->where('week_plan_item_id', $mondayMath->id)
                ->exists(),
        );

        $tuesdayMath = $service->nextIncompleteForSubject($user, SchoolSubject::Math);

        $this->assertNotNull($tuesdayMath);
        $this->assertSame(2, $tuesdayMath->weekday);
        $this->assertSame(SchoolSubject::Math, $tuesdayMath->subject);
    }

    public function test_grade_two_cannot_play_a_grade_one_pack(): void
    {
        $this->withoutVite();
        $this->seedWeek(SchoolGrade::First);
        $this->seedWeek(SchoolGrade::Second);

        $user = User::factory()->fullySetUp()->withStats()->create([
            'grade' => SchoolGrade::Second,
        ]);

        $gradeOne = WeekPlanItem::query()
            ->where('grade', SchoolGrade::First)
            ->where('subject', SchoolSubject::Math)
            ->where('weekday', 1)
            ->firstOrFail();

        Livewire::actingAs($user)
            ->test('pages::game-multiple-choice', ['item' => $gradeOne->id])
            ->assertRedirect(route('home'));
    }

    public function test_incomplete_monday_pack_is_still_next_later_in_the_week(): void
    {
        $this->seedWeek();

        $user = User::factory()->fullySetUp()->withStats()->create();
        $service = app(WeekPlanService::class);

        $this->travelTo(now()->startOfWeek()->addDays(2)->setTime(12, 0));

        $next = $service->nextIncompleteForSubject($user, SchoolSubject::Georgian);

        $this->assertNotNull($next);
        $this->assertSame(1, $next->weekday);
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
