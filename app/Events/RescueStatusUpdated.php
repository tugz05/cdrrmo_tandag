<?php

namespace App\Events;

use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcastNow;
use Illuminate\Foundation\Events\Dispatchable;

class RescueStatusUpdated implements ShouldBroadcastNow
{
    use Dispatchable, InteractsWithSockets;

    public function __construct(
        public int $reportId,
        public ?string $rescueStatus,
        public bool $sessionActive,
    ) {}

    public function broadcastOn(): array
    {
        return [new PrivateChannel("report.{$this->reportId}.tracking")];
    }

    public function broadcastAs(): string
    {
        return 'rescue-status.updated';
    }

    public function broadcastWith(): array
    {
        return [
            'report_id' => $this->reportId,
            'rescue_status' => $this->rescueStatus,
            'session_active' => $this->sessionActive,
        ];
    }
}
