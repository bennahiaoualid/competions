<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up()
    {
        Schema::table('users', function (Blueprint $table) {
            $table->boolean('guest')->default(false)->after('email')->index();
        });
    }

    public function down()
    {
        Schema::table('users', function (Blueprint $table) {
            // Drop the guest column and its index
            $table->dropIndex(['guest']); // drop the index
            $table->dropColumn('guest'); // drop the column
        });
    }

};
