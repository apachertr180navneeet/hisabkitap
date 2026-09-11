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
        Schema::table('users', function (Blueprint $table) {
            if (!Schema::hasColumn('users', 'can_manage_prefixes')) {
                $table->boolean('can_manage_prefixes')->default(true)->after('can_configure_pso');
            }
            if (!Schema::hasColumn('users', 'can_manage_salespersons')) {
                $table->boolean('can_manage_salespersons')->default(true)->after('can_manage_prefixes');
            }
            if (!Schema::hasColumn('users', 'can_create_pso')) {
                $table->boolean('can_create_pso')->default(true)->after('can_manage_salespersons');
            }
            if (!Schema::hasColumn('users', 'can_edit_pso')) {
                $table->boolean('can_edit_pso')->default(true)->after('can_create_pso');
            }
            if (!Schema::hasColumn('users', 'can_delete_pso')) {
                $table->boolean('can_delete_pso')->default(true)->after('can_edit_pso');
            }
            if (!Schema::hasColumn('users', 'can_close_pso')) {
                $table->boolean('can_close_pso')->default(true)->after('can_delete_pso');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn([
                'can_manage_prefixes',
                'can_manage_salespersons',
                'can_create_pso',
                'can_edit_pso',
                'can_delete_pso',
                'can_close_pso',
            ]);
        });
    }
};
