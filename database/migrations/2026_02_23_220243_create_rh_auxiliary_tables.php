<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // 1. Cargos
        Schema::create('job_titles', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->constrained('companies')->cascadeOnDelete();
            $table->string('name');
            $table->string('cbo')->nullable(); // Código Brasileiro de Ocupações
            $table->timestamps();
        });

        // 2. Centros de Custo
        Schema::create('cost_centers', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->constrained('companies')->cascadeOnDelete();
            $table->string('code')->nullable();
            $table->string('name');
            $table->timestamps();
        });

        // 3. Feriados (company_id nullable para feriados nacionais)
        Schema::create('holidays', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->nullable()->constrained('companies')->cascadeOnDelete();
            $table->string('name');
            $table->date('date');
            $table->timestamps();
        });

        // 4. Motivos de Demissão / Desligamento
        Schema::create('dismissal_reasons', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->constrained('companies')->cascadeOnDelete();
            $table->string('name');
            $table->timestamps();
        });

        // 5. Expandindo a Tabela de Funcionários (O Escopo Completo)
        Schema::table('employees', function (Blueprint $table) {
            $table->foreignId('job_title_id')->nullable()->constrained('job_titles')->nullOnDelete();
            $table->foreignId('cost_center_id')->nullable()->constrained('cost_centers')->nullOnDelete();
            
            // Dados Pessoais
            $table->string('rg')->nullable();
            $table->text('address')->nullable();
            
            // Acesso Mobile / Web
            $table->boolean('mobile_access')->default(false);
            $table->string('app_password')->nullable();
            
            // Desligamento
            $table->foreignId('dismissal_reason_id')->nullable()->constrained('dismissal_reasons')->nullOnDelete();
            $table->date('dismissal_date')->nullable();
        });
    }

    public function down(): void
    {
        Schema::table('employees', function (Blueprint $table) {
            $table->dropForeign(['job_title_id']);
            $table->dropForeign(['cost_center_id']);
            $table->dropForeign(['dismissal_reason_id']);
            $table->dropColumn([
                'job_title_id', 'cost_center_id', 'rg', 'address', 
                'mobile_access', 'app_password', 'dismissal_reason_id', 'dismissal_date'
            ]);
        });

        Schema::dropIfExists('dismissal_reasons');
        Schema::dropIfExists('holidays');
        Schema::dropIfExists('cost_centers');
        Schema::dropIfExists('job_titles');
    }
};