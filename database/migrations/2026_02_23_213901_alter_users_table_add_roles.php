<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            // Vincula o usuário a uma empresa (nullable para não quebrar usuários existentes agora)
            $table->foreignId('company_id')->nullable()->constrained('companies')->cascadeOnDelete();
            
            // Níveis: admin (Global/Prefeito), operator (RH/Secretaria), employee (Servidor base)
            $table->enum('role', ['admin', 'operator', 'employee'])->default('operator');
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropForeign(['company_id']);
            $table->dropColumn(['company_id', 'role']);
        });
    }
};