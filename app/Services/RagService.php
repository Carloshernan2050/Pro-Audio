<?php

namespace App\Services;

use App\Repositories\Interfaces\ServicioRepositoryInterface;

class RagService
{
    private ServicioRepositoryInterface $servicioRepository;

    public function __construct(ServicioRepositoryInterface $servicioRepository)
    {
        $this->servicioRepository = $servicioRepository;
    }

    /**
     * Busca datos relevantes en la base de datos según el mensaje del usuario.
     * Devuelve texto contextual o null si no encuentra nada útil.
     */
    public function recuperarContexto($mensaje)
    {
        // Usar repositorio en lugar de DB::table directo (DIP)
        $servicios = $this->servicioRepository->all();
        
        // Filtrar por término de búsqueda
        $resultados = $servicios->filter(function ($servicio) use ($mensaje) {
            return stripos($servicio->nombre_servicio, $mensaje) !== false
                || stripos($servicio->descripcion ?? '', $mensaje) !== false;
        })->take(5);

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
