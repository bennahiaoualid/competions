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
        Schema::create('coin_pricing', function (Blueprint $table) {
            $table->id();
            $table->string('name', 100)->unique()->comment('Short, unique name for pricing rule');
            $table->string('display_name', 255)->nullable()->comment('Optional longer description');
            $table->enum('user_type', ['user', 'admin', 'both'])->comment('Type of user this pricing applies to');
            $table->decimal('base_amount', 10, 2)->comment('Money amount in DZD');
            $table->float('base_coins')->comment('Number of coins given for this amount');
            $table->boolean('is_active')->default(true)->comment('Whether this pricing is currently active');
            $table->foreignId('created_by_admin_id')->nullable()->constrained('admins')->nullOnDelete()->comment('Admin who created this pricing');
            $table->timestamps();

            // Indexes
            $table->index('user_type');
            $table->index('is_active');
            $table->index('name');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('coin_pricing');
    }
}; 