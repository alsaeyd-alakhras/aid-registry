<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AidDistributionImportRow extends Model
{
    protected $fillable = [
        'batch_id',
        'row_number',
        'payload',
        'duplicate_in_file',
        'duplicate_in_db',
        'duplicate_details',
        'has_error',
        'error_messages',
        'decision',
        'created_distribution_id',
    ];

    protected $casts = [
        'payload' => 'array',
        'duplicate_details' => 'array',
        'error_messages' => 'array',
        'duplicate_in_file' => 'boolean',
        'duplicate_in_db' => 'boolean',
        'has_error' => 'boolean',
        'row_number' => 'integer',
    ];

    public function batch(): BelongsTo
    {
        return $this->belongsTo(AidDistributionImportBatch::class, 'batch_id');
    }

    public function createdDistribution(): BelongsTo
    {
        return $this->belongsTo(AidDistribution::class, 'created_distribution_id');
    }
}
