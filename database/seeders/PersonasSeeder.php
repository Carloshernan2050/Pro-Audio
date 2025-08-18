<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;

class PersonasSeeder extends Seeder
{
    public function run(): void
    {
        DB::table('personas')->insert([
            [
                'primer_nombre'   => 'Carlos',
                'segundo_nombre'  => 'Hernán',
                'primer_apellido' => 'Molina',
                'segundo_apellido'=> 'Arenas',
                'correo'          => 'carlos@example.com',
                'telefono'        => '3001234567',
                'direccion'       => 'Calle Falsa 123',
                'contrasena'      => Hash::make('12345678'),
                'fecha_registro'  => now(),
                'estado'          => 1,
                'created_at'      => now(),
                'updated_at'      => now(),
            ],
            [
                'primer_nombre'   => 'Ana',
                'segundo_nombre'  => 'María',
                'primer_apellido' => 'López',
                'segundo_apellido'=> 'García',
                'correo'          => 'ana@example.com',
                'telefono'        => '3019876543',
                'direccion'       => 'Carrera 45 # 67',
                'contrasena'      => Hash::make('87654321'),
                'fecha_registro'  => now(),
                'estado'          => 1,
                'created_at'      => now(),
                'updated_at'      => now(),
            ]
        ]);

    }
}
