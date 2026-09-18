<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class PasswordResetNotification extends Notification
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
            ->subject(__('password-reset.mail_subject'))
            ->greeting(__('password-reset.mail_greeting'))
            ->line(__('password-reset.mail_line_1'))
            ->line(__('password-reset.mail_code', ['code' => $this->code]))
            ->line(__('password-reset.mail_expires'));
    }
}
