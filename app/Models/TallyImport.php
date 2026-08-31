<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class TallyImport extends Model
{
    use HasFactory;

    protected $fillable = [
        'filename',
        'business_date',
        'total_records',
        'total_amount',
        'status',
        'operator_name',
    ];

    protected $casts = [
        'business_date' => 'date',
        'total_amount' => 'decimal:2',
        'total_records' => 'integer',
    ];

    public function bills()
    {
        return $this->hasMany(Bill::class, 'tally_import_id');
    }
}
