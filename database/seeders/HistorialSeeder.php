<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class HistorialSeeder extends Seeder
{
    public function run(): void
    {
        DB::table('historial')->insert([
            [
                'calendario_id' => 1,
            ],
            [
                'calendario_id' => 2,
            ],
        ]);
    }
}
