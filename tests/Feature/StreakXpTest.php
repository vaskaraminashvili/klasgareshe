<?php

namespace Tests\Feature;

use App\Enums\GameType;
use App\Enums\SchoolGrade;
use App\Enums\SchoolSubject;
use App\Enums\XpSource;
use App\Models\Game;
use App\Models\Question;
use App\Models\User;
use App\Models\UserActivityDay;
use App\Models\UserBadge;
use App\Models\UserStat;
use App\Models\WeekPlanItem;
use App\Models\XpEvent;
use App\Repositories\UserStatRepository;
use App\Services\GamePlayService;
use App\Services\UserStatService;
use Carbon\CarbonImmutable;
use Database\Seeders\BadgeSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class StreakXpTest extends TestCase
{
    use RefreshDatabase;

    public function test_three_day_milestone_awards_xp_once(): void
    {
        $user = User::factory()->fullySetUp()->create();
        $stats = app(UserStatService::class);

        $this->travelTo(CarbonImmutable::parse('2026-08-17 10:00:00'));
        $stats->recordPlay($user, 40);

        $this->travelTo(CarbonImmutable::parse('2026-08-18 10:00:00'));
        $stats->recordPlay($user, 40);

        $this->travelTo(CarbonImmutable::parse('2026-08-19 10:00:00'));
        $stat = $stats->recordPlay($user, 40);

        $this->assertSame(3, $stat->current_streak);
        $this->assertSame(140, $stat->xp);
        $this->assertSame(1, $this->eventCount($user, XpSource::StreakMilestone, '3'));

        $stat = $stats->recordPlay($user, 10);
        $this->assertSame(150, $stat->xp);
        $this->assertSame(1, $this->eventCount($user, XpSource::StreakMilestone, '3'));

        $this->travelTo(CarbonImmutable::parse('2026-08-20 10:00:00'));
        $stat = $stats->recordPlay($user, 40);
        $this->assertSame(4, $stat->current_streak);
        $this->assertSame(190, $stat->xp);
        $this->assertSame(1, $this->eventCount($user, XpSource::StreakMilestone, '3'));
    }

    public function test_freeze_consumes_on_a_missed_day_and_keeps_the_streak(): void
    {
        $user = User::factory()->fullySetUp()->create();
        $stats = app(UserStatService::class);
        $repo = app(UserStatRepository::class);

        $this->travelTo(CarbonImmutable::parse('2026-08-17 10:00:00'));
        $stats->recordPlay($user, 40);
        $repo->update($repo->firstOrCreateFor($user), ['streak_freezes' => 1]);

        $this->travelTo(CarbonImmutable::parse('2026-08-19 10:00:00'));
        $stat = $stats->recordPlay($user, 40);

        $this->assertSame(3, $stat->current_streak);
        $this->assertSame(0, $stat->streak_freezes);
        $this->assertTrue(
            UserActivityDay::query()
                ->where('user_id', $user->id)
                ->whereDate('played_on', '2026-08-18')
                ->where('frozen', true)
                ->exists(),
        );
    }

    public function test_seven_day_milestone_does_not_auto_grant_a_freeze(): void
    {
        $user = User::factory()->fullySetUp()->create();
        $stats = app(UserStatService::class);

        for ($day = 17; $day <= 23; $day++) {
            $this->travelTo(CarbonImmutable::parse('2026-08-'.$day.' 10:00:00'));
            $stats->recordPlay($user, 40);
        }

        $stat = UserStat::query()->where('user_id', $user->id)->first();

        $this->assertSame(7, $stat?->current_streak);
        $this->assertSame(0, $stat?->streak_freezes);
        $this->assertSame(1, $this->eventCount($user, XpSource::StreakMilestone, '7'));
        $this->assertSame(1, $this->eventCount($user, XpSource::StreakMilestone, '3'));
    }

    public function test_combo_bonus_awards_twenty_xp_for_five_in_a_row(): void
    {
        $item = $this->seedPack();
        $user = User::factory()->fullySetUp()->create();

        $xp = app(GamePlayService::class)->award(
            $user,
            GameType::MultipleChoice,
            5,
            $item->id,
            maxCombo: 5,
            questionTotal: 5,
            completedAll: true,
        );

        $this->assertSame(60, $xp);
        $this->assertSame(60, UserStat::query()->where('user_id', $user->id)->first()?->xp);
        $this->assertSame(1, $this->eventCount($user, XpSource::Combo));
        $this->assertSame(0, $this->eventCount($user, XpSource::Speed));
    }

    public function test_speed_bonus_unlocks_speed_runner(): void
    {
        $this->seed(BadgeSeeder::class);
        $item = $this->seedPack();
        $user = User::factory()->fullySetUp()->create();

        app(GamePlayService::class)->award(
            $user,
            GameType::MultipleChoice,
            5,
            $item->id,
            maxCombo: 5,
            elapsedSeconds: 40,
            questionTotal: 5,
            completedAll: true,
        );

        $this->assertSame(1, $this->eventCount($user, XpSource::Speed));
        $this->assertSame(
            1,
            UserBadge::query()
                ->where('user_id', $user->id)
                ->whereHas('badge', fn ($query) => $query->where('slug', 'speed-runner'))
                ->count(),
        );
    }

    public function test_daily_login_calendar_scales_and_is_idempotent(): void
    {
        $user = User::factory()->fullySetUp()->create();
        $stats = app(UserStatService::class);

        $this->travelTo(CarbonImmutable::parse('2026-08-17 09:00:00'));
        $this->assertSame(10, $stats->awardDailyLogin($user));
        $this->assertSame(0, $stats->awardDailyLogin($user));
        $this->assertSame(10, UserStat::query()->where('user_id', $user->id)->first()?->xp);
        $this->assertSame(0, UserStat::query()->where('user_id', $user->id)->first()?->current_streak);

        $this->travelTo(CarbonImmutable::parse('2026-08-18 09:00:00'));
        $this->assertSame(20, $stats->awardDailyLogin($user));
        $this->assertSame(30, UserStat::query()->where('user_id', $user->id)->first()?->xp);
    }

    public function test_finishing_three_subjects_awards_mission_bonus_once(): void
    {
        $items = $this->seedPacksForSubjects(SchoolSubject::ordered());
        $user = User::factory()->fullySetUp()->create();
        $play = app(GamePlayService::class);

        $play->award($user, GameType::MultipleChoice, 1, $items[0]->id);
        $play->award($user, GameType::MultipleChoice, 1, $items[1]->id);
        $this->assertSame(0, $this->eventCount($user, XpSource::DailyMission));

        $play->award($user, GameType::MultipleChoice, 1, $items[2]->id);
        $this->assertSame(1, $this->eventCount($user, XpSource::DailyMission));
        $this->assertSame(144, UserStat::query()->where('user_id', $user->id)->first()?->xp);

        $play->award($user, GameType::MultipleChoice, 1, $items[2]->id);
        $this->assertSame(1, $this->eventCount($user, XpSource::DailyMission));
        $this->assertSame(144, UserStat::query()->where('user_id', $user->id)->first()?->xp);
    }

    public function test_streak_screen_shows_best_streak_and_month_map(): void
    {
        $this->withoutVite();
        $this->travelTo(CarbonImmutable::parse('2026-08-20 12:00:00'));

        $user = User::factory()->fullySetUp()->withStats([
            'xp' => 200,
            'current_streak' => 7,
            'longest_streak' => 14,
        ])->create();

        app(UserStatRepository::class)->addDayXp($user, '2026-08-20', 40);

        Livewire::actingAs($user)
            ->test('pages::streak')
            ->assertSet('current', 7)
            ->assertSet('best', 14)
            ->assertSee(__('streak.milestones.7'))
            ->assertSee(__('streak.best'))
            ->assertSeeHtml('cal-cell');
    }

    public function test_home_and_profile_link_to_streak(): void
    {
        $this->withoutVite();
        $user = User::factory()->fullySetUp()->withStats()->create();

        Livewire::actingAs($user)
            ->test('pages::home')
            ->assertSee(route('streak'), false);

        Livewire::actingAs($user)
            ->test('pages::profile')
            ->assertSee(route('streak'), false);
    }

    public function test_xp_progress_lists_real_sources(): void
    {
        $this->withoutVite();
        $this->travelTo(CarbonImmutable::parse('2026-08-21 12:00:00'));
        $user = User::factory()->fullySetUp()->create();
        app(UserStatService::class)->recordPlay($user, 40);

        Livewire::actingAs($user)
            ->test('pages::xp-progress')
            ->assertSee(__('xp.sources.pack'))
            ->assertDontSee(__('xp.empty_sources'));
    }

    private function eventCount(User $user, XpSource $source, ?string $context = null): int
    {
        $query = XpEvent::query()
            ->where('user_id', $user->id)
            ->where('source', $source);

        if ($context !== null) {
            $query->where('context', $context);
        }

        return $query->count();
    }

    private function seedPack(): WeekPlanItem
    {
        return $this->seedPacksForSubjects([SchoolSubject::Math])[0];
    }

    /**
     * @param  list<SchoolSubject>  $subjects
     * @return list<WeekPlanItem>
     */
    private function seedPacksForSubjects(array $subjects): array
    {
        Game::factory()->create([
            'slug' => GameType::MultipleChoice,
            'user_id' => null,
        ]);

        $items = [];

        foreach ($subjects as $index => $subject) {
            $item = WeekPlanItem::factory()->create([
                'grade' => SchoolGrade::First,
                'week_number' => 1,
                'weekday' => $index + 1,
                'subject' => $subject,
                'level' => 1,
                'title' => $subject->value,
                'questions_per_round' => 5,
            ]);

            $questions = Question::factory()->count(5)->create([
                'subject' => $subject->favourite(),
                'grade' => 1,
            ]);

            $sync = [];
            foreach ($questions as $qIndex => $question) {
                $sync[$question->id] = ['sort_order' => $qIndex];
            }
            $item->questions()->sync($sync);
            $items[] = $item;
        }

        return $items;
    }
}
