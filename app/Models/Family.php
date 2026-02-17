<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Family extends Model
{
    protected $fillable = [
        'national_id',
        'full_name',
        'phone',
        'family_members_count',
        'address',
        'marital_status',
        'spouse_national_id',
        'spouse_full_name',
        'notes',
    ];

    protected $casts = [
        'family_members_count' => 'integer',
    ];

    public function distributions(): HasMany
    {
        return $this->hasMany(AidDistribution::class);
    }
}
