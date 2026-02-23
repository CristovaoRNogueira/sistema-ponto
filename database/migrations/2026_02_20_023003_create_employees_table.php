<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('employees', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('cpf')->unique()->nullable(); // Adicionado para o RH
            $table->string('pis')->unique();
            $table->string('registration_number')->nullable();
            
            // Chaves estrangeiras (As tabelas departments e shifts já devem existir antes desta)
            $table->foreignId('department_id')->nullable()->constrained('departments')->nullOnDelete();
            $table->foreignId('shift_id')->nullable()->constrained('shifts')->nullOnDelete();
            
            $table->boolean('is_active')->default(true); // Controle se está ativo na prefeitura
            
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('employees');
    }
};