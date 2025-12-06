<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // Eliminar la restricción antigua
        Schema::table('historial', function (Blueprint $table) {
            $table->dropForeign(['calendario_id']);
        });

        // Recrear la restricción con onDelete('cascade')
        Schema::table('historial', function (Blueprint $table) {
            $table->foreign('calendario_id')
                  ->references('id')
                  ->on('calendario')
                  ->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Eliminar la restricción con cascade
        Schema::table('historial', function (Blueprint $table) {
            $table->dropForeign(['calendario_id']);
        });

        // Recrear la restricción original sin cascade (NO ACTION)
        Schema::table('historial', function (Blueprint $table) {
            $table->foreign('calendario_id')
                  ->references('id')
                  ->on('calendario')
                  ->onDelete('restrict');
        });
    }
};
