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
        'created_by',
        'driver_name',
        'helper_1',
        'helper_2',
        'helper_3',
        'gadi_number',
        'vehicle_no',
        'is_active',
        'is_closed',
        'closed_at',
        'closed_by',
        'has_goods_return',
        'goods_return_amount',
        'goods_return_bill_no',
        'goods_return_particulars',
        'description',
    ];

    protected $casts = [
        'specials' => 'array',
        'series_ranges' => 'array',
        'is_active' => 'boolean',
        'is_closed' => 'boolean',
        'has_goods_return' => 'boolean',
        'goods_return_amount' => 'decimal:2',
        'closed_at' => 'datetime',
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
                'prefix' => $this->prefix ?: 'CB',
                'financial_year' => $this->financial_year ?? '2026-2027',
                'start_no' => (int) ($this->start_no ?: 1),
                'end_no' => (int) ($this->end_no ?: 10),
            ]
        ];
    }

    /**
     * Human-readable summary of all allowed series ranges and specials for this PSO
     */
    public function getFormattedSeriesSummaryAttribute(): string
    {
        $ranges = $this->getAllSeriesRanges();
        $parts = [];

        foreach ($ranges as $r) {
            $pfx = strtoupper(trim($r['prefix'] ?? 'CB'));
            $start = $r['start_no'] ?? 1;
            $end = $r['end_no'] ?? 10;
            $parts[] = "{$pfx} {$start} - {$end}";
        }

        if (!empty($this->specials) && is_array($this->specials)) {
            foreach ($this->specials as $sp) {
                if (trim((string)$sp) !== '') {
                    $parts[] = trim((string)$sp);
                }
            }
        }

        return !empty($parts) ? implode(', ', $parts) : ($this->prefix . ' ' . $this->start_no . ' - ' . $this->end_no);
    }

    /**
     * Validate whether a bill number belongs to this PSO's assigned series range or specials,
     * and check for cross-PSO duplicate or assignment mismatches.
     *
     * @param string $billNo
     * @param string|null $businessDate
     * @param int|null $ignoreBillId
     * @return array
     */
    public function validateBillNumber(string $billNo, ?string $businessDate = null, ?int $ignoreBillId = null): array
    {
        $rawBillNo = trim($billNo);
        $expectedSeries = $this->formatted_series_summary;

        if (empty($rawBillNo)) {
            return [
                'valid' => false,
                'mismatch_type' => 'Bill Series Mismatch',
                'expected_series' => $expectedSeries,
                'details' => 'Empty or blank bill number.',
                'matched_pso' => null,
            ];
        }

        // Normalize bill number parts
        $billPrefix = '';
        $billNum = null;
        $matchesThisPso = false;

        // 1. Check against specials (e.g. "ITC 01", "SPL-5")
        if (!empty($this->specials) && is_array($this->specials)) {
            foreach ($this->specials as $special) {
                $cleanSpecial = strtoupper(preg_replace('/\s+/', ' ', trim((string)$special)));
                $cleanInput = strtoupper(preg_replace('/\s+/', ' ', $rawBillNo));
                if ($cleanSpecial !== '' && $cleanSpecial === $cleanInput) {
                    $matchesThisPso = true;
                    break;
                }
            }
        }

        // 2. Parse Prefix and Serial Number (e.g. "CB 01" -> "CB", 1; "CB-15" -> "CB", 15; "CB15" -> "CB", 15)
        if (!$matchesThisPso) {
            if (preg_match('/^\s*([A-Za-z]+)[\s\-_]*0*(\d+)\s*$/', $rawBillNo, $matches)) {
                $billPrefix = strtoupper(trim($matches[1]));
                $billNum = (int)$matches[2];

                foreach ($this->getAllSeriesRanges() as $range) {
                    $rangePrefix = strtoupper(trim($range['prefix'] ?? ''));
                    $rangeStart = (int)($range['start_no'] ?? 1);
                    $rangeEnd = (int)($range['end_no'] ?? 10);

                    if ($rangePrefix === $billPrefix && $billNum >= $rangeStart && $billNum <= $rangeEnd) {
                        $matchesThisPso = true;
                        break;
                    }
                }
            }
        }

        // 3. If it matches this PSO, check for duplicate bill in another PSO
        if ($matchesThisPso) {
            $duplicateQuery = Bill::where('bill_no', $rawBillNo)
                ->where('pso_code', '!=', $this->code);

            if ($businessDate) {
                $duplicateQuery->whereDate('business_date', $businessDate);
            }
            if ($ignoreBillId) {
                $duplicateQuery->where('id', '!=', $ignoreBillId);
            }

            $otherBill = $duplicateQuery->first();
            if ($otherBill) {
                return [
                    'valid' => false,
                    'mismatch_type' => 'Duplicate / PSO Mismatch',
                    'expected_series' => $expectedSeries,
                    'details' => "Duplicate bill '{$rawBillNo}' is already assigned to {$otherBill->pso_code} on " . ($otherBill->business_date ? $otherBill->business_date->format('d/m/Y') : 'selected date') . ".",
                    'matched_pso' => $otherBill->pso_code,
                ];
            }

            return [
                'valid' => true,
                'mismatch_type' => null,
                'expected_series' => $expectedSeries,
                'details' => "Bill '{$rawBillNo}' matches assigned series ({$expectedSeries}).",
                'matched_pso' => $this->code,
            ];
        }

        // 4. Outside assigned series: Check if it belongs to another PSO config
        $otherPsoList = self::where('id', '!=', $this->id)->get();
        foreach ($otherPsoList as $otherPso) {
            // Check specials of other PSO
            if (!empty($otherPso->specials) && is_array($otherPso->specials)) {
                foreach ($otherPso->specials as $special) {
                    $cleanSpecial = strtoupper(preg_replace('/\s+/', ' ', trim((string)$special)));
                    $cleanInput = strtoupper(preg_replace('/\s+/', ' ', $rawBillNo));
                    if ($cleanSpecial !== '' && $cleanSpecial === $cleanInput) {
                        return [
                            'valid' => false,
                            'mismatch_type' => 'Duplicate / PSO Mismatch',
                            'expected_series' => $expectedSeries,
                            'details' => "Bill '{$rawBillNo}' belongs to {$otherPso->code} ({$otherPso->formatted_series_summary}), but was entered under {$this->code}.",
                            'matched_pso' => $otherPso->code,
                        ];
                    }
                }
            }

            // Check ranges of other PSO
            if ($billPrefix !== '' && $billNum !== null) {
                foreach ($otherPso->getAllSeriesRanges() as $range) {
                    $rangePrefix = strtoupper(trim($range['prefix'] ?? ''));
                    $rangeStart = (int)($range['start_no'] ?? 1);
                    $rangeEnd = (int)($range['end_no'] ?? 10);

                    if ($rangePrefix === $billPrefix && $billNum >= $rangeStart && $billNum <= $rangeEnd) {
                        return [
                            'valid' => false,
                            'mismatch_type' => 'Duplicate / PSO Mismatch',
                            'expected_series' => $expectedSeries,
                            'details' => "Bill '{$rawBillNo}' belongs to {$otherPso->code} ({$otherPso->formatted_series_summary}), but was entered under {$this->code}.",
                            'matched_pso' => $otherPso->code,
                        ];
                    }
                }
            }
        }

        // 5. General Bill Series Mismatch (out of range / unconfigured)
        return [
            'valid' => false,
            'mismatch_type' => 'Bill Series Mismatch',
            'expected_series' => $expectedSeries,
            'details' => "Bill '{$rawBillNo}' falls outside assigned bill series range ({$expectedSeries}).",
            'matched_pso' => null,
        ];
    }

    public function bills()
    {
        return $this->hasMany(Bill::class, 'pso_config_id');
    }

    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function closer()
    {
        return $this->belongsTo(User::class, 'closed_by');
    }

    public function getCreatedByNameAttribute(): ?string
    {
        return $this->creator?->name ?? (is_numeric($this->created_by) ? ('User #' . $this->created_by) : $this->created_by);
    }

    public function getClosedByNameAttribute(): ?string
    {
        return $this->closer?->name ?? (is_numeric($this->closed_by) ? ('User #' . $this->closed_by) : $this->closed_by);
    }
}
