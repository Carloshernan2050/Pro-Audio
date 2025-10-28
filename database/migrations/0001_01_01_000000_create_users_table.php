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
        // 1. Agregar la columna role_id a la tabla 'users'
        Schema::table('users', function (Blueprint $table) {
            // Utilizamos foreignId para crear el campo y la clave foránea en una línea.
            // Por defecto, será NULLABLE para manejar usuarios invitados/sin rol definido inicialmente.
            $table->foreignId('role_id')
                  ->nullable()
                  ->after('password') // Colocamos después del campo 'password'
                  ->constrained('roles') // Asume que tu tabla se llama 'roles'
                  ->onDelete('set null'); // Si se elimina un rol, el usuario se queda sin rol (null)
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            // Para revertir la migración, primero eliminamos la clave foránea
            $table->dropForeign(['role_id']);
            // Luego eliminamos la columna
            $table->dropColumn('role_id');
        });
    }
};
