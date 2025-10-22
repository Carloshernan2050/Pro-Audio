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
                'descripcion' => 'DJ con amplio repertorio musical y equipo profesional para animar tu evento.'
            ],
            [
                'servicios_id' => 1,
                'nombre' => 'Animador de Eventos',
                'descripcion' => 'Personal capacitado para dinamizar y entretener a los asistentes.'
            ],
            [
                'servicios_id' => 1,
                'nombre' => 'Iluminación de Eventos',
                'descripcion' => 'Sistemas de iluminación profesional para crear ambientes únicos.'
            ],
            [
                'servicios_id' => 1,
                'nombre' => 'Coordinador de Eventos',
                'descripcion' => 'Coordinación completa del evento desde la planificación hasta la ejecución.'
            ],
            
            // Sub-servicios para Publicidad (servicios_id = 2)
            [
                'servicios_id' => 2,
                'nombre' => 'Spot Radial',
                'descripcion' => 'Producción de comerciales radiales profesionales con locución y música.'
            ],
            [
                'servicios_id' => 2,
                'nombre' => 'Cuñas Publicitarias',
                'descripcion' => 'Mensajes publicitarios cortos y efectivos para radio y eventos.'
            ],
            [
                'servicios_id' => 2,
                'nombre' => 'Producción de Jingles',
                'descripcion' => 'Creación de melodías identificativas para tu marca o empresa.'
            ],
            [
                'servicios_id' => 2,
                'nombre' => 'Locución Comercial',
                'descripcion' => 'Servicios de locución profesional para diferentes medios.'
            ],
            
            // Sub-servicios para Alquiler (servicios_id = 3)
            [
                'servicios_id' => 3,
                'nombre' => 'Bafle Autoamplificado',
                'descripcion' => 'Parlantes con amplificador integrado, ideales para eventos medianos.'
            ],
            [
                'servicios_id' => 3,
                'nombre' => 'Micrófono Inalámbrico',
                'descripcion' => 'Micrófonos de alta calidad sin cables para máxima movilidad.'
            ],
            [
                'servicios_id' => 3,
                'nombre' => 'Consola de Mezclas',
                'descripcion' => 'Consolas profesionales para control total del audio del evento.'
            ],
            [
                'servicios_id' => 3,
                'nombre' => 'Luces Audiorítmicas',
                'descripcion' => 'Iluminación que se sincroniza automáticamente con la música.'
            ],
        ]);


    }
}
