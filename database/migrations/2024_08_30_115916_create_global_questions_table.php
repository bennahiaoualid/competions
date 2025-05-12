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
            $table->integer('score');
            $table->integer('duration'); // time in seconds
            $table->enum('text_direction', ['ltr', 'rtl'])->default('ltr');
            $table->foreignId('admin_id')->nullable()->references('id')->on('admins')->onDelete('set null');
            $table->foreignId('approved')->nullable()->references('id')->on('admins')->onDelete('set null');
            $table->string('deleted_admin_name')->nullable()->index();
            $table->timestamps();

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
