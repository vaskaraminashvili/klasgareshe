<?php

namespace Tests\Feature;

use App\Enums\AgeGroup;
use App\Enums\DailyGoal;
use App\Enums\OnboardingStep;
use App\Enums\ReminderTime;
use App\Models\User;
use App\Notifications\ParentVerificationNotification;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\URL;
use Livewire\Livewire;
use Tests\TestCase;

class KidSetupTest extends TestCase
{
    use RefreshDatabase;

    public function test_guests_are_redirected_from_home_to_login(): void
    {
        $this->get(route('home'))
            ->assertRedirect(route('user-login'));
    }

    public function test_registration_persists_kid_details_and_starts_onboarding(): void
    {
        Livewire::test('pages::user-register')
            ->set('name', 'Luna')
            ->set('age', '6')
            ->set('gender', 'Girl')
            ->set('email', 'parent@example.com')
            ->set('password', 'password12')
            ->set('agreed', true)
            ->call('register')
            ->assertRedirect(route('onboarding-age'));

        $user = User::query()->where('email', 'parent@example.com')->first();

        $this->assertNotNull($user);
        $this->assertSame('Luna', $user->name);
        $this->assertSame(6, $user->age);
        $this->assertSame('Girl', $user->gender?->value);
        $this->assertSame(OnboardingStep::Age, $user->onboarding_step);
        $this->assertNull($user->email_verified_at);
    }

    public function test_unfinished_users_cannot_open_home(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)
            ->get(route('home'))
            ->assertRedirect(route('onboarding-age'));
    }

    public function test_onboarding_age_is_preselected_from_signup_age_and_advances(): void
    {
        $user = User::factory()->create(['age' => 11]);

        Livewire::actingAs($user)
            ->test('pages::onboarding-age')
            ->assertSet('ageGroup', AgeGroup::Explorer->value)
            ->call('save')
            ->assertRedirect(route('onboarding-categories'));

        $user->refresh();

        $this->assertSame(AgeGroup::Explorer, $user->age_group);
        $this->assertSame(OnboardingStep::Categories, $user->onboarding_step);
    }

    public function test_subjects_can_be_skipped_and_extras_map_to_core_topics(): void
    {
        $user = User::factory()->create([
            'onboarding_step' => OnboardingStep::Categories,
        ]);

        Livewire::actingAs($user)
            ->test('pages::onboarding-categories')
            ->set('selected', ['world', 'colors', 'science'])
            ->call('save')
            ->assertRedirect(route('onboarding-goals'));

        $user->refresh();

        $this->assertSame(['knowledge'], $user->favourite_subjects);
        $this->assertSame(OnboardingStep::Goals, $user->onboarding_step);
    }

    public function test_empty_subjects_are_allowed(): void
    {
        $user = User::factory()->create([
            'onboarding_step' => OnboardingStep::Categories,
        ]);

        Livewire::actingAs($user)
            ->test('pages::onboarding-categories')
            ->set('selected', [])
            ->call('save')
            ->assertRedirect(route('onboarding-goals'));

        $user->refresh();

        $this->assertSame([], $user->favourite_subjects);
    }

    public function test_daily_goal_and_skipped_notifications_send_parent_to_verify(): void
    {
        Notification::fake();

        $user = User::factory()->create([
            'onboarding_step' => OnboardingStep::Goals,
        ]);

        Livewire::actingAs($user)
            ->test('pages::onboarding-goals')
            ->call('select', 'casual')
            ->call('save')
            ->assertRedirect(route('onboarding-notifications'));

        $user->refresh();
        $this->assertSame(DailyGoal::Casual, $user->daily_goal);

        Livewire::actingAs($user)
            ->test('pages::onboarding-notifications')
            ->call('skip')
            ->assertRedirect(route('parent-verify'));

        $user->refresh();

        $this->assertNotNull($user->onboarding_completed_at);
        $this->assertSame(OnboardingStep::Verify, $user->onboarding_step);
        $this->assertSame(ReminderTime::Evening, $user->reminder_time);
        $this->assertFalse($user->notification_preferences['streak'] ?? true);

        Notification::assertSentTo($user, ParentVerificationNotification::class);
    }

    public function test_login_resumes_the_current_onboarding_step(): void
    {
        $user = User::factory()->create([
            'email' => 'parent@example.com',
            'onboarding_step' => OnboardingStep::Goals,
        ]);

        Livewire::test('pages::user-login')
            ->set('email', 'parent@example.com')
            ->set('password', 'password')
            ->call('login')
            ->assertRedirect(route('onboarding-goals'));
    }

    public function test_onboarded_unverified_users_are_sent_to_parent_verify(): void
    {
        $user = User::factory()->onboarded()->create();

        $this->actingAs($user)
            ->get(route('home'))
            ->assertRedirect(route('parent-verify'));
    }

    public function test_six_digit_code_verifies_and_opens_home(): void
    {
        Notification::fake();

        $user = User::factory()->onboarded()->create();

        Cache::put('parent-verify:'.$user->id, [
            'hash' => Hash::make('123456'),
        ], now()->addMinutes(10));

        Livewire::actingAs($user)
            ->test('pages::parent-verify')
            ->call('verifyCode', '123456')
            ->assertRedirect(route('home'));

        $this->assertNotNull($user->fresh()?->email_verified_at);
    }

    public function test_magic_link_verifies_the_parent_email(): void
    {
        $user = User::factory()->onboarded()->create();

        $url = URL::temporarySignedRoute(
            'parent-verify.confirm',
            now()->addMinutes(10),
            ['user' => $user->id],
        );

        $this->get($url)->assertRedirect(route('home'));

        $this->assertAuthenticatedAs($user);
        $this->assertNotNull($user->fresh()?->email_verified_at);
    }

    public function test_fully_set_up_users_can_open_home(): void
    {
        $user = User::factory()->fullySetUp()->create();

        $this->withoutVite();

        $this->actingAs($user)
            ->get(route('home'))
            ->assertOk();
    }

    public function test_invalid_code_is_rejected(): void
    {
        $user = User::factory()->onboarded()->create();

        Cache::put('parent-verify:'.$user->id, [
            'hash' => Hash::make('123456'),
        ], now()->addMinutes(10));

        Livewire::actingAs($user)
            ->test('pages::parent-verify')
            ->call('verifyCode', '000000')
            ->assertHasErrors(['code']);

        $this->assertNull($user->fresh()?->email_verified_at);
    }

    public function test_users_cannot_skip_ahead_to_parent_verify(): void
    {
        $user = User::factory()->create([
            'onboarding_step' => OnboardingStep::Age,
        ]);

        $this->actingAs($user)
            ->get(route('parent-verify'))
            ->assertRedirect(route('onboarding-age'));
    }

    public function test_fully_set_up_login_goes_to_home(): void
    {
        User::factory()->fullySetUp()->create([
            'email' => 'parent@example.com',
        ]);

        Livewire::test('pages::user-login')
            ->set('email', 'parent@example.com')
            ->set('password', 'password')
            ->call('login')
            ->assertRedirect(route('home'));
    }
}
