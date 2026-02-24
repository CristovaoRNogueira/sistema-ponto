<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void {
        Schema::table('departments', function (Blueprint $table) {
            // Adiciona a coluna que aponta para ela mesma (Pai/Filho)
            $table->foreignId('parent_id')->nullable()->after('name')->constrained('departments')->cascadeOnDelete();
        });
    }

    public function down(): void {
        Schema::table('departments', function (Blueprint $table) {
            $table->dropForeign(['parent_id']);
            $table->dropColumn('parent_id');
        });
    }
};