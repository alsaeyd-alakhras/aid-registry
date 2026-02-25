<?php

namespace App\Services;

use App\Models\ProjectSequence;
use Illuminate\Support\Facades\DB;

class ProjectNumberService
{
    public function generateNumber(string $dependencyType, ?int $officeId = null): string
    {
        return DB::transaction(function () use ($dependencyType, $officeId) {
            $sequence = ProjectSequence::query()
                ->where('dependency_type', $dependencyType)
                ->where('office_id', $officeId)
                ->lockForUpdate()
                ->first();

            if (!$sequence) {
                $initialNumber = $dependencyType === 'admin' ? 0 : 9999;

                $sequence = ProjectSequence::create([
                    'dependency_type' => $dependencyType,
                    'office_id' => $officeId,
                    'last_number' => $initialNumber,
                ]);
            }

            $nextNumber = $sequence->last_number + 1;

            $sequence->update([
                'last_number' => $nextNumber,
            ]);

            return (string) $nextNumber;
        });
    }
}
