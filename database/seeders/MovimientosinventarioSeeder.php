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
                'tipo_movimiento' => 'entrada',
                'cantidad' => 10,
                'fecha_movimiento' => Carbon::now()->subDays(7),
                'descripcion' => 'Ingreso de micrófonos nuevos'
            ],
            [
                'inventario_id' => 1,
                'tipo_movimiento' => 'alquilado',
                'cantidad' => 4,
                'fecha_movimiento' => Carbon::now()->subDays(4),
                'descripcion' => 'Alquiler de micrófonos para evento corporativo'
            ],
            [
                'inventario_id' => 1,
                'tipo_movimiento' => 'devuelto',
                'cantidad' => 2,
                'fecha_movimiento' => Carbon::now()->subDays(3),
                'descripcion' => 'Devolución parcial de micrófonos'
            ],
            [
                'inventario_id' => 2,
                'tipo_movimiento' => 'salida',
                'cantidad' => 1,
                'fecha_movimiento' => Carbon::now()->subDays(2),
                'descripcion' => 'Préstamo de consola para evento'
            ],
            [
                'inventario_id' => 2,
                'tipo_movimiento' => 'devuelto',
                'cantidad' => 1,
                'fecha_movimiento' => Carbon::now()->subDay(),
                'descripcion' => 'Devolución de consola'
            ],
        ]);
    }
}
