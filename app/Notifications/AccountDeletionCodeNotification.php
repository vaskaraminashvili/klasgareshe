<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class AccountDeletionCodeNotification extends Notification
{
    use Queueable;

    public function __construct(
        public string $code,
        public string $kidName,
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
            ->subject(__('account.mail_delete_code_subject'))
            ->greeting(__('account.mail_delete_code_greeting'))
            ->line(__('account.mail_delete_code_line', ['name' => $this->kidName]))
            ->line(__('account.mail_code', ['code' => $this->code]))
            ->line(__('account.mail_delete_code_expires'));
    }
}
