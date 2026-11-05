<?php

namespace App\Http\Controllers;

use App\Models\Report;
use App\Services\LiveLocationService;
use Illuminate\Http\JsonResponse;
use Inertia\Inertia;
use Inertia\Response;

class ReportLiveMapController extends Controller
{
    public function __construct(private LiveLocationService $liveLocation) {}

    public function show(Report $report): Response
    {
        $report->load('user:id,name');

        return Inertia::render('Reports/LiveMap', [
            'report' => [
                'id' => $report->id,
                'type' => $report->type,
                'address' => $report->address,
                'latitude' => $report->latitude,
                'longitude' => $report->longitude,
                'reporter' => $report->user?->name ?? $report->reported_by,
            ],
            'initial_state' => $this->liveLocation->mapState($report),
            'paths' => $this->liveLocation->mapPaths($report),
        ]);
    }

    public function state(Report $report): JsonResponse
    {
        return response()->json($this->liveLocation->mapState($report));
    }
}
