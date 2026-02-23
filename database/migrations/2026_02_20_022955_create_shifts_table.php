<?php

// create_shifts_table.php (Jornadas de Trabalho)
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void {
        Schema::create('shifts', function (Blueprint $table) {
            $table->id();
            $table->string('name'); // Ex: Administrativo 08-12 / 14-17
            $table->time('in_1')->nullable();
            $table->time('out_1')->nullable();
            $table->time('in_2')->nullable();
            $table->time('out_2')->nullable();
            $table->integer('daily_work_minutes'); // Ex: 480 (8 horas)
            $table->integer('tolerance_minutes')->default(15); // Margem de atraso
            $table->timestamps();
        });
    }
    public function down(): void { Schema::dropIfExists('shifts'); }
};