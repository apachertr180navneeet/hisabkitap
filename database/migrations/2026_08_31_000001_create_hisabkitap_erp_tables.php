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
        // 1. PSO Configurations
        Schema::create('pso_configs', function (Blueprint $table) {
            $table->id();
            $table->string('code')->unique(); // e.g. PSO-1
            $table->string('name'); // e.g. PSO 1 - Main Wholesale Counter
            $table->string('prefix'); // CB
            $table->integer('start_no')->default(1);
            $table->integer('end_no')->default(10);
            $table->json('specials')->nullable(); // ["ITC 01", "ITC 03"]
            $table->string('operator_name');
            $table->boolean('is_active')->default(true);
            $table->text('description')->nullable();
            $table->timestamps();
        });

        // 2. Tally Imports
        Schema::create('tally_imports', function (Blueprint $table) {
            $table->id();
            $table->string('filename');
            $table->date('business_date');
            $table->integer('total_records')->default(0);
            $table->decimal('total_amount', 14, 2)->default(0);
            $table->string('status')->default('Imported & Scanned');
            $table->string('operator_name')->nullable();
            $table->timestamps();
        });

        // 3. Bills Table
        Schema::create('bills', function (Blueprint $table) {
            $table->id();
            $table->string('bill_no')->index(); // e.g. CB 01
            $table->foreignId('pso_config_id')->nullable()->constrained('pso_configs')->nullOnDelete();
            $table->string('pso_code')->index(); // PSO-1
            $table->foreignId('tally_import_id')->nullable()->constrained('tally_imports')->nullOnDelete();
            $table->date('business_date')->index();
            $table->string('bill_time')->nullable(); // e.g. 11:15
            $table->string('customer_name');
            $table->decimal('amount', 14, 2)->default(0);
            $table->string('payment_type')->default('Cash'); // Cash, Paytm, Check, Credit, Cancelled
            $table->decimal('cd_amount', 14, 2)->default(0);
            $table->decimal('refund_amount', 14, 2)->default(0);
            $table->decimal('net_amount', 14, 2)->default(0);
            $table->string('status')->default('Matched'); // Matched, Missing, Mismatch, Duplicate, Cancelled, Next Day PSO, Counter Sale Check
            $table->boolean('is_expected')->default(true);
            $table->boolean('tally_found')->default(true);
            $table->boolean('is_post_cutoff')->default(false);
            $table->text('remark')->nullable();
            $table->string('verified_by')->nullable();
            $table->timestamp('verified_at')->nullable();
            $table->timestamps();
        });

        // 4. Corrections / Cash Discounts / Returns
        Schema::create('corrections', function (Blueprint $table) {
            $table->id();
            $table->string('corr_code')->unique(); // e.g. CORR-01
            $table->foreignId('bill_id')->nullable()->constrained('bills')->cascadeOnDelete();
            $table->string('bill_no');
            $table->decimal('original_amount', 14, 2)->default(0);
            $table->string('correction_type'); // Cash Discount, Goods Return, Refund, Bill Correction, Other
            $table->decimal('cd_amount', 14, 2)->default(0);
            $table->decimal('goods_return_amount', 14, 2)->default(0);
            $table->decimal('refund_amount', 14, 2)->default(0);
            $table->decimal('net_adjustment', 14, 2)->default(0);
            $table->text('reason');
            $table->string('approved_by');
            $table->timestamps();
        });

        // 5. Credit Collections
        Schema::create('credit_collections', function (Blueprint $table) {
            $table->id();
            $table->foreignId('bill_id')->nullable()->constrained('bills')->nullOnDelete();
            $table->string('bill_no');
            $table->string('customer_name');
            $table->string('salesman_name');
            $table->date('bill_date');
            $table->date('due_date')->nullable();
            $table->decimal('bill_amount', 14, 2)->default(0);
            $table->decimal('paid_amount', 14, 2)->default(0);
            $table->decimal('outstanding_amount', 14, 2)->default(0);
            $table->string('collection_status')->default('Pending'); // Pending, Partially Collected, Collected
            $table->string('payment_mode')->nullable();
            $table->text('remark')->nullable();
            $table->date('last_payment_date')->nullable();
            $table->timestamps();
        });

        // 6. PSO Daily Seals
        Schema::create('pso_daily_seals', function (Blueprint $table) {
            $table->id();
            $table->date('business_date')->unique();
            $table->decimal('tally_total', 14, 2)->default(0);
            $table->decimal('pso_total', 14, 2)->default(0);
            $table->decimal('difference', 14, 2)->default(0);
            $table->boolean('is_reconciled')->default(false);
            $table->boolean('is_sealed')->default(false);
            $table->string('sealed_by')->nullable();
            $table->string('seal_hash')->nullable();
            $table->timestamp('sealed_at')->nullable();
            $table->string('unsealed_by')->nullable();
            $table->text('unseal_reason')->nullable();
            $table->timestamp('unsealed_at')->nullable();
            $table->string('status')->default('Draft'); // Draft, Reconciled, Sealed & Approved, Unsealed
            $table->text('remarks')->nullable();
            $table->timestamps();
        });

        // 7. PSO 7-Day Retention Radar
        Schema::create('pso_retentions', function (Blueprint $table) {
            $table->id();
            $table->string('pso_code');
            $table->date('business_date');
            $table->string('created_date_formatted');
            $table->integer('days_remaining')->default(7);
            $table->decimal('total_amount', 14, 2)->default(0);
            $table->string('status')->default('Pending Approval');
            $table->string('badge_class')->default('bg-warning text-dark');
            $table->timestamps();
        });

        // 8. System Settings
        Schema::create('system_settings', function (Blueprint $table) {
            $table->id();
            $table->string('key')->unique();
            $table->text('value')->nullable();
            $table->string('description')->nullable();
            $table->timestamps();
        });

        // 9. System Audit Logs
        Schema::create('audit_logs', function (Blueprint $table) {
            $table->id();
            $table->string('user_name');
            $table->string('action'); // EXCEL_IMPORT, VERIFY_SEQUENCE, RECON_CHECK, SEAL_DAY, UNSEAL_DAY, CORRECTION_ADDED, etc.
            $table->text('details');
            $table->string('ip_address')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('audit_logs');
        Schema::dropIfExists('system_settings');
        Schema::dropIfExists('pso_retentions');
        Schema::dropIfExists('pso_daily_seals');
        Schema::dropIfExists('credit_collections');
        Schema::dropIfExists('corrections');
        Schema::dropIfExists('bills');
        Schema::dropIfExists('tally_imports');
        Schema::dropIfExists('pso_configs');
    }
};
