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
        Schema::create('global_questions', function (Blueprint $table) {
            $table->id();
            $table->text('question_text');
            $table->text('explanation')->nullable(); // Explanation for the correct answer
            $table->integer('score');
            $table->integer('duration'); // time in seconds
            $table->enum('text_direction', ['ltr', 'rtl'])->default('ltr');
            $table->foreignId('admin_id')->nullable()->references('id')->on('admins')->onDelete('set null');
            $table->foreignId('approved')->nullable()->references('id')->on('admins')->onDelete('set null');
            $table->string('deleted_admin_name')->nullable()->index();
            
            // AI Question Generation Fields
            $table->boolean('ai')->default(false)->index(); // Whether question was AI-generated
            $table->foreignId('user_id')->nullable()->references('id')->on('users')->onDelete('set null'); // User who generated the AI question
            
            $table->timestamps();

            // Performance Indexes
            $table->index(['approved', 'ai', 'user_id'], 'idx_approved_ai_user'); // For AI questions by user
            $table->index(['ai', 'created_at'], 'idx_ai_created'); // For premium questions (after 48h)
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('global_questions');
    }
};
