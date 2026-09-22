<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class AccountDeletionRequestedNotification extends Notification
{
    use Queueable;

    public function __construct(
        public string $kidName,
        public string $purgeOn,
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
            ->subject(__('account.mail_delete_subject'))
            ->greeting(__('account.mail_delete_greeting'))
            ->line(__('account.mail_delete_line', [
                'name' => $this->kidName,
                'date' => $this->purgeOn,
            ]));
    }
}
