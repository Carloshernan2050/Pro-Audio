<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class SubServiciosSeeder extends Seeder
{
    public function run(): void
    {
        DB::table('sub_servicios')->insert([
            // Sub-servicios para Animación (servicios_id = 1)
            [
                'servicios_id' => 1,
                'nombre' => 'DJ Profesional',
                'descripcion' => 'DJ con amplio repertorio musical y equipo profesional para animar tu evento.',
                'precio' => 600000,
            ],
            [
                'servicios_id' => 1,
                'nombre' => 'Animador de Eventos',
                'descripcion' => 'Personal capacitado para dinamizar y entretener a los asistentes.',
                'precio' => 350000,
            ],
            [
                'servicios_id' => 1,
                'nombre' => 'Iluminación de Eventos',
                'descripcion' => 'Sistemas de iluminación profesional para crear ambientes únicos.',
                'precio' => 450000,
            ],
            [
                'servicios_id' => 1,
                'nombre' => 'Coordinador de Eventos',
                'descripcion' => 'Coordinación completa del evento desde la planificación hasta la ejecución.',
                'precio' => 500000,
            ],

            // Sub-servicios para Publicidad (servicios_id = 2)
            [
                'servicios_id' => 2,
                'nombre' => 'Spot Radial',
                'descripcion' => 'Producción de comerciales radiales profesionales con locución y música.',
                'precio' => 300000,
            ],
            [
                'servicios_id' => 2,
                'nombre' => 'Cuñas Publicitarias',
                'descripcion' => 'Mensajes publicitarios cortos y efectivos para radio y eventos.',
                'precio' => 180000,
            ],
            [
                'servicios_id' => 2,
                'nombre' => 'Producción de Jingles',
                'descripcion' => 'Creación de melodías identificativas para tu marca o empresa.',
                'precio' => 420000,
            ],
            [
                'servicios_id' => 2,
                'nombre' => 'Locución Comercial',
                'descripcion' => 'Servicios de locución profesional para diferentes medios.',
                'precio' => 220000,
            ],

            // Sub-servicios para Alquiler (servicios_id = 3)
            [
                'servicios_id' => 3,
                'nombre' => 'Bafle Autoamplificado',
                'descripcion' => 'Parlantes con amplificador integrado, ideales para eventos medianos.',
                'precio' => 150000,
            ],
            [
                'servicios_id' => 3,
                'nombre' => 'Micrófono Inalámbrico',
                'descripcion' => 'Micrófonos de alta calidad sin cables para máxima movilidad.',
                'precio' => 90000,
            ],
            [
                'servicios_id' => 3,
                'nombre' => 'Consola de Mezclas',
                'descripcion' => 'Consolas profesionales para control total del audio del evento.',
                'precio' => 200000,
            ],
            [
                'servicios_id' => 3,
                'nombre' => 'Luces Audiorítmicas',
                'descripcion' => 'Iluminación que se sincroniza automáticamente con la música.',
                'precio' => 110000,
            ],
        ]);

    }
}
