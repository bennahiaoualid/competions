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
        Schema::create('coin_transactions', function (Blueprint $table) {
            $table->id();
            
            // Transaction details
            $table->unsignedBigInteger('transactionable_id');
            $table->string('transactionable_type'); // 'App\Models\User' or 'App\Models\Admin\Admin'
            $table->enum('type', ['earn', 'spend']);
            $table->integer('amount'); // Number of coins
            $table->string('detail'); // Transaction type from enum
            
            // Timestamps
            $table->timestamp('processed_at')->nullable();
            $table->timestamps();
            
            // Indexes (only necessary ones)
            $table->index(['transactionable_id', 'type'], 'idx_transactionable_type');
            $table->index('processed_at', 'idx_processed_at');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('coin_transactions');
    }
}; 