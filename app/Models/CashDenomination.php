<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class CashDenomination extends Model
{
    use HasFactory;

    protected $fillable = [
        'business_date',
        'pso_config_id',
        'pso_code',
        'driver_name',
        'gadi_number',
        'notes_500',
        'notes_200',
        'notes_100',
        'notes_50',
        'notes_20',
        'notes_10',
        'coins_total',
        'total_physical_cash',
        'total_km',
        'km_rate',
        'km_allowance_amount',
        'book_cash_amount',
        'short_cash_amount',
        'excess_cash_amount',
        'cashier_name',
        'remarks',
    ];

    protected $casts = [
        'business_date' => 'date',
        'notes_500' => 'integer',
        'notes_200' => 'integer',
        'notes_100' => 'integer',
        'notes_50' => 'integer',
        'notes_20' => 'integer',
        'notes_10' => 'integer',
        'coins_total' => 'decimal:2',
        'total_physical_cash' => 'decimal:2',
        'total_km' => 'decimal:2',
        'km_rate' => 'decimal:2',
        'km_allowance_amount' => 'decimal:2',
        'book_cash_amount' => 'decimal:2',
        'short_cash_amount' => 'decimal:2',
        'excess_cash_amount' => 'decimal:2',
    ];

    public function psoConfig()
    {
        return $this->belongsTo(PsoConfig::class, 'pso_config_id');
    }

    /**
     * Calculate total from notes and coins
     */
    public function calculateTotalNotes(): float
    {
        return ($this->notes_500 * 500)
            + ($this->notes_200 * 200)
            + ($this->notes_100 * 100)
            + ($this->notes_50 * 50)
            + ($this->notes_20 * 20)
            + ($this->notes_10 * 10);
    }
}
