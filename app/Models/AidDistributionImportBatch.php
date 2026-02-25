<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Str;

class AidDistributionImportBatch extends Model
{
    protected $fillable = [
        'uuid',
        'filename',
        'uploaded_by',
        'status',
        'total_rows',
        'valid_rows',
        'duplicate_rows',
        'error_rows',
        'errors',
        'completed_at',
    ];

    protected $casts = [
        'errors' => 'array',
        'completed_at' => 'datetime',
        'total_rows' => 'integer',
        'valid_rows' => 'integer',
        'duplicate_rows' => 'integer',
        'error_rows' => 'integer',
    ];

    protected static function booted()
    {
        static::creating(function ($batch) {
            if (empty($batch->uuid)) {
                $batch->uuid = (string) Str::uuid();
            }
        });
    }

    public function uploader(): BelongsTo
    {
        return $this->belongsTo(User::class, 'uploaded_by');
    }

    public function rows(): HasMany
    {
        return $this->hasMany(AidDistributionImportRow::class, 'batch_id');
    }
}
