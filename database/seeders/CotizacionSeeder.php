<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class CotizacionSeeder extends Seeder
{
    public function run(): void
    {
        DB::table('cotizacion')->insert([
            [
                'personas_id' => 1,
                'sub_servicios_id' => 1,
                'monto' => 1200000,
                'fecha_cotizacion' => now()
            ],
            [
                'personas_id' => 2,
                'sub_servicios_id' => 2,
                'monto' => 850000,
                'fecha_cotizacion' => now()
            ],
        ]);

    }
}
