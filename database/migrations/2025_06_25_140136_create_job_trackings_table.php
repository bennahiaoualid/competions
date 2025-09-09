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
        Schema::create('job_trackings', function (Blueprint $table) {
            $table->id();
            $table->string('job_id')->unique();
            $table->string('job_class');
            $table->string('job_type');
            $table->enum('status', ['pending', 'processing', 'completed', 'failed'])->default('pending');            
            $table->json('payload')->nullable();
            $table->string('payload_hash')->nullable();
            $table->json('result')->nullable();
            $table->text('error_message')->nullable();
            $table->integer('attempts')->default(0);
            $table->integer('max_attempts')->default(3);
            $table->timestamp('started_at')->nullable();
            $table->timestamp('completed_at')->nullable();
            $table->timestamp('failed_at')->nullable();
            $table->unsignedBigInteger('user_id')->nullable();
            $table->string('entity_type')->nullable(); // Admin, User, etc.
            $table->unsignedBigInteger('entity_id')->nullable();
            $table->timestamps();

            $table->index(['job_id', 'status'], 'idx_job_id_status');
            $table->index(['status', 'started_at']);
            $table->index(['status', 'failed_at'], 'idx_status_failed_at');
            $table->index(['user_id', 'status', 'started_at']);
            $table->index(['entity_type', 'entity_id']);
            $table->index(['job_class','job_type','status','payload_hash'],'idx_job_tracking_duplicate_checker');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('job_trackings');
    }
};
