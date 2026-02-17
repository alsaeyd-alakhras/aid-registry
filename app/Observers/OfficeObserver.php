<?php

namespace App\Observers;

use App\Models\Office;
use App\Services\ActivityLogService;

class OfficeObserver
{
    /**
     * Handle the Office "created" event.
     */
    public function created(Office $office): void
    {
        ActivityLogService::log(
            'Created',
            'Office',
            "تم إضافة المكتب : {{ $office->name }}.",
            null,
            $office->toArray()
        );
    }

    /**
     * Handle the Office "updated" event.
     */
    public function updated(Office $office): void
    {
        ActivityLogService::log(
            'Updated',
            'Office',
            "تم تعديل المكتب : {{ $office->name }}.",
            $office->getOriginal(),
            $office->getChanges()
        );
    }

    /**
     * Handle the Office "deleted" event.
     */
    public function deleted(Office $office): void
    {
        ActivityLogService::log(
            'Deleted',
            'Office',
            "تم حذف المكتب : {{ $office->name }}.",
            $office->toArray(),
            null
        );    }

    /**
     * Handle the Office "restored" event.
     */
    public function restored(Office $office): void
    {
        //
    }

    /**
     * Handle the Office "force deleted" event.
     */
    public function forceDeleted(Office $office): void
    {
        //
    }
}
