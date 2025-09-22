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
        Schema::create('competitions', function (Blueprint $table) {
            $table->id();
            $table->string('title');
            $table->string('slug')->unique();
            $table->text('description')->nullable();
            $table->foreignId('admin_id')->nullable()->constrained('admins')->nullOnDelete();
            $table->dateTime('start_date');
            $table->integer('age_start');
            $table->integer('age_end');
            $table->integer('levels_number');
            $table->enum('status', ['pending','active','finished'])->default('pending')->comment('pending,active,finished');
            $table->enum('participants_sync_status', ['pending', 'in_progress', 'completed', 'failed'])->default('pending');
            $table->boolean('is_suspended')->default(false);
            $table->timestamp('last_synced_at')->nullable();
            $table->integer('winner_gifts')->default(0)->comment('Number of coins the winner gets');
            $table->boolean('multi_winner')->default(false)->comment('If 2nd and 3rd place also get rewards');
            $table->boolean('ai_auditing')->default(false)->comment('If auditing in this competition will be admins or AI');
            $table->integer('auditing_time_for_level')->default(30)->comment('Minutes after level end before auto-assign/confirm AI audit');
            $table->timestamps();

            // OPTIMIZED INDEXES based on our analysis:
    
            // For default admin dashboard: ORDER BY start_date (no WHERE clause)
            $table->index('start_date');
            
            // For admin filtered queries: status + date sorting + ownership
            $table->index(['status', 'start_date', 'admin_id']);
            $table->index(['is_suspended', 'admin_id']);

            
            // For user browsing: active, non-suspended competitions by date
            $table->index(['status', 'is_suspended', 'start_date']);
            
            // For age range filtering (user interface)
            $table->index(['age_start', 'age_end', 'status', 'is_suspended']);
            
            // For search optimization (PowerGrid search with is_suspended filter)
            $table->index(['is_suspended', 'title']);
            
            // Optional: Full-text search for better search performance (uncomment if needed)
            // $table->fullText(['title', 'description']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('competitions');
    }
};
