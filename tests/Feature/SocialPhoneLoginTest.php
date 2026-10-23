<?php

namespace Tests\Feature;

use App\Enums\OnboardingStep;
use App\Models\SocialIdentity;
use App\Models\User;
use App\Notifications\PasswordResetNotification;
use App\Notifications\PhoneLoginCodeNotification;
use App\Services\AccountService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Notification;
use Laravel\Socialite\Facades\Socialite;
use Laravel\Socialite\Two\User as SocialiteUser;
use Livewire\Livewire;
use Tests\TestCase;

class SocialPhoneLoginTest extends TestCase
{
    use RefreshDatabase;

    public function test_missing_provider_keys_fail_instead_of_signing_in(): void
    {
        config([
            'services.google.client_id' => null,
            'services.google.client_secret' => null,
            'services.apple.client_id' => null,
            'services.apple.client_secret' => null,
            'services.apple.private_key' => null,
            'services.apple.team_id' => null,
            'services.apple.key_id' => null,
        ]);

        $this->get(route('social.redirect', 'google'))
            ->assertRedirect(route('user-login'))
            ->assertSessionHas('auth_error', __('login.provider_not_configured', ['provider' => 'Google']));

        $this->get(route('social.redirect', 'apple'))
            ->assertRedirect(route('user-login'))
            ->assertSessionHas('auth_error', __('login.provider_not_configured', ['provider' => 'Apple']));

        $this->get(route('social.callback', 'google'))
            ->assertRedirect(route('user-login'));

        $this->assertGuest();
        $this->assertSame(0, User::query()->count());
    }

    public function test_facebook_stays_unwired_and_explains_that_in_georgian(): void
    {
        $this->withoutVite();

        $this->get('/auth/facebook/redirect')->assertNotFound();
        $this->get('/auth/facebook/callback')->assertNotFound();

        Livewire::test('pages::user-login')
            ->assertSee(__('login.continue_google'), false)
            ->assertSee(__('login.continue_apple'), false)
            ->assertSee(__('login.continue_facebook'), false)
            ->call('explainFacebook')
            ->assertSee(__('login.facebook_unavailable'), false);

        $this->assertGuest();
    }

    public function test_google_signup_keeps_the_kids_name_requires_consent_and_blocks_home(): void
    {
        $this->withoutVite();
        $this->fakeProvider('google', 'google-1', 'parent@example.com', 'Provider Parent', true);

        $this->get(route('social.callback', 'google'))
            ->assertRedirect(route('user-register'));

        $this->assertGuest();
        $this->assertSame(0, User::query()->count());

        Livewire::test('pages::user-register')
            ->assertSet('email', 'parent@example.com')
            ->assertSet('socialPending', true)
            ->set('name', 'ნინო')
            ->set('age', '8')
            ->set('gender', 'Girl')
            ->set('password', '')
            ->set('agreed', false)
            ->call('register')
            ->assertHasErrors('agreed');

        $this->assertSame(0, User::query()->count());

        Livewire::test('pages::user-register')
            ->set('name', 'ნინო')
            ->set('age', '8')
            ->set('gender', 'Girl')
            ->set('password', '')
            ->set('agreed', true)
            ->call('register')
            ->assertRedirect(route('onboarding-age'));

        $user = User::query()->first();
        $this->assertInstanceOf(User::class, $user);
        $this->assertSame(1, User::query()->count());
        $this->assertSame('ნინო', $user->name);
        $this->assertSame(8, $user->age);
        $this->assertNotSame('Provider Parent', $user->name);
        $this->assertNotSame('providerparent', $user->nickname);
        $this->assertNull($user->password);
        $this->assertNotNull($user->email_verified_at);
        $this->assertNull($user->onboarding_completed_at);
        $this->assertDatabaseHas('social_identities', [
            'user_id' => $user->id,
            'provider' => 'google',
            'provider_id' => 'google-1',
            'email' => 'parent@example.com',
        ]);

        $this->actingAs($user)
            ->get(route('home'))
            ->assertRedirect(route('onboarding-age'));

        $user->forceFill([
            'onboarding_completed_at' => now(),
            'onboarding_step' => OnboardingStep::Done,
        ])->save();

        $this->actingAs($user->fresh())
            ->get(route('home'))
            ->assertOk();
    }

