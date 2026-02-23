<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('command_queues', function (Blueprint $table) {
            // 1. Remove as colunas antigas da versão anterior
            if (Schema::hasColumn('command_queues', 'endpoint')) {
                $table->dropColumn('endpoint');
            }
            if (Schema::hasColumn('command_queues', 'is_executed')) {
                $table->dropColumn('is_executed');
            }

            // 2. Adiciona as colunas novas exigidas pela nossa nova arquitetura
            if (!Schema::hasColumn('command_queues', 'command_type')) {
                $table->string('command_type')->after('device_id')->default('user_set');
            }
            if (!Schema::hasColumn('command_queues', 'status')) {
                $table->string('status')->after('payload')->default('pending');
            }
        });
    }

    public function down(): void
    {
        Schema::table('command_queues', function (Blueprint $table) {
            $table->dropColumn(['command_type', 'status']);
            $table->string('endpoint')->nullable();
            $table->boolean('is_executed')->default(false);
        });
    }
};