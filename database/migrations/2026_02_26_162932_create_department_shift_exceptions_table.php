<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('department_shift_exceptions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('department_id')->constrained('departments')->cascadeOnDelete();
            $table->date('exception_date');
            $table->string('type'); // 'partial' (expediente parcial) ou 'day_off' (recesso total)
            $table->time('in_1')->nullable();
            $table->time('out_1')->nullable();
            $table->time('in_2')->nullable();
            $table->time('out_2')->nullable();
            $table->integer('daily_work_minutes')->default(0);
            $table->string('observation')->nullable();
            $table->timestamps();
        });
    }
    public function down(): void
    {
        Schema::dropIfExists('department_shift_exceptions');
    }
};
