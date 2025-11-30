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
        if (!Schema::hasTable('reservas')) {
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

        if (!Schema::hasTable('reserva_items')) {
            Schema::create('reserva_items', function (Blueprint $table) {
                $table->id();
                $table->foreignId('reserva_id')->constrained('reservas')->cascadeOnDelete();
                $table->foreignId('inventario_id')->constrained('inventario');
                $table->unsignedInteger('cantidad');
                $table->timestamps();
            });
        }

        if (Schema::hasTable('historial')) {
            Schema::table('historial', function (Blueprint $table) {
                if (!Schema::hasColumn('historial', 'reserva_id')) {
                    $table->foreignId('reserva_id')->nullable()->after('calendario_id')->constrained('reservas')->nullOnDelete();
                }
                if (!Schema::hasColumn('historial', 'accion')) {
                    $table->string('accion', 50)->nullable()->after('reserva_id');
                }
                if (!Schema::hasColumn('historial', 'confirmado_en')) {
                    $table->dateTime('confirmado_en')->nullable()->after('accion');
                }
                if (!Schema::hasColumn('historial', 'observaciones')) {
                    $table->text('observaciones')->nullable()->after('confirmado_en');
                }
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (Schema::hasTable('historial')) {
            Schema::table('historial', function (Blueprint $table) {
                if (Schema::hasColumn('historial', 'observaciones')) {
                    $table->dropColumn('observaciones');
                }
                if (Schema::hasColumn('historial', 'confirmado_en')) {
                    $table->dropColumn('confirmado_en');
                }
                if (Schema::hasColumn('historial', 'accion')) {
                    $table->dropColumn('accion');
                }
                if (Schema::hasColumn('historial', 'reserva_id')) {
                    $table->dropForeign(['reserva_id']);
                    $table->dropColumn('reserva_id');
                }
            });
        }

        if (Schema::hasTable('reserva_items')) {
            Schema::dropIfExists('reserva_items');
        }
        if (Schema::hasTable('reservas')) {
            Schema::dropIfExists('reservas');
        }
    }
};

