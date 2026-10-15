<?php

namespace App\Observers;

use App\Models\Report;
use App\Services\RegionUserService;

class ReportObserver
{
    /**
     * Dès qu'un signalement est créé, on s'assure que
     * le compte mairie de la région existe.
     */
    public function created(Report $report): void
    {
        if (!empty($report->region_name)) {
            RegionUserService::findOrCreateForRegion($report->region_name);
        }
    }

    public function updated(Report $report): void
    {
        //
    }

    public function deleted(Report $report): void
    {
        //
    }

    public function restored(Report $report): void
    {
        //
    }

    public function forceDeleted(Report $report): void
    {
        //
    }
}
