<?php

namespace App\Notifications;

use App\Models\Report;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;
use Kreait\Firebase\Messaging\CloudMessage;
use Kreait\Firebase\Messaging\Notification as FcmNotification;

class RescueArrivedNotification extends Notification
{
    use Queueable;

    public function __construct(public Report $report) {}

    public function via(object $notifiable): array
    {
        return ['firebase'];
    }

    public function toFirebase(object $notifiable): CloudMessage
    {
        return CloudMessage::new()
            ->withNotification(FcmNotification::create(
                'Rescuer has arrived!',
                'The rescue team has arrived at your location.',
            ))
            ->withData([
                'type'      => 'rescue_arrived',
                'report_id' => (string) $this->report->id,
                'route'     => '/rescue_tracking',
            ]);
    }
}
