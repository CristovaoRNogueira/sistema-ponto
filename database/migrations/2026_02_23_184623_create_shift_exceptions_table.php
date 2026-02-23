<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('shift_exceptions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('employee_id')->constrained('employees')->cascadeOnDelete();
            
            $table->date('exception_date');
            
            // 'swap' (troca), 'extra' (plantão extra), 'day_off' (folga/dispensa)
            $table->string('type'); 
            
            // Horários específicos para ESTE dia (sobrescreve a jornada padrão)
            // Se for folga, esses campos ficam null
            $table->time('in_1')->nullable();
            $table->time('out_1')->nullable();
            $table->time('in_2')->nullable();
            $table->time('out_2')->nullable();
            $table->integer('daily_work_minutes')->default(0);
            
            $table->string('observation')->nullable()->comment('Ex: Troca com o Enfermeiro João');
            
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('shift_exceptions');
    }
};