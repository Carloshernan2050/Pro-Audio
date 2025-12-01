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
        if (Schema::hasTable('inventario')) {
            Schema::table('inventario', function (Blueprint $table) {
                // Drop only if columns exist
                if (Schema::hasColumn('inventario', 'cantidad_disponible')) {
                    $table->dropColumn('cantidad_disponible');
                }
                if (Schema::hasColumn('inventario', 'fecha_actualizacion')) {
                    $table->dropColumn('fecha_actualizacion');
                }
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (Schema::hasTable('inventario')) {
            Schema::table('inventario', function (Blueprint $table) {
                if (! Schema::hasColumn('inventario', 'cantidad_disponible')) {
                    $table->integer('cantidad_disponible');
                }
                if (! Schema::hasColumn('inventario', 'fecha_actualizacion')) {
                    $table->dateTime('fecha_actualizacion')->nullable();
                }
            });
        }
    }
};
