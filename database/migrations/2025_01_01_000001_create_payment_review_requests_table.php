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
        Schema::create('payment_review_requests', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('payment_transaction_id');
            $table->text('request_reason');
            $table->enum('status', ['pending', 'approved', 'rejected'])->default('pending');
            $table->unsignedBigInteger('reviewed_by_admin_id')->nullable();
            $table->timestamp('reviewed_at')->nullable();
            $table->text('review_observation')->nullable();
            $table->timestamps();

            // Indexes
            $table->index('status', 'idx_payment_review_requests_status');
            $table->index('created_at', 'idx_payment_review_requests_created_at');

            // Foreign keys
            $table->foreign('payment_transaction_id')
                ->references('id')->on('payment_transactions')
                ->onDelete('cascade');

            $table->foreign('reviewed_by_admin_id')
                ->references('id')->on('admins')
                ->onDelete('set null');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('payment_review_requests');
    }
}; 