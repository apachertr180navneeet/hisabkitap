<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Schema;

class FinancialYear extends Model
{
    use HasFactory;

    protected $table = 'financial_years';

    protected $fillable = [
        'name',
        'start_date',
        'end_date',
        'is_active',
        'is_locked',
        'notes',
    ];

    protected $casts = [
        'start_date' => 'date',
        'end_date'   => 'date',
        'is_active'  => 'boolean',
        'is_locked'  => 'boolean',
    ];

    /**
     * Retrieve current active financial year model.
     */
    public static function getActive()
    {
        try {
            if (!Schema::hasTable('financial_years')) {
                return null;
            }

            $active = static::where('is_active', true)->first();
            if (!$active) {
                $active = static::orderBy('start_date', 'desc')->first();
            }
            return $active;
        } catch (\Throwable $e) {
            return null;
        }
    }

    /**
     * Mark a financial year as active and sync to system_settings.
     */
    public static function setActiveById($id): ?self
    {
        $target = static::find($id);
        if (!$target) {
            return null;
        }

        // Set all to inactive
        static::query()->update(['is_active' => false]);

        // Mark target as active
        $target->is_active = true;
        $target->save();

        // Sync with SystemSetting
        SystemSetting::setVal('financial_year', $target->name, 'Current Active Financial Year');
        SystemSetting::setVal('financial_year_start', $target->start_date->format('Y-m-d'), 'Active Financial Year Start Date');
        SystemSetting::setVal('financial_year_end', $target->end_date->format('Y-m-d'), 'Active Financial Year End Date');

        return $target;
    }

    /**
     * Formatted date range: e.g. "01/04/2026 - 31/03/2027"
     */
    public function getFormattedRangeAttribute(): string
    {
        $start = $this->start_date ? $this->start_date->format('d/m/Y') : '';
        $end   = $this->end_date ? $this->end_date->format('d/m/Y') : '';
        return "{$start} – {$end}";
    }
}
