<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void {
        Schema::table('punch_logs', function (Blueprint $table) {
            $table->boolean('is_manual')->default(false); // Diz se foi o RH que inseriu
            $table->string('justification')->nullable();  // O motivo (Ex: Esqueceu de bater)
            $table->unsignedBigInteger('user_id')->nullable(); // Qual usuário do RH fez a alteração
        });
    }
    public function down(): void {
        Schema::table('punch_logs', function (Blueprint $table) {
            $table->dropColumn(['is_manual', 'justification', 'user_id']);
        });
    }
};