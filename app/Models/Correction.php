<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Correction extends Model
{
    use HasFactory;

    protected $fillable = [
        'corr_code',
        'bill_id',
        'bill_no',
        'original_amount',
        'correction_type',
        'cd_amount',
        'goods_return_amount',
        'refund_amount',
        'net_adjustment',
        'reason',
        'approved_by',
    ];

    protected $casts = [
        'original_amount' => 'decimal:2',
        'cd_amount' => 'decimal:2',
        'goods_return_amount' => 'decimal:2',
        'refund_amount' => 'decimal:2',
        'net_adjustment' => 'decimal:2',
    ];

    public function bill()
    {
        return $this->belongsTo(Bill::class, 'bill_id');
    }
}
