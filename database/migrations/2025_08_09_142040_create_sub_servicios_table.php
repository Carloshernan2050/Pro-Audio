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
        Schema::create('sub_servicios', function (Blueprint $table) {
            $table->id();
            $table->foreignId('servicios_id')->constrained('servicios');
            $table->string('nombre', 100);
            $table->text('descripcion');
        });

    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('sub_servicios');
    }
};