    public function test_an_unverified_google_email_still_requires_parent_verification(): void
    {
        $this->withoutVite();
        $this->fakeProvider('google', 'google-2', 'parent@example.com', 'Provider Parent', false);

        $this->get(route('social.callback', 'google'))->assertRedirect(route('user-register'));

        Livewire::test('pages::user-register')
            ->set('name', 'ნინო')
            ->set('age', '7')
            ->set('agreed', true)
            ->call('register')
            ->assertRedirect(route('onboarding-age'));

        $user = User::query()->first();
        $this->assertInstanceOf(User::class, $user);
        $this->assertNull($user->email_verified_at);
        $this->assertSame('ნინო', $user->name);

        $user->forceFill([
            'onboarding_completed_at' => now(),
            'onboarding_step' => OnboardingStep::Done,
        ])->save();

        $this->actingAs($user->fresh())
            ->get(route('home'))
            ->assertRedirect(route('parent-verify'));
    }

    public function test_google_links_an_existing_parent_email_without_a_second_profile(): void
    {
        $user = User::factory()->create([
            'name' => 'ნინო',
            'nickname' => 'nino_kid',
            'email' => 'parent@example.com',
            'age' => 6,
            'email_verified_at' => null,
            'onboarding_step' => OnboardingStep::Age,
        ]);

        $this->fakeProvider('google', 'google-9', 'parent@example.com', 'Provider Parent', true);

        $this->get(route('social.callback', 'google'))
            ->assertRedirect(route('onboarding-age'));

        $this->assertAuthenticatedAs($user);
        $this->assertSame(1, User::query()->count());
        $user->refresh();
        $this->assertSame('ნინო', $user->name);
        $this->assertSame('nino_kid', $user->nickname);
        $this->assertSame(6, $user->age);
        $this->assertNotNull($user->email_verified_at);
        $this->assertSame(1, SocialIdentity::query()->count());
    }

    public function test_apple_private_relay_account_without_a_password_can_reset_it(): void
    {
        Notification::fake();
        $this->withoutVite();

        $relay = 'abc123@privaterelay.appleid.com';
        $this->fakeProvider('apple', 'apple-relay-1', $relay, 'Apple Parent', true);

        $this->get(route('social.callback', 'apple'))->assertRedirect(route('user-register'));

        Livewire::test('pages::user-register')
            ->set('name', 'ლუკა')
            ->set('age', '9')
            ->set('password', '')
            ->set('agreed', true)
            ->call('register')
            ->assertRedirect(route('onboarding-age'));

        $user = User::query()->where('email', $relay)->first();
        $this->assertInstanceOf(User::class, $user);
        $this->assertSame('ლუკა', $user->name);
        $this->assertNotSame('Apple Parent', $user->name);
        $this->assertNull($user->password);
        $this->assertNotNull($user->email_verified_at);

        Auth::logout();
        $this->assertFalse(Auth::attempt([
            'email' => $relay,
            'password' => 'not-the-password',
        ]));

        Livewire::test('pages::forgot-password')
            ->set('email', $relay)
            ->call('send')
            ->assertRedirect(route('otp'));

        $code = '';
        Notification::assertSentTo($user, PasswordResetNotification::class, function (PasswordResetNotification $mail) use (&$code): bool {
            $code = $mail->code;

            return strlen($mail->code) === 6 && ctype_digit($mail->code);
        });

        Livewire::test('pages::otp')
            ->call('verifyCode', $code)
            ->assertRedirect(route('reset-password'));

        Livewire::test('pages::reset-password')
            ->set('password', 'new-secret1')
            ->set('password_confirmation', 'new-secret1')
            ->call('save')
            ->assertRedirect(route('onboarding-age'));

        $user->refresh();
        $this->assertTrue(Hash::check('new-secret1', (string) $user->password));

        Auth::logout();
        $this->assertTrue(Auth::attempt([
            'email' => $relay,
            'password' => 'new-secret1',
        ]));
    }

    public function test_phone_login_mails_the_parent_when_sms_is_not_configured(): void
    {
        Notification::fake();
        $this->withoutVite();

        $user = User::factory()->fullySetUp()->create([
            'phone' => '+995555112233',
        ]);

        Livewire::test('pages::user-login')
            ->call('showPhone')
            ->set('phone', '555112233')
            ->call('sendPhoneCode')
            ->assertSet('codeSent', true)
            ->assertSee(__('login.phone_code_sent_mail'), false)
            ->assertDontSee('SMS გაიგზავნა', false);

        $code = '';
        Notification::assertSentTo($user, PhoneLoginCodeNotification::class, function (PhoneLoginCodeNotification $mail) use (&$code): bool {
            $code = $mail->code;

            return $mail->loggedInsteadOfSms === false && strlen($mail->code) === 6;
        });

        Livewire::test('pages::user-login')
            ->call('showPhone')
            ->set('phoneCode', '000000')
            ->set('codeSent', true)
            ->call('verifyPhoneCode')
            ->assertHasErrors('phoneCode');

        $this->assertGuest();

        Livewire::test('pages::user-login')
            ->call('showPhone')
            ->set('phoneCode', $code)
            ->set('codeSent', true)
            ->call('verifyPhoneCode')
            ->assertRedirect(route('home'));

        $this->assertAuthenticatedAs($user);
    }

