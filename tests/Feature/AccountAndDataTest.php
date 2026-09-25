<?php

namespace Tests\Feature;

use App\Models\Friendship;
use App\Models\User;
use App\Notifications\AccountDataExportNotification;
use App\Notifications\AccountDeletionCodeNotification;
use App\Notifications\AccountDeletionRequestedNotification;
use App\Notifications\ParentEmailChangeAlertNotification;
use App\Notifications\ParentEmailVerifyNotification;
use App\Notifications\PasswordResetNotification;
use App\Repositories\FriendshipRepository;
use App\Repositories\UserRepository;
use App\Repositories\UserStatRepository;
use App\Services\AccountService;
use App\Services\FriendshipService;
use App\Services\ParentZoneService;
use App\Services\PasswordResetService;
use App\Services\ProgressReportService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Notification;
use Livewire\Livewire;
use Tests\TestCase;

class AccountAndDataTest extends TestCase
{
    use RefreshDatabase;

    public function test_guests_cannot_open_account_routes(): void
    {
        $this->get(route('parent-email'))->assertRedirect(route('user-login'));
        $this->get(route('delete-account'))->assertRedirect(route('user-login'));
    }

    public function test_locked_parent_urls_bounce_to_the_pin_gate(): void
    {
        $user = User::factory()->fullySetUp()->withStats()->withParentPin()->create();

        $this->actingAs($user);

        $this->get(route('parent-email'))->assertRedirect(route('parent-controls'));
        $this->get(route('delete-account'))->assertRedirect(route('parent-controls'));
    }

    public function test_pending_email_keeps_the_old_address_until_verified(): void
    {
        Notification::fake();

        $user = User::factory()->fullySetUp()->withStats()->withParentPin('2580')->create([
            'email' => 'old.parent@example.com',
        ]);

        $this->actingAs($user);
        app(ParentZoneService::class)->unlock();

        Livewire::actingAs($user)
            ->test('pages::parent-email')
            ->set('newEmail', 'new.parent@example.com')
            ->set('confirmEmail', 'new.parent@example.com')
            ->set('currentPin', '2580')
            ->call('submit')
            ->assertHasNoErrors()
            ->assertSet('pendingEmail', 'new.parent@example.com')
            ->assertSet('email', 'old.parent@example.com');

        $user->refresh();
        $this->assertSame('old.parent@example.com', $user->email);
        $this->assertSame('new.parent@example.com', $user->pending_parent_email);

        Notification::assertSentTo($user, ParentEmailChangeAlertNotification::class);

        $url = '';
        Notification::assertSentOnDemand(ParentEmailVerifyNotification::class, function (ParentEmailVerifyNotification $mail) use ($user, &$url): bool {
            $url = $mail->url;

            return $mail->kidName === $user->name;
        });

        $this->assertNotSame('', $url);
        $this->get($url)->assertRedirect(route('parent-controls'));

        $user->refresh();
        $this->assertSame('new.parent@example.com', $user->email);
        $this->assertNull($user->pending_parent_email);
        $this->assertNotNull($user->email_verified_at);
    }

    public function test_six_digit_code_confirms_the_pending_email_from_livewire(): void
    {
        Notification::fake();
        $this->withoutVite();

        $user = User::factory()->fullySetUp()->withStats()->withParentPin('2580')->create([
            'email' => 'old.parent@example.com',
        ]);

        $this->actingAs($user);
        app(ParentZoneService::class)->unlock();

        $page = Livewire::actingAs($user)
            ->test('pages::parent-email')
            ->set('newEmail', 'new.parent@example.com')
            ->set('confirmEmail', 'new.parent@example.com')
            ->set('currentPin', '2580')
            ->call('submit')
            ->assertHasNoErrors()
            ->assertSet('pendingEmail', 'new.parent@example.com');

        $code = '';
        Notification::assertSentOnDemand(ParentEmailVerifyNotification::class, function (ParentEmailVerifyNotification $mail) use (&$code): bool {
            $code = $mail->code;

            return strlen($mail->code) === 6 && ctype_digit($mail->code);
        });

        $page->call('verifyCode', '000000')->assertHasErrors('code');
        $user->refresh();
        $this->assertSame('old.parent@example.com', $user->email);

        $page->call('verifyCode', $code)
            ->assertHasNoErrors()
            ->assertSet('email', 'new.parent@example.com')
            ->assertSet('pendingEmail', '');

        $user->refresh();
        $this->assertSame('new.parent@example.com', $user->email);
        $this->assertNull($user->pending_parent_email);
        $this->assertNotNull($user->email_verified_at);
    }

