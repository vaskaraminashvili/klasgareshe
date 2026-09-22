<?php

namespace Tests\Feature;

use App\Enums\GameType;
use App\Enums\PlanProgressStatus;
use App\Enums\ReportScope;
use App\Enums\SchoolGrade;
use App\Enums\SchoolSubject;
use App\Models\Game;
use App\Models\Question;
use App\Models\User;
use App\Models\UserPlanProgress;
use App\Models\WeekPlanItem;
use App\Notifications\WeeklyReportNotification;
use App\Repositories\UserStatRepository;
use App\Services\ParentZoneService;
use App\Services\ProgressPdfService;
use App\Services\ProgressReportService;
use Carbon\CarbonImmutable;
use Database\Seeders\BadgeSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\URL;
use Livewire\Livewire;
use Tests\TestCase;

class ProgressReportTest extends TestCase
{
    use RefreshDatabase;

    public function test_guests_cannot_open_report_routes(): void
    {
        $this->get(route('weekly-report'))->assertRedirect(route('user-login'));
        $this->get(route('full-report'))->assertRedirect(route('user-login'));
        $this->get(route('export-progress'))->assertRedirect(route('user-login'));
    }

    public function test_locked_parent_urls_bounce_to_the_pin_gate(): void
    {
        $user = User::factory()->fullySetUp()->withStats()->withParentPin()->create();

        $this->actingAs($user);

        $this->get(route('weekly-report'))->assertRedirect(route('parent-controls'));
        $this->get(route('full-report'))->assertRedirect(route('parent-controls'));
        $this->get(route('export-progress'))->assertRedirect(route('parent-controls'));
    }

    public function test_week_figures_match_profile_for_a_seeded_week(): void
    {
        $this->withoutVite();
        $this->travelTo(CarbonImmutable::parse('2026-08-20 12:00:00'));
        $this->seed(BadgeSeeder::class);
        $this->seedWeek();

        $user = User::factory()->fullySetUp()->withStats()->withParentPin()->create([
            'name' => 'ნინო',
            'grade' => SchoolGrade::First,
        ]);

        $days = app(UserStatRepository::class);

        foreach (['2026-08-17' => 100, '2026-08-18' => 200, '2026-08-19' => 300, '2026-08-20' => 340] as $date => $xp) {
            $days->addDayXp($user, $date, $xp);
        }

        $georgian = WeekPlanItem::query()
            ->where('grade', SchoolGrade::First)
            ->where('subject', SchoolSubject::Georgian)
            ->where('weekday', 1)
            ->firstOrFail();

        UserPlanProgress::query()->create([
            'user_id' => $user->id,
            'week_plan_item_id' => $georgian->id,
            'status' => PlanProgressStatus::Completed,
            'correct_count' => 1,
            'completed_at' => CarbonImmutable::parse('2026-08-20 10:00:00'),
        ]);

        $snap = app(ProgressReportService::class)->weekSnapshot($user);

        $this->assertSame(940, $snap->figures->xp);
        $this->assertSame(4, $snap->figures->activeDays);
        $this->assertSame(1, $snap->figures->packs);
        $this->assertSame('ნინო', $snap->kidName);

        Livewire::actingAs($user)
            ->test('pages::profile')
            ->assertSet('weekXp', 940)
            ->assertSet('weekActiveDays', 4)
            ->assertSet('weekLessons', 1)
            ->assertSee(route('weekly-report'), false);
    }

