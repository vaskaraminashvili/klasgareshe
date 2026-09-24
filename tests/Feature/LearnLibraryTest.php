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
use App\Services\WeekPlanService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class LearnLibraryTest extends TestCase
{
    use RefreshDatabase;

    public function test_guests_are_redirected_to_login(): void
    {
        $this->get(route('learn-categories'))
            ->assertRedirect(route('user-login'));
    }

    public function test_unfinished_users_cannot_open_the_learn_library(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)
            ->get(route('learn-categories'))
            ->assertRedirect(route('onboarding-age'));
    }

    public function test_the_library_shows_three_school_subjects_and_no_kidzio_extras(): void
    {
        $this->withoutVite();
        $this->seedWeek();

        $user = User::factory()->fullySetUp()->withStats()->create();
        $first = app(WeekPlanService::class)->firstIncomplete($user);

        $this->assertNotNull($first);

        $this->actingAs($user)
            ->get(route('learn-categories'))
            ->assertOk()
            ->assertSee(__('learn.library'), false)
            ->assertSee(__('subjects.georgian'), false)
            ->assertSee(__('subjects.math'), false)
            ->assertSee(__('subjects.history'), false)
            ->assertSee($first->title, false)
            ->assertSee(route('section-list', ['subject' => 'math']), false)
            ->assertDontSee(__('learn.animals'), false)
            ->assertDontSee(__('learn.opposites'), false)
            ->assertDontSee(__('learn.alphabet'), false)
            ->assertDontSee(__('learn.space_planets'), false)
            ->assertDontSee(__('learn.knowledge'), false)
            ->assertDontSee(__('learn.played_n_today', ['count' => 240]), false);

        Livewire::actingAs($user)
            ->test('pages::learn-categories')
            ->assertSet('subjectCount', 3)
            ->assertSet('gamesCount', 3)
            ->assertSeeHtml('id="searchOverlay"')
            ->assertSeeHtml('id="filterOverlay"');
    }

    public function test_subject_week_pack_opens_the_player_and_locks_the_next_pack(): void
    {
        $this->withoutVite();
        $this->seedWeek();

        $user = User::factory()->fullySetUp()->withStats()->create();
        $week = app(WeekPlanService::class);
        $firstMath = $week->nextIncompleteForSubject($user, SchoolSubject::Math);
        $this->assertNotNull($firstMath);

        $secondMath = WeekPlanItem::query()
            ->where('grade', SchoolGrade::First)
            ->where('subject', SchoolSubject::Math)
            ->where('weekday', 2)
            ->firstOrFail();

        Livewire::actingAs($user)
            ->test('pages::section-list', ['subject' => SchoolSubject::Math->value])
            ->assertOk()
            ->assertSee($firstMath->title, false)
            ->assertSee($secondMath->title, false)
            ->assertSee(route('lesson-details', ['item' => $firstMath->id]), false)
            ->assertSee(route('lesson-locked', ['item' => $secondMath->id]), false)
            ->assertSee($week->playUrl($firstMath->id), false);

        Livewire::actingAs($user)
            ->test('pages::lesson-details', ['item' => $firstMath->id])
            ->assertOk()
            ->assertSee($firstMath->title, false)
            ->assertSee($week->playUrl($firstMath->id), false);

        Livewire::actingAs($user)
            ->test('pages::lesson-locked', ['item' => $secondMath->id])
            ->assertOk()
            ->assertSee($secondMath->title, false)
            ->assertSee($firstMath->title, false)
            ->assertDontSee('500', false);
    }

    public function test_the_heart_toggles_the_subject_on_favourite_subjects(): void
    {
        $this->withoutVite();
        $this->seedWeek();

        $user = User::factory()->fullySetUp()->withStats()->create();

        Livewire::actingAs($user)
            ->test('pages::section-list', ['subject' => SchoolSubject::Math->value])
            ->assertSet('favourite', true)
            ->call('toggleFavourite')
            ->assertSet('favourite', false);

        $user->refresh();

        $this->assertNotContains(SchoolSubject::Math->value, $user->favourite_subjects ?? []);
    }

    public function test_unknown_subjects_are_not_found(): void
    {
        $user = User::factory()->fullySetUp()->create();

        $this->actingAs($user)
            ->get(route('section-list', ['subject' => 'animals']))
            ->assertNotFound();
    }

    public function test_continue_matches_the_home_next_pack(): void
    {
        $this->withoutVite();
        $this->seedWeek();

        $user = User::factory()->fullySetUp()->withStats()->create();
        $week = app(WeekPlanService::class);
        $home = $week->firstIncomplete($user);
        $math = $week->nextIncompleteForSubject($user, SchoolSubject::Math);

        $this->assertNotNull($home);
        $this->assertNotNull($math);

        Livewire::actingAs($user)
            ->test('pages::learn-categories')
            ->assertSee($home->title, false);

        Livewire::actingAs($user)
            ->test('pages::section-list', ['subject' => SchoolSubject::Math->value])
            ->assertSee($math->title, false)
            ->assertSee($week->playUrl($math->id), false);
    }

    public function test_completing_a_pack_unlocks_the_next_lesson_details(): void
    {
        $this->withoutVite();
        $this->seedWeek();

        $user = User::factory()->fullySetUp()->withStats()->create();
        $week = app(WeekPlanService::class);
        $firstMath = $week->nextIncompleteForSubject($user, SchoolSubject::Math);
        $this->assertNotNull($firstMath);

        $secondMath = WeekPlanItem::query()
            ->where('grade', SchoolGrade::First)
            ->where('subject', SchoolSubject::Math)
            ->where('weekday', 2)
            ->firstOrFail();

        UserPlanProgress::query()->create([
            'user_id' => $user->id,
            'week_plan_item_id' => $firstMath->id,
            'status' => PlanProgressStatus::Completed,
            'correct_count' => 1,
            'completed_at' => now(),
        ]);

        Livewire::actingAs($user)
            ->test('pages::lesson-locked', ['item' => $secondMath->id])
            ->assertRedirect(route('lesson-details', ['item' => $secondMath->id]));

        Livewire::actingAs($user)
            ->test('pages::lesson-details', ['item' => $secondMath->id])
            ->assertOk()
            ->assertSee($secondMath->title, false);
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
                    'title' => $subject->value.'-w1-d'.$day,
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
