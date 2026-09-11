<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        if (Schema::hasTable('users')) {
            // Update any existing approver accounts to ensure PSO, Financial Workflow and Prefix/Salesperson permissions
            DB::table('users')->where('role_code', 'APPROVER')->update([
                'title' => 'Accounts Approver',
                'tagline' => 'PSO, Financial Workflow & Master Setup.',
                'can_configure_pso' => 1,
                'can_create_pso' => 1,
                'can_edit_pso' => 1,
                'can_delete_pso' => 1,
                'can_close_pso' => 1,
                'can_manage_prefixes' => 1,
                'can_manage_salespersons' => 1,
                'can_import_excel' => 1,
                'can_edit_bills' => 1,
                'can_record_corrections' => 1,
                'can_record_credit' => 1,
                'can_approve_sealing' => 0,
                'can_edit_cutoff' => 0,
                'can_manage_users' => 0,
                'allowed_modules' => json_encode([
                    'Dashboard',
                    'PSO Series Management',
                    'Tally Excel Import',
                    'Bill Verification',
                    'Cash Denomination',
                    'Payment Classification',
                    'Corrections / Returns',
                    'Credit Collection',
                    'PSO Summary',
                    'Prefix Master',
                    'Sales Persons'
                ]),
            ]);

            // Ensure operator accounts also have can_configure_pso set
            DB::table('users')->where('role_code', 'OPERATOR')->update([
                'can_configure_pso' => 1,
                'can_create_pso' => 1,
                'can_edit_pso' => 1,
                'can_delete_pso' => 1,
                'can_close_pso' => 1,
            ]);
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        //
    }
};
