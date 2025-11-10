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
            return;
        }

        Schema::table('reservas', function (Blueprint $table) {
            if (!Schema::hasColumn('reservas', 'servicio')) $table->string('servicio')->nullable()->after('id');
            if (!Schema::hasColumn('reservas', 'personas_id')) $table->foreignId('personas_id')->nullable()->after('id')->constrained('personas');
            if (!Schema::hasColumn('reservas', 'fecha_inicio')) $table->dateTime('fecha_inicio')->nullable()->after('servicio');
            if (!Schema::hasColumn('reservas', 'fecha_fin')) $table->dateTime('fecha_fin')->nullable()->after('fecha_inicio');
            if (!Schema::hasColumn('reservas', 'descripcion_evento')) $table->text('descripcion_evento')->nullable()->after('fecha_fin');
            if (!Schema::hasColumn('reservas', 'cantidad_total')) $table->unsignedInteger('cantidad_total')->default(0)->after('descripcion_evento');
            if (!Schema::hasColumn('reservas', 'estado')) $table->string('estado', 20)->default('pendiente')->after('cantidad_total');
            if (!Schema::hasColumn('reservas', 'meta')) $table->json('meta')->nullable()->after('estado');
            if (!Schema::hasColumn('reservas', 'created_at')) $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (!Schema::hasTable('reservas')) {
            return;
        }

        Schema::table('reservas', function (Blueprint $table) {
            if (Schema::hasColumn('reservas', 'meta')) {
                $table->dropColumn('meta');
            }
            if (Schema::hasColumn('reservas', 'estado')) {
                $table->dropColumn('estado');
            }
            if (Schema::hasColumn('reservas', 'cantidad_total')) {
                $table->dropColumn('cantidad_total');
            }
            if (Schema::hasColumn('reservas', 'descripcion_evento')) {
                $table->dropColumn('descripcion_evento');
            }
            if (Schema::hasColumn('reservas', 'fecha_fin')) {
                $table->dropColumn('fecha_fin');
            }
            if (Schema::hasColumn('reservas', 'fecha_inicio')) {
                $table->dropColumn('fecha_inicio');
            }
            if (Schema::hasColumn('reservas', 'personas_id')) {
                $table->dropForeign(['personas_id']);
                $table->dropColumn('personas_id');
            }
            if (Schema::hasColumn('reservas', 'servicio')) {
                $table->dropColumn('servicio');
            }
            if (Schema::hasColumn('reservas', 'created_at') && Schema::hasColumn('reservas', 'updated_at')) {
                $table->dropTimestamps();
            }
        });
    }
};

