<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
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
}
