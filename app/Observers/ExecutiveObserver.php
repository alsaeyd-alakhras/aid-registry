<?php

namespace App\Observers;

use App\Models\Executive;
use App\Services\ActivityLogService;

class ExecutiveObserver
{
    /**
     * Handle the Executive "created" event.
     */
    public function created(Executive $executive): void
    {
        ActivityLogService::log(
            'Created',
            'Executive',
            "تم إضافة تنفيذ  .",
            null,
            $executive->toArray()
        );
    }

    /**
     * Handle the Executive "updated" event.
     */
    public function updated(Executive $executive): void
    {
        ActivityLogService::log(
            'Updated',
            'Executive',
            "تم تعديل تنفيذ .",
            $executive->getOriginal(),
            $executive->getChanges()
        );
    }

    /**
     * Handle the Executive "deleted" event.
     */
    public function deleted(Executive $executive): void
    {
        ActivityLogService::log(
            'Deleted',
            'Executive',
            "تم حذف تنفيذ .",
            $executive->toArray(),
            null
        );
    }

    /**
     * Handle the Executive "restored" event.
     */
    public function restored(Executive $executive): void
    {
        //
    }

    /**
     * Handle the Executive "force deleted" event.
     */
    public function forceDeleted(Executive $executive): void
    {
        //
    }
}
