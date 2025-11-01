<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        // Crear o actualizar usuario por defecto (no se duplica)
        User::updateOrCreate(
            ['email' => 'test@example.com'], // condición
            [
                'name' => 'Test User',
                'password' => Hash::make('12345678'),
            ]
        );

        // Llamada a otros seeders en orden correcto
        $this->call([
            SpatieRolesSeeder::class,   // roles en spatie/permission
            RolesSeeder::class,         // tabla roles previa (si la sigues usando)
            AssignSuperadminSeeder::class, // crea usuario super admin y asigna rol
            PersonasSeeder::class,
            UsuariosSeeder::class,

            ServiciosSeeder::class,
            SubserviciosSeeder::class,   // ojo: corregido nombre

            InventarioSeeder::class,
            MovimientosInventarioSeeder::class,

            IngresoSeeder::class,
            CotizacionSeeder::class,
            CalendarioSeeder::class,
            HistorialSeeder::class,
            
        ]);
    }
}
