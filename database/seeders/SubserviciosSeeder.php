<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class SubServiciosSeeder extends Seeder
{
    public function run(): void
    {
        DB::table('sub_servicios')->insert([
            [
                'servicios_id' => 1,
                'nombre' => 'Grabación en estudio',
                'descripcion' => 'Servicio de grabación profesional en cabina.'
            ],
            [
                'servicios_id' => 1,
                'nombre' => 'Edición de audio',
                'descripcion' => 'Mezcla y edición de pistas de audio.'
            ],
            [
                'servicios_id' => 2,
                'nombre' => 'Alquiler de equipos',
                'descripcion' => 'Renta de micrófonos y consolas.'
            ],
        ]);


    }
}
