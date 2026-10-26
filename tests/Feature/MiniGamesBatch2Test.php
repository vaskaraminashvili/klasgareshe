<?php

namespace Tests\Feature;

use App\Enums\FavouriteSubject;
use App\Enums\GameType;
use App\Enums\PlayDifficulty;
use App\Enums\QuestionFormat;
use App\Enums\SchoolSubject;
use App\Models\Game;
use App\Models\Question;
use App\Models\User;
use App\Models\WeekPlanItem;
use App\Repositories\PackPlayRepository;
use App\Services\GamePlayService;
use App\Services\WeekPlanService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Route;
use Livewire\Livewire;
use Tests\TestCase;

class MiniGamesBatch2Test extends TestCase
{
    use RefreshDatabase;

    public function test_class_four_packs_have_ten_questions_and_mixed_games(): void
    {
        $this->seed(WeekPlanSeeder::class);

        $items = WeekPlanItem::query()->where('week_number', 1)->get();

        $this->assertCount(21, $items);

        foreach ($items as $item) {
            $this->assertSame(10, $item->questions()->count());
        }

        $this->assertGreaterThanOrEqual(8, $items->map(fn (WeekPlanItem $item): string => $item->game_slug->value)->unique()->count());

        $this->assertFalse(Route::has('game-trace-letter'));
        $this->assertFalse(Route::has('game-match-animal'));
        $this->assertFalse(Route::has('game-guess-animal'));
        $this->assertFalse(Route::has('game-body-parts'));
        $this->assertFalse(Route::has('game-where-live'));
        $this->assertSame('game-multiple-choice', GameType::GuessAnimal->playerRoute());
    }

    public function test_difficulty_picks_questions_and_scales_pack_xp(): void
    {
        $this->ensureGame(GameType::FillLetter);

        $item = WeekPlanItem::factory()->create([
            'subject' => SchoolSubject::Georgian,
            'game_slug' => GameType::FillLetter,
            'questions_per_round' => 1,
            'weekday' => 4,
        ]);

        $easy = Question::factory()->create([
            'prompt' => 'მარტივი კითხვა',
            'difficulty' => PlayDifficulty::Easy,
            'source' => GameType::FillLetter,
            'subject' => FavouriteSubject::Georgian,
        ]);
        $hard = Question::factory()->create([
            'prompt' => 'რთული კითხვა',
            'difficulty' => PlayDifficulty::Hard,
            'source' => GameType::FillLetter,
            'subject' => FavouriteSubject::Georgian,
        ]);
        $item->questions()->sync([
            $easy->id => ['sort_order' => 0],
            $hard->id => ['sort_order' => 1],
        ]);

        $week = app(WeekPlanService::class);
        $easyUser = User::factory()->fullySetUp()->withStats()->create([
            'play_difficulty' => PlayDifficulty::Easy,
        ]);
        $hardUser = User::factory()->fullySetUp()->withStats()->create([
            'play_difficulty' => PlayDifficulty::Hard,
        ]);

        $this->assertSame([$easy->id], $week->questionIds($item, $easyUser));
        $this->assertSame([$hard->id], $week->questionIds($item, $hardUser));

        $play = app(GamePlayService::class);

        $this->assertSame(6, $play->award($easyUser, GameType::FillLetter, 1, $item->id));
        $this->assertSame(12, $play->award($hardUser, GameType::FillLetter, 1));
    }

    public function test_fill_letter_scores_and_compares_with_yesterday(): void
    {
        $this->withoutVite();
        $item = $this->seedPack(GameType::FillLetter, SchoolSubject::Georgian, $this->fillQuestion());
        $user = User::factory()->fullySetUp()->withStats()->create();

        $this->travelTo(now()->subDay());
        app(PackPlayRepository::class)->record($user, $item->id, GameType::FillLetter->value, 0);
        $this->travelBack();

        Livewire::actingAs($user)
            ->test('pages::game-fill-letter', ['item' => $item->id])
            ->assertSet('playMode', 'fill')
            ->call('chooseLetter', 'A')
            ->call('checkLetter')
            ->assertSet('correctCount', 1)
            ->call('next')
            ->assertSet('showResult', true)
            ->assertSet('beatYesterday', true)
            ->assertSee(__('quiz.beat_yesterday'), false)
            ->call('continueFromResult')
            ->assertRedirect(route('home'));
    }

