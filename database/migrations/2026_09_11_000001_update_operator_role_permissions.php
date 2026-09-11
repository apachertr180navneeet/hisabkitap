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
        // Update any existing operator accounts to ensure they have the new restricted permissions
        DB::table('users')->where('role_code', 'OPERATOR')->update([
            'title' => 'PSO Operator',
            'tagline' => 'Dashboard and PSO Management (Create & List View Only).',
            'can_configure_pso' => 1,
            'can_import_excel' => 0,
            'can_edit_bills' => 0,
            'can_record_corrections' => 0,
            'can_record_credit' => 0,
            'can_approve_sealing' => 0,
            'can_edit_cutoff' => 0,
            'can_manage_users' => 0,
            'allowed_modules' => json_encode(['Dashboard', 'PSO Series Management']),
        ]);
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        //
    }
};
