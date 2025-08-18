<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class ServiciosSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        DB::table('servicios')->insert([
            ['nombre_servicio' => 'Animación'],
            ['nombre_servicio' => 'Publicidad'],
            ['nombre_servicio' => 'Alquiler'],
        ]);
    }
}
