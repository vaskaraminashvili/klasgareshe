<?php

namespace App\Notifications;

use App\Data\WeeklyReportSnapshot;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;
use Illuminate\Support\Facades\URL;

class WeeklyReportNotification extends Notification
{
    use Queueable;

    public function __construct(
        public WeeklyReportSnapshot $report,
        public int $userId,
    ) {}

    /**
     * @return list<string>
     */
    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        $figures = $this->report->figures;
        $optOut = URL::temporarySignedRoute(
            'weekly-report.opt-out',
            now()->addDays(30),
            ['user' => $this->userId],
        );

        return (new MailMessage)
            ->subject(__('reports.mail_subject', ['name' => $this->report->kidName]))
            ->greeting(__('reports.mail_greeting'))
            ->line(__('reports.mail_intro', [
                'name' => $this->report->kidName,
                'range' => $figures->rangeLabel,
            ]))
            ->line(__('reports.mail_stats', [
                'xp' => number_format($figures->xp),
                'packs' => $figures->packs,
                'days' => $figures->activeDays,
            ]))
            ->line($this->report->subline)
            ->action(__('reports.mail_cta'), url(route('weekly-report')))
            ->line(__('reports.mail_opt_out_hint'))
            ->line($optOut);
    }
}
