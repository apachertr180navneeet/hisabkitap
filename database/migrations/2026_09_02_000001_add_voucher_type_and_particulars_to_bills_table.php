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
            if (!Schema::hasColumn('bills', 'particulars')) {
                $table->string('particulars')->nullable()->after('customer_name');
            }
            if (!Schema::hasColumn('bills', 'voucher_type')) {
                $table->string('voucher_type')->nullable()->after('payment_type');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('bills', function (Blueprint $table) {
            if (Schema::hasColumn('bills', 'particulars')) {
                $table->dropColumn('particulars');
            }
            if (Schema::hasColumn('bills', 'voucher_type')) {
                $table->dropColumn('voucher_type');
            }
        });
    }
};
