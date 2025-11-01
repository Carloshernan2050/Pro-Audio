<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('roles')) {
            return;
        }
        Schema::table('roles', function (Blueprint $table) {
            if (!Schema::hasColumn('roles', 'name')) {
                $table->string('name')->nullable()->after('id');
            }
            if (!Schema::hasColumn('roles', 'guard_name')) {
                $table->string('guard_name')->default('web')->after('name');
            }
        });
        // Copiar valores desde nombre_rol si existe
        if (Schema::hasColumn('roles', 'nombre_rol')) {
            DB::table('roles')->whereNull('name')->update([
                'name' => DB::raw('nombre_rol'),
            ]);
        }
        // Asegurar que guard_name tenga valor
        DB::table('roles')->whereNull('guard_name')->update(['guard_name' => 'web']);
    }

    public function down(): void
    {
        if (!Schema::hasTable('roles')) {
            return;
        }
        Schema::table('roles', function (Blueprint $table) {
            if (Schema::hasColumn('roles', 'guard_name')) {
                $table->dropColumn('guard_name');
            }
            if (Schema::hasColumn('roles', 'name')) {
                $table->dropColumn('name');
            }
        });
    }
};
