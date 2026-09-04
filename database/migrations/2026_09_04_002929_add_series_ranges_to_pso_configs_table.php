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
            $table->json('series_ranges')->nullable()->after('financial_year');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('pso_configs', function (Blueprint $table) {
            $table->dropColumn('series_ranges');
        });
    }
};
