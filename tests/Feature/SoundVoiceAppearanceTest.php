<?php

namespace Tests\Feature;

use App\Models\User;
use App\Notifications\SupportMessageNotification;
use App\Services\ShareService;
use App\Services\ShopService;
use Database\Seeders\ShopItemSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use Livewire\Livewire;
use Tests\TestCase;

class SoundVoiceAppearanceTest extends TestCase
{
    use RefreshDatabase;

    public function test_guests_are_redirected_from_the_new_screens(): void
    {
        $this->get(route('app-language'))->assertRedirect(route('user-login'));
        $this->get(route('help-faq'))->assertRedirect(route('user-login'));
        $this->get(route('contact-us'))->assertRedirect(route('user-login'));
        $this->get(route('about'))->assertRedirect(route('user-login'));
        $this->get(route('clear-cache'))->assertRedirect(route('user-login'));
    }

    public function test_language_picker_lists_only_georgian_and_searches(): void
    {
        $this->withoutVite();

        $user = User::factory()->fullySetUp()->withStats()->create();

        Livewire::actingAs($user)
            ->test('pages::app-language')
            ->assertSee(__('language.native'), false)
            ->assertDontSee('English', false)
            ->assertDontSee('Français', false)
            ->set('query', 'english')
            ->assertSee(__('language.empty_title'), false)
            ->assertDontSee(__('language.active'), false)
            ->set('query', 'ქართ')
            ->assertSee(__('language.active'), false)
            ->call('select', 'en')
            ->assertSet('query', 'ქართ');
    }

    public function test_help_search_filters_real_answers(): void
    {
        $this->withoutVite();

        $user = User::factory()->fullySetUp()->withStats()->create();

        Livewire::actingAs($user)
            ->test('pages::help-faq')
            ->assertSee(__('help.items.0.q'), false)
            ->assertDontSee('works offline', false)
            ->set('query', 'zzzz-missing')
            ->assertSee(__('help.empty'), false)
            ->assertDontSee(__('help.items.0.q'), false);
    }

    public function test_contact_form_stores_the_message_and_mails_support(): void
    {
        $this->withoutVite();
        Notification::fake();

        $user = User::factory()->fullySetUp()->withStats()->create([
            'name' => 'ნინო',
            'email' => 'parent@example.com',
        ]);

        Livewire::actingAs($user)
            ->test('pages::contact-us')
            ->assertSet('topic', 'general')
            ->set('body', 'გთხოვთ გადაამოწმოთ ეკრანის დრო.')
            ->call('send')
            ->assertHasNoErrors()
            ->assertSet('sent', true);

        $this->assertDatabaseHas('support_messages', [
            'user_id' => $user->id,
            'email' => 'parent@example.com',
            'topic' => 'general',
        ]);

        Notification::assertSentOnDemand(SupportMessageNotification::class);
    }

    public function test_rate_link_opens_the_contact_form_on_the_rate_topic(): void
    {
        $this->withoutVite();

        $user = User::factory()->fullySetUp()->withStats()->create();

        $this->actingAs($user)
            ->get(route('contact-us', ['topic' => 'rate']))
            ->assertOk()
            ->assertSee(__('support.topics.rate'), false)
            ->assertDontSee('4.9', false);
    }

    public function test_about_shows_the_version_without_invented_stats(): void
    {
        $this->withoutVite();

        $user = User::factory()->fullySetUp()->withStats()->create();

        Livewire::actingAs($user)
            ->test('pages::about')
            ->assertSee(config('kidzio.version'), false)
            ->assertSee(__('about.store_value'), false)
            ->assertDontSee('120K', false)
            ->assertDontSee('4.9', false);
    }

    public function test_clear_cache_does_not_invent_an_offline_lesson_size(): void
    {
        $this->withoutVite();

        $user = User::factory()->fullySetUp()->withStats()->create();

        Livewire::actingAs($user)
            ->test('pages::clear-cache')
            ->assertSee(__('cache.offline_row_hint'), false)
            ->assertSee(__('cache.safe_body'), false)
            ->assertDontSee('14 MB', false)
            ->assertDontSee('24 MB', false);
    }

    public function test_share_payload_has_no_child_data_and_weekly_report_uses_it(): void
    {
        $this->withoutVite();

        $user = User::factory()->fullySetUp()->withStats()->create([
            'name' => 'ნინო',
            'nickname' => 'nino-kid',
        ]);

        $share = app(ShareService::class);

        $this->assertSame(route('user-login'), $share->url());
        $this->assertStringNotContainsString('ნინო', $share->outbound());
        $this->assertStringNotContainsString('nino-kid', $share->outbound());

        Livewire::actingAs($user)
            ->test('pages::weekly-report')
            ->assertSee('https://wa.me/?text='.urlencode($share->outbound()), false)
            ->assertDontSee('https://wa.me/?text='.urlencode('ნინო'), false);

        Livewire::actingAs($user)
            ->test('pages::profile')
            ->assertSee('data-kidzio-share', false);
    }

    public function test_equipped_sound_pack_is_exposed_for_playback(): void
    {
        $this->withoutVite();
        $this->seed(ShopItemSeeder::class);

        $user = User::factory()->fullySetUp()->withStats(['coins' => 1000])->create();

        $this->actingAs($user)
            ->get(route('settings'))
            ->assertOk()
            ->assertSee('content="default"', false);

        app(ShopService::class)->purchase($user, 'sound-fun');

        $this->actingAs($user)
            ->get(route('home'))
            ->assertOk()
            ->assertSee('content="fun"', false);
    }

    public function test_every_live_game_screen_has_a_hear_control(): void
    {
        $games = [
            'game-multiple-choice',
            'game-tap-correct',
            'game-counting',
            'game-fill-letter',
            'game-spell-word',
            'game-trace-letter',
            'game-opposites',
            'game-knowledge',
            'game-word-search',
            'game-match-word',
            'game-connect-pair',
        ];

        foreach ($games as $game) {
            $path = resource_path('views/pages/⚡'.$game.'.blade.php');
            $this->assertFileExists($path);
            $this->assertStringContainsString('data-speak=', (string) file_get_contents($path), $game);
        }
    }
}
