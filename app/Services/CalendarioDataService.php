<?php

namespace App\Services;

use App\Models\Calendario;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class CalendarioDataService
{
    private const DEFAULT_PRODUCT_LABEL = 'Sin producto';

    /**
     * Elimina duplicados de registros basándose en contenido.
     */
    public function eliminarDuplicadosRegistros(Collection $registros): Collection
    {
        $registrosUnicos = [];
        $vistos = [];

        foreach ($registros as $registro) {
            $clave = $this->generarClaveUnicaRegistro($registro);

            if (!isset($vistos[$clave])) {
                $vistos[$clave] = true;
                $registrosUnicos[] = $registro;
            }
        }

        return collect($registrosUnicos);
    }

    /**
     * Genera una clave única para un registro basada en su contenido.
     */
    public function generarClaveUnicaRegistro($registro): string
    {
        $itemsKey = $registro->items->map(function($item) {
            return $item->movimientos_inventario_id . ':' . $item->cantidad;
        })->sort()->implode(',');

        return $registro->fecha_inicio . '|' . $registro->fecha_fin . '|' . $registro->descripcion_evento . '|' . $itemsKey;
    }

    /**
     * Transforma un registro en el formato de datos para la respuesta JSON.
     */
    public function transformarRegistroAData($registro, Collection $movimientos, Collection $inventarios): array
    {
        [$productosLista, $cantidadTotal] = $this->obtenerProductosYCantidad($registro, $movimientos, $inventarios);

        $fechaInicio = \Carbon\Carbon::parse($registro->fecha_inicio);
        $fechaFin = \Carbon\Carbon::parse($registro->fecha_fin);
        $diasReserva = $fechaInicio->diffInDays($fechaFin) + 1;

        return [
            'id' => $registro->id,
            'fecha_inicio' => $fechaInicio->format('d/m/Y'),
            'fecha_fin' => $fechaFin->format('d/m/Y'),
            'dias_reserva' => $diasReserva,
            'productos' => $productosLista,
            'cantidad_total' => $cantidadTotal,
            'descripcion_evento' => $registro->descripcion_evento,
            'tiene_items' => $registro->items && $registro->items->count() > 0
        ];
    }

    /**
     * Obtiene la lista de productos y cantidad total de un registro.
     */
    private function obtenerProductosYCantidad($registro, Collection $movimientos, Collection $inventarios): array
    {
        if ($registro->items && $registro->items->count() > 0) {
            return $this->obtenerProductosDesdeItems($registro, $movimientos, $inventarios);
        }

        return $this->obtenerProductoFormatoAntiguo($registro, $movimientos, $inventarios);
    }

    /**
     * Obtiene productos desde items (nuevo formato).
     */
    private function obtenerProductosDesdeItems($registro, Collection $movimientos, Collection $inventarios): array
    {
        $productosLista = [];
        $cantidadTotal = 0;

        foreach ($registro->items as $item) {
            $mov = $movimientos->get($item->movimientos_inventario_id);
            if (!$mov || !isset($inventarios[$mov->inventario_id])) {
                continue;
            }

            $productosLista[] = [
                'nombre' => $inventarios[$mov->inventario_id]->descripcion,
                'cantidad' => $item->cantidad
            ];
            $cantidadTotal += $item->cantidad;
        }

        return [$productosLista, $cantidadTotal];
    }

    /**
     * Obtiene producto del formato antiguo (un solo producto).
     */
    private function obtenerProductoFormatoAntiguo($registro, Collection $movimientos, Collection $inventarios): array
    {
        $productosLista = [];
        $cantidadTotal = 0;

        $movimiento = collect($movimientos)->first(function($m) use ($registro) {
            return $m->id == $registro->movimientos_inventario_id;
        });

        if ($movimiento && isset($inventarios[$movimiento->inventario_id])) {
            $cant = $registro->cantidad ?? 1;
            $productosLista[] = [
                'nombre' => $inventarios[$movimiento->inventario_id]->descripcion ?? self::DEFAULT_PRODUCT_LABEL,
                'cantidad' => $cant
            ];
            $cantidadTotal = $cant;
        }

        return [$productosLista, $cantidadTotal];
    }

    /**
     * Obtiene registros únicos.
     */
    public function obtenerRegistrosUnicos(): Collection
    {
        $registros = Calendario::with(['items', 'reserva'])
            ->orderBy('fecha', 'desc')
            ->orderBy('id', 'desc')
            ->get();

        return $this->eliminarDuplicadosRegistros($registros);
    }

    /**
     * Carga movimientos e inventarios.
     */
    public function cargarMovimientosEInventarios(): array
    {
        $movimientos = DB::table('movimientos_inventario')->get()->keyBy('id');
        $inventarios = DB::table('inventario')->orderBy('descripcion', 'asc')->get()->keyBy('id');

        return [$movimientos, $inventarios];
    }
}

