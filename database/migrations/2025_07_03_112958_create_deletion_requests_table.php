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
        Schema::create('deletion_requests', function (Blueprint $table) {
            $table->id();

            // Polymorphic relation
            $table->unsignedBigInteger('deletable_id');
            $table->string('deletable_type');
            $table->index(['deletable_type', 'deletable_id']);

            // Admin who requested deletion
            $table->foreignId('deleted_by_admin_id')->nullable()
                ->constrained('admins')->nullOnDelete();
            $table->string('snapshot_deleter_name')->nullable()->index();

            // Admin who approved
            $table->foreignId('approved_by_admin_id')->nullable()
                ->constrained('admins')->nullOnDelete();
            $table->string('snapshot_approver_name')->nullable()->index();

            // Snapshots of deletable (like name, email)
            $table->string('snapshot_name')->nullable()->index();

            // Reason + status
            $table->text('reason');
            $table->enum('status', ['pending', 'approved', 'rejected'])->default('pending')->index();

            $table->timestamp('requested_at')->useCurrent()->index();
            $table->timestamp('approved_at')->nullable()->index();

        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('deletion_requests');
    }
};
