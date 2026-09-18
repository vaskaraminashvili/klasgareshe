<?php

namespace App\Console\Commands;

use App\Repositories\UserRepository;
use App\Services\ProgressReportService;
use Illuminate\Console\Command;

class SendWeeklyReportsCommand extends Command
{
    protected $signature = 'reports:send-weekly';

    protected $description = 'Email last week\'s parent report to verified accounts that opted in';

    public function handle(UserRepository $users, ProgressReportService $reports): int
    {
        $sent = 0;
        $skipped = 0;

        foreach ($users->verifiedLearners() as $user) {
            if (! $reports->wantsWeeklyEmail($user)) {
                $skipped++;

                continue;
            }

            $weekStart = $reports->previousCompletedWeekStart($user)->toDateString();
            $reports->emailWeek($user, $weekStart);
            $sent++;
        }

        $this->info("Sent {$sent} weekly report(s); skipped {$skipped} opt-out(s).");

        return self::SUCCESS;
    }
}
