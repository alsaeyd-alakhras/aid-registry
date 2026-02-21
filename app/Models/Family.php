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
        'spouses',
        'notes',
    ];

    protected $casts = [
        'family_members_count' => 'integer',
        'spouses' => 'array',
    ];

    public function getWivesAttribute(): array
    {
        $spouses = collect($this->spouses ?? [])
            ->map(function ($spouse) {
                $fullName = isset($spouse['full_name']) ? trim((string) $spouse['full_name']) : null;
                $nationalId = isset($spouse['national_id']) ? trim((string) $spouse['national_id']) : null;

                return [
                    'full_name' => $fullName !== '' ? $fullName : null,
                    'national_id' => $nationalId !== '' ? $nationalId : null,
                ];
            })
            ->filter(fn ($spouse) => !empty($spouse['full_name']) || !empty($spouse['national_id']))
            ->values()
            ->toArray();

        if (!empty($spouses)) {
            return $spouses;
        }

        if ($this->spouse_full_name || $this->spouse_national_id) {
            return [[
                'full_name' => $this->spouse_full_name,
                'national_id' => $this->spouse_national_id,
            ]];
        }

        return [];
    }

    public function distributions(): HasMany
    {
        return $this->hasMany(AidDistribution::class);
    }
}
