<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Servicios;

class ServiciosSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $servicios = [
            [
                'nombre_servicio' => 'Animación',
                'descripcion' => '',
                'icono' => null,
            ],
            [
                'nombre_servicio' => 'Publicidad',
                'descripcion' => '',
                'icono' => null,
            ],
            [
                'nombre_servicio' => 'Alquiler',
                'descripcion' => '',
                'icono' => null,
            ],
        ];

        foreach ($servicios as $servicio) {
            Servicios::firstOrCreate(
                ['nombre_servicio' => $servicio['nombre_servicio']],
                [
                    'descripcion' => $servicio['descripcion'],
                    'icono' => $servicio['icono'],
                ]
            );
        }
    }
}
