<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class InventarioSeeder extends Seeder
{
    public function run(): void
    {
        DB::table('inventario')->insert([
            [
                'descripcion'         => 'Cámara fotográfica profesional',
                'cantidad_disponible'=> 5,
                'fecha_actualizacion'=> Carbon::now(),
            ],
            [
                'descripcion'         => 'Micrófono inalámbrico',
                'cantidad_disponible'=> 12,
                'fecha_actualizacion'=> Carbon::now(),
            ],
            [
                'descripcion'         => 'Luces LED para estudio',
                'cantidad_disponible'=> 20,
                'fecha_actualizacion'=> Carbon::now(),
            ],
            [
                'descripcion'         => 'Sillas plegables',
                'cantidad_disponible'=> 50,
                'fecha_actualizacion'=> Carbon::now(),
            ],
            [
                'descripcion'         => 'Proyector HD',
                'cantidad_disponible'=> 3,
                'fecha_actualizacion'=> Carbon::now(),
            ],
        ]);
    }
}
