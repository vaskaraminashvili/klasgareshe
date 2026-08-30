<?php

namespace Tests\Feature;

use App\Enums\SchoolGrade;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class EditProfileTest extends TestCase
{
    use RefreshDatabase;

    public function test_edit_profile_page_shows_current_name(): void
    {
        $this->withoutVite();

        $user = User::factory()->fullySetUp()->withStats()->create([
            'name' => 'ნინო',
            'nickname' => 'nino-star',
        ]);

        Livewire::actingAs($user)
            ->test('pages::edit-profile')
            ->assertSet('name', 'ნინო')
            ->assertSee('ნინო', false);
    }

    public function test_save_updates_name_nickname_avatar_and_grade(): void
    {
        $this->withoutVite();

        $user = User::factory()->fullySetUp()->withStats()->create([
            'name' => 'ნინო',
            'nickname' => 'nino-star',
            'avatar' => '🐻',
            'grade' => SchoolGrade::First,
        ]);

        Livewire::actingAs($user)
            ->test('pages::edit-profile')
            ->set('name', 'ანა')
            ->set('nickname', 'ana-fox')
            ->set('avatar', '🦊')
            ->set('grade', SchoolGrade::Second->value)
            ->call('save')
            ->assertRedirect(route('profile'));

        $user->refresh();

        $this->assertSame('ანა', $user->name);
        $this->assertSame('ana-fox', $user->nickname);
        $this->assertSame('🦊', $user->avatar);
        $this->assertSame(SchoolGrade::Second, $user->grade);
    }

    public function test_nickname_uniqueness_fails(): void
    {
        $this->withoutVite();

        User::factory()->fullySetUp()->withStats()->create([
            'nickname' => 'taken-nick',
        ]);

        $user = User::factory()->fullySetUp()->withStats()->create([
            'name' => 'გიორგი',
            'nickname' => 'giorgi',
        ]);

        Livewire::actingAs($user)
            ->test('pages::edit-profile')
            ->set('nickname', 'taken-nick')
            ->call('save')
            ->assertHasErrors(['nickname']);

        $this->assertSame('giorgi', $user->fresh()->nickname);
    }
}
