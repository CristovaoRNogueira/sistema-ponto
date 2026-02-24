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
        Schema::table('employees', function (Blueprint $table) {
        $table->string('pis')->nullable()->change();
    });
    }

    /**
     * Reverse the migrations.
     */
    public function down()
    {
    Schema::table('employees', function (Blueprint $table) {
        $table->string('pis')->nullable(false)->change();
    });
    }
};
