<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('devices', function (Blueprint $table) {
            $table->timestamp('last_seen_at')->nullable()->after('password');
            $table->boolean('is_online')->default(false)->after('last_seen_at');
            $table->string('paper_status')->default('ok')->after('is_online'); // 'ok', 'low', 'empty'
            $table->text('last_error')->nullable()->after('paper_status');
        });
    }

    public function down(): void
    {
        Schema::table('devices', function (Blueprint $table) {
            $table->dropColumn(['last_seen_at', 'is_online', 'paper_status', 'last_error']);
        });
    }
};
