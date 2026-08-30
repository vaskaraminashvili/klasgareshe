<?php

namespace Tests\Feature;

use App\Enums\FriendshipStatus;
use App\Models\Friendship;
use App\Models\User;
use App\Services\FriendshipService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class FriendsTest extends TestCase
{
    use RefreshDatabase;

    public function test_request_by_nickname_creates_accepted_friendship(): void
    {
        $me = User::factory()->fullySetUp()->withStats()->create([
            'nickname' => 'me-kid',
        ]);
        $friend = User::factory()->fullySetUp()->withStats()->create([
            'nickname' => 'leo-star',
            'allow_friend_requests' => true,
        ]);

        app(FriendshipService::class)->request($me, 'leo-star');

        $this->assertDatabaseHas('friendships', [
            'user_id' => $me->id,
            'friend_id' => $friend->id,
            'status' => FriendshipStatus::Accepted->value,
        ]);

        $this->assertTrue(app(FriendshipService::class)->profileStrip($me)->count === 1);
    }

    public function test_ranking_friends_shows_friend_xp_order(): void
    {
        $this->withoutVite();

        $me = User::factory()->fullySetUp()->withStats(['xp' => 500])->create([
            'name' => 'Luna',
            'nickname' => 'luna',
        ]);
        $high = User::factory()->fullySetUp()->withStats(['xp' => 2000])->create([
            'name' => 'Leo',
            'nickname' => 'leo',
            'allow_friend_requests' => true,
        ]);
        $low = User::factory()->fullySetUp()->withStats(['xp' => 100])->create([
            'name' => 'Ana',
            'nickname' => 'ana',
            'allow_friend_requests' => true,
        ]);

        Friendship::factory()->create([
            'user_id' => $me->id,
            'friend_id' => $high->id,
            'status' => FriendshipStatus::Accepted,
            'accepted_at' => now(),
        ]);
        Friendship::factory()->create([
            'user_id' => $me->id,
            'friend_id' => $low->id,
            'status' => FriendshipStatus::Accepted,
            'accepted_at' => now(),
        ]);

        Livewire::actingAs($me)
            ->test('pages::ranking-friends')
            ->assertSet('friendCount', 2)
            ->assertSet('yourRank', 2)
            ->assertSeeInOrder(['Leo', 'Luna', 'Ana'], false);
    }

    public function test_cannot_friend_self(): void
    {
        $this->withoutVite();

        $me = User::factory()->fullySetUp()->withStats()->create([
            'nickname' => 'solo-kid',
        ]);

        Livewire::actingAs($me)
            ->test('pages::ranking-friends')
            ->set('nickname', 'solo-kid')
            ->call('requestFriend')
            ->assertHasErrors(['nickname']);

        $this->assertDatabaseMissing('friendships', [
            'user_id' => $me->id,
            'friend_id' => $me->id,
        ]);
    }

    public function test_profile_strip_count_updates(): void
    {
        $this->withoutVite();

        $me = User::factory()->fullySetUp()->withStats()->create([
            'nickname' => 'me-kid',
        ]);
        $friend = User::factory()->fullySetUp()->withStats(['xp' => 50])->create([
            'nickname' => 'pal-kid',
            'avatar' => '🦊',
            'allow_friend_requests' => true,
        ]);

        Livewire::actingAs($me)
            ->test('pages::profile')
            ->assertSet('friendsCount', 0);

        app(FriendshipService::class)->request($me, 'pal-kid');

        Livewire::actingAs($me)
            ->test('pages::profile')
            ->assertSet('friendsCount', 1)
            ->assertSet('friendAvatars', ['🦊']);

        $this->assertSame(1, Friendship::query()->where('user_id', $me->id)->where('friend_id', $friend->id)->count());
    }
}
