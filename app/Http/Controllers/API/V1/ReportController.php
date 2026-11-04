<?php

namespace App\Http\Controllers\API\V1;

use App\Enums\AppMobileRole;
use App\Enums\ReportTypeEnum;
use App\Events\MessageReportReceived;
use App\Http\Controllers\Controller;
use App\Http\Requests\ReportStoreRequest;
use App\Models\Report;
use App\Models\ReportImage;
use App\Traits\JResponseApiTrait;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Symfony\Component\HttpFoundation\Response;

class ReportController extends Controller
{
    use JResponseApiTrait;

    /**
     * All call/message reports (staff app). Citizens may only use {@see history}.
     */
    public function historyAll(Request $request): JsonResponse
    {
        if ($request->user()->mobileApiAppRole() !== AppMobileRole::Staff) {
            return $this->responseError(
                'Only staff may view all report history.',
                [],
                Response::HTTP_FORBIDDEN
            );
        }

        $reports = Report::query()
            ->with([
                'user' => static fn ($q) => $q->select('id', 'name', 'phone', 'email'),
                'report_images',
            ])
            ->orderByDesc('id')
            ->get();

        return $this->responseOK($reports);
    }

    /**
     * Single call/message report for Flutter (owner or staff).
     */
    public function show(Request $request, Report $report): JsonResponse
    {
        $user = $request->user();
        $isOwner = (int) $report->user_id === (int) $user->id;
        $isStaff = $user->mobileApiAppRole() === AppMobileRole::Staff;

        if (! $isOwner && ! $isStaff) {
            return $this->responseError('You may not view this report.', [], Response::HTTP_FORBIDDEN);
        }

        $report->load([
            'report_images',
            'user' => static fn ($q) => $q->select('id', 'name', 'phone', 'email'),
        ]);

        return $this->responseOK($report);
    }

    public function history(Request $request, int $userId)
    {
        if ((int) $request->user()->id !== $userId) {
            return $this->responseError('You may only view your own report history.', [], Response::HTTP_FORBIDDEN);
        }

        $reports = Report::whereUserId($userId)->get();

        return $this->responseOK($reports);
    }

    public function store(ReportStoreRequest $request)
    {
        $validatedData = $request->validated();
        $validatedData['user_id'] = $request->user()->id;
        $validatedData['status'] = 'Pending';

        try {
            if (! empty($validatedData['id'])) {
                $report = Report::where('id', $validatedData['id'])
                    ->where('user_id', $request->user()->id)
                    ->firstOrFail();

                $report->update(
                    collect($validatedData)
                        ->except(['id', 'images'])
                        ->all()
                );
            } else {
                unset($validatedData['id']);
                $report = Report::create($validatedData);
            }

            $this->storeImages($report, $request);

            if ($report->wasRecentlyCreated && strcasecmp((string) $report->type, ReportTypeEnum::MESSAGE) === 0) {
                $this->broadcastMessageReport($report);
            }

            return $this->responseOK([], 'Report has been saved.');
        } catch (\Exception $exception) {
            return $this->responseError('An error has occured.');
        }
    }

    /**
     * The report is already saved; a WebSocket outage must not fail the citizen's submission.
     */
    private function broadcastMessageReport(Report $report): void
    {
        try {
            MessageReportReceived::dispatch($report);
        } catch (\Throwable $exception) {
            Log::warning('Message report broadcast failed', [
                'report_id' => $report->id,
                'error' => $exception->getMessage(),
            ]);
        }
    }

    private function storeImages(Report $report, ReportStoreRequest $request)
    {
        if ($request->hasFile('images')) {
            foreach ($request->file('images') as $image) {
                $path = $image->store('report_images', 'public');
                ReportImage::create([
                    'report_id' => $report->id,
                    'filename' => $path,
                ]);
            }
        }
    }
}
