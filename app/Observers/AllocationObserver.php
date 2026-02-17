<?php

namespace App\Observers;

use App\Models\Allocation;
use App\Services\ActivityLogService;

class AllocationObserver
{
    /**
     * Handle the Allocation "created" event.
     */
    public function created(Allocation $allocation): void
    {
        ActivityLogService::log(
            'Created',
            'Allocation',
            "تم إضافة تخصيص رقم موازنته : {{ $allocation->budget_number }}.",
            null,
            $allocation->toArray()
        );
    }

    /**
     * Handle the Allocation "updated" event.
     */
    public function updated(Allocation $allocation): void
    {
        ActivityLogService::log(
            'Updated',
            'Allocation',
            "تم تعديل تخصيص رقم موازنته : {{ $allocation->budget_number }}.",
            $allocation->getOriginal(),
            $allocation->getChanges()
        );
    }

    /**
     * Handle the Allocation "deleted" event.
     */
    public function deleted(Allocation $allocation): void
    {
        ActivityLogService::log(
            'Deleted',
            'Allocation',
            "تم حذف تخصيص رقم موازنته : {{ $allocation->budget_number }}.",
            $allocation->toArray(),
            null
        );    }

    /**
     * Handle the Allocation "restored" event.
     */
    public function restored(Allocation $allocation): void
    {
        //
    }

    /**
     * Handle the Allocation "force deleted" event.
     */
    public function forceDeleted(Allocation $allocation): void
    {
        //
    }
}
