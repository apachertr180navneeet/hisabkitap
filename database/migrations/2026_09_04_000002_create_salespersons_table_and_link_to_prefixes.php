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
        // Create Salespersons Table with prefix_id link
        Schema::create('salespersons', function (Blueprint $table) {
            $table->id();
            $table->string('code')->unique();            // e.g. SP-01
            $table->string('name');                      // e.g. Rajesh Kumar
            $table->foreignId('prefix_id')
                ->nullable()
                ->constrained('prefixes')
                ->nullOnDelete();
            $table->string('prefix_code')->nullable();   // e.g. CB, RB (for fast reference)
            $table->string('phone')->nullable();         // e.g. 9876543210
            $table->string('email')->nullable();
            $table->string('area')->nullable();          // e.g. Central Market / Counter 1
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('salespersons');
    }
};
