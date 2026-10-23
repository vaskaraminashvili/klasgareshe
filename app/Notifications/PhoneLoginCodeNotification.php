<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class PhoneLoginCodeNotification extends Notification
{
    use Queueable;

    public function __construct(
        public string $code,
        public ?string $phone = null,
        public bool $loggedInsteadOfSms = false,
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
        $message = (new MailMessage)
            ->subject(__('login.phone_mail_subject'))
            ->greeting(__('login.phone_mail_greeting'))
            ->line(__('login.phone_mail_line'))
            ->line(__('login.phone_mail_code', ['code' => $this->code]))
            ->line(__('login.phone_mail_expires'));

        if ($this->loggedInsteadOfSms) {
            $message->line(__('login.phone_mail_log_line', ['phone' => $this->phone ?? '']));
        }

        return $message;
    }
}
