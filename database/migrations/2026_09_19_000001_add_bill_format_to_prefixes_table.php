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
        Schema::table('prefixes', function (Blueprint $table) {
            if (!Schema::hasColumn('prefixes', 'bill_format')) {
                $table->string('bill_format')->nullable()->default('{PREFIX}/{FY}/{NO}')->after('name');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('prefixes', function (Blueprint $table) {
            if (Schema::hasColumn('prefixes', 'bill_format')) {
                $table->dropColumn('bill_format');
            }
        });
    }
};
