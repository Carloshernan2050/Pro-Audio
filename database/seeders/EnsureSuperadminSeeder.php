<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;

class EnsureSuperadminSeeder extends Seeder
{
    public function run(): void
    {
        // Configurable por .env
        $email      = env('SUPERADMIN_EMAIL', 'superadmin@proaudio.test');
        $password   = env('SUPERADMIN_PASSWORD', 'SuperAdmin#2025'); // úsalo para la tabla de autenticación si aplica
        $firstName  = env('SUPERADMIN_FIRST_NAME', 'Super');
        $lastName   = env('SUPERADMIN_LAST_NAME', 'Admin');
        $telefono   = env('SUPERADMIN_PHONE', null);

        // Asegurar rol "Superadmin" en tabla roles (name o nombre_rol)
        $role = DB::table('roles')
            ->select('id', DB::raw('COALESCE(name, nombre_rol) as name'))
            ->where(DB::raw('COALESCE(name, nombre_rol)'), 'Superadmin')
            ->first();

        if (!$role) {
            $roleId = DB::table('roles')->insertGetId([
                'name' => 'Superadmin',
            ]);
        } else {
            $roleId = $role->id;
        }

        // Asegurar persona en tabla personas (columna correo)
        $persona = DB::table('personas')->where('correo', $email)->first();
        if (!$persona) {
            $personaId = DB::table('personas')->insertGetId([
                'primer_nombre'   => $firstName,
                'primer_apellido' => $lastName,
                'correo'          => $email,
                'telefono'        => $telefono,
            ]);
        } else {
            $personaId = $persona->id;
            DB::table('personas')->where('id', $personaId)->update([
                'primer_nombre'   => $firstName,
                'primer_apellido' => $lastName,
                'telefono'        => $telefono,
            ]);
        }

        // Asegurar relación personas_roles
        $existsPivot = DB::table('personas_roles')
            ->where('personas_id', $personaId)
            ->where('roles_id', $roleId)
            ->exists();
        if (!$existsPivot) {
            DB::table('personas_roles')->insert([
                'personas_id' => $personaId,
                'roles_id'    => $roleId,
            ]);
        }

        // Si tu app también usa tabla users para login, crear/actualizar credenciales mínimas
        if (schemaHasTable('users')) {
            try {
                DB::table('users')->updateOrInsert(
                    ['email' => $email],
                    ['name' => $firstName.' '.$lastName, 'password' => Hash::make($password)]
                );
            } catch (\Throwable $e) {
                // Ignorar si no hay tabla users o columnas esperadas
            }
        }
    }
}

if (!function_exists('schemaHasTable')) {
    function schemaHasTable(string $table): bool
    {
        try { return DB::getSchemaBuilder()->hasTable($table); } catch (\Throwable $e) { return false; }
    }
}


