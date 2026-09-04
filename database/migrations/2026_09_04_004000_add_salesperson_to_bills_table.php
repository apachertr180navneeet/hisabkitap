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
        Schema::table('bills', function (Blueprint $table) {
            if (!Schema::hasColumn('bills', 'salesperson_id')) {
                $table->foreignId('salesperson_id')
                    ->nullable()
                    ->after('pso_code')
                    ->constrained('salespersons')
                    ->nullOnDelete();
            }
            if (!Schema::hasColumn('bills', 'salesman_name')) {
                $table->string('salesman_name')->nullable()->after('salesperson_id');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('bills', function (Blueprint $table) {
            if (Schema::hasColumn('bills', 'salesperson_id')) {
                $table->dropConstrainedForeignId('salesperson_id');
            }
            if (Schema::hasColumn('bills', 'salesman_name')) {
                $table->dropColumn('salesman_name');
            }
        });
    }
};
