<?php

namespace App\Models;

use App\Observers\AllocationObserver;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Allocation extends Model
{
    use HasFactory;

    protected $fillable = [
        'date_allocation',
        'budget_number',
        'broker_name',
        'organization_name',
        'project_name',
        'item_name',
        'quantity',
        'price',
        'total_dollar',
        'allocation',
        'currency_allocation',
        'currency_allocation_value',
        'amount',
        'implementation_items',
        'date_implementation',
        'implementation_statement',
        'amount_received',
        'notes',
        'number_beneficiaries',
        'arrest_receipt_number',
        'user_id',
        'user_name',
        'manager_name',
        'exchange_rate',
        'percentage_female_administrators',
    ];


    // relationsheps
    public function user(){
        return $this->belongsTo(User::class,'user_id');
    }

}
