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
        Schema::create('coin_offers', function (Blueprint $table) {
            $table->id();
            $table->string('name')->comment('Name of the offer');
            $table->text('description')->nullable()->comment('Description of the offer');
            $table->enum('user_type', ['user', 'admin', 'both'])->comment('Type of users this offer applies to');
            $table->integer('discount_percentage')->comment('Extra coins percentage (10 = 10% extra coins)');
            $table->decimal('min_amount', 10, 2)->nullable()->comment('Minimum purchase amount for offer');
            $table->decimal('max_amount', 10, 2)->nullable()->comment('Maximum purchase amount for offer');
            $table->timestamp('start_date')->comment('When the offer starts');
            $table->timestamp('end_date')->comment('When the offer ends');
            $table->boolean('is_active')->default(true)->comment('Whether this offer is currently active');
            $table->foreignId('created_by_admin_id')->constrained('admins')->onDelete('restrict')->comment('Admin who created this offer');
            $table->timestamps();

            // Indexes
            $table->index('user_type');
            $table->index('is_active');
            $table->index(['start_date', 'end_date']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('coin_offers');
    }
}; 