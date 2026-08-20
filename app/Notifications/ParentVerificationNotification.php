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
            ->subject('Verify your Kidzio parent email')
            ->greeting('Just to keep kids safe')
            ->line('We need to verify this parent email before your kid can start learning.')
            ->line('Your 6-digit code is: '.$this->code)
            ->action('Verify email', $this->url)
            ->line('This link and code expire in 10 minutes.');
    }
}
