<?php

namespace Tests\Feature;

use App\Enums\PlanProgressStatus;
use App\Models\Badge;
use App\Models\Director;
use App\Models\Friendship;
use App\Models\Game;
use App\Models\LeagueWeek;
use App\Models\Question;
use App\Models\User;
use App\Models\UserBadge;
use App\Models\UserPlanProgress;
use App\Models\WeekPlanItem;
use Database\Seeders\BadgeSeeder;
use Database\Seeders\DirectorSeeder;
use Filament\Auth\Pages\Login;
use Filament\Facades\Filament;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Auth;
use Livewire\Livewire;
use Tests\TestCase;

class DirectorPanelTest extends TestCase
{
    use RefreshDatabase;

    public function test_guests_are_sent_to_director_login(): void
    {
        $this->get('/director')->assertRedirect('/director/login');
    }

    public function test_parent_session_cannot_open_the_director_panel(): void
    {
        $user = User::factory()->fullySetUp()->create();

        $this->actingAs($user)
            ->get('/director')
            ->assertRedirect('/director/login');
    }

    public function test_director_can_open_the_panel(): void
    {
        $director = Director::factory()->create();

        $this->actingAs($director, 'director')
            ->get('/director')
            ->assertOk();
    }

    public function test_parent_credentials_do_not_authenticate_the_director_guard(): void
    {
        User::factory()->create([
            'email' => 'parent@example.com',
            'password' => 'password',
        ]);

        $this->assertFalse(Auth::guard('director')->attempt([
            'email' => 'parent@example.com',
            'password' => 'password',
        ]));
        $this->assertGuest('director');
    }

    public function test_director_can_sign_in_to_the_panel(): void
    {
        $director = Director::factory()->create([
            'email' => 'director@example.com',
            'password' => 'password',
        ]);

        Filament::setCurrentPanel(Filament::getPanel('director'));

        Livewire::test(Login::class)
            ->fillForm([
                'email' => 'director@example.com',
                'password' => 'password',
            ])
            ->call('authenticate')
            ->assertHasNoFormErrors()
            ->assertRedirect();

        $this->assertAuthenticatedAs($director, 'director');
    }

    public function test_parent_cannot_sign_in_to_the_panel(): void
    {
        User::factory()->create([
            'email' => 'parent@example.com',
            'password' => 'password',
        ]);

        Filament::setCurrentPanel(Filament::getPanel('director'));

        Livewire::test(Login::class)
            ->fillForm([
                'email' => 'parent@example.com',
                'password' => 'password',
            ])
            ->call('authenticate')
            ->assertHasFormErrors(['email']);

        $this->assertGuest('director');
    }

    public function test_director_seeder_creates_the_staff_account(): void
    {
        $this->seed(DirectorSeeder::class);

        $this->assertDatabaseHas('directors', [
            'email' => 'director@example.com',
        ]);
        $this->assertDatabaseMissing('users', [
            'email' => 'director@example.com',
        ]);
    }

    public function test_director_can_open_generated_resource_lists(): void
    {
        $director = Director::factory()->create();
        $user = User::factory()->fullySetUp()->withStats()->create();
        $item = WeekPlanItem::factory()->create();

        Question::factory()->create();
        Game::factory()->create();
        Friendship::factory()->create();
        LeagueWeek::factory()->create();
        $this->seed(BadgeSeeder::class);

        UserPlanProgress::query()->create([
            'user_id' => $user->id,
            'week_plan_item_id' => $item->id,
            'status' => PlanProgressStatus::Completed,
            'correct_count' => 5,
            'completed_at' => now(),
        ]);

        $badgeId = Badge::query()->value('id');
        $this->assertNotNull($badgeId);

        UserBadge::query()->create([
            'user_id' => $user->id,
            'badge_id' => $badgeId,
            'unlocked_at' => now(),
        ]);

        $this->actingAs($director, 'director');

        foreach ([
            '/director/badges',
            '/director/week-plan-items',
            '/director/questions',
            '/director/users',
            '/director/user-stats',
            '/director/user-plan-progress',
            '/director/user-badges',
            '/director/games',
            '/director/friendships',
            '/director/league-weeks',
        ] as $uri) {
            $this->get($uri)->assertOk();
        }
    }
}
