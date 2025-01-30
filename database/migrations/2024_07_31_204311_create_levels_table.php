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
        Schema::create('levels', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->text('description')->nullable();
            $table->foreignId('competition_id')->constrained('competitions')->onDelete('cascade');;
            $table->foreignId('admin_id')->constrained('admins');
            $table->integer('questions_number');
            $table->dateTime('start_date');
            $table->integer('duration'); // Duration in minutes
            $table->enum('status', [0,1,2])->default(0)->comment('inactive,active,finished');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('levels');
    }
};
