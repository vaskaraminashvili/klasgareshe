<?php

namespace App\Notifications;

use App\Enums\AlertType;
use Illuminate\Notifications\Notification;
use NotificationChannels\WebPush\WebPushChannel;
use NotificationChannels\WebPush\WebPushMessage;

class KidzioAlert extends Notification
{
    /**
     * @param  array<string, mixed>  $routeParams
     */
    public function __construct(
        public AlertType $type,
        public string $title,
        public string $body,
        public string $routeName,
        public array $routeParams = [],
        public ?string $dedupe = null,
        public string $url = '/',
        public bool $withPush = true,
    ) {}

    /**
     * @return list<string>
     */
    public function via(object $notifiable): array
    {
        $channels = ['database'];

        if ($this->withPush && $this->vapidIsConfigured()) {
            $channels[] = WebPushChannel::class;
        }

        return $channels;
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(object $notifiable): array
    {
        return [
            'type' => $this->type->value,
            'title' => $this->title,
            'body' => $this->body,
            'route' => $this->routeName,
            'params' => $this->routeParams,
            'icon' => $this->type->icon(),
            'tile' => $this->type->tile(),
            'dedupe' => $this->dedupe,
        ];
    }

    public function toWebPush(object $notifiable, Notification $notification): WebPushMessage
    {
        return (new WebPushMessage)
            ->title($this->title)
            ->body($this->body)
            ->icon(asset('assets/images/icon.png'))
            ->badge(asset('assets/images/icon.png'))
            ->data(['url' => $this->url])
            ->options(['TTL' => 3600]);
    }

    private function vapidIsConfigured(): bool
    {
        return filled(config('webpush.vapid.public_key'))
            && filled(config('webpush.vapid.private_key'));
    }
}
