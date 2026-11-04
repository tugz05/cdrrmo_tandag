<?php

namespace App\Http\Controllers\API\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\API\V1\SituationalIncidentReportRequest;
use App\Models\SituationalIncidentReport;
use App\Traits\JResponseApiTrait;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Symfony\Component\HttpFoundation\Response;

class SituationalIncidentReportController extends Controller
{
    use JResponseApiTrait;

    /**
     * All situational incident reports (staff / admin / super_admin app). Citizens use {@see history}.
     */
    public function historyAll(Request $request): JsonResponse
    {
        if (! $request->user()->mayAccessAllSituationalIncidentReportsViaApi()) {
            return $this->responseError(
                'Only staff or administrators may view all situational incident reports.',
                [],
                Response::HTTP_FORBIDDEN
            );
        }

        $reports = SituationalIncidentReport::query()
            ->with([
                'user' => static fn ($q) => $q->select('id', 'name', 'phone', 'email'),
            ])
            ->orderByDesc('id')
            ->get();

        return $this->responseOK($reports);
    }

    public function history(Request $request, int $userId): JsonResponse
    {
        $user = $request->user();
        $isOwn = (int) $user->id === $userId;

        if (! $isOwn && ! $user->mayAccessAllSituationalIncidentReportsViaApi()) {
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

        $diagram = $this->pullInjuryDiagram($data);
        $report = SituationalIncidentReport::query()->create($data);
        $this->applyInjuryDiagram($report, $diagram, $data);

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

        $data = $this->normalizePayload($request->validated());
        $diagram = $this->pullInjuryDiagram($data);
        $situationalIncidentReport->update($data);
        $this->applyInjuryDiagram($situationalIncidentReport, $diagram, $data);

        return $this->responseOK($situationalIncidentReport->fresh(), 'Situational incident report updated.');
    }

    public function destroy(Request $request, SituationalIncidentReport $situationalIncidentReport): JsonResponse
    {
        $this->authorize('delete', $situationalIncidentReport);

        $situationalIncidentReport->delete();

        return $this->responseOK([], 'Situational incident report deleted.');
    }

    /**
     * Removes the raw base64 diagram from the attributes and decodes it.
     *
     * @param  array<string, mixed>  $data
     */
    private function pullInjuryDiagram(array &$data): ?string
    {
        $raw = $data['injury_diagram'] ?? null;
        unset($data['injury_diagram']);

        if (! is_string($raw) || $raw === '') {
            return null;
        }

        $raw = preg_replace('#^data:image/png;base64,#', '', $raw);
        $binary = base64_decode($raw, true);

        // Only accept real PNG payloads.
        if ($binary === false || ! str_starts_with($binary, "\x89PNG\r\n\x1a\n")) {
            return null;
        }

        return $binary;
    }

    /**
     * Stores a new diagram (replacing the old file), or clears it when the
     * client sent an empty marker list.
     *
     * @param  array<string, mixed>  $data
     */
    private function applyInjuryDiagram(SituationalIncidentReport $report, ?string $diagram, array $data): void
    {
        $markersSent = array_key_exists('injury_markers', $data);
        $cleared = $markersSent && empty($data['injury_markers']);

        if ($diagram === null && ! $cleared) {
            return;
        }

        $old = $report->injury_diagram_path;
        $path = null;

        if ($diagram !== null && ! $cleared) {
            $path = 'injury_diagrams/report_'.$report->id.'_'.Str::random(12).'.png';
            Storage::disk('public')->put($path, $diagram);
        }

        $report->forceFill(['injury_diagram_path' => $path])->save();

        if ($old && $old !== $path) {
            Storage::disk('public')->delete($old);
        }
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
