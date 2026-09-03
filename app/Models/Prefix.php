<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Prefix extends Model
{
    use HasFactory;

    protected $fillable = [
        'code',
        'prefix',
        'name',
        'description',
        'is_active',
    ];

    protected $casts = [
        'is_active' => 'boolean',
    ];

    /**
     * Get PSO configs that use this prefix.
     */
    public function psoConfigs()
    {
        return $this->hasMany(PsoConfig::class, 'prefix', 'prefix');
    }

    /**
     * Get salespersons linked with this prefix.
     */
    public function salespersons()
    {
        return $this->hasMany(Salesperson::class, 'prefix_id');
    }

    /**
     * Get the primary salesperson linked to this prefix.
     */
    public function salesperson()
    {
        return $this->hasOne(Salesperson::class, 'prefix_id');
    }
}
