<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class ParentPinResetNotification extends Notification
{
    use Queueable;

    public function __construct(public string $code) {}

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
            ->subject(__('parent-zone.mail_subject'))
            ->greeting(__('parent-zone.mail_greeting'))
            ->line(__('parent-zone.mail_line_1'))
            ->line(__('parent-zone.mail_code', ['code' => $this->code]))
            ->line(__('parent-zone.mail_expires'));
    }
}
