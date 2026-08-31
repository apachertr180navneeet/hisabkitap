<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class PsoRetention extends Model
{
    use HasFactory;

    protected $fillable = [
        'pso_code',
        'business_date',
        'created_date_formatted',
        'days_remaining',
        'total_amount',
        'status',
        'badge_class',
    ];

    protected $casts = [
        'business_date' => 'date',
        'days_remaining' => 'integer',
        'total_amount' => 'decimal:2',
    ];
}
