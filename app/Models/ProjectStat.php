<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ProjectStat extends Model
{
    protected $table = 'project_stats_view';

    public $timestamps = false;

    protected $casts = [
        'total_quantity' => 'decimal:2',
        'consumed_quantity' => 'decimal:2',
        'remaining_quantity' => 'decimal:2',
        'total_amount_ils' => 'decimal:2',
        'consumed_amount' => 'decimal:2',
        'remaining_amount' => 'decimal:2',
        'estimated_amount' => 'decimal:2',
        'beneficiaries_total' => 'integer',
        'beneficiaries_consumed' => 'integer',
        'remaining_beneficiaries' => 'integer',
        'aid_distributions_count' => 'integer',
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
}
