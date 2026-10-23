<?php

namespace App\Services;

use App\Enums\SupportTopic;
use App\Models\SupportMessage;
use App\Models\User;
use App\Notifications\SupportMessageNotification;
use App\Repositories\SupportMessageRepository;
use Illuminate\Support\Facades\Notification;

class SupportService
{
    public function __construct(private SupportMessageRepository $messages) {}

    public function submit(User $user, string $name, string $email, SupportTopic $topic, string $body): SupportMessage
    {
        $message = $this->messages->create([
            'user_id' => $user->id,
            'name' => $name,
            'email' => $email,
            'topic' => $topic->value,
            'body' => $body,
        ]);

        Notification::route('mail', (string) config('kidzio.support_email'))
            ->notify(new SupportMessageNotification($name, $email, $topic, $body));

        return $message;
    }
}
