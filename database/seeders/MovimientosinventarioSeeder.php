<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class MovimientosInventarioSeeder extends Seeder
{
    public function run()
    {
        DB::table('movimientos_inventario')->insert([
            [
                'inventario_id' => 1,
                'tipo_movimiento' => 'Entrada',
                'cantidad' => 10,
                'fecha_movimiento' => Carbon::now()->subDays(7),
                'descripcion' => 'Ingreso de micrófonos nuevos'
            ],
            [
                'inventario_id' => 1,
                'tipo_movimiento' => 'Salida',
                'cantidad' => 2,
                'fecha_movimiento' => Carbon::now()->subDays(5),
                'descripcion' => 'Préstamo de micrófonos para evento'
            ],
            [
                'inventario_id' => 2,
                'tipo_movimiento' => 'Ajuste',
                'cantidad' => 1,
                'fecha_movimiento' => Carbon::now()->subDays(2),
                'descripcion' => 'Revisión de consolas de audio'
            ],
        ]);
    }
}
