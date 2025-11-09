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
        Schema::table('sub_servicios', function (Blueprint $table) {
            $table->dropForeign(['servicios_id']);
            $table->foreign('servicios_id')
                ->references('id')
                ->on('servicios')
                ->cascadeOnDelete();
        });

        Schema::table('cotizacion', function (Blueprint $table) {
            $table->dropForeign(['sub_servicios_id']);
            $table->foreign('sub_servicios_id')
                ->references('id')
                ->on('sub_servicios')
                ->cascadeOnDelete();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('cotizacion', function (Blueprint $table) {
            $table->dropForeign(['sub_servicios_id']);
            $table->foreign('sub_servicios_id')
                ->references('id')
                ->on('sub_servicios');
        });

        Schema::table('sub_servicios', function (Blueprint $table) {
            $table->dropForeign(['servicios_id']);
            $table->foreign('servicios_id')
                ->references('id')
                ->on('servicios');
        });
    }
};

