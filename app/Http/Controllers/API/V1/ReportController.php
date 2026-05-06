<?php

namespace App\Http\Controllers\API\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\ReportStoreRequest;
use App\Models\Report;
use App\Models\ReportImage;
use App\Traits\JResponseApiTrait;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class ReportController extends Controller
{
    use JResponseApiTrait;

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

            return $this->responseOK([], 'Report has been saved.');
        } catch (\Exception $exception) {
            return $this->responseError('An error has occured.');
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
