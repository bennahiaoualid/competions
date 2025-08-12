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
        Schema::create('coin_balances', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('balanceable_id');
            $table->string('balanceable_type'); // 'App\Models\User' or 'App\Models\Admin\Admin'
            $table->integer('balance')->default(0);
            $table->integer('total_earned')->default(0);
            $table->integer('total_spent')->default(0);
            $table->timestamps();
            
            // Indexes (only custom indexes, not unique constraints)
            $table->index(['balanceable_id', 'balanceable_type'], 'idx_balanceable');
            
            // Unique constraint (index created automatically)
            $table->unique(['balanceable_id', 'balanceable_type'], 'unique_balanceable');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('coin_balances');
    }
}; 