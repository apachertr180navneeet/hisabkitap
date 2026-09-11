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
            if (!Schema::hasColumn('pso_configs', 'created_by')) {
                $table->foreignId('created_by')->nullable()->after('operator_name')->constrained('users')->nullOnDelete();
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('pso_configs', function (Blueprint $table) {
            if (Schema::hasColumn('pso_configs', 'created_by')) {
                $table->dropColumn('created_by');
            }
        });
    }
};
