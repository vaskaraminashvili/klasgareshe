<?php

namespace Tests\Feature;

use App\Enums\FriendshipStatus;
use App\Enums\League;
use App\Enums\RewardClaimType;
use App\Enums\SchoolGrade;
use App\Enums\SchoolSubject;
use App\Enums\XpSource;
use App\Models\Friendship;
use App\Models\User;
use App\Models\UserBadge;
use App\Models\UserPlaySession;
use App\Models\WeekPlanItem;
use App\Repositories\UserStatRepository;
use App\Repositories\WeekPlanRepository;
use App\Services\FriendshipService;
use App\Services\LeagueSeasonService;
use App\Services\ParentZoneService;
use App\Services\RewardService;
use App\Services\UserStatService;
use Carbon\CarbonImmutable;
use Database\Seeders\BadgeSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Validation\ValidationException;
use Livewire\Livewire;
use Tests\TestCase;

class RankingDepthTest extends TestCase
{
    use RefreshDatabase;

    public function test_approve_without_parent_unlock_is_rejected(): void
    {
        $me = User::factory()->fullySetUp()->withStats()->create(['nickname' => 'social-me']);
        $other = User::factory()->fullySetUp()->withStats()->create(['nickname' => 'locked-pal']);
        $friends = app(FriendshipService::class);
        $friends->request($other, 'social-me');
        $id = (int) Friendship::query()->where('friend_id', $me->id)->value('id');

        $this->expectException(ValidationException::class);
        $friends->approve($me, $id);
    }

    public function test_five_approved_friends_unlock_social_star(): void
    {
        $this->seed(BadgeSeeder::class);

        $me = User::factory()->fullySetUp()->withStats()->create(['nickname' => 'social-me']);
        session([ParentZoneService::SESSION_UNLOCKED_AT => now()->toIso8601String()]);
        $friends = app(FriendshipService::class);

        for ($i = 1; $i <= 5; $i++) {
            $other = User::factory()->fullySetUp()->withStats()->create(['nickname' => 'pal-'.$i]);
            $friends->request($other, 'social-me');
            $id = (int) Friendship::query()
                ->where('friend_id', $me->id)
                ->where('status', FriendshipStatus::Pending)
                ->latest('id')
                ->value('id');
            $friends->approve($me, $id);
        }

        $this->assertSame(1, UserBadge::query()->where('user_id', $me->id)->whereHas('badge', fn ($q) => $q->where('slug', 'social-star'))->count());
    }

    public function test_suggestions_match_grade_and_league_and_skip_hidden_kids(): void
    {
        $me = User::factory()->fullySetUp()->withStats(['league' => League::Silver])->create([
            'grade' => SchoolGrade::Second,
            'nickname' => 'me-kid',
        ]);
        $match = User::factory()->fullySetUp()->withStats(['league' => League::Silver])->create([
            'grade' => SchoolGrade::Second,
            'name' => 'მეგი',
            'show_on_leaderboard' => true,
            'allow_friend_requests' => true,
        ]);
        User::factory()->fullySetUp()->withStats(['league' => League::Silver])->create([
            'grade' => SchoolGrade::Second,
            'name' => 'დამალული',
            'show_on_leaderboard' => false,
        ]);
        User::factory()->fullySetUp()->withStats(['league' => League::Bronze])->create([
            'grade' => SchoolGrade::Second,
            'name' => 'სხვა ლიგა',
        ]);
        User::factory()->fullySetUp()->withStats(['league' => League::Silver])->create([
            'grade' => SchoolGrade::First,
            'name' => 'სხვა კლასი',
        ]);

        $names = array_column(app(FriendshipService::class)->suggestions($me), 'name');

        $this->assertSame(['მეგი'], $names);
        $this->assertNotContains('დამალული', $names);
        $this->assertSame($match->name, $names[0]);
    }

    public function test_season_close_pays_stay_bonus_once_and_not_the_place_prize(): void
    {
        $this->travelTo(CarbonImmutable::parse('2026-08-17 10:00:00'));
        $leagues = app(LeagueSeasonService::class);
        $stats = app(UserStatService::class);

        $kid = User::factory()->fullySetUp()->create();
        $stats->recordPlay($kid, 40);
        $week = $leagues->ensureCurrentWeek();

        $this->travelTo(CarbonImmutable::parse('2026-08-24 01:00:00'));
        $leagues->closeWeek($week);
        $leagues->closeWeek($week);

        $stat = app(UserStatRepository::class)->firstOrCreateFor($kid->fresh());
        $this->assertSame(League::Bronze, $stat->league);
        $this->assertSame(240, $stat->xp);
        $this->assertSame('bronze', $kid->fresh()->avatar_frame);
        $this->assertSame(1, $this->eventCount($kid, XpSource::LeagueStay));
        $this->assertSame(0, $this->eventCount($kid, XpSource::WeeklyPrize));
    }

