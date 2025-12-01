<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class IngresoSeeder extends Seeder
{
    public function run(): void
    {
        DB::table('ingreso')->insert([
            [
                'personas_id' => 1,
                'fecha_ingreso' => now(),
            ],
            [
                'personas_id' => 2,
                'fecha_ingreso' => now(),
            ],
        ]);

    }
}
