<?php

namespace App\Services;

use App\Models\Report;
use App\Services\Statistics\ReportStatusStatService;

class ReportService
{
    public function index(?string $type = null, ?string $status = null, string $search = '')
    {
        $type = $type ?? 'All';

        return [
            'reports' => Report::with('user')
                ->when(! is_null($type), function ($query) use ($type) {
                    if ($type === 'All') {
                        return $query;
                    }
                    $query->whereType($type);
                })
                ->when(! is_null($status) && $status !== '', function ($query) use ($status) {
                    $query->whereStatus($status);
                })
                ->when($search !== '', function ($query) use ($search) {
                    $needle = '%'.$search.'%';
                    $query->where(function ($q) use ($needle) {
                        $q->where('details', 'like', $needle)
                            ->orWhere('address', 'like', $needle)
                            ->orWhere('reporters_address', 'like', $needle)
                            ->orWhere('reported_by', 'like', $needle)
                            ->orWhereHas('user', function ($uq) use ($needle) {
                                $uq->where('name', 'like', $needle)
                                    ->orWhere('email', 'like', $needle)
                                    ->orWhere('fname', 'like', $needle)
                                    ->orWhere('lname', 'like', $needle)
                                    ->orWhere('phone', 'like', $needle);
                            });
                    });
                })
                ->latest()
                ->get(),
            'active_status' => $status,
            'active_type' => $type,
            'search' => $search,
            'stats' => (new ReportStatusStatService)->all(),
        ];
    }

    public function store($validatedData)
    {
        Report::updateOrCreate([
            'id' => $validatedData['id'],
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
