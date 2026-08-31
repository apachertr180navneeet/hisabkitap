<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class PsoDailySeal extends Model
{
    use HasFactory;

    protected $fillable = [
        'business_date',
        'tally_total',
        'pso_total',
        'difference',
        'is_reconciled',
        'is_sealed',
        'sealed_by',
        'seal_hash',
        'sealed_at',
        'unsealed_by',
        'unseal_reason',
        'unsealed_at',
        'status',
        'remarks',
    ];

    protected $casts = [
        'business_date' => 'date',
        'tally_total' => 'decimal:2',
        'pso_total' => 'decimal:2',
        'difference' => 'decimal:2',
        'is_reconciled' => 'boolean',
        'is_sealed' => 'boolean',
        'sealed_at' => 'datetime',
        'unsealed_at' => 'datetime',
    ];
}
