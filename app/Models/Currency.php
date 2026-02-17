<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Currency extends Model
{
    use HasFactory;
    public $timestamps = false;
    protected $fillable = ['name', 'code','value','value_to_ils'];

    protected $casts = [
        'value' => 'decimal:6',
        'value_to_ils' => 'decimal:6'
    ];
}
