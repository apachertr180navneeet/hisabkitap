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
        'bill_format',
        'description',
        'is_active',
    ];

    protected $casts = [
        'is_active' => 'boolean',
    ];

    /**
     * Standard bill format presets supported across the system.
     */
    public const FORMAT_PRESETS = [
        '{PREFIX}/{FY}/{NO}'     => '{PREFIX}/{FY}/{NO} (e.g. Sc/26-27/1, RB/26-27/1)',
        '{PREFIX}/{NO}/{FY}'     => '{PREFIX}/{NO}/{FY} (e.g. HS/1/26-27)',
        '{FY}/{PREFIX}/{NO}'     => '{FY}/{PREFIX}/{NO} (e.g. 26-27/PG/1, 26-27/AT/1)',
        '{PREFIX}/{FY}/{000000}' => '{PREFIX}/{FY}/{000000} (e.g. I/26-27/000001)',
        '{FY}/{NO}/{PREFIX}'     => '{FY}/{NO}/{PREFIX} (e.g. 26-27/1/PG)',
        '{PREFIX} {NO}'          => '{PREFIX} {NO} (e.g. CB 01, CB 15)',
        '{PREFIX}-{NO}'          => '{PREFIX}-{NO} (e.g. CB-01, SC-15)',
        '{PREFIX}{NO}'           => '{PREFIX}{NO} (e.g. CB15, SC001)',
    ];

    /**
     * Format a bill number string according to this prefix's configured format template.
     */
    public function formatBillNo(int|string $serialNo, ?string $fy = '26-27', ?string $customFormat = null): string
    {
        $template = $customFormat ?: ($this->bill_format ?: '{PREFIX}/{FY}/{NO}');
        $prefix = $this->prefix ?: 'CB';
        $fy = $fy ?: '26-27';

        // Check if zero padding is requested like {000000} or {0000}
        if (preg_match('/\{(0+)\}/', $template, $padMatches)) {
            $padLen = strlen($padMatches[1]);
            $formattedNum = sprintf("%0{$padLen}d", (int)$serialNo);
            $template = str_replace($padMatches[0], $formattedNum, $template);
        } else {
            $template = str_ireplace('{NO}', (string)$serialNo, $template);
        }

        $template = str_ireplace('{PREFIX}', $prefix, $template);
        $template = str_ireplace('{FY}', $fy, $template);

        return $template;
    }

    /**
     * Get a formatted sample bill number for UI preview.
     */
    public function getSampleBillNoAttribute(): string
    {
        return $this->formatBillNo(1, '26-27');
    }

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
