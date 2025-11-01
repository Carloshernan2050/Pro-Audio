<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Role;

class SpatieRolesSeeder extends Seeder
{
    public function run(): void
    {
        $roles = ['Superadmin', 'Admin', 'Usuario', 'Invitado'];
        foreach ($roles as $name) {
            Role::findOrCreate($name, 'web');
        }
    }
}
