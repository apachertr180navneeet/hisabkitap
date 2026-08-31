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
        'payment_mode',
        'remark',
        'last_payment_date',
    ];

    protected $casts = [
        'bill_date' => 'date',
        'due_date' => 'date',
        'last_payment_date' => 'date',
        'bill_amount' => 'decimal:2',
        'paid_amount' => 'decimal:2',
        'outstanding_amount' => 'decimal:2',
    ];

    public function bill()
    {
        return $this->belongsTo(Bill::class, 'bill_id');
    }
}
