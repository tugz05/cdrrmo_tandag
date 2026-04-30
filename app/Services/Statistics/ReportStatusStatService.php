<?php

namespace App\Services\Statistics;

use App\Enums\ReportStatusEnum;
use App\Models\Report;

class ReportStatusStatService
{
    public function all()
    {
        // JTODO: optimize this queries later.
        return [
            'status' => [
                'all' => Report::count(),
                'pending' => Report::whereStatus(ReportStatusEnum::PENDING)->count(),
                'in_progress' => Report::whereStatus(ReportStatusEnum::IN_PROGRESS)->count(),
                'rescued' => Report::whereStatus(ReportStatusEnum::RESCUED)->count(),
            ],
            'type' => [
                'all' => Report::count(),
                'messages' => Report::select('type')->whereType('Message')->count(),
                'calls' => Report::select('type')->whereType('Call')->count(),
            ]
        ];        
    }
}
