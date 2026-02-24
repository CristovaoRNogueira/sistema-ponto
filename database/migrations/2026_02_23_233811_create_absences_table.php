<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('absences', function (Blueprint $table) {
            $table->id();
            $table->foreignId('employee_id')->constrained('employees')->cascadeOnDelete();
            
            $table->string('type'); // Ex: 'medical_certificate', 'vacation', 'day_off'
            $table->dateTime('start_date');
            $table->dateTime('end_date');
            $table->boolean('waive_hours')->default(false); // Se true, abona as horas (não gera falta)
            $table->text('reason'); // Motivo ou CID
            $table->string('attachment_path')->nullable(); // Caminho para foto do atestado
            
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('absences');
    }
};