<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class AidItem extends Model
{
    protected $fillable = [
        'name',
        'estimated_value',
        'description',
        'is_active',
    ];

    protected $casts = [
        'estimated_value' => 'decimal:2',
        'is_active' => 'boolean',
    ];

    public function distributions(): HasMany
    {
        return $this->hasMany(AidDistribution::class);
    }
}
