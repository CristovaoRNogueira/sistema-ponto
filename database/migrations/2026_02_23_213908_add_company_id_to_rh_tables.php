<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Adiciona a coluna company_id nas tabelas que você já possui
        $tables = ['employees', 'devices', 'departments', 'shifts'];

        foreach ($tables as $table_name) {
            Schema::table($table_name, function (Blueprint $table) {
                $table->foreignId('company_id')->nullable()->constrained('companies')->cascadeOnDelete();
            });
        }
    }

    public function down(): void
    {
        $tables = ['employees', 'devices', 'departments', 'shifts'];

        foreach ($tables as $table_name) {
            Schema::table($table_name, function (Blueprint $table) {
                $table->dropForeign(['company_id']);
                $table->dropColumn('company_id');
            });
        }
    }
};