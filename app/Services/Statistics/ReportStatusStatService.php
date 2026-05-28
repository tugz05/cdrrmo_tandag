<?php

namespace App\Services\Statistics;

use App\Enums\ReportStatusEnum;
use App\Models\Report;

class ReportStatusStatService
{
    /**
     * All-time counts (used on reports list filter badges).
     *
     * @return array<string, mixed>
     */
    public function all(): array
    {
        return $this->forYear(null);
    }

    /**
     * Counts scoped to a calendar year, or all-time when {@code $year} is null.
     *
     * @return array<string, mixed>
     */
    public function forYear(?int $year): array
    {
        $q = static function ($query) use ($year) {
            if ($year !== null) {
                $query->whereYear('created_at', $year);
            }
        };

        return [
            'status' => [
                'all' => Report::query()->tap($q)->count(),
                'pending' => Report::query()->tap($q)->whereStatus(ReportStatusEnum::PENDING)->count(),
                'in_progress' => Report::query()->tap($q)->whereStatus(ReportStatusEnum::IN_PROGRESS)->count(),
                'rescued' => Report::query()->tap($q)->whereStatus(ReportStatusEnum::RESCUED)->count(),
            ],
            'type' => [
                'all' => Report::query()->tap($q)->count(),
                'messages' => Report::query()->tap($q)->whereType('Message')->count(),
                'calls' => Report::query()->tap($q)->whereType('Call')->count(),
            ],
        ];
    }
}
