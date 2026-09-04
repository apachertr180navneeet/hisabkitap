<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Bill extends Model
{
    use HasFactory;

    protected $fillable = [
        'bill_no',
        'pso_config_id',
        'pso_code',
        'tally_import_id',
        'business_date',
        'bill_time',
        'customer_name',
        'particulars',
        'amount',
        'payment_type',
        'voucher_type',
        'salesperson_id',
        'salesman_name',
        'cd_amount',
        'refund_amount',
        'net_amount',
        'status',
        'is_expected',
        'tally_found',
        'is_post_cutoff',
        'remark',
        'verified_by',
        'verified_at',
    ];

    protected $casts = [
        'business_date' => 'date',
        'amount' => 'decimal:2',
        'cd_amount' => 'decimal:2',
        'refund_amount' => 'decimal:2',
        'net_amount' => 'decimal:2',
        'is_expected' => 'boolean',
        'tally_found' => 'boolean',
        'is_post_cutoff' => 'boolean',
        'verified_at' => 'datetime',
    ];

    public function psoConfig()
    {
        return $this->belongsTo(PsoConfig::class, 'pso_config_id');
    }

    public function tallyImport()
    {
        return $this->belongsTo(TallyImport::class, 'tally_import_id');
    }

    public function salesperson()
    {
        return $this->belongsTo(Salesperson::class, 'salesperson_id');
    }

    public function corrections()
    {
        return $this->hasMany(Correction::class, 'bill_id');
    }

    public function creditCollection()
    {
        return $this->hasOne(CreditCollection::class, 'bill_id');
    }
}
