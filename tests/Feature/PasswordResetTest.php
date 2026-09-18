<?php

namespace Tests\Feature;

use App\Models\User;
use App\Notifications\PasswordResetNotification;
use App\Services\PasswordResetService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\RateLimiter;
use Livewire\Livewire;
use Tests\TestCase;

class PasswordResetTest extends TestCase
{
    use RefreshDatabase;

    public function test_login_forgot_link_opens_the_reset_form(): void
    {
        $this->withoutVite();

        Livewire::test('pages::user-login')
            ->assertSee(__('login.forgot'), false)
            ->assertSee(route('forgot-password'), false);
    }

    public function test_edit_profile_links_to_the_same_reset_flow(): void
    {
        $this->withoutVite();

        $user = User::factory()->fullySetUp()->withStats()->create();

        Livewire::actingAs($user)
            ->test('pages::edit-profile')
            ->assertSee(__('edit-profile.reset_password'), false)
            ->assertSee(route('forgot-password'), false);
    }

    public function test_a_known_parent_email_is_sent_a_six_digit_code(): void
    {
        Notification::fake();

        $user = User::factory()->fullySetUp()->create([
            'email' => 'parent@example.com',
        ]);

        Livewire::test('pages::forgot-password')
            ->set('email', 'parent@example.com')
            ->call('send')
            ->assertRedirect(route('otp'));

        Notification::assertSentTo($user, PasswordResetNotification::class, function (PasswordResetNotification $mail): bool {
            return strlen($mail->code) === 6 && ctype_digit($mail->code);
        });
    }

    public function test_an_unknown_email_does_not_leak_and_sends_nothing(): void
    {
        Notification::fake();

        Livewire::test('pages::forgot-password')
            ->set('email', 'nobody@example.com')
            ->call('send')
            ->assertRedirect(route('otp'));

        Notification::assertNothingSent();
    }

    public function test_happy_path_sets_a_new_password_and_logs_in(): void
    {
        Notification::fake();
        $this->withoutVite();

        $user = User::factory()->fullySetUp()->create([
            'email' => 'parent@example.com',
            'password' => 'old-password',
        ]);

        Livewire::test('pages::forgot-password')
            ->set('email', 'parent@example.com')
            ->call('send')
            ->assertRedirect(route('otp'));

        $code = '';
        Notification::assertSentTo($user, PasswordResetNotification::class, function (PasswordResetNotification $mail) use (&$code): bool {
            $code = $mail->code;

            return true;
        });

        Livewire::test('pages::otp')
            ->call('verifyCode', $code)
            ->assertRedirect(route('reset-password'));

        Livewire::test('pages::reset-password')
            ->set('password', 'new-secret1')
            ->set('password_confirmation', 'new-secret1')
            ->call('save')
            ->assertRedirect(route('home'));

        $this->assertAuthenticatedAs($user->fresh());
        $this->assertTrue(Hash::check('new-secret1', $user->fresh()?->password));
        $this->assertFalse(Hash::check('old-password', $user->fresh()?->password));
    }

    public function test_wrong_code_is_rejected(): void
    {
        $user = User::factory()->fullySetUp()->create([
            'email' => 'parent@example.com',
        ]);

        $this->primeResetCode($user, '123456');

        Livewire::test('pages::otp')
            ->call('verifyCode', '000000')
            ->assertHasErrors(['code']);

        $this->assertGuest();
        $this->assertFalse(session()->has(PasswordResetService::SESSION_USER_ID));
    }

    public function test_expired_code_is_rejected(): void
    {
        $user = User::factory()->fullySetUp()->create([
            'email' => 'parent@example.com',
        ]);

        $this->primeResetCode($user, '123456');
        $this->travel(11)->minutes();

        Livewire::test('pages::otp')
            ->call('verifyCode', '123456')
            ->assertHasErrors(['code']);
    }

    public function test_code_is_single_use(): void
    {
        $user = User::factory()->fullySetUp()->create([
            'email' => 'parent@example.com',
        ]);

        $this->primeResetCode($user, '123456');

        Livewire::test('pages::otp')
            ->call('verifyCode', '123456')
            ->assertRedirect(route('reset-password'));

        session()->forget(PasswordResetService::SESSION_USER_ID);

        Livewire::test('pages::otp')
            ->call('verifyCode', '123456')
            ->assertHasErrors(['code']);
    }

    public function test_attempts_are_throttled_after_five_failures(): void
    {
        $user = User::factory()->fullySetUp()->create([
            'email' => 'parent@example.com',
        ]);

        $this->primeResetCode($user, '123456');

        for ($i = 0; $i < 5; $i++) {
            Livewire::test('pages::otp')
                ->call('verifyCode', '000000')
                ->assertHasErrors(['code']);
        }

        Livewire::test('pages::otp')
            ->call('verifyCode', '123456')
            ->assertHasErrors(['code']);
    }

    public function test_resend_is_throttled(): void
    {
        Notification::fake();

        $user = User::factory()->fullySetUp()->create([
            'email' => 'parent@example.com',
        ]);

        Livewire::test('pages::forgot-password')
            ->set('email', $user->email)
            ->call('send')
            ->assertRedirect(route('otp'));

        Notification::assertSentTimes(PasswordResetNotification::class, 1);

        Livewire::test('pages::otp')
            ->call('resend');

        Notification::assertSentTimes(PasswordResetNotification::class, 1);
    }

    public function test_other_sessions_are_removed_after_reset(): void
    {
        Notification::fake();

        $user = User::factory()->fullySetUp()->create([
            'email' => 'parent@example.com',
        ]);

        DB::table('sessions')->insert([
            'id' => 'stale-session',
            'user_id' => $user->id,
            'ip_address' => '127.0.0.1',
            'user_agent' => 'test',
            'payload' => 'x',
            'last_activity' => time(),
        ]);

        $this->primeResetCode($user, '123456');

        Livewire::test('pages::otp')
            ->call('verifyCode', '123456')
            ->assertRedirect(route('reset-password'));

        Livewire::test('pages::reset-password')
            ->set('password', 'new-secret1')
            ->set('password_confirmation', 'new-secret1')
            ->call('save')
            ->assertRedirect(route('home'));

        $this->assertDatabaseMissing('sessions', [
            'id' => 'stale-session',
        ]);
    }

    public function test_otp_without_a_pending_email_returns_to_the_form(): void
    {
        Livewire::test('pages::otp')
            ->assertRedirect(route('forgot-password'));
    }

    protected function tearDown(): void
    {
        RateLimiter::clear('password-reset-send:parent@example.com');
        RateLimiter::clear('password-reset-attempt:parent@example.com');

        parent::tearDown();
    }

    private function primeResetCode(User $user, string $code): void
    {
        $email = mb_strtolower($user->email);

        Cache::put('password-reset:'.$email, [
            'hash' => Hash::make($code),
            'user_id' => $user->id,
        ], now()->addMinutes(10));

        session([
            PasswordResetService::SESSION_EMAIL => $email,
        ]);
    }
}
