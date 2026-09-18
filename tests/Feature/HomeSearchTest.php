<?php

namespace Tests\Feature;

use App\Models\User;
use App\Services\SearchService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class HomeSearchTest extends TestCase
{
    use RefreshDatabase;

    public function test_every_search_destination_points_at_a_real_route(): void
    {
        $catalog = app(SearchService::class)->homeCatalog([]);

        $this->assertNotEmpty($catalog);

        foreach ($catalog as $entry) {
            $this->assertStringNotContainsString('.html', $entry['href']);
            $this->assertStringStartsWith(url('/'), $entry['href']);
            $this->assertNotSame('', $entry['name']);
            $this->assertNotSame('', $entry['keys']);
        }
    }

    public function test_the_catalog_opens_the_next_pack_for_each_subject(): void
    {
        $catalog = app(SearchService::class)->homeCatalog([
            [
                'id' => 42,
                'subject' => 'მათემატიკა',
                'title' => 'რიცხვები 1–10',
                'subtitle' => '5 კითხვა',
                'completed' => false,
                'playable' => true,
                'emoji' => '➗',
                'tile' => 'tile-violet',
                'inkClass' => 'text-violet-ink',
            ],
        ]);

        $this->assertSame('მათემატიკა', $catalog[0]['name']);
        $this->assertSame(route('game-multiple-choice', ['item' => 42]), $catalog[0]['href']);
    }

    public function test_a_completed_subject_falls_back_to_the_daily_mission(): void
    {
        $catalog = app(SearchService::class)->homeCatalog([
            [
                'id' => 42,
                'subject' => 'ქართული',
                'title' => 'ასოები',
                'subtitle' => 'დასრულებულია',
                'completed' => true,
                'playable' => false,
                'emoji' => '🔤',
                'tile' => 'tile-sun',
                'inkClass' => 'text-sun-ink',
            ],
        ]);

        $this->assertSame(route('daily-mission'), $catalog[0]['href']);
    }

    public function test_home_renders_the_search_index_and_no_invented_data(): void
    {
        $this->withoutVite();

        $user = User::factory()->fullySetUp()->create();

        Livewire::actingAs($user)
            ->test('pages::home')
            ->assertSeeHtml('id="searchIndex"')
            ->assertSeeHtml('id="searchOverlay"')
            ->assertSee(__('home.search_to.library.name'), false)
            // Hardcoded unread count, invented notifications and the "ლუნა" parent tip
            // all left with docs/tasks/T02-dead-links-sweep.md.
            ->assertDontSeeHtml('id="bellBadge"')
            ->assertDontSeeHtml('id="notifSheet"')
            ->assertDontSee(__('home.parent_tip_text'), false)
            ->assertDontSee(__('home.leo_finished_math'), false);
    }
}
