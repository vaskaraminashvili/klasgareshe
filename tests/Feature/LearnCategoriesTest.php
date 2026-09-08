<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class LearnCategoriesTest extends TestCase
{
    use RefreshDatabase;

    public function test_guests_are_redirected_to_login(): void
    {
        $this->get(route('learn-categories'))
            ->assertRedirect(route('user-login'));
    }

    public function test_unfinished_users_cannot_open_the_learn_library(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)
            ->get(route('learn-categories'))
            ->assertRedirect(route('onboarding-age'));
    }

    public function test_learn_library_shows_the_kidzio_catalog_shell(): void
    {
        $this->withoutVite();

        $user = User::factory()->fullySetUp()->create();

        $this->actingAs($user)
            ->get(route('learn-categories'))
            ->assertOk()
            ->assertSee(__('learn.library'), false)
            ->assertSee('assets/js/learn-categories.js', false)
            ->assertSee(__('nav.learn'), false);

        Livewire::actingAs($user)
            ->test('pages::learn-categories')
            ->assertOk()
            ->assertSee(__('learn.library'), false)
            ->assertSee(__('learn.todays_spotlight'), false)
            ->assertSee(__('learn.math'), false)
            ->assertSee(__('learn.quick_quiz'), false)
            ->assertSeeHtml('id="searchOverlay"')
            ->assertSeeHtml('id="filterOverlay"');
    }
}
