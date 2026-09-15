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
            if (!Schema::hasColumn('bills', 'expected_series')) {
                $table->string('expected_series')->nullable()->after('status');
            }
            if (!Schema::hasColumn('bills', 'mismatch_status')) {
                $table->string('mismatch_status')->nullable()->after('expected_series');
            }
            if (!Schema::hasColumn('bills', 'is_mismatch_approved')) {
                $table->boolean('is_mismatch_approved')->default(false)->after('mismatch_status');
            }
            if (!Schema::hasColumn('bills', 'mismatch_approved_by')) {
                $table->string('mismatch_approved_by')->nullable()->after('is_mismatch_approved');
            }
            if (!Schema::hasColumn('bills', 'mismatch_approved_at')) {
                $table->timestamp('mismatch_approved_at')->nullable()->after('mismatch_approved_by');
            }
            if (!Schema::hasColumn('bills', 'mismatch_approval_reason')) {
                $table->text('mismatch_approval_reason')->nullable()->after('mismatch_approved_at');
            }
            if (!Schema::hasColumn('bills', 'mismatch_rejected_by')) {
                $table->string('mismatch_rejected_by')->nullable()->after('mismatch_approval_reason');
            }
            if (!Schema::hasColumn('bills', 'mismatch_rejected_at')) {
                $table->timestamp('mismatch_rejected_at')->nullable()->after('mismatch_rejected_by');
            }
            if (!Schema::hasColumn('bills', 'mismatch_rejection_reason')) {
                $table->text('mismatch_rejection_reason')->nullable()->after('mismatch_rejected_at');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('bills', function (Blueprint $table) {
            $columnsToDrop = [
                'expected_series',
                'mismatch_status',
                'is_mismatch_approved',
                'mismatch_approved_by',
                'mismatch_approved_at',
                'mismatch_approval_reason',
                'mismatch_rejected_by',
                'mismatch_rejected_at',
                'mismatch_rejection_reason',
            ];
            foreach ($columnsToDrop as $col) {
                if (Schema::hasColumn('bills', $col)) {
                    $table->dropColumn($col);
                }
            }
        });
    }
};
