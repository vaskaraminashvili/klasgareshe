<?php

namespace Tests\Feature;

use App\Enums\GameType;
use App\Enums\SchoolSubject;
use App\Models\Game;
use App\Models\Question;
use App\Models\User;
use App\Models\UserStat;
use App\Models\WeekPlanItem;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class QuickQuizTest extends TestCase
{
    use RefreshDatabase;

    public function test_guests_are_redirected_from_the_quiz_to_login(): void
    {
        $this->get(route('game-multiple-choice'))
            ->assertRedirect(route('user-login'));
    }

    public function test_unfinished_kids_cannot_open_the_quiz(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)
            ->get(route('game-multiple-choice'))
            ->assertRedirect(route('onboarding-age'));
    }

    public function test_home_links_to_the_quiz(): void
    {
        $this->withoutVite();
        $item = $this->seedQuiz(1);

        $user = User::factory()->fullySetUp()->withStats()->create();

        Livewire::actingAs($user)
            ->test('pages::home')
            ->assertSee(route('game-multiple-choice', ['item' => $item->id]), false);
    }

    public function test_quiz_loads_choice_questions_from_the_pack_without_leaking_answers(): void
    {
        $this->withoutVite();
        $item = $this->seedQuiz(1);

        $user = User::factory()->fullySetUp()->withStats()->create();

        $component = Livewire::actingAs($user)
            ->test('pages::game-multiple-choice', ['item' => $item->id])
            ->assertSet('lives', 3)
            ->assertDontSee('data-ans="correct"', false);

        $ids = $component->get('questionIds');

        $this->assertNotEmpty($ids);
        $this->assertContains($component->get('prompt'), Question::query()->pluck('prompt')->all());
        $this->assertNull($component->get('correctKey'));
    }

    public function test_a_wrong_pick_costs_a_life_and_then_reveals_the_answer(): void
    {
        $this->withoutVite();
        $item = $this->seedQuiz(1);

        $user = User::factory()->fullySetUp()->withStats()->create();

        $component = Livewire::actingAs($user)
            ->test('pages::game-multiple-choice', ['item' => $item->id]);

        $question = Question::query()->findOrFail($component->get('questionIds')[0]);
        $wrong = $question->correctKey() === 'A' ? 'C' : 'A';

        $component->call('pick', $wrong)
            ->assertSet('answered', true)
            ->assertSet('lives', 2)
            ->assertSet('correctCount', 0)
            ->assertSet('correctKey', $question->correctKey())
            ->assertSee('wrong', false)
            ->assertSee('correct', false);
    }

    public function test_finishing_the_quiz_awards_xp_for_correct_answers(): void
    {
        $this->withoutVite();
        $item = $this->seedQuiz(1);

        $user = User::factory()->fullySetUp()->withStats()->create();

        $component = Livewire::actingAs($user)
            ->test('pages::game-multiple-choice', ['item' => $item->id]);

        $question = Question::query()->findOrFail($component->get('questionIds')[0]);

        $component->call('pick', $question->correctKey())
            ->assertSet('correctCount', 1)
            ->assertSet('lives', 3)
            ->call('next')
            ->assertRedirect(route('home'));

        $this->assertSame(8, UserStat::query()->where('user_id', $user->id)->first()?->xp);
        $this->assertSame(1, UserStat::query()->where('user_id', $user->id)->first()?->current_streak);
    }

    private function seedQuiz(int $count = 1): WeekPlanItem
    {
        Game::factory()->create([
            'slug' => GameType::MultipleChoice,
            'user_id' => null,
        ]);

        $item = WeekPlanItem::factory()->create([
            'subject' => SchoolSubject::Georgian,
            'weekday' => 1,
            'level' => 1,
            'questions_per_round' => $count,
        ]);

        $questions = Question::factory()->count($count)->create();

        $sync = [];

        foreach ($questions as $index => $question) {
            $sync[$question->id] = ['sort_order' => $index];
        }

        $item->questions()->sync($sync);

        return $item;
    }
}
