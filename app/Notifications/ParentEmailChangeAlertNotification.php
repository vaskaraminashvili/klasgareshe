<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class ParentEmailChangeAlertNotification extends Notification
{
    use Queueable;

    public function __construct(
        public string $kidName,
        public string $newEmail,
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
        return (new MailMessage)
            ->subject(__('account.mail_alert_subject'))
            ->greeting(__('account.mail_alert_greeting'))
            ->line(__('account.mail_alert_line', [
                'name' => $this->kidName,
                'email' => $this->newEmail,
            ]))
            ->line(__('account.mail_alert_expires'));
    }
}
