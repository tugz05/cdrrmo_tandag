<?php

namespace App\Observers;

use App\Models\Report;
use App\Services\LiveLocationService;
use App\Traits\JSharedTrait;

class ReportObserver
{
    use JSharedTrait;

    /**
     * Live sharing caches owner, rescuer and closed state: apply changes straight away.
     */
    public function saved(Report $report): void
    {
        LiveLocationService::forgetReport($report->id);
    }

    /**
     * Handle the Report "created" event.
     */
    public function created(Report $report): void
    {
        $this->toastSuccess('Report has been successfully added.');
    }

    /**
     * Handle the Report "updated" event.
     */
    public function updated(Report $report): void
    {
        $this->toastSuccess('Report has been successfully updated.');
    }

    /**
     * Handle the Report "deleted" event.
     */
    public function deleted(Report $report): void
    {
        LiveLocationService::forgetReport($report->id);
        $this->toastSuccess('Report has been successfully deleted.');
    }

    /**
     * Handle the Report "restored" event.
     */
    public function restored(Report $report): void
    {
        //
    }

    /**
     * Handle the Report "force deleted" event.
     */
    public function forceDeleted(Report $report): void
    {
        //
    }
}
