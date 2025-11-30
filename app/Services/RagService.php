<?php

namespace App\Services;

use Illuminate\Support\Facades\DB;

class RagService
{
    /**
     * Busca datos relevantes en la base de datos según el mensaje del usuario.
     * Devuelve texto contextual o null si no encuentra nada útil.
     */
    public function recuperarContexto($mensaje)
    {
        // Puedes adaptar esto a tu tabla y columnas
        $resultados = DB::table('servicios')
            ->where('nombre_servicio', 'like', "%{$mensaje}%")
            ->orWhere('descripcion', 'like', "%{$mensaje}%")
            ->limit(5)
            ->get();

        if ($resultados->isEmpty()) {
            return null;
        }

        // Concatenar los registros encontrados para dárselos al modelo
        $contexto = "Datos relacionados en la base de datos:\n";
        foreach ($resultados as $servicio) {
            $contexto .= "- {$servicio->nombre_servicio}: {$servicio->descripcion}\n";
        }

        return $contexto;
    }
}
