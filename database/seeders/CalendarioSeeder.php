<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class CalendarioSeeder extends Seeder
{
    public function run(): void
    {
        DB::table('calendario')->insert([
            [
                'descripcion_evento' => 'Grabación con cliente VIP',
                'evento'             => 'Sesión de grabación',
                'fecha'              => '2025-08-20',
                'fecha_inicio'       => '2025-08-20 09:00:00',
                'fecha_fin'          => '2025-08-20 12:00:00',
                'personas_id'        => 1,
                'movimientos_inventario_id' => 1,
            ],
            [
                'descripcion_evento' => 'Revisión general del estudio',
                'evento'             => 'Mantenimiento de equipos',
                'fecha'              => '2025-08-25',
                'fecha_inicio'       => '2025-08-25 14:00:00',
                'fecha_fin'          => '2025-08-25 18:00:00',
                'personas_id'        => 1,
                'movimientos_inventario_id' => 2,
            ]
        ]);

    }
}
