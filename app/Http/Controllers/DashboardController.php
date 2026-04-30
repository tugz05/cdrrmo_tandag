<?php

namespace App\Http\Controllers;

use App\Services\DashboardService;
use Illuminate\Http\Request;
use Inertia\Inertia;

class DashboardController extends Controller
{
    public function __construct(private DashboardService $dashboardService)
    {}

    public function index()
    {
        return Inertia::render('Dashboard/Index',
            $this->dashboardService->all()
        );
    }
}
