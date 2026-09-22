<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class AccountDataExportNotification extends Notification
{
    use Queueable;

    /**
     * @param  array<string, mixed>  $payload
     */
    public function __construct(public array $payload) {}

    /**
     * @return list<string>
     */
    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        $json = json_encode($this->payload, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);
        $kid = is_string($this->payload['kid_name'] ?? null) ? $this->payload['kid_name'] : '';

        return (new MailMessage)
            ->subject(__('account.mail_export_subject', ['name' => $kid]))
            ->greeting(__('account.mail_export_greeting'))
            ->line(__('account.mail_export_line', ['name' => $kid]))
            ->attachData((string) $json, 'kidzio-data.json', [
                'mime' => 'application/json',
            ]);
    }
}
