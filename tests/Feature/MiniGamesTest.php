<?php

namespace Tests\Feature;

use App\Enums\GameType;
use App\Enums\QuestionFormat;
use App\Enums\SchoolGrade;
use App\Enums\SchoolSubject;
use App\Models\Game;
use App\Models\Question;
use App\Models\User;
use App\Models\UserPlanProgress;
use App\Models\UserStat;
use App\Models\WeekPlanItem;
use App\Services\WeekPlanService;
use Database\Seeders\WeekPlanQuestionBank;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class MiniGamesTest extends TestCase
{
    use RefreshDatabase;

    public function test_week_one_class_one_mixes_three_formats(): void
    {
        $this->assertSame(
            GameType::MultipleChoice,
            WeekPlanQuestionBank::gameType(SchoolGrade::First, SchoolSubject::Georgian, 1, 1),
        );
        $this->assertSame(
            GameType::TapCorrect,
            WeekPlanQuestionBank::gameType(SchoolGrade::First, SchoolSubject::Math, 1, 1),
        );
        $this->assertSame(
            GameType::Counting,
            WeekPlanQuestionBank::gameType(SchoolGrade::First, SchoolSubject::Math, 3, 1),
        );
        $this->assertSame(
            GameType::MultipleChoice,
            WeekPlanQuestionBank::gameType(SchoolGrade::Second, SchoolSubject::Math, 3, 1),
        );
    }

    public function test_unbuilt_game_types_fall_back_to_the_quiz_player(): void
    {
        $this->assertSame('game-tap-correct', GameType::TapCorrect->playerRoute());
        $this->assertSame('game-counting', GameType::Counting->playerRoute());
        $this->assertSame('game-multiple-choice', GameType::WordSearch->playerRoute());
    }

    public function test_count_questions_read_payload_items_and_value_answers(): void
    {
        $question = Question::factory()->create($this->countQuestion());

        $this->assertSame('B', $question->correctKey());
        $this->assertSame(['🍎', '🍎', '🍎'], $question->countItems());
    }

    public function test_opening_the_wrong_player_redirects_to_the_pack_format(): void
    {
        $this->withoutVite();
        $item = $this->seedPack(GameType::TapCorrect, SchoolSubject::Math, 1);

        $user = User::factory()->fullySetUp()->withStats()->create();

        Livewire::actingAs($user)
            ->test('pages::game-multiple-choice', ['item' => $item->id])
            ->assertRedirect(route('game-tap-correct', ['item' => $item->id]));
    }

    public function test_bare_counting_url_opens_the_next_counting_pack(): void
    {
        $this->withoutVite();
        $this->seedPack(GameType::MultipleChoice, SchoolSubject::Georgian, 1);
        $counting = $this->seedPack(GameType::Counting, SchoolSubject::Math, 1);

        $user = User::factory()->fullySetUp()->withStats()->create();

        Livewire::actingAs($user)
            ->test('pages::game-counting')
            ->assertRedirect(route('game-counting', ['item' => $counting->id]));
    }

    public function test_tap_correct_plays_to_completion_and_records_a_play(): void
    {
        $this->withoutVite();
        $item = $this->seedPack(GameType::TapCorrect, SchoolSubject::Math, 1);

        $user = User::factory()->fullySetUp()->withStats()->create();

        $component = Livewire::actingAs($user)
            ->test('pages::game-tap-correct', ['item' => $item->id])
            ->assertSet('lives', 3)
            ->assertSee(__('quiz.tap_correct'), false)
            ->assertDontSee('data-ans="correct"', false);

        $question = Question::query()->findOrFail($component->get('questionIds')[0]);

        $component->call('pick', $question->correctKey())
            ->assertSet('answered', true)
            ->assertSet('correctCount', 1)
            ->call('next')
            ->assertRedirect(route('home'));

        $this->assertSame(8, UserStat::query()->where('user_id', $user->id)->first()?->xp);
        $this->assertTrue(
            UserPlanProgress::query()
                ->where('user_id', $user->id)
                ->where('week_plan_item_id', $item->id)
                ->exists(),
        );
    }

    public function test_counting_check_then_continue_awards_xp(): void
    {
        $this->withoutVite();
        $item = $this->seedPack(GameType::Counting, SchoolSubject::Math, 1, $this->countQuestion());

        $user = User::factory()->fullySetUp()->withStats()->create();

        $component = Livewire::actingAs($user)
            ->test('pages::game-counting', ['item' => $item->id])
            ->assertSet('countItems', ['🍎', '🍎', '🍎'])
            ->assertSee(__('quiz.math_exercise'), false)
            ->assertSee(__('quiz.check'), false);

        $question = Question::query()->findOrFail($component->get('questionIds')[0]);

        $component->call('selectCount', $question->correctKey())
            ->assertSet('hasCountInput', true)
            ->assertSet('stepper', 3)
            ->call('check')
            ->assertSet('answered', true)
            ->assertSet('correctCount', 1)
            ->assertSet('lives', 3)
            ->call('next')
            ->assertRedirect(route('home'));

        $this->assertSame(8, UserStat::query()->where('user_id', $user->id)->first()?->xp);
        $this->assertTrue(
            UserPlanProgress::query()
                ->where('user_id', $user->id)
                ->where('week_plan_item_id', $item->id)
                ->exists(),
        );
    }

    public function test_home_and_daily_mission_link_to_the_pack_player(): void
    {
        $this->withoutVite();
        $quiz = $this->seedPack(GameType::MultipleChoice, SchoolSubject::Georgian, 1);
        $tap = $this->seedPack(GameType::TapCorrect, SchoolSubject::Math, 1);
        $count = $this->seedPack(GameType::Counting, SchoolSubject::History, 1);

        $user = User::factory()->fullySetUp()->withStats()->create();
        $week = app(WeekPlanService::class);

        $this->assertSame(
            route('game-tap-correct', ['item' => $tap->id]),
            $week->playUrl($tap->id),
        );
        $this->assertSame(
            route('game-counting', ['item' => $count->id]),
            $week->playUrl($count->id),
        );

        Livewire::actingAs($user)
            ->test('pages::home')
            ->assertSee(route('game-multiple-choice', ['item' => $quiz->id]), false)
            ->assertSee(route('game-tap-correct', ['item' => $tap->id]), false)
            ->assertSee(route('game-counting', ['item' => $count->id]), false)
            ->assertSee(__('home.counting_fun'), false);

        Livewire::actingAs($user)
            ->test('pages::daily-mission')
            ->assertSee(route('game-tap-correct', ['item' => $tap->id]), false)
            ->assertSee(route('game-counting', ['item' => $count->id]), false);
    }

    /**
     * @param  array<string, mixed>|null  $question
     */
    private function seedPack(
        GameType $type,
        SchoolSubject $subject,
        int $weekday,
        ?array $question = null,
    ): WeekPlanItem {
        $this->ensureGame($type);

        $item = WeekPlanItem::factory()->create([
            'subject' => $subject,
            'weekday' => $weekday,
            'level' => $weekday,
            'game_slug' => $type,
            'questions_per_round' => 1,
        ]);

        $row = Question::factory()->create($question ?? [
            'format' => $type->format() === QuestionFormat::Count
                ? QuestionFormat::Count
                : QuestionFormat::Choice,
            'source' => $type,
            'subject' => $subject->favourite(),
        ]);

        $item->questions()->sync([$row->id => ['sort_order' => 0]]);

        return $item;
    }

    private function ensureGame(GameType $type): void
    {
        if (Game::query()->where('slug', $type)->whereNull('user_id')->exists()) {
            return;
        }

        Game::factory()->create([
            'slug' => $type,
            'format' => $type->format(),
            'user_id' => null,
            'lives' => 3,
            'questions_per_round' => 1,
            'xp_per_correct' => 8,
        ]);
    }

    /**
     * @return array<string, mixed>
     */
    private function countQuestion(): array
    {
        return [
            'format' => QuestionFormat::Count,
            'source' => GameType::Counting,
            'prompt' => 'დათვალე ვაშლები 🍎',
            'media' => ['emoji' => '🍎', 'tile' => 'tile-coral'],
            'payload' => [
                'item_emoji' => '🍎',
                'count' => 3,
                'choices' => [
                    ['key' => 'A', 'label' => '2', 'emoji' => ''],
                    ['key' => 'B', 'label' => '3', 'emoji' => ''],
                    ['key' => 'C', 'label' => '4', 'emoji' => ''],
                    ['key' => 'D', 'label' => '5', 'emoji' => ''],
                ],
            ],
            'answer' => ['value' => 3],
        ];
    }
}
