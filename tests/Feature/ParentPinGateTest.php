<?php

namespace Tests\Feature;

use App\Models\User;
use App\Notifications\ParentPinResetNotification;
use App\Repositories\UserStatRepository;
use App\Services\ParentZoneService;
use Carbon\CarbonImmutable;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\RateLimiter;
use Livewire\Livewire;
use Tests\TestCase;

class ParentPinGateTest extends TestCase
{
    use RefreshDatabase;

    public function test_guests_cannot_open_parent_routes(): void
    {
        $this->get(route('parent-controls'))->assertRedirect(route('user-login'));
        $this->get(route('change-pin'))->assertRedirect(route('user-login'));
        $this->get(route('preferred-subjects'))->assertRedirect(route('user-login'));
    }

    public function test_profile_links_to_parent_controls(): void
    {
        $this->withoutVite();

        $user = User::factory()->fullySetUp()->withStats()->create();

        Livewire::actingAs($user)
            ->test('pages::profile')
            ->assertSee(route('parent-controls'), false)
            ->assertSee(__('profile.parent_controls'), false);
    }

    public function test_locked_parent_controls_shows_the_gate_not_week_stats(): void
    {
        $this->withoutVite();
        $this->travelTo(CarbonImmutable::parse('2026-08-20 12:00:00'));

        $user = User::factory()->fullySetUp()->withStats()->withParentPin('2580')->create();
        app(UserStatRepository::class)->addDayXp($user, '2026-08-20', 777);

        Livewire::actingAs($user)
            ->test('pages::parent-controls')
            ->assertSet('unlocked', false)
            ->assertSee(__('parent-zone.gate_title'), false)
            ->assertDontSee('777', false);
    }

    public function test_first_visit_creates_a_pin_then_shows_the_dashboard(): void
    {
        $this->withoutVite();

        $user = User::factory()->fullySetUp()->withStats()->create();

        Livewire::actingAs($user)
            ->test('pages::parent-controls')
            ->assertSet('gateMode', 'setup')
            ->call('press', '2')
            ->call('press', '5')
            ->call('press', '8')
            ->call('press', '0')
            ->assertSet('gateMode', 'confirm')
            ->call('press', '2')
            ->call('press', '5')
            ->call('press', '8')
            ->call('press', '0')
            ->assertSet('unlocked', true)
            ->assertSee(__('parent-zone.heading'), false);

        $user->refresh();

        $this->assertTrue(Hash::check('2580', (string) $user->parent_pin));
        $this->assertNotNull($user->parent_pin_set_at);
    }

    public function test_correct_pin_unlocks_and_week_numbers_match_profile(): void
    {
        $this->withoutVite();
        $this->travelTo(CarbonImmutable::parse('2026-08-20 12:00:00'));

        $user = User::factory()->fullySetUp()->withStats()->withParentPin('2580')->create();
        app(UserStatRepository::class)->addDayXp($user, '2026-08-17', 100);
        app(UserStatRepository::class)->addDayXp($user, '2026-08-20', 240);

        $profile = Livewire::actingAs($user)->test('pages::profile');

        Livewire::actingAs($user)
            ->test('pages::parent-controls')
            ->call('press', '2')
            ->call('press', '5')
            ->call('press', '8')
            ->call('press', '0')
            ->assertSet('unlocked', true)
            ->assertSet('weekXp', $profile->get('weekXp'))
            ->assertSet('weekActiveDays', $profile->get('weekActiveDays'))
            ->assertSet('weekLessons', $profile->get('weekLessons'));
    }

    public function test_wrong_pin_is_throttled_after_five_attempts(): void
    {
        $this->withoutVite();

        $user = User::factory()->fullySetUp()->withParentPin('2580')->create();
        RateLimiter::clear('parent-pin:'.$user->id);

        $page = Livewire::actingAs($user)->test('pages::parent-controls');

        for ($i = 0; $i < 5; $i++) {
            $page->set('pin', '')
                ->call('press', '1')
                ->call('press', '1')
                ->call('press', '1')
                ->call('press', '1');
        }

        $page->assertSet('unlocked', false);
        $this->assertTrue(RateLimiter::tooManyAttempts('parent-pin:'.$user->id, ParentZoneService::MAX_ATTEMPTS));
        $this->assertNotSame('', $page->get('hint'));
    }

    public function test_direct_parent_urls_bounce_until_the_pin_is_entered(): void
    {
        $user = User::factory()->fullySetUp()->withParentPin('2580')->create();

        $this->actingAs($user)
            ->get(route('change-pin'))
            ->assertRedirect(route('parent-controls'));

        $this->actingAs($user)
            ->get(route('preferred-subjects'))
            ->assertRedirect(route('parent-controls'));
    }

    public function test_unlock_expires_after_idle_and_when_leaving_the_zone(): void
    {
        $user = User::factory()->fullySetUp()->withParentPin('2580')->create();

        $this->actingAs($user);
        app(ParentZoneService::class)->unlock();

        $this->get(route('change-pin'))->assertOk();

        $this->travel(ParentZoneService::IDLE_MINUTES + 1)->minutes();
        $this->get(route('change-pin'))->assertRedirect(route('parent-controls'));

        app(ParentZoneService::class)->unlock();
        $this->get(route('profile'))->assertOk();
        $this->get(route('change-pin'))->assertRedirect(route('parent-controls'));
    }

    public function test_forgot_pin_emails_a_code_then_sets_a_new_pin(): void
    {
        Notification::fake();
        $this->withoutVite();

        $user = User::factory()->fullySetUp()->withParentPin('2580')->create([
            'email' => 'parent@example.com',
        ]);

        $this->actingAs($user);

        Livewire::test('pages::parent-controls')
            ->call('forgotPin')
            ->assertRedirect(route('parent-pin-otp'));

        $code = '';
        Notification::assertSentTo($user, ParentPinResetNotification::class, function (ParentPinResetNotification $mail) use (&$code): bool {
            $code = $mail->code;

            return strlen($mail->code) === 6 && ctype_digit($mail->code);
        });

        Livewire::test('pages::parent-pin-otp')
            ->call('verifyCode', $code)
            ->assertRedirect(route('change-pin'));

        Livewire::test('pages::change-pin')
            ->assertSet('skipCurrent', true)
            ->assertSet('step', 2)
            ->call('press', '1')
            ->call('press', '4')
            ->call('press', '7')
            ->call('press', '0')
            ->call('next')
            ->call('press', '1')
            ->call('press', '4')
            ->call('press', '7')
            ->call('press', '0')
            ->call('next')
            ->assertRedirect(route('parent-controls'));

        $user->refresh();
        $this->assertTrue(Hash::check('1470', (string) $user->parent_pin));
    }

    public function test_preferred_subjects_save_the_parent_override(): void
    {
        $this->withoutVite();

        $user = User::factory()->fullySetUp()->withParentPin('2580')->create([
            'favourite_subjects' => ['georgian', 'math', 'history'],
        ]);

        $this->actingAs($user);
        app(ParentZoneService::class)->unlock();

        Livewire::test('pages::preferred-subjects')
            ->call('applyQuick', 'read')
            ->call('save')
            ->assertSet('saved', true);

        $user->refresh();
        $this->assertSame(['georgian'], $user->favourite_subjects);
    }
}
