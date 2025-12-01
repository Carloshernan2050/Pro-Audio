<?php

namespace Database\Seeders;

use App\Models\User;
use App\Models\Usuario;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Spatie\Permission\Models\Role;

class AssignSuperadminSeeder extends Seeder
{
    public function run(): void
    {
        $email = env('SUPERADMIN_EMAIL', 'proaudio14@gmail.com');
        $password = env('SUPERADMIN_PASSWORD', '123456789');

        // Asegurar que el rol exista en Spatie
        $role = Role::findOrCreate('Superadmin', 'web');

        // Crear o actualizar el usuario base de Laravel
        $user = User::updateOrCreate(
            ['email' => $email],
            [
                'name' => 'Super Admin',
                'password' => Hash::make($password),
            ]
        );

        // Asignar rol Spatie al usuario
        $user->syncRoles([$role->name]);

        // Si manejas el esquema legado de personas/roles, intenta enlazar también
        $legacyRoleId = DB::table('roles')
            ->where('name', 'Superadmin')
            ->orWhere('nombre_rol', 'Superadmin')
            ->value('id');

        $personaId = DB::table('personas')->where('correo', $email)->value('id');

        // Crear persona si no existe (para el flujo de login propio con tabla personas)
        if (! $personaId) {
            $persona = Usuario::create([
                'primer_nombre' => 'Edwin',
                'segundo_nombre' => 'Fernando',
                'primer_apellido' => 'Vargas',
                'segundo_apellido' => 'Flores',
                'correo' => $email,
                'telefono' => '3142377656',
                'direccion' => '',
                'contrasena' => Hash::make($password),
                'fecha_registro' => now(),
                'estado' => 1,
            ]);
            $personaId = $persona->id;
        } else {
            // Asegurar contraseña actualizada en personas
            DB::table('personas')->where('id', $personaId)->update([
                'contrasena' => Hash::make($password),
                'estado' => 1,
                'telefono' => DB::raw("CASE WHEN telefono IS NULL OR telefono = '' THEN '3142377656' ELSE telefono END"),
                'direccion' => DB::raw("COALESCE(direccion, '')"),
            ]);
        }

        if ($legacyRoleId && $personaId) {
            DB::table('personas_roles')->updateOrInsert(
                ['personas_id' => $personaId, 'roles_id' => $legacyRoleId],
                []
            );
        }
    }
}
