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
            ->assertSee('georgian-w1-d1', false)
            ->assertSee('math-w1-d1', false)
            ->assertSee('history-w1-d1', false);
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

    public function test_active_week_stays_on_week_one_while_packs_remain(): void
    {
        $this->seedCurriculumWeeks();

        $user = User::factory()->fullySetUp()->withStats()->create();
        $service = app(WeekPlanService::class);

        $this->completeWeekPacks($user, 1, weekdays: 1);

        $this->assertSame(1, $service->activeWeekNumber($user));

        $next = $service->firstIncomplete($user);

        $this->assertNotNull($next);
        $this->assertSame(1, $next->week_number);
        $this->assertSame(2, $next->weekday);
    }

    public function test_completing_all_week_one_packs_advances_to_week_two(): void
    {
        $this->withoutVite();
        $this->seedCurriculumWeeks();

        $user = User::factory()->fullySetUp()->withStats()->create();
        $service = app(WeekPlanService::class);

        $this->completeWeekPacks($user, 1);

        $this->assertSame(2, $service->activeWeekNumber($user));

        $next = $service->firstIncomplete($user);

        $this->assertNotNull($next);
        $this->assertSame(2, $next->week_number);
        $this->assertSame(1, $next->weekday);
        $this->assertSame(SchoolSubject::Georgian, $next->subject);

        Livewire::actingAs($user)
            ->test('pages::home')
            ->assertSee('georgian-w2-d1', false)
            ->assertSee('math-w2-d1', false)
            ->assertSee('history-w2-d1', false)
            ->assertDontSee('georgian-w1-d1', false);

        Livewire::actingAs($user)
            ->test('pages::daily-mission')
            ->assertSee('georgian-w2-d1', false)
            ->assertSee('math-w2-d1', false)
            ->assertDontSee('georgian-w1-d1', false);
    }

    public function test_subject_mastery_uses_active_curriculum_week(): void
    {
        $this->seedCurriculumWeeks(weekdays: 2);

        $user = User::factory()->fullySetUp()->withStats()->create();
        $service = app(WeekPlanService::class);

        $this->completeWeekPacks($user, 1, weekdays: 2);

        $rows = $service->subjectMastery($user);
        $georgian = collect($rows)->first(
            fn ($row) => $row->subject === SchoolSubject::Georgian,
        );

        $this->assertNotNull($georgian);
        $this->assertSame(0, $georgian->percent);
        $this->assertSame(0, $georgian->done);
        $this->assertSame(2, $georgian->total);
        $this->assertNotNull($georgian->nextItemId);

        $next = WeekPlanItem::query()->findOrFail($georgian->nextItemId);
        $this->assertSame(2, $next->week_number);
    }

    public function test_all_seeded_weeks_complete_stays_on_last_week(): void
    {
        $this->seedCurriculumWeeks(weekdays: 1);

        $user = User::factory()->fullySetUp()->withStats()->create();
        $service = app(WeekPlanService::class);

        $this->completeWeekPacks($user, 1, weekdays: 1);
        $this->completeWeekPacks($user, 2, weekdays: 1);

        $this->assertSame(2, $service->activeWeekNumber($user));
        $this->assertNull($service->firstIncomplete($user));

        foreach ($service->homeTasks($user) as $task) {
            $this->assertTrue($task->completed);
        }
    }

    private function seedWeek(SchoolGrade $grade = SchoolGrade::First, int $weekdays = 2, int $perPack = 1): void
    {
        $this->seedCurriculumWeeks($grade, weekNumbers: [1], weekdays: $weekdays, perPack: $perPack);
    }

    /**
     * @param  list<int>  $weekNumbers
     */
    private function seedCurriculumWeeks(
        SchoolGrade $grade = SchoolGrade::First,
        array $weekNumbers = [1, 2],
        int $weekdays = 2,
        int $perPack = 1,
    ): void {
        Game::factory()->create([
            'slug' => GameType::MultipleChoice,
            'user_id' => null,
        ]);

        foreach ($weekNumbers as $weekNumber) {
            foreach (SchoolSubject::ordered() as $subject) {
                for ($day = 1; $day <= $weekdays; $day++) {
                    $item = WeekPlanItem::factory()->create([
                        'grade' => $grade,
                        'week_number' => $weekNumber,
                        'weekday' => $day,
                        'subject' => $subject,
                        'level' => (($weekNumber - 1) * 7) + $day,
                        'title' => $subject->value.'-w'.$weekNumber.'-d'.$day,
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

    private function completeWeekPacks(User $user, int $weekNumber, int $weekdays = 2): void
    {
        $items = WeekPlanItem::query()
            ->where('grade', $user->grade ?? SchoolGrade::First)
            ->where('week_number', $weekNumber)
            ->where('weekday', '<=', $weekdays)
            ->get();

        foreach ($items as $item) {
            UserPlanProgress::query()->create([
                'user_id' => $user->id,
                'week_plan_item_id' => $item->id,
                'status' => PlanProgressStatus::Completed,
                'correct_count' => $item->questions_per_round,
                'completed_at' => now(),
            ]);
        }
    }
}
