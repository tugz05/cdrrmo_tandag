<?php

namespace App\Http\Controllers;

use App\Enums\ReportStatusEnum;
use App\Enums\ReportTypeEnum;
use App\Models\Report;
use App\Models\User;
use App\Services\StaffPresenceService;
use Illuminate\Http\Request;

class CallerInfoController extends Controller
{
    public function __construct(private StaffPresenceService $staffPresence) {}

    public function index($callerId)
    {
        return response()->json(
            User::with('latest_call')->findOrFail($callerId)
        );
    }

    public function setLocation(Request $request) // Changed from Client\Request to Http\Request
    {
        if (! $this->staffPresence->isCallRoutingAllowed()) {
            return response()->json([
                'success' => false,
                'code' => 'ALL_OPERATORS_BUSY',
                'message' => 'All emergency operators are currently busy. Please try again in a few minutes or use a text report if your app supports it.',
            ], 503);
        }

        $validated = $request->validate([
            'user_id' => 'required|integer|exists:users,id', // Assuming users table exists
            'latitude' => 'required|numeric|between:-90,90',
            'longitude' => 'required|numeric|between:-180,180',
            'accuracy' => 'nullable|numeric|min:0',
        ]);

        $authenticated = $request->user('sanctum');
        if ($authenticated !== null
            && (int) $validated['user_id'] !== (int) $authenticated->id) {
            return response()->json([
                'success' => false,
                'message' => 'user_id must match the authenticated user.',
            ], 403);
        }

        $report = Report::create([
            'user_id' => $validated['user_id'],
            'latitude' => $validated['latitude'],
            'longitude' => $validated['longitude'],
            'accuracy' => $validated['accuracy'] ?? null,
            'type' => ReportTypeEnum::CALL,
            'status' => ReportStatusEnum::PENDING,
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Caller location stored successfully',
            'report_id' => $report->id,
        ], 200);
    }

    public function callStarted(Request $request)
    {
        $request->validate([
            'report_id' => 'required|exists:reports,id',
        ]);

        $report = Report::find($request->report_id);
        $report->markCallStarted();

        return response()->json([
            'status' => 'success',
            'message' => 'Call start time recorded',
            'data' => $report,
        ]);
    }

    public function callEnded(Request $request)
    {
        $request->validate([
            'report_id' => 'required|exists:reports,id',
            'status' => 'nullable|in:completed,failed,canceled', // Customize as needed
        ]);

        $report = Report::find($request->report_id);
        $report->markCallEnded();

        // if ($request->has('status')) {
        //     $report->update(['status' => $request->status]);
        // }

        return response()->json([
            'status' => 'success',
            'message' => 'Call end time recorded',
            'data' => $report,
        ]);
    }
}
