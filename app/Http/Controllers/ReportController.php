<?php

namespace App\Http\Controllers;

use App\Http\Requests\ManualReportStoreRequest;
use App\Models\Report;
use App\Services\ReportService;
use Illuminate\Http\Request;
use Inertia\Inertia;

class ReportController extends Controller
{
    public function __construct(private ReportService $reportService) {}

    public function index(string $type = 'All', ?string $status = null)
    {
        return Inertia::render('Reports/Index',
            $this->reportService->index($type, $status)
        );
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(ManualReportStoreRequest $request)
    {
        $this->reportService->store($request->validated());
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
