<?php

namespace Tests\Feature;

use App\Enums\GameType;
use App\Enums\GameVisibility;
use App\Models\Game;
use App\Models\User;
use App\Repositories\GameRepository;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class GameVisibilityTest extends TestCase
{
    use RefreshDatabase;

    public function test_system_games_are_public_and_found_by_slug(): void
    {
        $game = Game::factory()->create([
            'slug' => GameType::MultipleChoice,
        ]);

        $found = app(GameRepository::class)->findBySlug(GameType::MultipleChoice);

        $this->assertNotNull($found);
        $this->assertTrue($found->is($game));
        $this->assertTrue($found->isSystem());
        $this->assertTrue($found->isPublic());
    }

    public function test_parent_games_default_to_private_and_are_hidden_from_slug_catalog(): void
    {
        Game::factory()->create([
            'slug' => GameType::MultipleChoice,
        ]);

        $parent = User::factory()->create();

        $private = app(GameRepository::class)->createForParent($parent, [
            'title' => 'სახლის ვიქტორინა',
            'slug' => GameType::MultipleChoice,
            'format' => GameType::MultipleChoice->format(),
            'lives' => 3,
            'questions_per_round' => 5,
            'xp_per_correct' => 8,
            'is_active' => true,
        ]);

        $this->assertSame(GameVisibility::Private, $private->visibility);
        $this->assertTrue($private->isOwnedBy($parent));
        $this->assertFalse($private->isAccessibleTo(User::factory()->create()));
        $this->assertTrue($private->isAccessibleTo($parent));

        $catalog = app(GameRepository::class)->findBySlug(GameType::MultipleChoice);

        $this->assertNotNull($catalog);
        $this->assertFalse($catalog->is($private));
        $this->assertNull($catalog->user_id);
    }

    public function test_list_for_user_includes_public_games_and_own_private_games_only(): void
    {
        $public = Game::factory()->create();
        $owner = User::factory()->create();
        $other = User::factory()->create();
        $mine = Game::factory()->forUser($owner)->create([
            'slug' => GameType::Counting,
            'format' => GameType::Counting->format(),
        ]);
        Game::factory()->forUser($other)->create([
            'slug' => GameType::TapCorrect,
            'format' => GameType::TapCorrect->format(),
        ]);

        $listed = app(GameRepository::class)->listForUser($owner);

        $this->assertTrue($listed->contains(fn (Game $game): bool => $game->is($public)));
        $this->assertTrue($listed->contains(fn (Game $game): bool => $game->is($mine)));
        $this->assertCount(2, $listed);
    }
}
