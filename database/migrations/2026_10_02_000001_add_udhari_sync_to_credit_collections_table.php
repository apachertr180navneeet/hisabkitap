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
        Schema::table('credit_collections', function (Blueprint $table) {
            if (!Schema::hasColumn('credit_collections', 'is_udhari_synced')) {
                $table->boolean('is_udhari_synced')->default(false)->after('collection_status');
            }
            if (!Schema::hasColumn('credit_collections', 'udhari_synced_at')) {
                $table->timestamp('udhari_synced_at')->nullable()->after('is_udhari_synced');
            }
            if (!Schema::hasColumn('credit_collections', 'udhari_api')) {
                $table->string('udhari_api')->nullable()->after('udhari_synced_at');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('credit_collections', function (Blueprint $table) {
            $table->dropColumn(['is_udhari_synced', 'udhari_synced_at', 'udhari_api']);
        });
    }
};
