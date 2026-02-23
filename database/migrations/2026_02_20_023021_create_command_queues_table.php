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
        Schema::create('command_queues', function (Blueprint $table) {
    $table->id();
    $table->foreignId('device_id')->constrained('devices')->onDelete('cascade');
    $table->string('endpoint');
    $table->json('payload');
    $table->boolean('is_executed')->default(false);
    $table->timestamps();
});
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('command_queues');
    }
};
