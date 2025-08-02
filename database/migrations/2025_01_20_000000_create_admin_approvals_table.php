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
        Schema::create('admin_approvals', function (Blueprint $table) {
            $table->id();
            
            // Admin being assigned
            $table->foreignId('admin_id')->constrained('admins')->cascadeOnDelete();
            
            // Polymorphic relation for the entity (Competition, Level, etc.)
            $table->unsignedBigInteger('entity_id');
            $table->string('entity_type');
            
            // Assignment type (string for flexibility)
            $table->string('type')->index();
            
            // Status
            $table->enum('status', ['pending', 'approved', 'rejected'])->default('pending')->index();
            
            // Timestamps
            $table->timestamps();
            
            // Indexes optimized for PowerGrid queries
            $table->index(['admin_id', 'created_at'], 'admin_approvals_admin_created_index');
            $table->index(['admin_id', 'status'], 'admin_approvals_admin_status_index');
            $table->index(['admin_id', 'type'], 'admin_approvals_admin_type_index');
            
            // 🆕 UNIQUE CONSTRAINT - Prevent duplicate pending approvals
            $table->unique(['admin_id', 'entity_type', 'entity_id', 'type'], 'unique_pending_approval');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('admin_approvals');
    }
}; 