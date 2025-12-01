<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class InventarioSeeder extends Seeder
{
    public function run(): void
    {
        DB::table('inventario')->insert([
            [
                'descripcion' => 'Cámara fotográfica profesional',
                'stock' => 5,
            ],
            [
                'descripcion' => 'Micrófono inalámbrico',
                'stock' => 12,
            ],
            [
                'descripcion' => 'Luces LED para estudio',
                'stock' => 20,
            ],
            [
                'descripcion' => 'Sillas plegables',
                'stock' => 50,
            ],
            [
                'descripcion' => 'Proyector HD',
                'stock' => 3,
            ],
            [
                'descripcion' => 'Consola de mezclas',
                'stock' => 8,
            ],
            [
                'descripcion' => 'Bafles autoamplificados',
                'stock' => 15,
            ],
            [
                'descripcion' => 'Cables de audio',
                'stock' => 100,
            ],
        ]);
    }
}
