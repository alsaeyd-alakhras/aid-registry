<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Project extends Model
{
    protected $fillable = [
        'project_number',
        'name',
        'institution_id',
        'project_type',
        'aid_item_id',
        'total_quantity',
        'unit_value_ils',
        'total_amount_ils',
        'estimated_amount',
        'beneficiaries_total',
        'created_by',
        'dependency_type',
        'dependency_office_id',
        'notes',
        'project_date',
        'execution_date',
        'receipt_date',
        'department',
        'supervisor_name',
        'execution_location',
        'status',
    ];

    protected $guarded = [
        'consumed_quantity',
        'consumed_amount',
        'beneficiaries_consumed',
    ];

    protected $casts = [
        'total_quantity' => 'decimal:2',
        'unit_value_ils' => 'decimal:2',
        'consumed_quantity' => 'decimal:2',
        'total_amount_ils' => 'decimal:2',
        'consumed_amount' => 'decimal:2',
        'estimated_amount' => 'decimal:2',
        'beneficiaries_total' => 'integer',
        'beneficiaries_consumed' => 'integer',
        'project_date' => 'date',
        'execution_date' => 'date',
        'receipt_date' => 'date',
    ];

    public function scopeActive($query)
    {
        return $query->where('status', 'active');
    }

    public function institution(): BelongsTo
    {
        return $this->belongsTo(Institution::class);
    }

    public function aidItem(): BelongsTo
    {
        return $this->belongsTo(AidItem::class);
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function dependencyOffice(): BelongsTo
    {
        return $this->belongsTo(Office::class, 'dependency_office_id');
    }

    public function aidDistributions(): HasMany
    {
        return $this->hasMany(AidDistribution::class);
    }

    public function officeAllocations(): HasMany
    {
        return $this->hasMany(ProjectOfficeAllocation::class);
    }

    public function getRemainingQuantityAttribute(): float
    {
        return (float) ($this->total_quantity - $this->consumed_quantity);
    }

    public function getRemainingAmountAttribute(): float
    {
        return (float) ($this->total_amount_ils - $this->consumed_amount);
    }

    public function getRemainingBeneficiariesAttribute(): int
    {
        return (int) ($this->beneficiaries_total - $this->beneficiaries_consumed);
    }

    /** رصيد المكاتب: مجموع max_amount من التوزيعات على المكاتب. عند عدم وجود توزيعات: consumed */
    public function getOfficesBalanceAmountAttribute(): float
    {
        $allocations = $this->relationLoaded('officeAllocations') ? $this->officeAllocations : $this->officeAllocations()->get();
        if ($allocations->isEmpty()) {
            return (float) $this->consumed_amount;
        }

        return (float) $allocations->sum('max_amount');
    }

    /** رصيد المكاتب: مجموع max_quantity من التوزيعات على المكاتب. عند عدم وجود توزيعات: consumed */
    public function getOfficesBalanceQuantityAttribute(): float
    {
        $allocations = $this->relationLoaded('officeAllocations') ? $this->officeAllocations : $this->officeAllocations()->get();
        if ($allocations->isEmpty()) {
            return (float) $this->consumed_quantity;
        }

        return (float) $allocations->sum('max_quantity');
    }

    /** رصيد المخزن: الإجمالي - رصيد المكاتب. عند عدم وجود توزيعات: remaining */
    public function getStorageBalanceAmountAttribute(): float
    {
        $allocations = $this->relationLoaded('officeAllocations') ? $this->officeAllocations : $this->officeAllocations()->get();
        if ($allocations->isEmpty()) {
            return (float) $this->remaining_amount;
        }
        $officesBalance = (float) $allocations->sum('max_amount');

        return max(0, (float) $this->total_amount_ils - $officesBalance);
    }

    /** رصيد المخزن: الإجمالي - رصيد المكاتب. عند عدم وجود توزيعات: remaining */
    public function getStorageBalanceQuantityAttribute(): float
    {
        $allocations = $this->relationLoaded('officeAllocations') ? $this->officeAllocations : $this->officeAllocations()->get();
        if ($allocations->isEmpty()) {
            return (float) $this->remaining_quantity;
        }
        $officesBalance = (float) $allocations->sum('max_quantity');

        return max(0, (float) $this->total_quantity - $officesBalance);
    }
}
