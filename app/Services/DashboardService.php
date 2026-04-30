<?php

namespace App\Services;

use App\Services\Statistics\ReportStatusStatService;

class DashboardService
{
    public function all()
    {
        return [
            'stats' => (new ReportStatusStatService)->all()
        ];
    }
}