    public function test_signed_confirm_link_works_while_logged_out(): void
    {
        Notification::fake();

        $user = User::factory()->fullySetUp()->withStats()->withParentPin('2580')->create([
            'email' => 'old.parent@example.com',
        ]);

        $this->actingAs($user);
        app(ParentZoneService::class)->unlock();

        Livewire::actingAs($user)
            ->test('pages::parent-email')
            ->set('newEmail', 'new.parent@example.com')
            ->set('confirmEmail', 'new.parent@example.com')
            ->set('currentPin', '2580')
            ->call('submit');

        $url = '';
        Notification::assertSentOnDemand(ParentEmailVerifyNotification::class, function (ParentEmailVerifyNotification $mail) use (&$url): bool {
            $url = $mail->url;

            return $url !== '';
        });

        Auth::logout();

        $this->get($url)->assertRedirect(route('user-login'));

        $user->refresh();
        $this->assertSame('new.parent@example.com', $user->email);
        $this->assertNull($user->pending_parent_email);
    }

    public function test_password_reset_stays_on_the_old_email_while_a_change_is_pending(): void
    {
        Notification::fake();

        $user = User::factory()->fullySetUp()->withStats()->withParentPin('2580')->create([
            'email' => 'old.parent@example.com',
        ]);

        $this->actingAs($user);
        app(ParentZoneService::class)->unlock();

        Livewire::actingAs($user)
            ->test('pages::parent-email')
            ->set('newEmail', 'new.parent@example.com')
            ->set('confirmEmail', 'new.parent@example.com')
            ->set('currentPin', '2580')
            ->call('submit');

        $user->refresh();

        $users = app(UserRepository::class);
        $this->assertSame($user->id, $users->findByEmail('old.parent@example.com')?->id);
        $this->assertNull($users->findByEmail('new.parent@example.com'));
        $this->assertSame('old.parent@example.com', app(ProgressReportService::class)->weekSnapshot($user)->parentEmail);

        $resets = app(PasswordResetService::class);
        $resets->request('new.parent@example.com');
        Notification::assertNotSentTo($user, PasswordResetNotification::class);

        $resets->request('old.parent@example.com');
        Notification::assertSentTo($user, PasswordResetNotification::class);
    }

    public function test_unlocked_account_pages_render_the_ported_shell(): void
    {
        $this->withoutVite();

        $user = User::factory()->fullySetUp()->withStats()->withParentPin('2580')->create();

        $this->actingAs($user);
        app(ParentZoneService::class)->unlock();

        $this->get(route('parent-email'))
            ->assertOk()
            ->assertSee('id="emailForm"', false)
            ->assertSee('id="emailHint"', false)
            ->assertSee('id="confirmHint"', false)
            ->assertSee('id="saveBtn"', false)
            ->assertSee('id="copyBtn"', false)
            ->assertSee(__('account.heading'), false);

        $this->get(route('delete-account'))
            ->assertOk()
            ->assertSee(__('account.delete_item_profile'), false)
            ->assertSee(__('account.delete_item_friends'), false)
            ->assertSee(__('account.delete_send'), false);
    }

    public function test_delete_account_livewire_sends_a_code_then_soft_deletes(): void
    {
        Notification::fake();
        $this->withoutVite();

        $kid = User::factory()->fullySetUp()->withStats(['xp' => 220])->withParentPin('2580')->create([
            'nickname' => 'delete_lw',
        ]);

        $this->actingAs($kid);
        app(ParentZoneService::class)->unlock();

        $page = Livewire::actingAs($kid)
            ->test('pages::delete-account')
            ->assertSee(__('account.delete_item_profile'), false)
            ->assertSet('codeSent', false)
            ->call('sendCode')
            ->assertSet('codeSent', true);

        $code = '';
        Notification::assertSentTo($kid, AccountDeletionCodeNotification::class, function (AccountDeletionCodeNotification $mail) use (&$code): bool {
            $code = $mail->code;

            return strlen($mail->code) === 6 && ctype_digit($mail->code);
        });

        $page->call('verifyCode', '000000')->assertHasErrors('code');
        $this->assertNotNull(User::query()->find($kid->id));

        $page->call('verifyCode', $code)
            ->assertRedirect(route('user-login'));

        $this->assertGuest();
        $this->assertNull(User::query()->find($kid->id));
        $this->assertNotNull(User::withTrashed()->find($kid->id));
        $this->assertTrue(app(UserRepository::class)->nicknameExists('delete_lw'));
        Notification::assertSentOnDemand(AccountDeletionRequestedNotification::class);
    }

