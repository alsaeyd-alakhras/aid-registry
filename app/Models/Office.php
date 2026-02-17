<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Office extends Model
{
    protected $fillable = [
        'name',
        'location',
        'is_active',
        'notes',
    ];

    protected $casts = [
        'is_active' => 'boolean',
    ];

    public function distributions(): HasMany
    {
        return $this->hasMany(AidDistribution::class);
    }

    public function users(): HasMany
    {
        return $this->hasMany(User::class);
    }
}
