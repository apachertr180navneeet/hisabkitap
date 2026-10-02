<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class CreditCollection extends Model
{
    use HasFactory;

    protected $fillable = [
        'bill_id',
        'bill_no',
        'customer_name',
        'salesman_name',
        'bill_date',
        'due_date',
        'bill_amount',
        'paid_amount',
        'outstanding_amount',
        'collection_status',
        'is_udhari_synced',
        'udhari_synced_at',
        'udhari_api',
        'payment_mode',
        'remark',
        'last_payment_date',
    ];

    protected $casts = [
        'bill_date' => 'date',
        'due_date' => 'date',
        'last_payment_date' => 'date',
        'udhari_synced_at' => 'datetime',
        'is_udhari_synced' => 'boolean',
        'bill_amount' => 'decimal:2',
        'paid_amount' => 'decimal:2',
        'outstanding_amount' => 'decimal:2',
    ];

    public function bill()
    {
        return $this->belongsTo(Bill::class, 'bill_id');
    }

    /**
     * Get the bill prefix parsed from bill number or linked bill/pso config.
     */
    public function getBillPrefixAttribute(): string
    {
        $parsed = PsoConfig::parseBillNumber($this->bill_no ?? '');
        if (!empty($parsed['prefix'])) {
            return strtoupper(trim($parsed['prefix']));
        }

        if ($this->relationLoaded('bill') && $this->bill) {
            if ($this->bill->relationLoaded('psoConfig') && $this->bill->psoConfig?->prefix) {
                return strtoupper(trim($this->bill->psoConfig->prefix));
            }
            if ($this->bill->psoConfig?->prefix) {
                return strtoupper(trim($this->bill->psoConfig->prefix));
            }
        }

        return '';
    }

    /**
     * Scope query to filter credit collections by bill prefix.
     */
    public function scopeFilterPrefix($query, ?string $prefix)
    {
        if (empty($prefix) || strtoupper($prefix) === 'ALL') {
            return $query;
        }

        $pfx = trim($prefix);

        return $query->where(function ($q) use ($pfx) {
            $q->where('bill_no', 'like', $pfx . ' %')
              ->orWhere('bill_no', 'like', $pfx . '/%')
              ->orWhere('bill_no', 'like', $pfx . '-%')
              ->orWhere('bill_no', 'like', $pfx . '_%')
              ->orWhere('bill_no', 'like', '%/' . $pfx . '/%')
              ->orWhere('bill_no', 'like', '%/' . $pfx . '-%')
              ->orWhere('bill_no', 'like', '%-' . $pfx . '/%')
              ->orWhere('bill_no', 'like', '%-' . $pfx . '-%')
              ->orWhere('bill_no', 'like', '%/' . $pfx)
              ->orWhere('bill_no', 'like', '%-' . $pfx)
              ->orWhere('bill_no', 'like', '% ' . $pfx)
              ->orWhere('bill_no', '=', $pfx)
              ->orWhere('bill_no', 'like', $pfx . '%')
              ->orWhereHas('bill.psoConfig', function ($sq) use ($pfx) {
                  $sq->where('prefix', $pfx);
              });
        });
    }
}
