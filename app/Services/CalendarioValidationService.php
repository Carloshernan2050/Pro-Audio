<?php

namespace App\Services;

use App\Exceptions\InventarioNotFoundException;
use App\Exceptions\StockInsuficienteException;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class CalendarioValidationService
{
    private const MSG_SELECCIONAR_PRODUCTO = 'Debe seleccionar al menos un producto.';

    private const MSG_PRODUCTO_INVENTARIO = 'Debe seleccionar un producto del inventario.';

    private const MSG_PRODUCTO_NO_EXISTE = 'El producto seleccionado no existe.';

    private const MSG_CANTIDAD_OBLIGATORIA = 'La cantidad es obligatoria para cada producto.';

    private const MSG_CANTIDAD_ENTERO = 'La cantidad debe ser un número entero.';

    private const MSG_CANTIDAD_MAYOR_CERO = 'La cantidad debe ser mayor a 0.';

    private const MSG_FECHA_INICIO_OBLIGATORIA = 'La fecha de inicio es obligatoria.';

    private const MSG_FECHA_INICIO_VALIDA = 'La fecha de inicio debe ser una fecha válida.';

    private const MSG_FECHA_FIN_OBLIGATORIA = 'La fecha de fin es obligatoria.';

    private const MSG_FECHA_FIN_VALIDA = 'La fecha de fin debe ser una fecha válida.';

    private const MSG_FECHA_FIN_AFTER = 'La fecha de fin debe ser posterior o igual a la fecha de inicio.';

    private const MSG_DESCRIPCION_OBLIGATORIA = 'La descripción del evento es obligatoria.';

    private const MSG_DESCRIPCION_TEXTO = 'La descripción del evento debe ser texto.';

    private const MSG_SERVICIO_OBLIGATORIO = 'Debe seleccionar un servicio.';

    /**
     * Obtiene las reglas de validación para items.
     */
    public function getValidationRulesForItems(): array
    {
        return [
            'items' => 'required|array|min:1',
            'items.*.inventario_id' => 'required|exists:inventario,id',
            'items.*.cantidad' => 'required|integer|min:1',
            'fecha_inicio' => 'required|date',
            'fecha_fin' => 'required|date|after_or_equal:fecha_inicio',
            'descripcion_evento' => 'required|string',
        ];
    }

    /**
     * Obtiene los mensajes de validación para items.
     */
    public function getValidationMessagesForItems(): array
    {
        return [
            'items.required' => self::MSG_SELECCIONAR_PRODUCTO,
            'items.min' => self::MSG_SELECCIONAR_PRODUCTO,
            'items.*.inventario_id.required' => self::MSG_PRODUCTO_INVENTARIO,
            'items.*.inventario_id.exists' => self::MSG_PRODUCTO_NO_EXISTE,
            'items.*.cantidad.required' => self::MSG_CANTIDAD_OBLIGATORIA,
            'items.*.cantidad.integer' => self::MSG_CANTIDAD_ENTERO,
            'items.*.cantidad.min' => self::MSG_CANTIDAD_MAYOR_CERO,
            'fecha_inicio.required' => self::MSG_FECHA_INICIO_OBLIGATORIA,
            'fecha_inicio.date' => self::MSG_FECHA_INICIO_VALIDA,
            'fecha_fin.required' => self::MSG_FECHA_FIN_OBLIGATORIA,
            'fecha_fin.date' => self::MSG_FECHA_FIN_VALIDA,
            'fecha_fin.after_or_equal' => self::MSG_FECHA_FIN_AFTER,
            'descripcion_evento.required' => self::MSG_DESCRIPCION_OBLIGATORIA,
            'descripcion_evento.string' => self::MSG_DESCRIPCION_TEXTO,
        ];
    }

    /**
     * Obtiene las reglas de validación para formato antiguo.
     */
    public function getValidationRulesForOldFormat(): array
    {
        return [
            'movimientos_inventario_id' => 'required|exists:movimientos_inventario,id',
            'fecha_inicio' => 'required|date',
            'fecha_fin' => 'required|date|after_or_equal:fecha_inicio',
            'descripcion_evento' => 'required|string',
        ];
    }

    /**
     * Obtiene los mensajes de validación para formato antiguo.
     */
    public function getValidationMessagesForOldFormat(): array
    {
        return [
            'movimientos_inventario_id.required' => self::MSG_PRODUCTO_INVENTARIO,
            'movimientos_inventario_id.exists' => self::MSG_PRODUCTO_NO_EXISTE,
            'fecha_inicio.required' => self::MSG_FECHA_INICIO_OBLIGATORIA,
            'fecha_inicio.date' => self::MSG_FECHA_INICIO_VALIDA,
            'fecha_fin.required' => self::MSG_FECHA_FIN_OBLIGATORIA,
            'fecha_fin.date' => self::MSG_FECHA_FIN_VALIDA,
            'fecha_fin.after_or_equal' => self::MSG_FECHA_FIN_AFTER,
            'descripcion_evento.required' => self::MSG_DESCRIPCION_OBLIGATORIA,
            'descripcion_evento.string' => self::MSG_DESCRIPCION_TEXTO,
        ];
    }

    /**
     * Obtiene las reglas de validación para actualización con items.
     */
    public function getValidationRulesForUpdateItems(): array
    {
        return [
            'servicio' => 'required|string|max:120',
            'items' => 'required|array|min:1',
            'items.*.inventario_id' => 'required|exists:inventario,id',
            'items.*.cantidad' => 'required|integer|min:1',
            'fecha_inicio' => 'required|date',
            'fecha_fin' => 'required|date|after_or_equal:fecha_inicio',
            'descripcion_evento' => 'required|string',
        ];
    }

    /**
     * Obtiene los mensajes de validación para actualización con items.
     */
    public function getValidationMessagesForUpdateItems(): array
    {
        return array_merge([
            'servicio.required' => self::MSG_SERVICIO_OBLIGATORIO,
        ], $this->getValidationMessagesForItems());
    }

    /**
     * Obtiene las reglas de validación para actualización formato antiguo.
     */
    public function getValidationRulesForUpdateOldFormat(): array
    {
        return [
            'servicio' => 'required|string|max:120',
            'movimientos_inventario_id' => 'required|exists:movimientos_inventario,id',
            'fecha_inicio' => 'required|date',
            'fecha_fin' => 'required|date|after_or_equal:fecha_inicio',
            'descripcion_evento' => 'required|string',
            'cantidad' => 'nullable|integer|min:1',
        ];
    }

    /**
     * Obtiene los mensajes de validación para actualización formato antiguo.
     */
    public function getValidationMessagesForUpdateOldFormat(): array
    {
        return array_merge([
            'servicio.required' => self::MSG_SERVICIO_OBLIGATORIO,
            'cantidad.integer' => self::MSG_CANTIDAD_ENTERO,
            'cantidad.min' => self::MSG_CANTIDAD_MAYOR_CERO,
        ], $this->getValidationMessagesForOldFormat());
    }

    /**
     * Valida el stock disponible para todos los items.
     *
     * @throws InventarioNotFoundException Si el inventario no existe
     * @throws StockInsuficienteException Si no hay suficiente stock
     */
    public function validarStockParaItems(Request $request, array $items): void
    {
        foreach ($items as $item) {
            $inventarioId = $item['inventario_id'];

            $inventario = DB::table('inventario')->where('id', $inventarioId)->first();
            if (! $inventario) {
                throw new InventarioNotFoundException('El producto del inventario seleccionado no existe.');
            }

            $cantidadSolicitada = $item['cantidad'] ?? 1;
            $stockTotal = DB::table('inventario')->where('id', $inventarioId)->value('stock') ?? 0;
            $reservadas = $this->calcularReservadas($inventarioId, $request->fecha_inicio, $request->fecha_fin);
            $disponible = $stockTotal - $reservadas;

            if ($disponible < $cantidadSolicitada) {
                $producto = DB::table('inventario')->where('id', $inventarioId)->first();
                $nombreProducto = $producto->descripcion ?? 'Producto';
                throw new StockInsuficienteException($nombreProducto, $disponible, $cantidadSolicitada);
            }
        }
    }

    /**
     * Valida el stock para una actualización.
     *
     * @throws InventarioNotFoundException Si el inventario no existe
     * @throws StockInsuficienteException Si no hay suficiente stock
     */
    public function validarStockParaActualizacion(Request $request, array $items, array $itemsActuales, int $calendarioId): void
    {
        foreach ($items as $item) {
            $inventarioId = $item['inventario_id'];

            $inventario = DB::table('inventario')->where('id', $inventarioId)->first();
            if (! $inventario) {
                throw new InventarioNotFoundException('El producto del inventario seleccionado no existe.');
            }

            $cantidadSolicitada = $item['cantidad'] ?? 1;
            $stockTotal = DB::table('inventario')->where('id', $inventarioId)->value('stock') ?? 0;
            $cantidadActual = $itemsActuales[$inventarioId] ?? 0;
            $reservadas = $this->calcularReservadasExcluyendo($inventarioId, $request->fecha_inicio, $request->fecha_fin, $calendarioId);
            $disponible = ($stockTotal + $cantidadActual) - $reservadas;

            if ($disponible < $cantidadSolicitada) {
                $producto = DB::table('inventario')->where('id', $inventarioId)->first();
                $nombreProducto = $producto->descripcion ?? 'Producto';
                throw new StockInsuficienteException($nombreProducto, $disponible, $cantidadSolicitada);
            }
        }
    }

    /**
     * Calcula la cantidad reservada para un inventario en un rango de fechas.
     */
    public function calcularReservadas(int $inventarioId, string $fechaInicio, string $fechaFin): int
    {
        return DB::table('calendario')
            ->join('calendario_items', 'calendario.id', '=', 'calendario_items.calendario_id')
            ->join('movimientos_inventario', 'calendario_items.movimientos_inventario_id', '=', 'movimientos_inventario.id')
            ->where('movimientos_inventario.inventario_id', $inventarioId)
            ->where('calendario.fecha_inicio', '<', $fechaFin)
            ->where('calendario.fecha_fin', '>', $fechaInicio)
            ->sum('calendario_items.cantidad') ?? 0;
    }

    /**
     * Calcula reservadas excluyendo un calendario específico.
     */
    public function calcularReservadasExcluyendo(int $inventarioId, string $fechaInicio, string $fechaFin, int $excluirId): int
    {
        return DB::table('calendario')
            ->join('calendario_items', 'calendario.id', '=', 'calendario_items.calendario_id')
            ->join('movimientos_inventario', 'calendario_items.movimientos_inventario_id', '=', 'movimientos_inventario.id')
            ->where('movimientos_inventario.inventario_id', $inventarioId)
            ->where('calendario.id', '!=', $excluirId)
            ->where('calendario.fecha_inicio', '<', $fechaFin)
            ->where('calendario.fecha_fin', '>', $fechaInicio)
            ->sum('calendario_items.cantidad') ?? 0;
    }
}
