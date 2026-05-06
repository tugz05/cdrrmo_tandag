<?php

namespace App\Http\Controllers;

use App\Models\SituationalIncidentReport;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class SituationalIncidentReportManageController extends Controller
{
    public function index(Request $request): Response
    {
        $reports = SituationalIncidentReport::query()
            ->with('user:id,name,email')
            ->orderByDesc('id')
            ->paginate(15)
            ->withQueryString();

        return Inertia::render('SituationalIncidentReports/Index', [
            'reports' => $reports,
        ]);
    }

    public function show(SituationalIncidentReport $situationalIncidentReport): Response
    {
        $situationalIncidentReport->load('user:id,name,email,phone');

        return Inertia::render('SituationalIncidentReports/Show', [
            'report' => $situationalIncidentReport,
        ]);
    }

    public function print(SituationalIncidentReport $situationalIncidentReport)
    {
        $situationalIncidentReport->load('user:id,name,email,phone');

        return view('print.situational-incident-report', [
            'report' => $situationalIncidentReport,
        ]);
    }
}
