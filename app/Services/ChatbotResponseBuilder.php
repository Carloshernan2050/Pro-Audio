<?php

namespace App\Services;

use App\Models\SubServicios;

class ChatbotResponseBuilder
{
    public function responderConOpciones(): \Illuminate\Http\JsonResponse
    {
        return $this->mostrarCatalogoJson(
            '¡Hola! Soy tu asistente de cotizaciones. Selecciona los sub-servicios que deseas agregar a tu cotización:',
            null,
            (array) session('chat.selecciones', [])
        );
    }

    public function mostrarCatalogoJson(string $mensaje, ?int $dias = null, array $seleccionesPrevias = []): \Illuminate\Http\JsonResponse
    {
        $items = $this->ordenarSubServicios($this->subServiciosQuery())->get();

        return $this->responderOpciones($mensaje, $items, $dias, $seleccionesPrevias);
    }

    public function responderOpciones(string $mensaje, $items, ?int $dias = null, array $seleccionesPrevias = []): \Illuminate\Http\JsonResponse
    {
        return response()->json([
            'respuesta' => $mensaje,
            'optionGroups' => $this->formatearOpciones($items),
            'days' => $dias,
            'seleccionesPrevias' => $seleccionesPrevias,
        ]);
    }

    public function solicitarConfirmacionIntencion(string $lista, array $intenciones, int $dias, ?int $daysForResponse, ?array $hint = null): \Illuminate\Http\JsonResponse
    {
        $tok = isset($hint['token']) ? trim((string) $hint['token']) : null;
        $diasParaMeta = $dias > 0 ? $dias : null;
        $respuestaTexto = $tok ? "Por \"{$tok}\" ¿te refieres a {$lista}?" : "¿Te refieres a {$lista}?";

        return response()->json([
            'respuesta' => $respuestaTexto,
            'actions' => [
                ['id' => 'confirm_intent', 'label' => 'Sí, continuar', 'meta' => ['intenciones' => $intenciones, 'dias' => $diasParaMeta]],
                ['id' => 'reject_intent', 'label' => 'No, mostrar catálogo'],
            ],
            'days' => $daysForResponse,
        ]);
    }

    public function mostrarOpcionesConIntenciones(array $intenciones, $relSub, int $dias, ?int $daysForResponse): \Illuminate\Http\JsonResponse
    {
        $lista = implode(' y ', $intenciones);
        $prefijo = '';
        if ($dias > 0) {
            $prefijo = " para {$dias} día".($dias > 1 ? 's' : '');
        }
        $seleccionesActuales = (array) session('chat.selecciones', []);

        return $this->responderOpciones(
            "Estas son nuestras opciones de {$lista}{$prefijo}. Selecciona los sub-servicios que deseas cotizar:",
            $relSub,
            $daysForResponse,
            $seleccionesActuales
        );
    }

    public function responderCotizacion($items, int $diasCalculo, array $selecciones, bool $mostrarDiasSiempre = false): \Illuminate\Http\JsonResponse
    {
        $detalle = $this->construirDetalleCotizacion($items, $diasCalculo, $mostrarDiasSiempre);
        $total = $detalle['total'];
        $mensaje = $detalle['mensaje'];

        return response()->json([
            'respuesta' => $mensaje,
            'cotizacion' => [
                'items' => $detalle['items'],
                'total' => $total,
                'dias' => $diasCalculo,
            ],
            'selecciones' => $selecciones,
            'actions' => [
                ['id' => 'add_more', 'label' => 'Agregar más servicios'],
                ['id' => 'clear', 'label' => 'Limpiar cotización'],
                ['id' => 'finish', 'label' => 'Terminar cotización'],
            ],
        ]);
    }

    public function construirDetalleCotizacion($items, int $diasCalculo, bool $mostrarDiasSiempre): array
    {
        $total = 0.0;
        $itemsArray = [];
        foreach ($items as $item) {
            $precio = (float) (is_array($item) ? $item['precio'] : $item->precio);
            $subtotal = $precio * $diasCalculo;
            $total += $subtotal;
            $itemsArray[] = [
                'id' => is_array($item) ? $item['id'] : $item->id,
                'nombre' => is_array($item) ? $item['nombre'] : $item->nombre,
                'precio_unitario' => $precio,
                'dias' => $diasCalculo,
                'subtotal' => $subtotal,
            ];
        }
        $mensaje = "Cotización calculada para {$diasCalculo} día".($diasCalculo > 1 ? 's' : '').':';
        if ($mostrarDiasSiempre || $diasCalculo > 1) {
            $mensaje .= ' Total: $'.number_format($total, 2, '.', ',');
        }

        return [
            'items' => $itemsArray,
            'total' => $total,
            'mensaje' => $mensaje,
        ];
    }

    public function subServiciosQuery()
    {
        return SubServicios::query()
            ->select('sub_servicios.id', 'sub_servicios.nombre', 'sub_servicios.precio', 'servicios.nombre_servicio')
            ->join('servicios', 'servicios.id', '=', 'sub_servicios.servicios_id');
    }

    public function ordenarSubServicios($query)
    {
        return $query
            ->orderBy('servicios.nombre_servicio')
            ->orderBy('sub_servicios.nombre');
    }

    private function formatearOpciones($items): array
    {
        return collect($items)
            ->groupBy(function ($item) {
                return is_array($item) ? $item['servicio'] : $item->nombre_servicio;
            })
            ->sortKeys()
            ->map(function ($grupo, $servicio) {
                return [
                    'servicio' => $servicio,
                    'items' => collect($grupo)
                        ->map(function ($item) {
                            return [
                                'id' => is_array($item) ? $item['id'] : $item->id,
                                'nombre' => is_array($item) ? $item['nombre'] : $item->nombre,
                                'precio' => (float) (is_array($item) ? $item['precio'] : $item->precio),
                            ];
                        })
                        ->sortBy('nombre')
                        ->values()
                        ->all(),
                ];
            })
            ->values()
            ->all();
    }
}
