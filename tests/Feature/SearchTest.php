<?php

namespace Tests\Feature;

use App\Enums\SchoolGrade;
use App\Models\User;
use App\Models\WeekPlanItem;
use App\Services\SearchService;
use Database\Seeders\BadgeSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class SearchTest extends TestCase
{
    use RefreshDatabase;

    public function test_georgian_queries_match_partial_words_and_ignore_case(): void
    {
        $search = app(SearchService::class);

        $this->assertTrue($search->matches('მათემატიკა', 'მათ'));
        $this->assertTrue($search->matches('მათემატიკა კვირა 2', 'კვირ 2'));
        $this->assertFalse($search->matches('ისტორია', 'მათ'));
        $this->assertTrue($search->matches('XP პროგრესი', 'xp'));
        $this->assertSame('მათემატიკა', $search->normalize('  მათემატიკა  '));
    }

    public function test_the_index_lists_real_packs_weeks_and_public_badges(): void
    {
        $this->seed(BadgeSeeder::class);

        $user = User::factory()->fullySetUp()->create(['grade' => SchoolGrade::First]);
        WeekPlanItem::factory()->create([
            'grade' => SchoolGrade::First,
            'subject' => 'math',
            'week_number' => 2,
            'title' => 'რიცხვები 1–10',
        ]);

        $index = app(SearchService::class)->indexFor($user);
        $names = array_column($index, 'name');

        $this->assertContains('რიცხვები 1–10', $names);
        $this->assertContains(__('learn.subject_week', ['subject' => __('subjects.math'), 'week' => 2]), $names);
        $this->assertContains(__('badges.items.first-win.name'), $names);
        $this->assertNotContains(__('badges.items.secret-ace.name'), $names);

        $pack = null;

        foreach ($index as $entry) {
            if ($entry['name'] === 'რიცხვები 1–10') {
                $pack = $entry;
                break;
            }
        }

        $this->assertNotNull($pack);
        $this->assertSame('math', $pack['subject']);
        $this->assertSame(2, $pack['week']);
        $this->assertStringNotContainsString('.html', $pack['href']);
    }

    public function test_filters_narrow_the_index_by_subject_week_and_status(): void
    {
        $user = User::factory()->fullySetUp()->create(['grade' => SchoolGrade::First]);
        WeekPlanItem::factory()->create([
            'grade' => SchoolGrade::First,
            'subject' => 'math',
            'week_number' => 1,
            'title' => 'დათვლა',
        ]);
        WeekPlanItem::factory()->create([
            'grade' => SchoolGrade::First,
            'subject' => 'history',
            'week_number' => 2,
            'title' => 'დროშა',
        ]);

        $search = app(SearchService::class);
        $index = $search->indexFor($user);

        $math = $search->filter($index, '', 'math', null, null);
        $this->assertNotEmpty($math);
        foreach ($math as $hit) {
            $this->assertSame('math', $hit['subject']);
        }

        $weekTwo = $search->filter($index, 'დრო', null, 2, null);
        $this->assertSame(['დროშა'], array_column($weekTwo, 'name'));
    }

    public function test_recent_searches_are_per_user_and_popular_chips_follow_counts(): void
    {
        $one = User::factory()->fullySetUp()->create();
        $two = User::factory()->fullySetUp()->create();
        $search = app(SearchService::class);

        $search->record($one, 'მათემატიკა');
        $search->record($two, 'მათემატიკა');
        $search->record($one, 'ისტორია');

        $this->assertSame(['ისტორია', 'მათემატიკა'], $search->recent($one));
        $this->assertSame(['მათემატიკა'], $search->recent($two));
        $this->assertSame('მათემატიკა', $search->popular()[0]);
    }

    public function test_leaderboard_search_matches_nicknames_and_hides_opted_out_kids(): void
    {
        User::factory()->fullySetUp()->create([
            'nickname' => 'ნიკა123',
            'name' => 'ნიკა',
            'show_on_leaderboard' => true,
        ]);
        User::factory()->fullySetUp()->create([
            'nickname' => 'დამალული',
            'name' => 'დამალული სახელი',
            'show_on_leaderboard' => false,
        ]);

        $search = app(SearchService::class);
        $nicknames = array_column($search->players('ნიკ'), 'nickname');

        $this->assertSame(['ნიკა123'], $nicknames);
        $this->assertSame([], $search->players('დამ'));
    }

    public function test_home_and_learn_render_the_live_index_without_hardcoded_chips(): void
    {
        $this->withoutVite();
        $this->seed(BadgeSeeder::class);

        $user = User::factory()->fullySetUp()->create(['grade' => SchoolGrade::First]);
        WeekPlanItem::factory()->create([
            'grade' => SchoolGrade::First,
            'title' => 'რიცხვები 1–10',
        ]);

        Livewire::actingAs($user)
            ->test('pages::home')
            ->assertSee('რიცხვები 1–10', false)
            ->assertSee(__('badges.items.first-win.name'), false)
            ->assertDontSee(__('home.search_chip_spell'), false);

        Livewire::actingAs($user)
            ->test('pages::learn-categories')
            ->assertSee('data-subject="math"', false)
            ->assertSee(__('learn.week_n', ['n' => 1]), false)
            ->assertDontSee(__('learn.chip_space'), false)
            ->assertDontSee(__('learn.chip_animals'), false);
    }

    public function test_leaderboard_page_applies_a_nickname_search(): void
    {
        $this->withoutVite();

        $viewer = User::factory()->fullySetUp()->create();
        User::factory()->fullySetUp()->withStats(['xp' => 40])->create([
            'nickname' => 'ნიკა123',
            'show_on_leaderboard' => true,
        ]);
        User::factory()->fullySetUp()->withStats(['xp' => 90])->create([
            'nickname' => 'დამალული',
            'show_on_leaderboard' => false,
        ]);

        Livewire::actingAs($viewer)
            ->test('pages::leaderboard')
            ->assertSeeHtml('id="rankSearchInput"')
            ->assertDontSee('Brazil', false)
            ->call('applyPlayerSearch', 'ნიკ')
            ->assertSet('nicknameQuery', 'ნიკ')
            ->assertSee('ნიკა123', false)
            ->assertDontSee('დამალული', false);
    }
}
