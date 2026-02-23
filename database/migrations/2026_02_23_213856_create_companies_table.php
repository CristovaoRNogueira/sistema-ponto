<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('companies', function (Blueprint $table) {
            $table->id();
            $table->string('name'); // Razão Social
            $table->string('fantasy_name')->nullable(); // Nome Fantasia
            $table->string('cnpj')->unique();
            $table->string('legal_responsible_name')->nullable();
            $table->string('legal_responsible_cpf')->nullable();
            $table->string('legal_responsible_email')->nullable();
            $table->string('logo_path')->nullable(); // Caminho para a logo
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('companies');
    }
};