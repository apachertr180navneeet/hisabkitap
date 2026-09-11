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
            if (!Schema::hasColumn('pso_configs', 'is_closed')) {
                $table->boolean('is_closed')->default(false)->after('is_active');
            }
            if (!Schema::hasColumn('pso_configs', 'closed_at')) {
                $table->timestamp('closed_at')->nullable()->after('is_closed');
            }
            if (!Schema::hasColumn('pso_configs', 'closed_by')) {
                $table->foreignId('closed_by')->nullable()->after('closed_at')->constrained('users')->nullOnDelete();
            }
            if (!Schema::hasColumn('pso_configs', 'has_goods_return')) {
                $table->boolean('has_goods_return')->default(false)->after('closed_by');
            }
            if (!Schema::hasColumn('pso_configs', 'goods_return_amount')) {
                $table->decimal('goods_return_amount', 14, 2)->default(0)->after('has_goods_return');
            }
            if (!Schema::hasColumn('pso_configs', 'goods_return_bill_no')) {
                $table->string('goods_return_bill_no')->nullable()->after('goods_return_amount');
            }
            if (!Schema::hasColumn('pso_configs', 'goods_return_particulars')) {
                $table->text('goods_return_particulars')->nullable()->after('goods_return_bill_no');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('pso_configs', function (Blueprint $table) {
            $columns = [
                'is_closed',
                'closed_at',
                'closed_by',
                'has_goods_return',
                'goods_return_amount',
                'goods_return_bill_no',
                'goods_return_particulars',
            ];
            foreach ($columns as $col) {
                if (Schema::hasColumn('pso_configs', $col)) {
                    $table->dropColumn($col);
                }
            }
        });
    }
};