    public function test_deletion_hides_the_kid_from_rankings_and_friends_immediately(): void
    {
        Notification::fake();

        $kid = User::factory()->fullySetUp()->withStats(['xp' => 800])->withParentPin('2580')->create([
            'name' => 'ნინო',
            'nickname' => 'nino_star',
        ]);
        $friend = User::factory()->fullySetUp()->withStats(['xp' => 400])->create([
            'nickname' => 'leo_pal',
        ]);

        app(FriendshipService::class)->request($friend, 'nino_star');
        session([ParentZoneService::SESSION_UNLOCKED_AT => now()->toIso8601String()]);
        $pendingId = (int) Friendship::query()->where('friend_id', $kid->id)->value('id');
        app(FriendshipService::class)->approve($kid, $pendingId);

        $this->assertContains($kid->id, app(UserStatRepository::class)->topByXp()->pluck('user_id')->all());
        $this->assertContains($kid->id, app(FriendshipRepository::class)->acceptedFriendIds($friend));

        $code = '';
        app(AccountService::class)->sendDeletionCode($kid);
        Notification::assertSentTo($kid, AccountDeletionCodeNotification::class, function (AccountDeletionCodeNotification $mail) use (&$code): bool {
            $code = $mail->code;

            return strlen($mail->code) === 6;
        });

        $this->assertTrue(app(AccountService::class)->verifyDeletionCode($kid, $code));
        app(AccountService::class)->requestDeletion($kid);

        $this->assertNull(User::query()->find($kid->id));
        $this->assertNotNull(User::withTrashed()->find($kid->id));
        $this->assertNotContains($kid->id, app(UserStatRepository::class)->topByXp()->pluck('user_id')->all());
        $this->assertNotContains($kid->id, app(FriendshipRepository::class)->acceptedFriendIds($friend));
        $this->assertTrue(app(UserRepository::class)->nicknameExists('nino_star'));
        $this->assertNull(app(UserRepository::class)->findByNickname('nino_star'));

        Notification::assertSentOnDemand(AccountDeletionRequestedNotification::class);
    }

    public function test_purge_removes_dependents_after_the_grace_window(): void
    {
        Notification::fake();

        $kid = User::factory()->fullySetUp()->withStats(['xp' => 120])->withParentPin('2580')->create([
            'nickname' => 'purge_me',
        ]);
        $id = $kid->id;

        app(AccountService::class)->requestDeletion($kid);

        $this->artisan('accounts:purge-deleted')->assertSuccessful();
        $this->assertNotNull(User::withTrashed()->find($id));

        $this->travel(AccountService::GRACE_DAYS + 1)->days();
        $this->artisan('accounts:purge-deleted')->assertSuccessful();

        $this->assertNull(User::withTrashed()->find($id));
        $this->assertFalse(app(UserRepository::class)->nicknameExists('purge_me'));
    }

    public function test_json_export_is_emailed_to_the_verified_parent(): void
    {
        Notification::fake();
        $this->withoutVite();

        $user = User::factory()->fullySetUp()->withStats(['xp' => 50])->withParentPin('2580')->create();

        $this->actingAs($user);
        app(ParentZoneService::class)->unlock();

        Livewire::actingAs($user)
            ->test('pages::export-progress')
            ->call('pickFormat', 'json')
            ->call('download');

        Notification::assertSentTo($user, AccountDataExportNotification::class, function (AccountDataExportNotification $mail) use ($user): bool {
            return ($mail->payload['kid_name'] ?? null) === $user->name
                && ($mail->payload['parent_email'] ?? null) === $user->email;
        });
    }
}
