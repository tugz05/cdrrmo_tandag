<?php

namespace App\Events;

use App\Models\Report;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcastNow;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

/**
 * Fired when a citizen submits a new message report from the mobile app.
 * Broadcast immediately (not queued) so the admin alert never waits on a queue worker.
 */
class MessageReportReceived implements ShouldBroadcastNow
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public function __construct(public Report $report)
    {
    }

    public function broadcastOn(): array
    {
        return [new PrivateChannel('admin.reports')];
    }

    public function broadcastAs(): string
    {
        return 'message-report.received';
    }

    public function broadcastWith(): array
    {
        $report = $this->report->loadMissing('user:id,name,phone')->loadCount('report_images');

        return [
            'id' => $report->id,
            'type' => $report->type,
            'status' => $report->status,
            'details' => $report->details,
            'address' => $report->address,
            'latitude' => $report->latitude,
            'longitude' => $report->longitude,
            'images_count' => $report->report_images_count,
            'reporter' => [
                'name' => $report->user?->name,
                'phone' => $report->user?->phone,
            ],
            'created_at' => $report->created_at?->toIso8601String(),
        ];
    }
}
