<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('reservas')) {
            Schema::create('reservas', function (Blueprint $table) {
                $table->id();
                $table->foreignId('personas_id')->nullable()->constrained('personas');
                $table->string('servicio')->nullable();
                $table->dateTime('fecha_inicio');
                $table->dateTime('fecha_fin');
                $table->text('descripcion_evento')->nullable();
                $table->unsignedInteger('cantidad_total')->default(0);
                $table->string('estado', 20)->default('pendiente');
                $table->json('meta')->nullable();
                $table->timestamps();
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasTable('reservas')) {
            Schema::dropIfExists('reservas');
        }
    }
};
