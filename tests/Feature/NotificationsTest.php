<?php

namespace Tests\Feature;

use App\Enums\GameType;
use App\Enums\League;
use App\Enums\LeagueWeekStatus;
use App\Enums\ReminderTime;
use App\Enums\SchoolGrade;
use App\Enums\SchoolSubject;
use App\Models\Badge;
use App\Models\Game;
use App\Models\Question;
use App\Models\User;
use App\Models\UserStat;
use App\Models\WeekPlanItem;
use App\Services\FriendshipService;
use App\Services\LeagueSeasonService;
use App\Services\NotificationService;
use App\Services\UserStatService;
use App\Services\WeekPlanService;
use Carbon\CarbonImmutable;
use Database\Seeders\BadgeSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class NotificationsTest extends TestCase
{
    use RefreshDatabase;

    public function test_home_bell_sheet_is_empty_without_invented_rows(): void
    {
        $this->withoutVite();

        $user = User::factory()->fullySetUp()->withStats()->create();

        Livewire::actingAs($user)
            ->test('pages::home')
            ->assertSeeHtml('id="bellBadge"')
            ->assertSeeHtml('id="notifSheet"')
            ->assertSee(__('alerts.empty_title'), false)
            ->assertDontSee(__('home.leo_finished_math'), false)
            ->assertSee(route('settings'), false);
    }

    public function test_badge_unlock_lists_on_home_when_rewards_are_on(): void
    {
        $this->withoutVite();
        $this->seed(BadgeSeeder::class);
        $item = $this->seedWeek()[0];

        $user = User::factory()->fullySetUp()->withStats()->create([
            'notification_preferences' => $this->prefs(['rewards' => true]),
        ]);

        $component = Livewire::actingAs($user)
            ->test('pages::game-multiple-choice', ['item' => $item->id]);
        $question = Question::query()->findOrFail($component->get('questionIds')[0]);
        $component->call('pick', $question->correctKey())->call('next');

        $home = Livewire::actingAs($user)->test('pages::home');
        $home->assertSee(__('alerts.badge_unlocked.title'), false)
            ->assertSee(__('badges.items.first-win.name'), false)
            ->assertSee(route('badges'), false)
            ->assertSet('unreadAlertCount', $user->unreadNotifications()->count());

        $id = $user->notifications()->get()->first(
            fn ($row): bool => ($row->data['type'] ?? null) === 'badge_unlocked',
        )?->id;
        $this->assertNotNull($id);
        $this->assertGreaterThan(0, $user->unreadNotifications()->count());

        $home->call('openAlert', $id)
            ->assertRedirect(route('badges'));

        $this->assertNotNull($user->notifications()->whereKey($id)->first()?->read_at);
    }

    public function test_rewards_off_skips_badge_alerts(): void
    {
        $this->seed(BadgeSeeder::class);
        $badge = Badge::query()->where('slug', 'first-win')->firstOrFail();
        $user = User::factory()->fullySetUp()->create([
            'notification_preferences' => $this->prefs(['rewards' => false]),
        ]);

        app(NotificationService::class)->badgeUnlocked($user, $badge);

        $this->assertSame(0, $user->notifications()->count());
    }

    public function test_friend_accepted_notifies_the_target_when_enabled(): void
    {
        $from = User::factory()->fullySetUp()->withStats()->create(['name' => 'ნინო', 'nickname' => 'nino-kid']);
        $target = User::factory()->fullySetUp()->withStats()->create([
            'nickname' => 'leo-star',
            'allow_friend_requests' => true,
            'notification_preferences' => $this->prefs(['friend_activity' => true]),
        ]);

        app(FriendshipService::class)->request($from, 'leo-star');

        $this->assertSame(1, $target->notifications()->count());
        $this->assertSame(0, $from->notifications()->count());
        $this->assertSame(__('alerts.friend_request.body', ['name' => 'ნინო']), $target->notifications()->first()?->data['body']);
    }

    public function test_league_promotion_notifies_when_rewards_are_on(): void
    {
        $this->travelTo(CarbonImmutable::parse('2026-08-17 10:00:00'));
        $leagues = app(LeagueSeasonService::class);
        $stats = app(UserStatService::class);

        $players = User::factory()->fullySetUp()->count(4)->create([
            'notification_preferences' => $this->prefs(['rewards' => true]),
        ]);
        $xpAmounts = [100, 80, 50, 10];

        foreach ($players as $i => $player) {
            $stats->recordPlay($player, $xpAmounts[$i]);
        }

        $week = $leagues->ensureCurrentWeek();
        $this->travelTo(CarbonImmutable::parse('2026-08-24 01:00:00'));
        $leagues->closeWeek($week);

        $this->assertSame(LeagueWeekStatus::Closed, $week->fresh()?->status);

        $top = $players[0]->fresh();
        $this->assertNotNull($top);
        $this->assertTrue(
            $top->notifications()->get()->contains(
                fn ($row): bool => ($row->data['type'] ?? null) === 'league_promoted',
            ),
        );
        $this->assertSame(League::Silver, UserStat::query()->where('user_id', $top->id)->first()?->league);
    }

    public function test_new_week_unlock_notifies_when_lessons_are_on(): void
    {
        $this->seedCurriculumWeeks();
        $user = User::factory()->fullySetUp()->create([
            'notification_preferences' => $this->prefs(['new_lessons' => true]),
        ]);
        $week = app(WeekPlanService::class);
        $items = WeekPlanItem::query()->where('week_number', 1)->orderBy('id')->get();
        $last = $items->pop();
        $this->assertNotNull($last);

        foreach ($items as $item) {
            $week->completeItem($user, $item->id, 1);
        }

        $this->assertSame(0, $user->notifications()->count());
        $week->completeItem($user, $last->id, 1);
        $this->assertSame(1, $user->notifications()->count());
        $this->assertSame(__('alerts.new_week.title'), $user->notifications()->first()?->data['title']);
    }

    public function test_reminder_sends_streak_and_mission_in_the_local_window(): void
    {
        $user = User::factory()->fullySetUp()->withStats([
            'current_streak' => 7,
            'last_played_on' => '2026-09-24',
        ])->create([
            'timezone' => 'Asia/Tbilisi',
            'reminder_time' => ReminderTime::Evening,
            'notification_preferences' => $this->prefs([
                'streak' => true,
                'daily_mission' => true,
            ]),
        ]);
        app(NotificationService::class)->subscribe(
            $user,
            'https://push.example.test/device-window',
            str_repeat('A', 87),
            str_repeat('B', 22),
        );

        $this->travelTo(CarbonImmutable::parse('2026-09-25 14:05:00', 'UTC'));

        $sent = app(NotificationService::class)->sendDueReminders();

        $this->assertSame(2, $sent);
        $this->assertSame(2, $user->notifications()->count());
        $this->assertSame(1, $user->pushSubscriptions()->count());

        $again = app(NotificationService::class)->sendDueReminders();
        $this->assertSame(0, $again);
        $this->assertSame(2, $user->notifications()->count());
    }

    public function test_reminder_skips_when_the_type_is_off_and_during_quiet_bedtime(): void
    {
        $user = User::factory()->fullySetUp()->withStats([
            'current_streak' => 4,
            'last_played_on' => '2026-09-24',
        ])->withBedtime('18:00', '07:00')->create([
            'timezone' => 'Asia/Tbilisi',
            'reminder_time' => ReminderTime::Evening,
            'notification_preferences' => $this->prefs([
                'streak' => true,
                'daily_mission' => false,
                'quiet_hours' => true,
            ]),
        ]);

        $this->travelTo(CarbonImmutable::parse('2026-09-25 14:05:00', 'UTC'));

        $this->assertSame(0, app(NotificationService::class)->sendDueReminders());
        $this->assertSame(0, $user->notifications()->count());

        $off = User::factory()->fullySetUp()->withStats([
            'current_streak' => 4,
            'last_played_on' => '2026-09-24',
        ])->create([
            'timezone' => 'Asia/Tbilisi',
            'reminder_time' => ReminderTime::Evening,
            'notification_preferences' => $this->prefs([
                'streak' => true,
                'daily_mission' => true,
            ]),
        ]);

        $this->travelTo(CarbonImmutable::parse('2026-09-25 10:00:00', 'UTC'));
        $this->assertSame(0, app(NotificationService::class)->sendDueReminders());
        $this->assertSame(0, $off->notifications()->count());
    }

    public function test_push_subscribe_and_unsubscribe(): void
    {
        $user = User::factory()->fullySetUp()->create([
            'notification_preferences' => $this->prefs(['rewards' => true]),
        ]);
        $endpoint = 'https://push.example.test/device-1';

        $this->actingAs($user)
            ->postJson(route('push-subscribe'), [
                'endpoint' => $endpoint,
                'key' => str_repeat('A', 87),
                'token' => str_repeat('B', 22),
                'encoding' => 'aes128gcm',
            ])
            ->assertOk();

        $this->assertDatabaseHas('push_subscriptions', [
            'subscribable_id' => $user->id,
            'subscribable_type' => $user->getMorphClass(),
            'endpoint' => $endpoint,
        ]);

        $this->actingAs($user)
            ->deleteJson(route('push-unsubscribe'), ['endpoint' => $endpoint])
            ->assertOk();

        $this->assertDatabaseMissing('push_subscriptions', [
            'subscribable_id' => $user->id,
            'endpoint' => $endpoint,
        ]);
    }

    public function test_mark_all_clears_the_unread_count(): void
    {
        $this->withoutVite();
        $this->seed(BadgeSeeder::class);
        $user = User::factory()->fullySetUp()->withStats()->create([
            'notification_preferences' => $this->prefs(['rewards' => true]),
        ]);
        $badge = Badge::query()->where('slug', 'first-win')->firstOrFail();
        app(NotificationService::class)->badgeUnlocked($user, $badge);

        Livewire::actingAs($user)
            ->test('pages::home')
            ->assertSet('unreadAlertCount', 1)
            ->call('markAllAlertsRead')
            ->assertSet('unreadAlertCount', 0);

        $this->assertNotNull($user->notifications()->first()?->read_at);
    }

    /**
     * @param  array<string, bool>  $overrides
     * @return array<string, bool>
     */
    private function prefs(array $overrides = []): array
    {
        return array_merge([
            'streak' => false,
            'new_lessons' => false,
            'rewards' => false,
            'daily_mission' => false,
            'friend_activity' => false,
            'quiet_hours' => true,
            'weekly_report' => true,
        ], $overrides);
    }

    /**
     * @return list<WeekPlanItem>
     */
    private function seedWeek(): array
    {
        Game::factory()->create([
            'slug' => GameType::MultipleChoice,
            'user_id' => null,
        ]);

        $item = WeekPlanItem::factory()->create([
            'grade' => SchoolGrade::First,
            'week_number' => 1,
            'weekday' => 1,
            'subject' => SchoolSubject::Georgian,
            'questions_per_round' => 1,
        ]);
        $question = Question::factory()->create([
            'subject' => SchoolSubject::Georgian->favourite(),
            'grade' => 1,
        ]);
        $item->questions()->sync([$question->id => ['sort_order' => 0]]);

        return [$item];
    }

    private function seedCurriculumWeeks(): void
    {
        Game::factory()->create([
            'slug' => GameType::MultipleChoice,
            'user_id' => null,
        ]);

        foreach ([1, 2] as $weekNumber) {
            foreach (SchoolSubject::ordered() as $subject) {
                $item = WeekPlanItem::factory()->create([
                    'grade' => SchoolGrade::First,
                    'week_number' => $weekNumber,
                    'weekday' => 1,
                    'subject' => $subject,
                    'questions_per_round' => 1,
                ]);
                $question = Question::factory()->create([
                    'subject' => $subject->favourite(),
                    'grade' => 1,
                ]);
                $item->questions()->sync([$question->id => ['sort_order' => 0]]);
            }
        }
    }
}
