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
        Schema::table('cash_denominations', function (Blueprint $table) {
            $colsToDrop = [];
            foreach (['notes_2000', 'notes_5', 'notes_2', 'notes_1'] as $col) {
                if (Schema::hasColumn('cash_denominations', $col)) {
                    $colsToDrop[] = $col;
                }
            }
            if (!empty($colsToDrop)) {
                $table->dropColumn($colsToDrop);
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('cash_denominations', function (Blueprint $table) {
            if (!Schema::hasColumn('cash_denominations', 'notes_2000')) {
                $table->integer('notes_2000')->default(0);
            }
            if (!Schema::hasColumn('cash_denominations', 'notes_5')) {
                $table->integer('notes_5')->default(0);
            }
            if (!Schema::hasColumn('cash_denominations', 'notes_2')) {
                $table->integer('notes_2')->default(0);
            }
            if (!Schema::hasColumn('cash_denominations', 'notes_1')) {
                $table->integer('notes_1')->default(0);
            }
        });
    }
};
