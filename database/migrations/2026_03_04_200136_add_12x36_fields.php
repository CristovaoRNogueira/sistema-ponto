<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('shifts', function (Blueprint $table) {
            $table->boolean('is_12x36')->default(false)->after('name'); // Marca se a jornada é escala
        });

        Schema::table('employees', function (Blueprint $table) {
            $table->date('scale_start_date')->nullable()->after('shift_id'); // Define o "Dia 1" da escala do servidor
        });
    }

    public function down(): void
    {
        Schema::table('shifts', function (Blueprint $table) {
            $table->dropColumn('is_12x36');
        });
        Schema::table('employees', function (Blueprint $table) {
            $table->dropColumn('scale_start_date');
        });
    }
};
