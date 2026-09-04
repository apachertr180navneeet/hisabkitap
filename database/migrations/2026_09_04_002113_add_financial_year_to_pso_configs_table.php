<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('pso_configs', function (Blueprint $table) {
            $table->string('financial_year', 20)->nullable()->default('2026-2027')->after('prefix');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('pso_configs', function (Blueprint $table) {
            $table->dropColumn('financial_year');
        });
    }
};
