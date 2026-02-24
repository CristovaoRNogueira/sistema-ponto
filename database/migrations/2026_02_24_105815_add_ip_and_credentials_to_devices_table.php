<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('devices', function (Blueprint $table) {
            $table->string('ip_address')->nullable()->after('serial_number');
            $table->string('username')->default('admin')->after('ip_address');
            $table->string('password')->default('admin')->after('username');
        });
    }

    public function down(): void
    {
        Schema::table('devices', function (Blueprint $table) {
            $table->dropColumn(['ip_address', 'username', 'password']);
        });
    }
};