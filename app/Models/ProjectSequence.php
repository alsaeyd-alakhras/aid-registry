<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ProjectSequence extends Model
{
    protected $fillable = [
        'dependency_type',
        'office_id',
        'last_number',
    ];

    protected $casts = [
        'last_number' => 'integer',
    ];

    public function office(): BelongsTo
    {
        return $this->belongsTo(Office::class);
    }
}
