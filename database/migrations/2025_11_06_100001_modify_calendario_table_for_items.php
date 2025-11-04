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
        Schema::table('calendario', function (Blueprint $table) {
            // Hacer movimientos_inventario_id nullable ya que ahora los productos están en calendario_items
            $table->foreignId('movimientos_inventario_id')->nullable()->change();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('calendario', function (Blueprint $table) {
            // Revertir si es necesario (puede fallar si hay nulls)
            $table->foreignId('movimientos_inventario_id')->nullable(false)->change();
        });
    }
};

