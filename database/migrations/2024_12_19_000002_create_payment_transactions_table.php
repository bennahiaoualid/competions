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
        Schema::create('payment_transactions', function (Blueprint $table) {
            $table->id();
            $table->uuid('uuid')->unique(); // Public-facing transaction ID
            
            // User making the payment (morph)
            $table->unsignedBigInteger('payable_id');
            $table->string('payable_type'); // 'App\Models\User' or 'App\Models\Admin\Admin'
            
            // Payment approval
            $table->unsignedBigInteger('approver_admin_id')->nullable(); // Accountant who approved
            $table->timestamp('approved_at')->nullable();
            
            // Payment details
            $table->decimal('amount', 10, 2); // Money amount (DZD)
            $table->integer('coins_credited'); // Coins given
            $table->enum('payment_method', ['cash', 'bank_transfer', 'mobile_money']);
            
            // Proof and status
            $table->string('proof_image_path')->nullable();
            $table->enum('status', ['pending', 'approved', 'rejected', 'cancelled'])->default('pending');
            
            // Accountant observation (simple text field)
            $table->text('accountant_observation')->nullable(); // Why approved/rejected/cancelled
            
            // Timestamps
            $table->timestamps();
            
            // Indexes (only custom indexes, not foreign keys)
            $table->index(['payable_id', 'payable_type'], 'idx_payment_transactions_payable');
            $table->index('status', 'idx_payment_transactions_status');
            $table->index('created_at', 'idx_payment_transactions_created_at');
            
            // Foreign keys (indexes created automatically)
            $table->foreign('approver_admin_id')->references('id')->on('admins')->onDelete('set null');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('payment_transactions');
    }
}; 