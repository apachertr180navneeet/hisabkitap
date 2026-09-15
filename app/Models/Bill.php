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
        'cash_amount',
        'paytm_amount',
        'is_split_payment',
        'status',
        'is_expected',
        'tally_found',
        'is_post_cutoff',
        'remark',
        'expected_series',
        'mismatch_status',
        'is_mismatch_approved',
        'mismatch_approved_by',
        'mismatch_approved_at',
        'mismatch_approval_reason',
        'mismatch_rejected_by',
        'mismatch_rejected_at',
        'mismatch_rejection_reason',
        'verified_by',
        'verified_at',
    ];

    protected $casts = [
        'business_date' => 'date',
        'amount' => 'decimal:2',
        'cd_amount' => 'decimal:2',
        'refund_amount' => 'decimal:2',
        'net_amount' => 'decimal:2',
        'cash_amount' => 'decimal:2',
        'paytm_amount' => 'decimal:2',
        'is_split_payment' => 'boolean',
        'is_expected' => 'boolean',
        'tally_found' => 'boolean',
        'is_post_cutoff' => 'boolean',
        'is_mismatch_approved' => 'boolean',
        'mismatch_approved_at' => 'datetime',
        'mismatch_rejected_at' => 'datetime',
        'verified_at' => 'datetime',
    ];

    /**
     * Check if the bill has a series mismatch
     */
    public function isSeriesMismatch(): bool
    {
        return $this->status === 'Bill Series Mismatch' 
            || $this->mismatch_status === 'Bill Series Mismatch'
            || stripos((string)$this->mismatch_status, 'Series Mismatch') !== false;
    }

    /**
     * Check if the bill has a duplicate or cross-PSO mismatch
     */
    public function isPsoMismatch(): bool
    {
        return $this->status === 'Duplicate / PSO Mismatch' 
            || $this->mismatch_status === 'Duplicate / PSO Mismatch'
            || stripos((string)$this->mismatch_status, 'Duplicate') !== false
            || stripos((string)$this->mismatch_status, 'PSO Mismatch') !== false;
    }

    /**
     * Check if any mismatch has been formally approved by an authorized user
     */
    public function isMismatchApproved(): bool
    {
        return (bool) ($this->is_mismatch_approved || $this->mismatch_status === 'Approved');
    }

    /**
     * Check if the bill currently has an active unapproved mismatch
     */
    public function hasUnapprovedMismatch(): bool
    {
        if ($this->isMismatchApproved()) {
            return false;
        }

        return $this->isSeriesMismatch() 
            || $this->isPsoMismatch() 
            || in_array($this->status, ['Bill Series Mismatch', 'Duplicate / PSO Mismatch', 'Mismatch']);
    }

    /**
     * Check if the bill is considered valid for PSO accounting & reconciliation
     */
    public function isValidForReconciliation(): bool
    {
        if ($this->status === 'Cancelled' || $this->status === 'Missing') {
            return false;
        }

        if ($this->hasUnapprovedMismatch()) {
            return false;
        }

        return true;
    }

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
