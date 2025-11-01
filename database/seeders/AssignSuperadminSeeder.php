<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class AssignSuperadminSeeder extends Seeder
{
    public function run(): void
    {
        $email = 'proaudio14@gmail.com';

        $rolId = DB::table('roles')
            ->where('name', 'Superadmin')
            ->orWhere('nombre_rol', 'Superadmin')
            ->value('id');

        $personaId = DB::table('personas')->where('correo', $email)->value('id');

        if ($rolId && $personaId) {
            DB::table('personas_roles')->updateOrInsert(
                ['personas_id' => $personaId, 'roles_id' => $rolId],
                []
            );
        } else {
            // Opcionalmente, podríamos lanzar una excepción o loggear
        }
    }
}
