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
            $table->string('driver_name')->nullable()->after('operator_name');
            $table->string('helper_1')->nullable()->after('driver_name');
            $table->string('helper_2')->nullable()->after('helper_1');
            $table->string('helper_3')->nullable()->after('helper_2');
            $table->string('gadi_number')->nullable()->after('helper_3');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('pso_configs', function (Blueprint $table) {
            $table->dropColumn([
                'driver_name',
                'helper_1',
                'helper_2',
                'helper_3',
                'gadi_number',
            ]);
        });
    }
};
