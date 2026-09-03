<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('financial_years', function (Blueprint $table) {
            $table->id();
            $table->string('name')->unique(); // e.g. '2026-2027' or 'FY 2026-27'
            $table->date('start_date');       // e.g. '2026-04-01'
            $table->date('end_date');         // e.g. '2027-03-31'
            $table->boolean('is_active')->default(false);
            $table->boolean('is_locked')->default(false); // Locked for accounting closures/audits
            $table->string('notes')->nullable();
            $table->timestamps();
        });

        // Seed initial standard financial years
        $now = now();
        $years = [
            [
                'name' => '2024-2025',
                'start_date' => '2024-04-01',
                'end_date' => '2025-03-31',
                'is_active' => false,
                'is_locked' => true,
                'notes' => 'Audited & Closed Financial Year',
                'created_at' => $now,
                'updated_at' => $now,
            ],
            [
                'name' => '2025-2026',
                'start_date' => '2025-04-01',
                'end_date' => '2026-03-31',
                'is_active' => false,
                'is_locked' => false,
                'notes' => 'Previous Financial Year',
                'created_at' => $now,
                'updated_at' => $now,
            ],
            [
                'name' => '2026-2027',
                'start_date' => '2026-04-01',
                'end_date' => '2027-03-31',
                'is_active' => true,
                'is_locked' => false,
                'notes' => 'Current Active Operating Year',
                'created_at' => $now,
                'updated_at' => $now,
            ],
            [
                'name' => '2027-2028',
                'start_date' => '2027-04-01',
                'end_date' => '2028-03-31',
                'is_active' => false,
                'is_locked' => false,
                'notes' => 'Upcoming Financial Year',
                'created_at' => $now,
                'updated_at' => $now,
            ],
        ];

        DB::table('financial_years')->insert($years);

        // Also ensure system_settings has initial sync values
        $existingFy = DB::table('system_settings')->where('key', 'financial_year')->first();
        if (!$existingFy) {
            DB::table('system_settings')->insert([
                ['key' => 'financial_year', 'value' => '2026-2027', 'description' => 'Current Active Financial Year', 'created_at' => $now, 'updated_at' => $now],
                ['key' => 'financial_year_start', 'value' => '2026-04-01', 'description' => 'Active Financial Year Start Date', 'created_at' => $now, 'updated_at' => $now],
                ['key' => 'financial_year_end', 'value' => '2027-03-31', 'description' => 'Active Financial Year End Date', 'created_at' => $now, 'updated_at' => $now],
            ]);
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('financial_years');
    }
};
