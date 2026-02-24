<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('punch_logs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('employee_id')->constrained('employees');
            
            // ADICIONAMOS O NULLABLE AQUI:
            $table->foreignId('device_id')->nullable()->constrained('devices');
            
            $table->integer('nsr')->nullable();
            $table->dateTime('punch_time');
            $table->timestamps();
        });
    }
    
    public function down(): void
    {
        Schema::dropIfExists('punch_logs');
    }
};
