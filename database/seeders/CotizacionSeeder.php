<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class CotizacionSeeder extends Seeder
{
    public function run(): void
    {
        $precio1 = DB::table('sub_servicios')->where('id', 1)->value('precio') ?? 0;
        $precio2 = DB::table('sub_servicios')->where('id', 2)->value('precio') ?? 0;

        DB::table('cotizacion')->insert([
            [
                'personas_id' => 1,
                'sub_servicios_id' => 1,
                'monto' => $precio1,
                'fecha_cotizacion' => now()
            ],
            [
                'personas_id' => 2,
                'sub_servicios_id' => 2,
                'monto' => $precio2,
                'fecha_cotizacion' => now()
            ],
        ]);

    }
}
