<?php

namespace App\Http\Controllers;

use App\Services\LiveMapService;
use Illuminate\Http\JsonResponse;
use Inertia\Inertia;
use Inertia\Response;

class LiveMapController extends Controller
{
    public function __construct(private LiveMapService $liveMap) {}

    public function index(): Response
    {
        return Inertia::render('LiveMap/Index', [
            'initial_state' => $this->liveMap->state(),
        ]);
    }

    public function state(): JsonResponse
    {
        return response()->json($this->liveMap->state());
    }
}
