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
            $table->string('slug')->index();
            $table->text('description')->nullable();
            $table->foreignId('admin_id')->nullable()->constrained('admins')->nullOnDelete();
            $table->dateTime('start_date');
            $table->integer('age_start')->index();
            $table->integer('age_end');
            $table->integer('levels_number');
            $table->enum('status', ['pending','active','finished'])->default('pending')->index()->comment('pending,active,finished');
            $table->enum('participants_sync_status', ['pending', 'in_progress', 'completed', 'failed'])->default('pending');
            $table->boolean('is_suspended')->default(false)->index();
            $table->timestamp('last_synced_at')->nullable();
            $table->integer('winner_gifts')->default(0)->comment('Number of coins the winner gets');
            $table->boolean('multi_winner')->default(false)->comment('If 2nd and 3rd place also get rewards');
            $table->boolean('ai_auditing')->default(false)->comment('If auditing in this competition will be admins or AI');
            $table->integer('auditing_time_for_level')->default(30)->comment('Minutes after level end before auto-assign/confirm AI audit');
            $table->timestamps();
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
