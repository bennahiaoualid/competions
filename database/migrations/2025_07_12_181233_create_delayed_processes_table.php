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
        Schema::create('delayed_processes', function (Blueprint $table) {
            $table->id();
            $table->string('process_type', 50);
            $table->string('target_type', 50);
            $table->unsignedBigInteger('target_id');
            $table->unsignedBigInteger('initiator_id')->nullable();
            $table->json('context_data')->nullable();
            $table->integer('check_period_hours')->default(24);
            $table->timestamps();

            // Indexes for performance
            $table->index(['process_type', 'target_type', 'target_id', 'created_at'], 'idx_process_retry');
            $table->index(['created_at', 'check_period_hours'], 'idx_ready_for_retry');
            
            // Unique constraint to prevent duplicates
            $table->unique(['process_type', 'target_type', 'target_id'], 'uk_delayed_process_unique');
            
            // Foreign key constraints
            $table->foreign('initiator_id')->references('id')->on('admins')->onDelete('set null');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('delayed_processes');
    }
};
