<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class LegalScreensTest extends TestCase
{
    use RefreshDatabase;

    public function test_guests_can_read_terms_and_privacy_without_auth(): void
    {
        $this->withoutVite();

        $this->get(route('terms-privacy'))->assertOk()->assertSee(__('legal.heading_terms'), false);
        $this->get(route('privacy-policy'))->assertOk()->assertSee(__('legal.heading_privacy'), false);
        $this->get(route('terms-privacy', ['tab' => 'terms']))
            ->assertOk()
            ->assertSee('aria-selected="true">'.__('legal.tab_terms'), false);
    }

    public function test_signup_consent_links_open_the_legal_screens(): void
    {
        $this->withoutVite();

        Livewire::test('pages::user-register')
            ->assertSee(route('terms-privacy', ['tab' => 'terms']), false)
            ->assertSee(route('privacy-policy'), false)
            ->assertDontSee('href="#"', false);
    }

    public function test_legal_copy_states_what_the_app_actually_does(): void
    {
        $this->withoutVite();

        $this->get(route('terms-privacy'))
            ->assertSee(__('legal.collect_profile'), false)
            ->assertSee(__('legal.acc_rights_optout_body'), false)
            ->assertSee(__('legal.collect_progress_hint'), false)
            ->assertDontSee('kidSAFE', false)
            ->assertDontSee('stored locally', false)
            ->assertDontSee('Seal certified', false);

        $this->get(route('privacy-policy'))
            ->assertSee(__('legal.acc_gdpr_p1'), false)
            ->assertSee(__('legal.collect_email_hint'), false);
    }

    public function test_logged_in_parent_can_still_open_legal_screens(): void
    {
        $this->withoutVite();

        $user = User::factory()->fullySetUp()->withStats()->create();

        $this->actingAs($user)->get(route('privacy-policy'))->assertOk();
        $this->actingAs($user)->get(route('terms-privacy'))->assertOk();
    }
}
