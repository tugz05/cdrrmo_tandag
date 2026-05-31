<?php

namespace App\Http\Controllers;

use App\Http\Requests\ManualReportStoreRequest;
use App\Models\Report;
use App\Services\ReportService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Inertia\Inertia;

class ReportController extends Controller
{
    public function __construct(private ReportService $reportService) {}

    public function index(Request $request, string $type = 'All', ?string $status = null)
    {
        return Inertia::render('Reports/Index',
            $this->reportService->index($type, $status, trim((string) $request->input('q', '')))
        );
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(ManualReportStoreRequest $request)
    {
        $this->reportService->store($request->validated());
    }

    public function resolveAddress(Report $report): JsonResponse
    {
        // Already stored — return immediately
        if (! empty($report->address)) {
            return response()->json(['address' => $report->address]);
        }

        if (! $report->latitude || ! $report->longitude) {
            return response()->json(['address' => null]);
        }

        // Round to 5 dp (~1 m precision) so nearby reports share one cache entry
        $lat = round((float) $report->latitude, 5);
        $lng = round((float) $report->longitude, 5);
        $cacheKey = "geocode:{$lat},{$lng}";

        $address = Cache::remember($cacheKey, now()->addDays(30), function () use ($lat, $lng) {
            $response = Http::withHeaders([
                'User-Agent' => 'CDRRMO-Tandag/1.0 cdrrmo-tandag.com',
                'Accept-Language' => 'en',
            ])->timeout(8)->get('https://nominatim.openstreetmap.org/reverse', [
                'format' => 'json',
                'lat'    => $lat,
                'lon'    => $lng,
            ]);

            return $response->successful() ? ($response->json()['display_name'] ?? null) : null;
        });

        if ($address && empty($report->address)) {
            $report->updateQuietly(['address' => $address]);
        }

        return response()->json(['address' => $address]);
    }

    public function destroy(Report $report)
    {
        $this->reportService->destroy($report);
    }

    public function updateStatus(Request $request)
    {
        $this->reportService->updateStatus($request->validate([
            'id' => 'required',
            'status' => 'required|string',
        ]));
    }
}
