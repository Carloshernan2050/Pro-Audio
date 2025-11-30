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
        Schema::create('historial', function (Blueprint $table) {
            $table->id();
            $table->foreignId('calendario_id')->nullable()->constrained('calendario')->onDelete('cascade');
            $table->unsignedBigInteger('reserva_id')->nullable();
            $table->string('accion')->nullable();
            $table->dateTime('confirmado_en')->nullable();
            $table->text('observaciones')->nullable();
        });

    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('historial');
    }
};
