<?php

namespace Database\Seeders;

use App\Models\Servicios;
use Illuminate\Database\Seeder;

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
