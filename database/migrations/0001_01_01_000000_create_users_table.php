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
        Schema::create('users', function (Blueprint $table) {
            $table->id();
            $table->string('code')->unique()->nullable();
            $table->string('name');
            $table->string('email')->unique();
            $table->timestamp('email_verified_at')->nullable();
            $table->string('password');
            $table->string('role_name')->default('Accountant (PSO Operator)');
            $table->string('role_code')->default('OPERATOR');
            $table->string('badge_color')->default('primary');
            $table->string('badge_class')->default('bg-primary');
            $table->string('avatar')->default('RS');
            $table->string('icon')->default('bi-person-badge-fill');
            $table->string('title')->nullable();
            $table->text('tagline')->nullable();
            $table->boolean('can_edit_bills')->default(true);
            $table->boolean('can_import_excel')->default(true);
            $table->boolean('can_record_corrections')->default(true);
            $table->boolean('can_record_credit')->default(true);
            $table->boolean('can_approve_sealing')->default(false);
            $table->boolean('can_configure_pso')->default(true);
            $table->boolean('can_edit_cutoff')->default(false);
            $table->boolean('is_read_only')->default(false);
            $table->json('responsibilities')->nullable();
            $table->json('restrictions')->nullable();
            $table->json('allowed_modules')->nullable();
            $table->rememberToken();
            $table->timestamps();
        });

        Schema::create('password_reset_tokens', function (Blueprint $table) {
            $table->string('email')->primary();
            $table->string('token');
            $table->timestamp('created_at')->nullable();
        });

        Schema::create('sessions', function (Blueprint $table) {
            $table->string('id')->primary();
            $table->foreignId('user_id')->nullable()->index();
            $table->string('ip_address', 45)->nullable();
            $table->text('user_agent')->nullable();
            $table->longText('payload');
            $table->integer('last_activity')->index();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('users');
        Schema::dropIfExists('password_reset_tokens');
        Schema::dropIfExists('sessions');
    }
};
