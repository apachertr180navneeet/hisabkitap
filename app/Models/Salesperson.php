<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Salesperson extends Model
{
    use HasFactory;

    protected $table = 'salespersons';

    protected $fillable = [
        'code',
        'name',
        'prefix_id',
        'prefix_code',
        'phone',
        'email',
        'area',
        'is_active',
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'prefix_id' => 'integer',
    ];

    /**
     * Get the bill prefix assigned to this salesperson.
     */
    public function prefix()
    {
        return $this->belongsTo(Prefix::class, 'prefix_id');
    }

    /**
     * Get all credit collections assigned to this salesperson by name.
     */
    public function creditCollections()
    {
        return $this->hasMany(CreditCollection::class, 'salesman_name', 'name');
    }

    /**
     * Get all bills assigned to this salesperson.
     */
    public function bills()
    {
        return $this->hasMany(Bill::class, 'salesperson_id');
    }
}
