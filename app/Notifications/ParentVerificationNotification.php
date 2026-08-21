<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class ParentVerificationNotification extends Notification
{
    use Queueable;

    public function __construct(
        public string $code,
        public string $url,
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
            ->subject(__('parent-verify.mail_subject'))
            ->greeting(__('parent-verify.mail_greeting'))
            ->line(__('parent-verify.mail_line_1'))
            ->line(__('parent-verify.mail_code', ['code' => $this->code]))
            ->action(__('parent-verify.mail_action'), $this->url)
            ->line(__('parent-verify.mail_expires'));
    }
}
