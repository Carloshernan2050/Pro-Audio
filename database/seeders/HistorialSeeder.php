<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class HistorialSeeder extends Seeder
{
    public function run(): void
    {
        // Verificar si los IDs existen en la tabla calendario
        $calendarioIds = DB::table('calendario')->pluck('id')->toArray();

        $data = [];
        foreach ([1, 2] as $id) {
            if (in_array($id, $calendarioIds)) {
                $data[] = ['calendario_id' => $id];
            }
        }

        // Insertar solo si hay datos válidos
        if (!empty($data)) {
            DB::table('historial')->insert($data);
        }
    }
}