    public function test_a_new_phone_uses_the_mail_log_channel_then_registers_one_kid(): void
    {
        Notification::fake();
        $this->withoutVite();

        $page = Livewire::test('pages::user-login')
            ->call('showPhone')
            ->set('phone', '+995555112244')
            ->call('sendPhoneCode')
            ->assertSee(__('login.phone_code_sent_log'), false)
            ->assertDontSee('SMS გაიგზავნა', false);

        $code = '';
        Notification::assertSentOnDemand(PhoneLoginCodeNotification::class, function (PhoneLoginCodeNotification $mail) use (&$code): bool {
            $code = $mail->code;

            return $mail->loggedInsteadOfSms === true && $mail->phone === '+995555112244';
        });

        $page->set('phoneCode', $code)
            ->call('verifyPhoneCode')
            ->assertRedirect(route('user-register'));

        $this->assertSame(0, User::query()->count());

        Livewire::test('pages::user-register')
            ->assertSet('phonePending', true)
            ->set('name', 'ანა')
            ->set('age', '6')
            ->set('email', 'parent-phone@example.com')
            ->set('password', '')
            ->set('agreed', true)
            ->call('register')
            ->assertRedirect(route('onboarding-age'));

        $user = User::query()->first();
        $this->assertInstanceOf(User::class, $user);
        $this->assertSame(1, User::query()->count());
        $this->assertSame('ანა', $user->name);
        $this->assertSame('+995555112244', $user->phone);
        $this->assertNull($user->email_verified_at);
        $this->assertNull($user->password);

        $this->actingAs($user)
            ->get(route('home'))
            ->assertRedirect(route('onboarding-age'));
    }

    public function test_an_unimplemented_sms_driver_does_not_pretend_to_send(): void
    {
        Notification::fake();

        config(['services.sms.driver' => 'twilio']);

        Livewire::test('pages::user-login')
            ->call('showPhone')
            ->set('phone', '+995555112255')
            ->call('sendPhoneCode')
            ->assertHasErrors('phone')
            ->assertSee(__('login.sms_driver_unavailable', ['driver' => 'twilio']), false);

        Notification::assertNothingSent();
        $this->assertGuest();
    }

    public function test_account_deletion_removes_linked_social_identities(): void
    {
        Notification::fake();

        $user = User::factory()->fullySetUp()->create([
            'email' => 'parent@example.com',
            'name' => 'ნინო',
        ]);

        SocialIdentity::query()->create([
            'user_id' => $user->id,
            'provider' => 'google',
            'provider_id' => 'google-delete',
            'email' => 'parent@example.com',
        ]);

        app(AccountService::class)->requestDeletion($user);

        $this->assertSoftDeleted('social_identities', [
            'user_id' => $user->id,
            'provider_id' => 'google-delete',
        ]);

        $this->fakeProvider('google', 'google-delete', 'parent@example.com', 'Provider Parent', true);

        $this->get(route('social.callback', 'google'))
            ->assertRedirect(route('user-login'))
            ->assertSessionHas('auth_error', __('login.account_pending_deletion'));

        $this->assertGuest();
        $this->assertSame(1, User::withTrashed()->count());

        $this->travel(AccountService::GRACE_DAYS + 1)->days();
        $this->artisan('accounts:purge-deleted')->assertSuccessful();

        $this->assertDatabaseMissing('social_identities', [
            'provider_id' => 'google-delete',
        ]);
        $this->assertNull(User::withTrashed()->find($user->id));
    }

    public function test_faked_socialite_redirect_does_not_need_real_keys(): void
    {
        config([
            'services.google.client_id' => null,
            'services.google.client_secret' => null,
        ]);

        Socialite::fake('google');

        $this->get(route('social.redirect', 'google'))
            ->assertRedirect('https://socialite.fake/google/authorize');
    }

    private function fakeProvider(string $driver, string $id, string $email, string $name, bool $emailVerified): void
    {
        Socialite::fake($driver, SocialiteUser::fake([
            'id' => $id,
            'name' => $name,
            'nickname' => 'providerparent',
            'email' => $email,
            'email_verified' => $emailVerified,
        ]));
    }
}
