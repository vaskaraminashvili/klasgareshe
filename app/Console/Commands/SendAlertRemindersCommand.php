<?php

namespace App\Console\Commands;

use App\Services\NotificationService;
use Illuminate\Console\Command;

class SendAlertRemindersCommand extends Command
{
    protected $signature = 'alerts:send-reminders';

    protected $description = 'Send streak and daily-mission reminders at each account reminder time';

    public function handle(NotificationService $alerts): int
    {
        $sent = $alerts->sendDueReminders();

        $this->info("Sent {$sent} reminder alert(s).");

        return self::SUCCESS;
    }
}
