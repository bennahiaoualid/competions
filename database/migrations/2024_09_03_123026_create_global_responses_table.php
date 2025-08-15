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
        Schema::create('global_responses', function (Blueprint $table) {
            $table->id();
            $table->foreignId('question_id')->constrained('global_questions')->onDelete('cascade');
            $table->foreignId('choice_id')->nullable()->constrained('choices')->onDelete('set null');
            $table->foreignId('user_id')->constrained('users')->onDelete('cascade');
            $table->float('score')->default(0);
            $table->integer('response_duration')->default(0);
            $table->timestamps();

            // Performance Indexes
            $table->index(['user_id', 'question_id'], 'idx_user_question'); // For finding user responses to questions
            $table->index(['user_id', 'question_id', 'score'], 'idx_user_question_score'); // For eligibility logic
            $table->index(['question_id', 'user_id'], 'idx_question_user'); // For question-based queries
            $table->index(['user_id', 'score'], 'idx_user_score'); // For user performance queries
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('global_responses');
    }
};
