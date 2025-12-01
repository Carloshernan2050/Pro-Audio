<?php

namespace App\Services;

use App\Models\SubServicios;
use Illuminate\Support\Collection;

class ChatbotSubServicioService
{
    private ChatbotResponseBuilder $responseBuilder;

    public function __construct(ChatbotResponseBuilder $responseBuilder)
    {
        $this->responseBuilder = $responseBuilder;
    }

    public function obtenerSubServiciosPorIntenciones(array $intenciones): Collection
    {
        if (empty($intenciones)) {
            return collect();
        }

        $query = SubServicios::query()
            ->select('sub_servicios.id', 'sub_servicios.nombre', 'sub_servicios.precio', 'servicios.nombre_servicio')
            ->join('servicios', 'servicios.id', '=', 'sub_servicios.servicios_id')
            ->whereIn('servicios.nombre_servicio', $intenciones);

        return $this->responseBuilder->ordenarSubServicios($query)->get();
    }

    public function obtenerItemsSeleccionados(array $ids): Collection
    {
        if (empty($ids)) {
            return collect();
        }

        return SubServicios::query()
            ->whereIn('id', $ids)
            ->with('servicio')
            ->get(['id', 'servicios_id', 'nombre', 'precio']);
    }

    public function buscarSubServiciosRelacionados(string $mensajeCorregido, array $tokens, array $intenciones): Collection
    {
        return $this->responseBuilder->ordenarSubServicios(
            $this->responseBuilder->subServiciosQuery()
                ->where(function ($q) use ($mensajeCorregido, $tokens) {
                    if ($mensajeCorregido !== '') {
                        $q->where('sub_servicios.nombre', 'like', "%{$mensajeCorregido}%")
                            ->orWhere('sub_servicios.descripcion', 'like', "%{$mensajeCorregido}%");
                    }
                    foreach ($tokens as $tk) {
                        $tk = trim($tk);
                        if ($tk !== '') {
                            $q->orWhere('sub_servicios.nombre', 'like', "%{$tk}%")
                                ->orWhere('sub_servicios.descripcion', 'like', "%{$tk}%");
                        }
                    }
                })
                ->when(! empty($intenciones), function ($q) use ($intenciones) {
                    $q->whereIn('servicios.nombre_servicio', $intenciones);
                })
        )->limit(12)->get();
    }
}
