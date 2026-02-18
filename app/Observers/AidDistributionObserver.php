<?php

namespace App\Observers;

use App\Models\AidDistribution;
use App\Services\ActivityLogService;

class AidDistributionObserver
{
    /**
     * Handle the AidDistribution "created" event.
     */
    public function created(AidDistribution $aidDistribution): void
    {
        ActivityLogService::log(
            'Created',
            'AidDistribution',
            "تم إضافة عملية صرف مساعدة رقم: {{ $aidDistribution->id }}.",
            null,
            $aidDistribution->toArray()
        );
    }

    /**
     * Handle the AidDistribution "updated" event.
     */
    public function updated(AidDistribution $aidDistribution): void
    {
        ActivityLogService::log(
            'Updated',
            'AidDistribution',
            "تم تعديل عملية صرف المساعدة رقم: {{ $aidDistribution->id }}.",
            $aidDistribution->getOriginal(),
            $aidDistribution->getChanges()
        );
    }

    /**
     * Handle the AidDistribution "deleted" event.
     */
    public function deleted(AidDistribution $aidDistribution): void
    {
        ActivityLogService::log(
            'Deleted',
            'AidDistribution',
            "تم حذف عملية صرف المساعدة رقم: {{ $aidDistribution->id }}.",
            $aidDistribution->toArray(),
            null
        );
    }

    /**
     * Handle the AidDistribution "restored" event.
     */
    public function restored(AidDistribution $aidDistribution): void
    {
        //
    }

    /**
     * Handle the AidDistribution "force deleted" event.
     */
    public function forceDeleted(AidDistribution $aidDistribution): void
    {
        //
    }
}
