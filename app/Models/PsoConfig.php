<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class PsoConfig extends Model
{
    use HasFactory;

    protected $fillable = [
        'code',
        'prefix',
        'start_no',
        'end_no',
        'specials',
        'operator_name',
        'is_active',
        'description',
    ];

    protected $casts = [
        'specials' => 'array',
        'is_active' => 'boolean',
        'start_no' => 'integer',
        'end_no' => 'integer',
    ];

    public function bills()
    {
        return $this->hasMany(Bill::class, 'pso_config_id');
    }
}
