<?php

namespace App\Http\Controllers;

use App\Http\Requests\ManualReportStoreRequest;
use App\Models\Report;
use App\Services\ReportService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
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

    public function saveAddress(Request $request, Report $report): JsonResponse
    {
        $data = $request->validate(['address' => 'required|string|max:500']);

        // Only fill if the address column is still empty — don't overwrite user-entered data
        if (empty($report->address)) {
            $report->updateQuietly(['address' => $data['address']]);
        }

        return response()->json(['address' => $report->address]);
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
