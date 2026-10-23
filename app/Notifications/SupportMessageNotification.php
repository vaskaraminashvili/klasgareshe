<?php

namespace App\Notifications;

use App\Enums\SupportTopic;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class SupportMessageNotification extends Notification
{
    use Queueable;

    public function __construct(
        public string $name,
        public string $email,
        public SupportTopic $topic,
        public string $body,
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
            ->subject(__('support.mail_subject', ['topic' => $this->topic->label()]))
            ->replyTo($this->email, $this->name)
            ->line(__('support.mail_from', ['name' => $this->name, 'email' => $this->email]))
            ->line($this->topic->label())
            ->line($this->body);
    }
}