    public function test_spell_word_grades_the_typed_word(): void
    {
        $this->withoutVite();
        $item = $this->seedPack(GameType::SpellWord, SchoolSubject::Georgian, $this->spellQuestion());
        $user = User::factory()->fullySetUp()->withStats()->create();

        Livewire::actingAs($user)
            ->test('pages::game-spell-word', ['item' => $item->id])
            ->call('typeLetter', 'კ')
            ->call('typeLetter', 'ა')
            ->call('typeLetter', 'ტ')
            ->call('typeLetter', 'ა')
            ->call('checkSpelling')
            ->assertSet('correctCount', 1)
            ->call('next')
            ->assertSet('showResult', true)
            ->assertSee(__('quiz.no_yesterday'), false);
    }

    public function test_word_search_finds_a_placed_word(): void
    {
        $this->withoutVite();
        $item = $this->seedPack(GameType::WordSearch, SchoolSubject::Georgian, $this->searchQuestion());
        $user = User::factory()->fullySetUp()->withStats()->create();

        Livewire::actingAs($user)
            ->test('pages::game-word-search', ['item' => $item->id])
            ->assertSee('კატა', false)
            ->call('selectCell', 0, 0)
            ->call('selectCell', 0, 3)
            ->assertSet('correctCount', 1)
            ->call('finishSearch')
            ->assertSet('showResult', true);
    }

    public function test_settings_saves_the_difficulty_row(): void
    {
        $this->withoutVite();
        $user = User::factory()->fullySetUp()->withStats()->create();

        Livewire::actingAs($user)
            ->test('pages::settings')
            ->assertSee(__('settings.difficulty'), false)
            ->call('selectDifficulty', 'hard')
            ->assertSet('playDifficulty', 'hard');

        $this->assertSame(PlayDifficulty::Hard, $user->fresh()?->play_difficulty);
    }

    public function test_home_links_the_word_search_pack(): void
    {
        $this->withoutVite();
        $item = $this->seedPack(GameType::WordSearch, SchoolSubject::Georgian, $this->searchQuestion());
        $user = User::factory()->fullySetUp()->withStats()->create();

        Livewire::actingAs($user)
            ->test('pages::home')
            ->assertSee(__('home.word_search'), false)
            ->assertSee(route('game-word-search', ['item' => $item->id]), false);
    }

    /**
     * @param  array<string, mixed>  $question
     */
    private function seedPack(GameType $type, SchoolSubject $subject, array $question): WeekPlanItem
    {
        $this->ensureGame($type);

        $item = WeekPlanItem::factory()->create([
            'subject' => $subject,
            'weekday' => 4,
            'level' => 4,
            'game_slug' => $type,
            'questions_per_round' => 1,
        ]);

        $row = Question::factory()->create($question);
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
    private function fillQuestion(): array
    {
        return [
            'format' => QuestionFormat::Choice,
            'source' => GameType::FillLetter,
            'subject' => FavouriteSubject::Georgian,
            'grade' => 4,
            'locale' => 'ka',
            'prompt' => 'კ_ტა',
            'media' => ['emoji' => '🐱', 'tile' => 'tile-coral'],
            'payload' => [
                'choices' => [
                    ['key' => 'A', 'label' => 'ა', 'emoji' => ''],
                    ['key' => 'B', 'label' => 'ო', 'emoji' => ''],
                    ['key' => 'C', 'label' => 'ე', 'emoji' => ''],
                    ['key' => 'D', 'label' => 'ი', 'emoji' => ''],
                ],
            ],
            'answer' => ['key' => 'A'],
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function spellQuestion(): array
    {
        return [
            'format' => QuestionFormat::Spell,
            'source' => GameType::SpellWord,
            'subject' => FavouriteSubject::Georgian,
            'grade' => 4,
            'locale' => 'ka',
            'prompt' => 'დაწერე ცხოველის სახელი',
            'media' => ['emoji' => '🐱', 'tile' => 'tile-coral'],
            'payload' => [
                'choices' => [
                    ['key' => 'A', 'label' => 'კატა', 'emoji' => ''],
                    ['key' => 'B', 'label' => 'მზე', 'emoji' => ''],
                ],
                'keyboard' => ['კ', 'ა', 'ტ', 'მ', 'ზ'],
                'slots' => 4,
            ],
            'answer' => ['key' => 'A'],
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function searchQuestion(): array
    {
        return [
            'format' => QuestionFormat::Grid,
            'source' => GameType::WordSearch,
            'subject' => FavouriteSubject::Georgian,
            'grade' => 4,
            'locale' => 'ka',
            'prompt' => 'კატა',
            'media' => ['emoji' => '🐱', 'tile' => 'tile-pink'],
            'payload' => [
                'choices' => [
                    ['key' => 'A', 'label' => 'კატა', 'emoji' => ''],
                    ['key' => 'B', 'label' => 'მზე', 'emoji' => ''],
                ],
            ],
            'answer' => ['key' => 'A'],
        ];
    }
}
