<?php

namespace App\Events;

use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcastNow;
use Illuminate\Foundation\Events\Dispatchable;

/**
 * A new latest position. With no report, it is an on-duty position for admins.
 * Only ever dispatched for a report's owner or assigned rescuer: the owner can
 * subscribe to the report's channel and must not see any other staff member.
 */
class LiveLocationUpdated implements ShouldBroadcastNow
{
    use Dispatchable, InteractsWithSockets;

    /**
     * @param  array{lat: float, lng: float, accuracy: ?float, speed: ?float, heading: ?float, recorded_at: string}  $position
     */
    public function __construct(
        public ?int $reportId,
        public int $userId,
        public string $role,
        public array $position,
    ) {}

    public function broadcastOn(): array
    {
        return [
            new PrivateChannel($this->reportId === null ? 'staff.locations' : "report.{$this->reportId}.tracking"),
            new PrivateChannel('admin.live-map'),
        ];
    }

    public function broadcastAs(): string
    {
        return 'live-location.updated';
    }

    public function broadcastWith(): array
    {
        return [
            'report_id' => $this->reportId,
            'user_id' => $this->userId,
            'role' => $this->role,
            'lat' => $this->position['lat'],
            'lng' => $this->position['lng'],
            'accuracy' => $this->position['accuracy'],
            'speed' => $this->position['speed'],
            'heading' => $this->position['heading'],
            'recorded_at' => $this->position['recorded_at'],
        ];
    }
}
