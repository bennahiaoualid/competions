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
