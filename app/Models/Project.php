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
        'total_amount_ils',
        'estimated_amount',
        'beneficiaries_total',
        'created_by',
        'dependency_type',
        'dependency_office_id',
        'notes',
    ];

    protected $guarded = [
        'consumed_quantity',
        'consumed_amount',
        'beneficiaries_consumed',
    ];

    protected $casts = [
        'total_quantity' => 'decimal:2',
        'consumed_quantity' => 'decimal:2',
        'total_amount_ils' => 'decimal:2',
        'consumed_amount' => 'decimal:2',
        'estimated_amount' => 'decimal:2',
        'beneficiaries_total' => 'integer',
        'beneficiaries_consumed' => 'integer',
    ];

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
}
