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
            [
                'servicios_id' => 3,
                'nombre' => 'Micrófono de Mano',
                'descripcion' => 'Micrófonos profesionales con cable para presentaciones y eventos.',
                'precio' => 70000,
            ],
            [
                'servicios_id' => 3,
                'nombre' => 'Proyector Multimedia',
                'descripcion' => 'Proyectores de alta definición para presentaciones y proyecciones.',
                'precio' => 180000,
            ],
            [
                'servicios_id' => 3,
                'nombre' => 'Pantalla de Proyección',
                'descripcion' => 'Pantallas profesionales para proyecciones de diferentes tamaños.',
                'precio' => 120000,
            ],
            [
                'servicios_id' => 3,
                'nombre' => 'Sistema de Sonido Completo',
                'descripcion' => 'Paquete completo de audio con bafles, consola y micrófonos.',
                'precio' => 400000,
            ],
            [
                'servicios_id' => 3,
                'nombre' => 'Equipo de DJ',
                'descripcion' => 'Equipo completo para DJ incluyendo mezcladora, reproductores y auriculares.',
                'precio' => 350000,
            ],
            [
                'servicios_id' => 3,
                'nombre' => 'Luces LED',
                'descripcion' => 'Sistemas de iluminación LED para crear ambientes dinámicos.',
                'precio' => 130000,
            ],
            [
                'servicios_id' => 3,
                'nombre' => 'Mesa de Sonido',
                'descripcion' => 'Mesas de mezcla profesionales para control de audio en vivo.',
                'precio' => 250000,
            ],
            [
                'servicios_id' => 3,
                'nombre' => 'Monitor de Escenario',
                'descripcion' => 'Monitores de escenario para que los artistas escuchen su interpretación.',
                'precio' => 140000,
            ],
            [
                'servicios_id' => 3,
                'nombre' => 'Cables y Accesorios',
                'descripcion' => 'Cables de audio, adaptadores y accesorios necesarios para conexiones.',
                'precio' => 50000,
            ],
        ]);

    }
}
