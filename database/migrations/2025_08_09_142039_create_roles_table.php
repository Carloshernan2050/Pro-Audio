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
        Schema::create('roles', function (Blueprint $table) {
            $table->id();
            $table->enum('nombre_rol', ['Administrador', 'Cliente', 'Invitado']);
            $table->timestamps();
        });


    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Algunas tablas (de spatie/permission u otras) pueden referenciar roles
        if (Schema::hasTable('model_has_roles')) {
            try {
                Schema::table('model_has_roles', function (Blueprint $table) {
                    if (Schema::hasColumn('model_has_roles', 'role_id')) {
                        // El nombre de la FK puede variar; intentar drop por columna
                        $table->dropForeign(['role_id']);
                    }
                });
            } catch (\Throwable $e) {
                // ignorar si no existe la FK
            }
            Schema::dropIfExists('model_has_roles');
        }

        if (Schema::hasTable('role_has_permissions')) {
            try {
                Schema::table('role_has_permissions', function (Blueprint $table) {
                    if (Schema::hasColumn('role_has_permissions', 'role_id')) {
                        $table->dropForeign(['role_id']);
                    }
                });
            } catch (\Throwable $e) {
                // Ignorar si no existe la FK o hay algún error al eliminarla
            }
            // no siempre se elimina esta tabla aquí, pero si existe la quitamos para poder soltar roles
            Schema::dropIfExists('role_has_permissions');
        }

        Schema::dropIfExists('roles');
    }
};
