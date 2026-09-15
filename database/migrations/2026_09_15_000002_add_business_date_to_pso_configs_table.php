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
            if (!Schema::hasColumn('pso_configs', 'business_date')) {
                $table->date('business_date')->nullable()->after('code')->index();
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('pso_configs', function (Blueprint $table) {
            if (Schema::hasColumn('pso_configs', 'business_date')) {
                $table->dropColumn('business_date');
            }
        });
    }
};
