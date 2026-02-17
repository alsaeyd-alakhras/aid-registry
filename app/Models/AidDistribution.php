<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AidDistribution extends Model
{
    protected $fillable = [
        'family_id',
        'office_id',
        'aid_mode',
        'aid_item_id',
        'cash_amount',
        'distributed_at',
        'created_by',
        'status',
        'cancelled_at',
        'cancelled_by',
        'notes',
    ];

    protected $casts = [
        'cash_amount'    => 'decimal:2',
        'distributed_at' => 'datetime',
        'cancelled_at'   => 'datetime',
    ];

    public function family(): BelongsTo
    {
        return $this->belongsTo(Family::class);
    }

    public function office(): BelongsTo
    {
        return $this->belongsTo(Office::class);
    }

    public function aidItem(): BelongsTo
    {
        return $this->belongsTo(AidItem::class, 'aid_item_id');
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function canceller(): BelongsTo
    {
        return $this->belongsTo(User::class, 'cancelled_by');
    }
}
