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
        'financial_year',
        'series_ranges',
        'start_no',
        'end_no',
        'specials',
        'operator_name',
        'driver_name',
        'helper_1',
        'helper_2',
        'helper_3',
        'gadi_number',
        'vehicle_no',
        'is_active',
        'description',
    ];

    protected $casts = [
        'specials' => 'array',
        'series_ranges' => 'array',
        'is_active' => 'boolean',
        'start_no' => 'integer',
        'end_no' => 'integer',
    ];

    public function getVehicleNoAttribute(): ?string
    {
        return $this->gadi_number;
    }

    public function setVehicleNoAttribute($value): void
    {
        $this->attributes['gadi_number'] = $value;
    }

    /**
     * Get active non-empty helper names
     */
    public function getHelpersListAttribute(): array
    {
        return array_values(array_filter([
            $this->helper_1,
            $this->helper_2,
            $this->helper_3,
        ]));
    }

    /**
     * Get formatted comma-separated helper text
     */
    public function getHelpersTextAttribute(): string
    {
        return implode(', ', $this->helpers_list);
    }

    /**
     * Get all configured series ranges (including fallback to primary range)
     */
    public function getAllSeriesRanges(): array
    {
        if (!empty($this->series_ranges) && is_array($this->series_ranges)) {
            return $this->series_ranges;
        }

        return [
            [
                'prefix' => $this->prefix,
                'financial_year' => $this->financial_year ?? '2026-2027',
                'start_no' => (int) $this->start_no,
                'end_no' => (int) $this->end_no,
            ]
        ];
    }

    public function bills()
    {
        return $this->hasMany(Bill::class, 'pso_config_id');
    }
}
