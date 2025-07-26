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
        Schema::table('responses', function (Blueprint $table) {
            $table->integer('keystrokes')->default(0);
            $table->json('flags')->nullable();
            $table->float('penalty')->default(0);
            $table->float('final_score')->nullable();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('responses', function (Blueprint $table) {
            $table->dropColumn('keystrokes');
            $table->dropColumn('flags');
            $table->dropColumn('penalty');
            $table->dropColumn('final_score');
        });
    }
};
