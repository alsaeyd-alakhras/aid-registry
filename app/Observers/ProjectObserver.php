<?php

namespace App\Observers;

use App\Models\Project;
use App\Services\ActivityLogService;

class ProjectObserver
{
    public function created(Project $project): void
    {
        ActivityLogService::log(
            'Created',
            'Project',
            "تم إضافة مشروع رقم: {$project->project_number}.",
            null,
            $project->toArray()
        );
    }

    public function updated(Project $project): void
    {
        ActivityLogService::log(
            'Updated',
            'Project',
            "تم تعديل المشروع رقم: {$project->project_number}.",
            $project->getOriginal(),
            $project->getChanges()
        );
    }

    public function deleted(Project $project): void
    {
        ActivityLogService::log(
            'Deleted',
            'Project',
            "تم حذف المشروع رقم: {$project->project_number}.",
            $project->toArray(),
            null
        );
    }
}
