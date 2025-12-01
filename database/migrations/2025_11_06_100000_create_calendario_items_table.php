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
        Schema::create('calendario_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('calendario_id')->constrained('calendario')->onDelete('cascade');
            $table->foreignId('movimientos_inventario_id')->constrained('movimientos_inventario');
            $table->integer('cantidad')->default(1);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('calendario_items');
    }
};
