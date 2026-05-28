<?php

namespace App\Services;

use App\Models\Report;
use App\Services\Statistics\ReportStatusStatService;
use Carbon\Carbon;
use Illuminate\Http\Request;

class DashboardService
{
    /**
     * @return array<string, mixed>
     */
    public function all(Request $request): array
    {
        $year = (int) $request->input('year', (int) now()->year);
        $year = max(2020, min($year, (int) now()->year + 1));

        return [
            'year' => $year,
            'years' => $this->yearOptions(),
            'stats' => (new ReportStatusStatService)->forYear($year),
            'heatmap_points' => $this->heatmapPoints($year),
            'monthly_chart' => $this->monthlyChart($year),
        ];
    }

    /**
     * @return list<int>
     */
    private function yearOptions(): array
    {
        $minDate = Report::query()->min('created_at');
        $startYear = $minDate ? (int) Carbon::parse($minDate)->year : (int) now()->year;
        $endYear = (int) now()->year;
        if ($startYear > $endYear) {
            return [$endYear];
        }

        return range($endYear, $startYear);
    }

    /**
     * Aggregated lat/lng cells for Leaflet.heat: [[lat, lng, intensity], ...].
     *
     * @return list<array{0: float, 1: float, 2: float}>
     */
    private function heatmapPoints(int $year): array
    {
        $rows = Report::query()
            ->whereYear('created_at', $year)
            ->whereNotNull('latitude')
            ->whereNotNull('longitude')
            ->where('latitude', '!=', '')
            ->where('longitude', '!=', '')
            ->selectRaw('ROUND(latitude, 3) as lat, ROUND(longitude, 3) as lng, COUNT(*) as w')
            ->groupByRaw('ROUND(latitude, 3), ROUND(longitude, 3)')
            ->orderByDesc('w')
            ->limit(800)
            ->get();

        return $rows->map(static function ($r): array {
            return [(float) $r->lat, (float) $r->lng, max(1.0, (float) $r->w)];
        })->values()->all();
    }

    /**
     * @return array{labels: list<string>, counts: list<int>}
     */
    private function monthlyChart(int $year): array
    {
        $labels = ['Jan', 'Feb', 'Mar', 'Apr', 'May', 'Jun', 'Jul', 'Aug', 'Sep', 'Oct', 'Nov', 'Dec'];
        $counts = [];
        for ($m = 1; $m <= 12; $m++) {
            $counts[] = (int) Report::query()
                ->whereYear('created_at', $year)
                ->whereMonth('created_at', $m)
                ->count();
        }

        return [
            'labels' => $labels,
            'counts' => $counts,
        ];
    }
}
