<?php

namespace Tests\Feature;

use App\Enums\DailyGoal;
use App\Enums\ReminderTime;
use App\Enums\SchoolGrade;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class SettingsTest extends TestCase
{
    use RefreshDatabase;

    public function test_guests_are_redirected_from_settings_to_login(): void
    {
        $this->get(route('settings'))
            ->assertRedirect(route('user-login'));
    }

    public function test_settings_shows_live_profile_and_onboarding_choices(): void
    {
        $this->withoutVite();

        $user = User::factory()->fullySetUp()->withStats()->create([
            'name' => 'ნინო',
            'email' => 'parent@example.com',
            'age' => 6,
            'grade' => SchoolGrade::First,
            'daily_goal' => DailyGoal::Casual,
            'favourite_subjects' => ['georgian', 'history'],
            'notification_preferences' => [
                'streak' => true,
                'new_lessons' => false,
                'rewards' => true,
                'daily_mission' => true,
                'friend_activity' => false,
                'quiet_hours' => true,
                'weekly_report' => true,
            ],
            'reminder_time' => ReminderTime::Morning,
        ]);

        Livewire::actingAs($user)
            ->test('pages::settings')
            ->assertSet('name', 'ნინო')
            ->assertSet('email', 'parent@example.com')
            ->assertSet('dailyGoal', DailyGoal::Casual->value)
            ->assertSet('streak', true)
            ->assertSet('newLessons', false)
            ->assertSet('rewards', true)
            ->assertSet('reminderTime', ReminderTime::Morning->value)
            ->assertSee('ნინო', false)
            ->assertSee(__('settings.dark_mode'), false)
            ->assertSee(__('settings.delivery_later'), false)
            ->assertSee(__('settings.language_value'), false)
            ->assertSee(route('privacy-policy'), false)
            ->assertSee(route('terms-privacy'), false)
            ->assertSee(route('parent-controls'), false)
            ->assertSee(route('parent-email'), false)
            ->assertSee(route('delete-account'), false)
            ->assertDontSeeHtml('ph-palette')
            ->assertDontSeeHtml('ph-speaker-high')
            ->assertDontSeeHtml('ph-chat-circle-dots');
    }

    public function test_toggles_persist_through_the_profile_service(): void
    {
        $this->withoutVite();

        $user = User::factory()->fullySetUp()->withStats()->create([
            'show_on_leaderboard' => true,
            'allow_friend_requests' => true,
            'notification_preferences' => [
                'streak' => false,
                'new_lessons' => false,
                'rewards' => false,
                'daily_mission' => true,
                'friend_activity' => false,
                'quiet_hours' => true,
                'weekly_report' => true,
            ],
        ]);

        Livewire::actingAs($user)
            ->test('pages::settings')
            ->set('streak', true)
            ->set('newLessons', true)
            ->set('rewards', true)
            ->set('showOnLeaderboard', false)
            ->set('allowFriendRequests', false);

        $user->refresh();

        $this->assertTrue($user->notification_preferences['streak'] ?? false);
        $this->assertTrue($user->notification_preferences['new_lessons'] ?? false);
        $this->assertTrue($user->notification_preferences['rewards'] ?? false);
        $this->assertTrue($user->notification_preferences['weekly_report'] ?? false);
        $this->assertTrue($user->notification_preferences['daily_mission'] ?? false);
        $this->assertFalse($user->show_on_leaderboard);
        $this->assertFalse($user->allow_friend_requests);
    }

    public function test_daily_goal_subjects_and_reminder_time_persist(): void
    {
        $this->withoutVite();

        $user = User::factory()->fullySetUp()->withStats()->create([
            'daily_goal' => DailyGoal::Regular,
            'favourite_subjects' => ['georgian', 'math', 'history'],
            'reminder_time' => ReminderTime::Evening,
        ]);

        Livewire::actingAs($user)
            ->test('pages::settings')
            ->call('selectGoal', DailyGoal::Intense->value)
            ->call('selectReminderTime', ReminderTime::Bedtime->value)
            ->call('toggleSubject', 'math')
            ->assertSet('dailyGoal', DailyGoal::Intense->value)
            ->assertSet('reminderTime', ReminderTime::Bedtime->value)
            ->assertSet('subjects', ['georgian', 'history']);

        $user->refresh();

        $this->assertSame(DailyGoal::Intense, $user->daily_goal);
        $this->assertSame(ReminderTime::Bedtime, $user->reminder_time);
        $this->assertSame(['georgian', 'history'], $user->favourite_subjects);
    }

    public function test_search_filters_visible_rows(): void
    {
        $this->withoutVite();

        $user = User::factory()->fullySetUp()->withStats()->create();

        Livewire::actingAs($user)
            ->test('pages::settings')
            ->set('query', 'მუქი')
            ->assertSee(__('settings.dark_mode'), false)
            ->assertDontSee(__('settings.delete_account'), false)
            ->set('query', 'zzzz-missing')
            ->assertSee(__('settings.search_empty'), false)
            ->assertDontSee(__('settings.dark_mode'), false);
    }

    public function test_profile_and_daily_mission_link_to_settings(): void
    {
        $this->withoutVite();

        $user = User::factory()->fullySetUp()->withStats()->create();

        Livewire::actingAs($user)
            ->test('pages::profile')
            ->assertSee(route('settings'), false)
            ->assertSee(__('profile.settings'), false);

        Livewire::actingAs($user)
            ->test('pages::daily-mission')
            ->assertSee(route('settings'), false);
    }
}