    public function test_parent_can_open_weekly_report_after_pin(): void
    {
        $this->withoutVite();
        $this->travelTo(CarbonImmutable::parse('2026-08-20 12:00:00'));
        $this->seed(BadgeSeeder::class);

        $user = User::factory()->fullySetUp()->withStats()->withParentPin()->create(['name' => 'ნინო']);
        app(UserStatRepository::class)->addDayXp($user, '2026-08-20', 120);

        $this->actingAs($user);
        app(ParentZoneService::class)->unlock();

        Livewire::actingAs($user)
            ->test('pages::weekly-report')
            ->assertSee('ნინო', false)
            ->assertSee('120', false)
            ->assertSee(__('reports.heading'), false);

        Livewire::actingAs($user)
            ->test('pages::full-report')
            ->assertSee('ნინო', false)
            ->assertSee(__('reports.full_heading'), false);

        Livewire::actingAs($user)
            ->test('pages::export-progress')
            ->assertSee(__('reports.export_heading'), false);
    }

    public function test_monday_command_emails_verified_parents_and_respects_opt_out(): void
    {
        Notification::fake();
        $this->travelTo(CarbonImmutable::parse('2026-08-24 08:00:00'));
        $this->seed(BadgeSeeder::class);

        $on = User::factory()->fullySetUp()->withStats()->create();
        $off = User::factory()->fullySetUp()->withStats()->create();
        $unverified = User::factory()->onboarded()->withStats()->create();
        app(ProgressReportService::class)->setWeeklyEmail($off, false);

        $this->artisan('reports:send-weekly')->assertSuccessful();

        Notification::assertSentTo($on, WeeklyReportNotification::class);
        Notification::assertNotSentTo($off, WeeklyReportNotification::class);
        Notification::assertNotSentTo($unverified, WeeklyReportNotification::class);
    }

    public function test_signed_opt_out_stops_the_weekly_email(): void
    {
        Notification::fake();
        $this->seed(BadgeSeeder::class);

        $user = User::factory()->fullySetUp()->withStats()->create();
        $url = URL::temporarySignedRoute(
            'weekly-report.opt-out',
            now()->addDay(),
            ['user' => $user->id],
        );

        $this->get($url)->assertOk()->assertSee(__('reports.optout_heading'), false);

        $user->refresh();
        $this->assertFalse(app(ProgressReportService::class)->wantsWeeklyEmail($user));
    }

    public function test_export_pdf_html_renders_georgian(): void
    {
        $this->seed(BadgeSeeder::class);
        $user = User::factory()->fullySetUp()->withStats()->create(['name' => 'ნინო']);

        $html = app(ProgressPdfService::class)->html($user, ReportScope::Week);

        $this->assertStringContainsString('ნინო', $html);
        $this->assertStringContainsString('NotoSansGeorgian', $html);
    }

    public function test_export_pdf_embeds_noto_sans_georgian(): void
    {
        $this->seed(BadgeSeeder::class);
        $user = User::factory()->fullySetUp()->withStats()->create(['name' => 'ნინო']);

        $response = app(ProgressPdfService::class)->download($user, ReportScope::Week);
        $binary = $response->getContent();

        $this->assertNotFalse($binary);
        $this->assertNotSame('', $binary);
        $this->assertStringStartsWith('%PDF', $binary);
        $this->assertStringContainsString('NotoSansGeorgian', $binary);
    }

    private function seedWeek(SchoolGrade $grade = SchoolGrade::First, int $weekdays = 2, int $perPack = 1): void
    {
        Game::factory()->create([
            'slug' => GameType::MultipleChoice,
            'user_id' => null,
        ]);

        foreach (SchoolSubject::ordered() as $subject) {
            for ($day = 1; $day <= $weekdays; $day++) {
                $item = WeekPlanItem::factory()->create([
                    'grade' => $grade,
                    'week_number' => 1,
                    'weekday' => $day,
                    'subject' => $subject,
                    'level' => $day,
                    'title' => $subject->value.'-d'.$day,
                    'questions_per_round' => $perPack,
                ]);

                $questions = Question::factory()->count($perPack)->create([
                    'subject' => $subject->favourite(),
                    'grade' => $grade->value,
                ]);

                $sync = [];

                foreach ($questions as $index => $question) {
                    $sync[$question->id] = ['sort_order' => $index];
                }

                $item->questions()->sync($sync);
            }
        }
    }
}
