<?php

namespace App\Channels;

use Illuminate\Notifications\Notification;
use Kreait\Firebase\Contract\Messaging;

class FirebaseChannel
{
    public function __construct(private Messaging $messaging) {}

    public function send(object $notifiable, Notification $notification): void
    {
        if (! method_exists($notification, 'toFirebase')) {
            return;
        }

        $tokens = $notifiable->device_fcm_tokens()->pluck('fcm_token')->filter()->values()->all();
        if (empty($tokens)) {
            return;
        }

        $message = $notification->toFirebase($notifiable);

        $this->messaging->sendMulticast($message, $tokens);
    }
}
