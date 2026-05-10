<?php

namespace App\Http\Controllers\API\V1;

use App\Enums\AppMobileRole;
use App\Http\Controllers\Controller;
use App\Http\Requests\API\V1\SituationalIncidentReportRequest;
use App\Models\SituationalIncidentReport;
use App\Traits\JResponseApiTrait;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class SituationalIncidentReportController extends Controller
{
    use JResponseApiTrait;

    public function history(Request $request, int $userId): JsonResponse
    {
        $user = $request->user();
        $isOwn = (int) $user->id === $userId;
        $isStaff = $user->mobileApiAppRole() === AppMobileRole::Staff;

        if (! $isOwn && ! $isStaff) {
            return $this->responseError('You may only view your own situational incident reports.', [], Response::HTTP_FORBIDDEN);
        }

        $reports = SituationalIncidentReport::query()
            ->where('user_id', $userId)
            ->orderByDesc('id')
            ->get();

        return $this->responseOK($reports);
    }

    public function store(SituationalIncidentReportRequest $request): JsonResponse
    {
        $data = $this->normalizePayload($request->validated());
        $data['user_id'] = $request->user()->id;

        $report = SituationalIncidentReport::query()->create($data);

        return $this->responseOK($report->fresh(), 'Situational incident report created.', Response::HTTP_CREATED);
    }

    public function show(Request $request, SituationalIncidentReport $situationalIncidentReport): JsonResponse
    {
        $this->authorize('view', $situationalIncidentReport);

        return $this->responseOK($situationalIncidentReport);
    }

    public function update(SituationalIncidentReportRequest $request, SituationalIncidentReport $situationalIncidentReport): JsonResponse
    {
        $this->authorize('update', $situationalIncidentReport);

        $situationalIncidentReport->update($this->normalizePayload($request->validated()));

        return $this->responseOK($situationalIncidentReport->fresh(), 'Situational incident report updated.');
    }

    public function destroy(Request $request, SituationalIncidentReport $situationalIncidentReport): JsonResponse
    {
        $this->authorize('delete', $situationalIncidentReport);

        $situationalIncidentReport->delete();

        return $this->responseOK([], 'Situational incident report deleted.');
    }

    /**
     * @param  array<string, mixed>  $validated
     * @return array<string, mixed>
     */
    private function normalizePayload(array $validated): array
    {
        $booleans = [
            'is_alert_response',
            'is_verbal_response',
            'is_pain_response',
            'is_unconscious',
            'has_deformity',
            'has_contusion',
            'has_abrasion',
            'has_puncture_penetration',
            'has_tenderness',
            'has_laceration',
            'has_swelling',
        ];

        foreach ($booleans as $key) {
            if (! array_key_exists($key, $validated)) {
                continue;
            }
            $validated[$key] = filter_var($validated[$key], FILTER_VALIDATE_BOOLEAN, FILTER_NULL_ON_FAILURE) ?? false;
        }

        return $validated;
    }
}
