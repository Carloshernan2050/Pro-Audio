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
        Schema::table('servicios', function (Blueprint $table) {
            if (!Schema::hasColumn('servicios', 'icono')) {
                $table->string('icono', 80)->nullable()->after('descripcion');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('servicios', function (Blueprint $table) {
            if (Schema::hasColumn('servicios', 'icono')) {
                $table->dropColumn('icono');
            }
        });
    }
};

