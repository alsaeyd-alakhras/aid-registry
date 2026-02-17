<?php

namespace App\Models;

use App\Observers\ExecutiveObserver;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Executive extends Model
{
    use HasFactory;

    protected $fillable = [
        'implementation_date',
        'budget_number',
        'month',
        'broker_name',
        'account',
        'affiliate_name',
        'project_name',
        'detail',
        'item_name',
        'quantity',
        'price',
        'total_ils',
        'received',
        'notes',
        'amount_payments',
        'payment_mechanism',
        'payment_status',
        'executive_status',
        'user_id',
        'user_name',
        'manager_name',
        'allocation_id',
        'field',
    ];

    // relationsheps
    public function user(){
        return $this->belongsTo(User::class,'user_id');
    }

    public function allocation(){
        return $this->belongsTo(Allocation::class,'allocation_id');
    }
}
