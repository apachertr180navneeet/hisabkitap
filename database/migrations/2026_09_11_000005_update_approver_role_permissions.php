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
            // Update any existing approver accounts to ensure PSO management permissions
            DB::table('users')->where('role_code', 'APPROVER')->update([
                'title' => 'Accounts Approver',
                'tagline' => 'PSO Series Management.',
                'can_configure_pso' => 1,
                'can_create_pso' => 1,
                'can_edit_pso' => 1,
                'can_delete_pso' => 1,
                'can_close_pso' => 1,
                'can_manage_prefixes' => 0,
                'can_manage_salespersons' => 0,
                'can_import_excel' => 0,
                'can_edit_bills' => 0,
                'can_record_corrections' => 0,
                'can_record_credit' => 0,
                'can_approve_sealing' => 0,
                'can_edit_cutoff' => 0,
                'can_manage_users' => 0,
                'allowed_modules' => json_encode(['Dashboard', 'PSO Series Management']),
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
