<?php

namespace Tests\Feature;

use App\Enums\GameType;
use App\Enums\SchoolGrade;
use App\Enums\SchoolSubject;
use App\Models\Game;
use App\Models\Question;
use App\Models\User;
use App\Models\UserBadge;
use App\Models\WeekPlanItem;
use App\Services\GamePlayService;
use Database\Seeders\BadgeSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class BadgeTest extends TestCase
{
    use RefreshDatabase;

    public function test_first_pack_awards_first_win_and_redirects_to_unlock(): void
    {
        $this->withoutVite();
        $this->seed(BadgeSeeder::class);
        $item = $this->seedWeek(weekdays: 2, perPack: 1, subjects: [SchoolSubject::Georgian])[0];

        $user = User::factory()->fullySetUp()->withStats()->create();

        $component = Livewire::actingAs($user)
            ->test('pages::game-multiple-choice', ['item' => $item->id]);

        $question = Question::query()->findOrFail($component->get('questionIds')[0]);

        $component->call('pick', $question->correctKey())
            ->call('next')
            ->assertRedirect(route('badge-unlock', ['slug' => 'first-win']));

        $this->assertBadgeCount($user, 'first-win', 1);
    }

    public function test_second_play_does_not_duplicate_first_win(): void
    {
        $this->seed(BadgeSeeder::class);
        $items = $this->seedWeek(weekdays: 2, perPack: 5, subjects: [SchoolSubject::Georgian]);
        $user = User::factory()->fullySetUp()->withStats()->create();
        $play = app(GamePlayService::class);

        $play->award($user, GameType::MultipleChoice, 3, $items[0]->id);
        $play->award($user, GameType::MultipleChoice, 3, $items[1]->id);

        $this->assertBadgeCount($user, 'first-win', 1);
        $this->assertBadgeCount($user, 'star-mini', 0);
    }

    public function test_three_packs_in_one_day_award_star_mini_and_secret_mission(): void
    {
        $this->seed(BadgeSeeder::class);
        $items = $this->seedWeek(weekdays: 3, perPack: 5, subjects: [SchoolSubject::Georgian]);
        $user = User::factory()->fullySetUp()->withStats()->create();
        $play = app(GamePlayService::class);

        foreach ($items as $item) {
            $play->award($user, GameType::MultipleChoice, 3, $item->id);
        }

        $this->assertBadgeCount($user, 'star-mini', 1);
        $this->assertBadgeCount($user, 'secret-mission', 1);
        $this->assertBadgeCount($user, 'bookworm', 1);
    }

    public function test_seven_georgian_packs_award_letter_lord_but_not_locked_badges(): void
    {
        $this->seed(BadgeSeeder::class);
        $items = $this->seedWeek(weekdays: 7, perPack: 5, subjects: [SchoolSubject::Georgian]);
        $user = User::factory()->fullySetUp()->withStats()->create();
        $play = app(GamePlayService::class);

        foreach ($items as $item) {
            $play->award($user, GameType::MultipleChoice, 3, $item->id);
        }

        $this->assertBadgeCount($user, 'letter-lord', 1);
        $this->assertBadgeCount($user, 'social-star', 0);
        $this->assertBadgeCount($user, 'speed-runner', 0);
    }

    public function test_secret_names_stay_hidden_until_earned(): void
    {
        $this->withoutVite();
        $this->seed(BadgeSeeder::class);
        $user = User::factory()->fullySetUp()->withStats()->create();

        Livewire::actingAs($user)
            ->test('pages::badges')
            ->assertSee(__('badges.secret_name'))
            ->assertDontSee(__('badges.items.secret-week.name'), false)
            ->assertDontSee(__('badges.items.secret-mission.name'), false)
            ->assertDontSee(__('badges.items.secret-ace.name'), false);
    }

    public function test_revisit_unlock_after_seen_redirects_to_collection(): void
    {
        $this->withoutVite();
        $this->seed(BadgeSeeder::class);
        $items = $this->seedWeek(weekdays: 1, perPack: 5, subjects: [SchoolSubject::Georgian]);
        $user = User::factory()->fullySetUp()->withStats()->create();

        app(GamePlayService::class)->award($user, GameType::MultipleChoice, 3, $items[0]->id);

        Livewire::actingAs($user)
            ->test('pages::badge-unlock', ['slug' => 'first-win'])
            ->assertSet('slug', 'first-win')
            ->assertSee(__('badges.items.first-win.name'));

        Livewire::actingAs($user)
            ->test('pages::badge-unlock', ['slug' => 'first-win'])
            ->assertRedirect(route('badges'));
    }

    public function test_home_without_earned_badges_does_not_show_dummy_first_win_as_earned(): void
    {
        $this->withoutVite();
        $user = User::factory()->fullySetUp()->withStats()->create();

        Livewire::actingAs($user)
            ->test('pages::home')
            ->assertDontSee(__('home.first_win'), false)
            ->assertDontSee(route('badge-unlock', ['slug' => 'first-win']), false);
    }

    public function test_home_shows_earned_georgian_badge_title(): void
    {
        $this->withoutVite();
        $this->seed(BadgeSeeder::class);
        $items = $this->seedWeek(weekdays: 1, perPack: 5, subjects: [SchoolSubject::Georgian]);
        $user = User::factory()->fullySetUp()->withStats()->create();

        app(GamePlayService::class)->award($user, GameType::MultipleChoice, 3, $items[0]->id);

        Livewire::actingAs($user)
            ->test('pages::home')
            ->assertSee(__('badges.items.first-win.name'))
            ->assertSee(route('badge-unlock', ['slug' => 'first-win']), false);
    }

    public function test_locked_badges_are_never_awarded(): void
    {
        $this->seed(BadgeSeeder::class);
        $items = $this->seedWeek(weekdays: 3, perPack: 5);
        $user = User::factory()->fullySetUp()->withStats()->create();
        $play = app(GamePlayService::class);

        foreach ($items as $item) {
            $play->award($user, GameType::MultipleChoice, 5, $item->id);
        }

        $this->assertBadgeCount($user, 'social-star', 0);
        $this->assertBadgeCount($user, 'speed-runner', 0);
    }

    public function test_fully_set_up_kids_can_open_badges(): void
    {
        $this->withoutVite();
        $this->seed(BadgeSeeder::class);
        $user = User::factory()->fullySetUp()->withStats()->create();

        $this->actingAs($user)->get(route('badges'))->assertOk();
    }

    private function assertBadgeCount(User $user, string $slug, int $times): void
    {
        $this->assertSame(
            $times,
            UserBadge::query()
                ->where('user_id', $user->id)
                ->whereHas('badge', fn ($query) => $query->where('slug', $slug))
                ->count(),
        );
    }

    /**
     * @param  list<SchoolSubject>|null  $subjects
     * @return list<WeekPlanItem>
     */
    private function seedWeek(int $weekdays = 2, int $perPack = 5, ?array $subjects = null): array
    {
        Game::factory()->create([
            'slug' => GameType::MultipleChoice,
            'user_id' => null,
        ]);

        $subjects ??= SchoolSubject::ordered();
        $items = [];

        foreach ($subjects as $subject) {
            for ($day = 1; $day <= $weekdays; $day++) {
                $item = WeekPlanItem::factory()->create([
                    'grade' => SchoolGrade::First,
                    'week_number' => 1,
                    'weekday' => $day,
                    'subject' => $subject,
                    'level' => $day,
                    'title' => $subject->value.'-d'.$day,
                    'questions_per_round' => $perPack,
                ]);

                $questions = Question::factory()->count($perPack)->create([
                    'subject' => $subject->favourite(),
                    'grade' => 1,
                ]);

                $sync = [];

                foreach ($questions as $index => $question) {
                    $sync[$question->id] = ['sort_order' => $index];
                }

                $item->questions()->sync($sync);
                $items[] = $item;
            }
        }

        return $items;
    }
}
