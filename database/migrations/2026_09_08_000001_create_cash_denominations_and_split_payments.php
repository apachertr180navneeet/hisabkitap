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
        // 1. Create Cash Denominations Table
        if (!Schema::hasTable('cash_denominations')) {
            Schema::create('cash_denominations', function (Blueprint $table) {
                $table->id();
                $table->date('business_date')->index();
                $table->foreignId('pso_config_id')->nullable()->constrained('pso_configs')->nullOnDelete();
                $table->string('pso_code')->nullable()->index();
                $table->string('driver_name')->nullable();
                $table->string('gadi_number')->nullable();
                
                // Notes count
                $table->integer('notes_2000')->default(0);
                $table->integer('notes_500')->default(0);
                $table->integer('notes_200')->default(0);
                $table->integer('notes_100')->default(0);
                $table->integer('notes_50')->default(0);
                $table->integer('notes_20')->default(0);
                $table->integer('notes_10')->default(0);
                $table->integer('notes_5')->default(0);
                $table->integer('notes_2')->default(0);
                $table->integer('notes_1')->default(0);
                
                // Coins and totals
                $table->decimal('coins_total', 14, 2)->default(0);
                $table->decimal('total_physical_cash', 14, 2)->default(0);
                
                // Travel / KM Details
                $table->decimal('total_km', 10, 2)->default(0);
                $table->decimal('km_rate', 10, 2)->default(0);
                $table->decimal('km_allowance_amount', 14, 2)->default(0);
                
                // Reconciliation amounts
                $table->decimal('book_cash_amount', 14, 2)->default(0);
                $table->decimal('short_cash_amount', 14, 2)->default(0);
                $table->decimal('excess_cash_amount', 14, 2)->default(0);
                
                $table->string('cashier_name')->nullable();
                $table->text('remarks')->nullable();
                $table->timestamps();
            });
        }

        // 2. Add split payment columns to bills table
        Schema::table('bills', function (Blueprint $table) {
            if (!Schema::hasColumn('bills', 'cash_amount')) {
                $table->decimal('cash_amount', 14, 2)->default(0)->after('amount');
            }
            if (!Schema::hasColumn('bills', 'paytm_amount')) {
                $table->decimal('paytm_amount', 14, 2)->default(0)->after('cash_amount');
            }
            if (!Schema::hasColumn('bills', 'is_split_payment')) {
                $table->boolean('is_split_payment')->default(false)->after('paytm_amount');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('cash_denominations');

        Schema::table('bills', function (Blueprint $table) {
            if (Schema::hasColumn('bills', 'cash_amount')) {
                $table->dropColumn('cash_amount');
            }
            if (Schema::hasColumn('bills', 'paytm_amount')) {
                $table->dropColumn('paytm_amount');
            }
            if (Schema::hasColumn('bills', 'is_split_payment')) {
                $table->dropColumn('is_split_payment');
            }
        });
    }
};
