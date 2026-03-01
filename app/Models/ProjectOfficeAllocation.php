<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ProjectOfficeAllocation extends Model
{
    protected $fillable = [
        'project_id',
        'office_id',
        'max_beneficiaries',
        'max_amount',
        'max_quantity',
    ];

    protected $casts = [
        'max_beneficiaries' => 'integer',
        'max_amount' => 'decimal:2',
        'max_quantity' => 'decimal:2',
    ];

    public function project(): BelongsTo
    {
        return $this->belongsTo(Project::class);
    }

    public function office(): BelongsTo
    {
        return $this->belongsTo(Office::class);
    }
}
