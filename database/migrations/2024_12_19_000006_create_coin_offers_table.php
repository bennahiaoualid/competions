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
            $table->foreignId('coin_pricing_id')->constrained('coin_pricing')->cascadeOnDelete()->comment('Link to coin pricing rule');
            $table->string('name')->comment('Name of the offer');
            $table->text('description')->nullable()->comment('Description of the offer');
            $table->integer('discount_percentage')->comment('Discount percentage (5-90% range)');
            $table->timestamp('start_date')->nullable()->comment('When the offer starts');
            $table->timestamp('end_date')->nullable()->comment('When the offer ends');
            $table->boolean('expired')->default(false)->comment('Whether this offer is expired');
            $table->foreignId('created_by_admin_id')->nullable()->constrained('admins')->nullOnDelete()->comment('Admin who created this offer');
            $table->timestamps();

            // Indexes
            $table->index('expired');
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