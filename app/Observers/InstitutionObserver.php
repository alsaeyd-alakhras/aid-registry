<?php

namespace App\Observers;

use App\Models\Institution;
use App\Services\ActivityLogService;

class InstitutionObserver
{
    public function created(Institution $institution): void
    {
        ActivityLogService::log(
            'Created',
            'Institution',
            "تم إضافة المؤسسة : {{ $institution->name }}.",
            null,
            $institution->toArray()
        );
    }

    public function updated(Institution $institution): void
    {
        ActivityLogService::log(
            'Updated',
            'Institution',
            "تم تعديل المؤسسة : {{ $institution->name }}.",
            $institution->getOriginal(),
            $institution->getChanges()
        );
    }

    public function deleted(Institution $institution): void
    {
        ActivityLogService::log(
            'Deleted',
            'Institution',
            "تم حذف المؤسسة : {{ $institution->name }}.",
            $institution->toArray(),
            null
        );
    }

    public function restored(Institution $institution): void
    {
        //
    }

    public function forceDeleted(Institution $institution): void
    {
        //
    }
}
