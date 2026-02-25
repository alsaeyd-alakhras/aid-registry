<?php

namespace App\Services;

use App\Models\AidDistribution;
use App\Models\Family;
use App\Models\Project;
use Carbon\Carbon;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class ProjectConsumptionService
{
    public function createDistribution(array $validated): AidDistribution
    {
        return DB::transaction(function () use ($validated) {
            $projectId = $validated['project_id'];

            $project = Project::query()
                ->whereKey($projectId)
                ->lockForUpdate()
                ->firstOrFail();

            $this->validateProjectConstraints($project, $validated, null);

            $family = $this->resolveFamilyForDistribution($validated);

            $distribution = AidDistribution::create([
                'family_id' => $family->id,
                'office_id' => $validated['office_id'],
                'institution_id' => $validated['institution_id'],
                'project_id' => $projectId,
                'aid_mode' => $validated['aid_mode'],
                'aid_item_id' => $validated['aid_mode'] === 'in_kind' ? $validated['aid_item_id'] : null,
                'quantity' => $validated['aid_mode'] === 'in_kind' ? $validated['quantity'] : null,
                'cash_amount' => $validated['aid_mode'] === 'cash' ? $validated['cash_amount'] : null,
                'distributed_at' => !empty($validated['distributed_date'])
                    ? Carbon::parse($validated['distributed_date'])->startOfDay()
                    : now()->startOfDay(),
                'created_by' => Auth::id(),
                'notes' => $validated['distribution_notes'] ?? null,
                'status' => 'active',
            ]);

            $this->incrementProjectConsumption($project, $validated);

            return $distribution;
        });
    }

    public function updateDistribution(AidDistribution $distribution, array $validated): AidDistribution
    {
        return DB::transaction(function () use ($distribution, $validated) {
            $oldProjectId = $distribution->project_id;
            $newProjectId = $validated['project_id'] ?? null;

            if ($oldProjectId === $newProjectId) {
                if ($newProjectId !== null) {
                    $project = Project::query()
                        ->whereKey($newProjectId)
                        ->lockForUpdate()
                        ->firstOrFail();

                    $this->validateProjectConstraintsForUpdate($project, $distribution, $validated);

                    $this->applyConsumptionDifference($project, $distribution, $validated);
                }
            } else {
                $projectsToLock = collect([$oldProjectId, $newProjectId])
                    ->filter()
                    ->unique()
                    ->sort()
                    ->values();

                $lockedProjects = Project::query()
                    ->whereIn('id', $projectsToLock)
                    ->orderBy('id')
                    ->lockForUpdate()
                    ->get()
                    ->keyBy('id');

                if ($oldProjectId && isset($lockedProjects[$oldProjectId])) {
                    $this->decrementProjectConsumption($lockedProjects[$oldProjectId], $distribution);
                }

                if ($newProjectId && isset($lockedProjects[$newProjectId])) {
                    $this->validateProjectConstraints($lockedProjects[$newProjectId], $validated, null);
                    $this->incrementProjectConsumption($lockedProjects[$newProjectId], $validated);
                }
            }

            $distribution->family->update($this->extractFamilyData($validated));

            $distribution->update([
                'office_id' => $validated['office_id'],
                'institution_id' => $validated['institution_id'],
                'project_id' => $newProjectId,
                'aid_mode' => $validated['aid_mode'],
                'aid_item_id' => $validated['aid_mode'] === 'in_kind' ? $validated['aid_item_id'] : null,
                'quantity' => $validated['aid_mode'] === 'in_kind' ? $validated['quantity'] : null,
                'cash_amount' => $validated['aid_mode'] === 'cash' ? $validated['cash_amount'] : null,
                'distributed_at' => !empty($validated['distributed_date'])
                    ? Carbon::parse($validated['distributed_date'])->startOfDay()
                    : $distribution->distributed_at,
                'notes' => $validated['distribution_notes'] ?? null,
            ]);

            return $distribution->fresh();
        });
    }

    public function deleteDistribution(AidDistribution $distribution): void
    {
        DB::transaction(function () use ($distribution) {
            $projectId = $distribution->project_id;

            if ($projectId !== null) {
                $project = Project::query()
                    ->whereKey($projectId)
                    ->lockForUpdate()
                    ->firstOrFail();

                $this->decrementProjectConsumption($project, $distribution);
            }

            $distribution->delete();
        });
    }

    private function validateProjectConstraints(Project $project, array $validated, ?AidDistribution $existingDistribution): void
    {
        if ($project->remaining_beneficiaries <= 0) {
            throw ValidationException::withMessages([
                'project_id' => "المشروع {$project->name} ممتلئ (لا مستفيدين متبقيين).",
            ]);
        }

        if ($project->project_type === 'cash') {
            if ($validated['aid_mode'] !== 'cash') {
                throw ValidationException::withMessages([
                    'aid_mode' => 'المشروع نقدي، يجب اختيار مساعدة نقدية.',
                ]);
            }

            $requestedAmount = (float) $validated['cash_amount'];
            if ($requestedAmount > $project->remaining_amount) {
                throw ValidationException::withMessages([
                    'cash_amount' => "المبلغ يتجاوز المتبقي في المشروع ({$project->remaining_amount} ₪).",
                ]);
            }
        }

        if ($project->project_type === 'in_kind') {
            if ($validated['aid_mode'] !== 'in_kind') {
                throw ValidationException::withMessages([
                    'aid_mode' => 'المشروع عيني، يجب اختيار مساعدة عينية.',
                ]);
            }

            if ((int) $validated['aid_item_id'] !== (int) $project->aid_item_id) {
                throw ValidationException::withMessages([
                    'aid_item_id' => 'نوع المساعدة لا يطابق نوع المشروع.',
                ]);
            }

            $requestedQuantity = (float) $validated['quantity'];
            if ($requestedQuantity > $project->remaining_quantity) {
                throw ValidationException::withMessages([
                    'quantity' => "الكمية تتجاوز المتبقي في المشروع ({$project->remaining_quantity}).",
                ]);
            }
        }
    }

    private function validateProjectConstraintsForUpdate(Project $project, AidDistribution $distribution, array $validated): void
    {
        $oldCashAmount = (float) ($distribution->cash_amount ?? 0);
        $newCashAmount = (float) ($validated['cash_amount'] ?? 0);
        $oldQuantity = (float) ($distribution->quantity ?? 0);
        $newQuantity = (float) ($validated['quantity'] ?? 0);

        if ($project->project_type === 'cash') {
            $difference = $newCashAmount - $oldCashAmount;
            if ($difference > $project->remaining_amount) {
                throw ValidationException::withMessages([
                    'cash_amount' => "الزيادة في المبلغ ({$difference} ₪) تتجاوز المتبقي ({$project->remaining_amount} ₪).",
                ]);
            }
        }

        if ($project->project_type === 'in_kind') {
            $difference = $newQuantity - $oldQuantity;
            if ($difference > $project->remaining_quantity) {
                throw ValidationException::withMessages([
                    'quantity' => "الزيادة في الكمية ({$difference}) تتجاوز المتبقي ({$project->remaining_quantity}).",
                ]);
            }
        }
    }

    private function incrementProjectConsumption(Project $project, array $validated): void
    {
        $increments = ['beneficiaries_consumed' => 1];

        if ($project->project_type === 'cash') {
            $increments['consumed_amount'] = (float) $validated['cash_amount'];
        } else {
            $increments['consumed_quantity'] = (float) $validated['quantity'];
        }

        $project->increment('beneficiaries_consumed', 1);
        if (isset($increments['consumed_amount'])) {
            $project->increment('consumed_amount', $increments['consumed_amount']);
        }
        if (isset($increments['consumed_quantity'])) {
            $project->increment('consumed_quantity', $increments['consumed_quantity']);
        }
    }

    private function decrementProjectConsumption(Project $project, AidDistribution $distribution): void
    {
        $project->decrement('beneficiaries_consumed', 1);

        if ($project->project_type === 'cash' && $distribution->cash_amount !== null) {
            $project->decrement('consumed_amount', (float) $distribution->cash_amount);
        }

        if ($project->project_type === 'in_kind' && $distribution->quantity !== null) {
            $project->decrement('consumed_quantity', (float) $distribution->quantity);
        }
    }

    private function applyConsumptionDifference(Project $project, AidDistribution $distribution, array $validated): void
    {
        if ($project->project_type === 'cash') {
            $oldAmount = (float) ($distribution->cash_amount ?? 0);
            $newAmount = (float) ($validated['cash_amount'] ?? 0);
            $difference = $newAmount - $oldAmount;

            if ($difference > 0) {
                $project->increment('consumed_amount', $difference);
            } elseif ($difference < 0) {
                $project->decrement('consumed_amount', abs($difference));
            }
        }

        if ($project->project_type === 'in_kind') {
            $oldQuantity = (float) ($distribution->quantity ?? 0);
            $newQuantity = (float) ($validated['quantity'] ?? 0);
            $difference = $newQuantity - $oldQuantity;

            if ($difference > 0) {
                $project->increment('consumed_quantity', $difference);
            } elseif ($difference < 0) {
                $project->decrement('consumed_quantity', abs($difference));
            }
        }
    }

    private function resolveFamilyForDistribution(array $validated): Family
    {
        $nationalId = $validated['national_id'];
        $resolutionMode = $validated['resolution_mode'] ?? null;

        $primaryMatch = Family::query()->where('national_id', $nationalId)->first();
        $spouseMatch = $this->findFamilyBySpouseNationalId($nationalId);

        if ($primaryMatch) {
            $primaryMatch->update($this->extractFamilyData($validated));
            return $primaryMatch;
        }

        if ($spouseMatch) {
            if (!$resolutionMode) {
                throw new \Exception('يجب اختيار طريقة التعامل مع الأسرة الموجودة');
            }

            if ($resolutionMode === 'attach_to_existing') {
                return $spouseMatch;
            }

            if ($resolutionMode === 'create_new_family') {
                return Family::create($this->extractFamilyData($validated));
            }
        }

        return Family::create($this->extractFamilyData($validated));
    }

    private function findFamilyBySpouseNationalId(string $nationalId): ?Family
    {
        return Family::query()
            ->where(function ($query) use ($nationalId) {
                $query->where('wife_1_national_id_gen', $nationalId)
                    ->orWhere('wife_2_national_id_gen', $nationalId)
                    ->orWhere('wife_3_national_id_gen', $nationalId)
                    ->orWhere('wife_4_national_id_gen', $nationalId)
                    ->orWhere('spouse_national_id', $nationalId);
            })
            ->first();
    }

    private function extractFamilyData(array $validated): array
    {
        $status = $validated['marital_status'];
        $spouses = in_array($status, ['married', 'polygamous'], true)
            ? $this->normalizedSpousesFromInput($validated)
            : [];
        $firstSpouse = $spouses[0] ?? null;

        return [
            'full_name' => $validated['primary_name'] ?? $validated['full_name'] ?? null,
            'national_id' => $validated['national_id'],
            'phone' => $validated['mobile'] ?? null,
            'family_members_count' => $validated['family_members_count'] ?? null,
            'address' => $validated['housing_location'] ?? null,
            'marital_status' => $status,
            'spouses' => !empty($spouses) ? $spouses : null,
            'spouse_full_name' => $firstSpouse['full_name'] ?? null,
            'spouse_national_id' => $firstSpouse['national_id'] ?? null,
        ];
    }

    private function normalizedSpousesFromInput(array $validated): array
    {
        $spouses = collect($validated['spouses'] ?? [])->map(function ($spouse) {
            $fullName = isset($spouse['full_name']) ? trim((string) $spouse['full_name']) : null;
            $nationalId = isset($spouse['national_id']) ? trim((string) $spouse['national_id']) : null;

            return [
                'full_name' => $fullName !== '' ? $fullName : null,
                'national_id' => $nationalId !== '' ? $nationalId : null,
            ];
        });

        $legacySpouseName = trim((string) ($validated['spouse_name'] ?? ''));
        $legacySpouseNationalId = trim((string) ($validated['spouse_national_id'] ?? ''));
        if ($spouses->isEmpty() && ($legacySpouseName !== '' || $legacySpouseNationalId !== '')) {
            $spouses = collect([[
                'full_name' => $legacySpouseName !== '' ? $legacySpouseName : null,
                'national_id' => $legacySpouseNationalId !== '' ? $legacySpouseNationalId : null,
            ]]);
        }

        return $spouses
            ->filter(fn ($spouse) => !empty($spouse['full_name']) || !empty($spouse['national_id']))
            ->take(4)
            ->values()
            ->toArray();
    }
}
