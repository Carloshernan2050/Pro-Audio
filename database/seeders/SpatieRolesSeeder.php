<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Spatie\Permission\Models\Role;

class SpatieRolesSeeder extends Seeder
{
    public function run(): void
    {
        $roles = ['Superadmin', 'Admin', 'Usuario', 'Invitado'];
        
        // Mapeo de roles Spatie a nombre_rol (enum)
        $nombreRolMap = [
            'Superadmin' => 'Administrador',
            'Admin' => 'Administrador',
            'Usuario' => 'Cliente',
            'Invitado' => 'Invitado',
        ];
        
        $hasNombreRol = DB::getSchemaBuilder()->hasColumn('roles', 'nombre_rol');
        
        foreach ($roles as $name) {
            // Verificar si el rol ya existe
            $existingRole = DB::table('roles')
                ->where('name', $name)
                ->where('guard_name', 'web')
                ->first();
            
            if (!$existingRole) {
                // Crear el rol directamente en la base de datos con todos los campos
                $roleData = [
                    'name' => $name,
                    'guard_name' => 'web',
                    'created_at' => now(),
                    'updated_at' => now(),
                ];
                
                if ($hasNombreRol) {
                    $roleData['nombre_rol'] = $nombreRolMap[$name] ?? 'Invitado';
                }
                
                DB::table('roles')->insert($roleData);
            } else {
                // Actualizar nombre_rol si existe y no está establecido
                if ($hasNombreRol && empty($existingRole->nombre_rol)) {
                    DB::table('roles')
                        ->where('id', $existingRole->id)
                        ->update(['nombre_rol' => $nombreRolMap[$name] ?? 'Invitado']);
                }
            }
        }
    }
}
