<?php

namespace App\Services;

use App\Models\Report;
use App\Services\Statistics\ReportStatusStatService;

class ReportService
{
    public function index(string $type = null, string $status = null)
    {
        return [
            'reports' => Report::with('user')
                ->when(!is_null($type), function ($query) use ($type) {
                    if ($type == 'All') 
                        return $query;
                    $query->whereType($type);
                })
                ->when(!is_null($status), function ($query) use ($status) {
                    $query->whereStatus($status);
                })
                ->latest()
                ->get(),
            'active_status' => $status,           
            'active_type' => $type,                                                                                                                                                                                                                                                                                                                                                                                                                                                           
            'stats' => (new ReportStatusStatService)->all()
        ];
    }

    public function store($validatedData)
    {
        Report::updateOrCreate([
            'id' => $validatedData['id']
        ], $validatedData);
    }

    public function updateStatus($validatedData)
    {
        $report = Report::find($validatedData['id']);
        $report->status = $validatedData['status'];
        $report->save();
    }

    public function destroy(Report $report)
    {
        $report->delete();
    }

    public function restore($id)
    {
        Report::withTrashed()->find($id)->restore();
    }
}