    public function test_place_prize_is_claimed_after_promotion_not_granted_by_the_job(): void
    {
        $this->travelTo(CarbonImmutable::parse('2026-08-17 10:00:00'));
        $leagues = app(LeagueSeasonService::class);
        $stats = app(UserStatService::class);
        $players = User::factory()->fullySetUp()->count(4)->create();
        $amounts = [100, 80, 50, 10];

        foreach ($players as $i => $player) {
            $stats->recordPlay($player, $amounts[$i]);
        }

        $week = $leagues->ensureCurrentWeek();
        $this->travelTo(CarbonImmutable::parse('2026-08-24 01:00:00'));
        $leagues->closeWeek($week);

        $top = $players[0]->fresh();
        $this->assertSame(League::Silver, app(UserStatRepository::class)->firstOrCreateFor($top)->league);
        $this->assertSame(100, app(UserStatRepository::class)->firstOrCreateFor($top)->xp);

        app(RewardService::class)->claim($top, RewardClaimType::WeeklyPrize, (string) $week->id);
        $this->assertSame(600, app(UserStatRepository::class)->firstOrCreateFor($top->fresh())->xp);
        app(RewardService::class)->claim($top, RewardClaimType::WeeklyPrize, (string) $week->id);
        $this->assertSame(600, app(UserStatRepository::class)->firstOrCreateFor($top->fresh())->xp);
        $this->assertSame(League::Silver, app(UserStatRepository::class)->firstOrCreateFor($top->fresh())->league);
    }

    public function test_four_stay_weeks_award_the_champion_mark_once(): void
    {
        $leagues = app(LeagueSeasonService::class);
        $kid = User::factory()->fullySetUp()->withStats()->create();
        $start = CarbonImmutable::parse('2026-08-17 10:00:00');

        for ($i = 0; $i < 4; $i++) {
            $this->travelTo($start->addWeeks($i));
            $leagues->ensureMembership($kid);
            $this->travelTo($start->addWeeks($i + 1)->addHour());
            $leagues->closeDueWeeks();
        }

        $stat = app(UserStatRepository::class)->firstOrCreateFor($kid->fresh());
        $this->assertContains('bronze', $stat->champion_tiers ?? []);
        $this->assertSame(800, $stat->xp);
        $this->assertSame(4, $this->eventCount($kid, XpSource::LeagueStay));
    }

    public function test_home_feed_uses_a_real_friend_play(): void
    {
        $this->withoutVite();

        $me = User::factory()->fullySetUp()->withStats()->create();
        $friend = User::factory()->fullySetUp()->withStats()->create(['name' => 'მეგი']);
        Friendship::factory()->create([
            'user_id' => $me->id,
            'friend_id' => $friend->id,
            'status' => FriendshipStatus::Accepted,
            'accepted_at' => now(),
        ]);
        $item = WeekPlanItem::factory()->create([
            'subject' => SchoolSubject::Math,
            'grade' => SchoolGrade::First,
        ]);
        app(WeekPlanRepository::class)->markCompleted($friend, $item->id, 5);

        Livewire::actingAs($me)
            ->test('pages::home')
            ->assertSee('მეგი')
            ->assertSee(SchoolSubject::Math->label());
    }

    public function test_country_filter_data_and_online_signal_are_real(): void
    {
        $this->withoutVite();

        $me = User::factory()->fullySetUp()->withStats(['xp' => 10])->create([
            'country' => 'ge',
            'name' => 'მე',
        ]);
        $online = User::factory()->fullySetUp()->withStats(['xp' => 50])->create([
            'country' => 'ge',
            'name' => 'ონლაინი',
        ]);
        $away = User::factory()->fullySetUp()->withStats(['xp' => 40])->create([
            'country' => 'fr',
            'name' => 'შორს',
        ]);

        UserPlaySession::factory()->open()->create(['user_id' => $online->id]);
        UserPlaySession::factory()->create([
            'user_id' => $away->id,
            'ended_at' => null,
            'last_heartbeat_at' => now()->subMinutes(10),
        ]);

        Livewire::actingAs($me)
            ->test('pages::leaderboard')
            ->assertSet('viewerCountry', 'ge')
            ->assertSee('საქართველო')
            ->assertSee('data-online="1"', false)
            ->assertSee('ონლაინი')
            ->assertSee('შორს');

        Livewire::actingAs($me)
            ->test('pages::country')
            ->call('choose', 'fr')
            ->assertSet('selected', 'fr');

        $this->assertSame('fr', $me->fresh()->country);
    }

    private function eventCount(User $user, XpSource $source): int
    {
        return app(UserStatRepository::class)->countXpEvents($user, $source);
    }
}
