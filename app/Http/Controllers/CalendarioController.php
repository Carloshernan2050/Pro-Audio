<?php

namespace App\Http\Controllers;

use App\Exceptions\InventarioNotFoundException;
use App\Exceptions\StockInsuficienteException;
use App\Models\Calendario;
use App\Models\CalendarioItem;
use App\Models\MovimientosInventario;
use App\Models\Historial;
use App\Models\Inventario;
use App\Models\Reserva;
use App\Services\CalendarioValidationService;
use App\Services\CalendarioDataService;
use App\Services\CalendarioEventService;
use App\Services\CalendarioItemService;
use App\Services\ReservaService;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class CalendarioController extends Controller
{
    private CalendarioValidationService $validationService;
    private CalendarioDataService $dataService;
    private CalendarioEventService $eventService;
    private CalendarioItemService $itemService;
    private ReservaService $reservaService;

    private const DEFAULT_EVENT_TITLE = 'Alquiler';

    public function __construct(
        CalendarioValidationService $validationService,
        CalendarioDataService $dataService,
        CalendarioEventService $eventService,
        CalendarioItemService $itemService,
        ReservaService $reservaService
    ) {
        $this->validationService = $validationService;
        $this->dataService = $dataService;
        $this->eventService = $eventService;
        $this->itemService = $itemService;
        $this->reservaService = $reservaService;
    }



    private function isAdminLike(): bool
    {
        $roles = session('roles') ?? [session('role')];
        if (!is_array($roles)) { $roles = [$roles]; }
        $roles = array_map(function($r){ return is_string($r) ? strtolower($r) : $r; }, $roles);
        return in_array('administrador', $roles, true) || in_array('admin', $roles, true) || in_array('superadmin', $roles, true);
    }

    private function ensureAdminLike(): void
    {
        if (!$this->isAdminLike()) {
            abort(403, 'No autorizado');
        }
    }
    // Mostrar el calendario
    public function inicio()
    {
        $registros = $this->dataService->obtenerRegistrosUnicos();
        [$movimientos, $inventarios] = $this->dataService->cargarMovimientosEInventarios();

        $this->eventService->resetPaletteCursor();

        $eventos = [];
        $eventosItems = [];

        foreach ($registros as $registro) {
            $eventoData = $this->eventService->construirEvento($registro, $movimientos, $inventarios);
            $eventos[] = $eventoData['evento'];
            if (!empty($eventoData['items'])) {
                $eventosItems[$registro->id] = $eventoData['items'];
            }
        }

        $reservasPendientes = Reserva::with(['items.inventario'])
            ->where('estado', 'pendiente')
            ->orderByDesc('created_at')
            ->get();

        return view('usuarios.calendario', [
            'registros'   => $registros,
            'inventarios' => $inventarios,
            'movimientos' => $movimientos->values(),
            'eventos'     => $eventos,
            'eventosItems' => $eventosItems,
            'reservasPendientes' => $reservasPendientes,
        ]);
    }

    // Obtener eventos en formato JSON para recargar el calendario
    public function getEventos()
    {
        $registros = $this->dataService->obtenerRegistrosUnicos();
        [$movimientos, $inventarios] = $this->dataService->cargarMovimientosEInventarios();

        $this->eventService->resetPaletteCursor();

        $eventos = [];

        foreach ($registros as $registro) {
            $eventos[] = $this->eventService->construirEvento($registro, $movimientos, $inventarios)['evento'];
        }

        return response()->json($eventos);
    }

    // Obtener registros para el sidebar en formato JSON
    public function getRegistros()
    {
        $registros = Calendario::with(['items', 'reserva'])
            ->orderBy('fecha', 'desc')
            ->orderBy('id', 'desc')
            ->get();
        
        $registrosUnicos = $this->dataService->eliminarDuplicadosRegistros($registros);
        [$movimientos, $inventarios] = $this->dataService->cargarMovimientosEInventarios();

        $registrosData = [];
        foreach ($registrosUnicos as $registro) {
            $registrosData[] = $this->dataService->transformarRegistroAData($registro, $movimientos, $inventarios);
        }

        return response()->json([
            'registros' => $registrosData,
            'total' => count($registrosData)
        ]);
    }



    // Guardar nuevo evento
    public function guardar(Request $request)
    {
        $this->ensureAdminLike();
        
        DB::beginTransaction();
        
        try {
            $items = $this->normalizarItems($request->input('items', []));
            
            if (!empty($items)) {
                $calendario = $this->guardarConItems($request, $items);
            } else {
                $calendario = $this->guardarFormatoAntiguo($request);
            }
            
            DB::commit();
            
            return response()->json([
                'success' => true,
                'message' => 'Alquiler registrado correctamente',
                'calendario_id' => $calendario->id
            ]);
        } catch (\Illuminate\Validation\ValidationException $e) {
            DB::rollBack();
            throw $e;
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'error' => 'Error al guardar el alquiler: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Normaliza el array de items para asegurar índices numéricos.
     */
    private function normalizarItems($items): array
    {
        return is_array($items) ? array_values($items) : [];
    }

    /**
     * Guarda un calendario con items (nuevo formato).
     */
    private function guardarConItems(Request $request, array $items): Calendario
    {
        $request->validate($this->validationService->getValidationRulesForItems(), $this->validationService->getValidationMessagesForItems());
        
        $this->validationService->validarStockParaItems($request, $items);
        
        $cantidadTotal = array_sum(array_column($items, 'cantidad'));
        
        $calendario = Calendario::create([
            'personas_id'               => session('usuario_id'),
            'movimientos_inventario_id' => null,
            'fecha'                     => now(),
            'fecha_inicio'              => $request->fecha_inicio,
            'fecha_fin'                 => $request->fecha_fin,
            'descripcion_evento'        => $request->descripcion_evento,
            'cantidad'                  => $cantidadTotal,
            'evento'                    => self::DEFAULT_EVENT_TITLE
        ]);
        
        $this->itemService->crearItemsCalendario($calendario->id, $items);
        
        return $calendario;
    }

    /**
     * Guarda un calendario en formato antiguo.
     */
    private function guardarFormatoAntiguo(Request $request): Calendario
    {
        $request->validate($this->validationService->getValidationRulesForOldFormat(), $this->validationService->getValidationMessagesForOldFormat());

        return Calendario::create([
            'personas_id'               => session('usuario_id'),
            'movimientos_inventario_id' => $request->movimientos_inventario_id,
            'fecha'                     => now(),
            'fecha_inicio'              => $request->fecha_inicio,
            'fecha_fin'                 => $request->fecha_fin,
            'descripcion_evento'        => $request->descripcion_evento,
            'evento'                    => self::DEFAULT_EVENT_TITLE
        ]);
    }




    // Actualizar evento existente
    public function actualizar(Request $request, $id)
    {
        $this->ensureAdminLike();
        
        DB::beginTransaction();
        
        try {
            $calendario = Calendario::with(['items.movimientoInventario', 'reserva'])->findOrFail($id);
            $tieneItems = $calendario->items && $calendario->items->count() > 0;
            
            if ($tieneItems) {
                $this->actualizarConItems($request, $calendario, $id);
            } else {
                $this->actualizarFormatoAntiguo($request, $calendario, $id);
            }
            
            DB::commit();
            return redirect()->route('usuarios.calendario')->with('ok', 'Alquiler actualizado correctamente.');
        } catch (\Illuminate\Validation\ValidationException $e) {
            DB::rollBack();
            return redirect()->back()->withErrors($e->errors())->withInput();
        } catch (\Exception $e) {
            DB::rollBack();
            return redirect()->route('usuarios.calendario')->with('error', 'Error al actualizar: ' . $e->getMessage());
        }
    }

    /**
     * Actualiza un calendario con items (nuevo formato).
     */
    private function actualizarConItems(Request $request, Calendario $calendario, int $id): void
    {
        $items = $this->normalizarItems($request->input('items', []));
        
        $request->validate(
            $this->validationService->getValidationRulesForUpdateItems(),
            $this->validationService->getValidationMessagesForUpdateItems()
        );

        $itemsActuales = $this->obtenerItemsActuales($calendario);
        $this->validationService->validarStockParaActualizacion($request, $items, $itemsActuales, $id);
        $this->devolverStockActual($itemsActuales, $id);
        
        $cantidadTotal = array_sum(array_column($items, 'cantidad'));
        $calendario->update([
            'fecha_inicio'       => $request->fecha_inicio,
            'fecha_fin'         => $request->fecha_fin,
            'descripcion_evento' => $request->descripcion_evento,
            'cantidad'           => $cantidadTotal,
        ]);
        
        CalendarioItem::where('calendario_id', $id)->delete();
        $nuevosItemsReserva = $this->itemService->crearItemsCalendarioParaActualizacion($id, $items);
        $this->reservaService->actualizarReservaVinculada($request, $id, $cantidadTotal, $nuevosItemsReserva);
    }

    /**
     * Actualiza un calendario en formato antiguo.
     */
    private function actualizarFormatoAntiguo(Request $request, Calendario $calendario, int $id): void
    {
        $request->validate(
            $this->validationService->getValidationRulesForUpdateOldFormat(),
            $this->validationService->getValidationMessagesForUpdateOldFormat()
        );
        
        $updateData = [
            'movimientos_inventario_id' => $request->movimientos_inventario_id,
            'fecha_inicio'              => $request->fecha_inicio,
            'fecha_fin'                 => $request->fecha_fin,
            'descripcion_evento'        => $request->descripcion_evento,
        ];
        
        if ($request->has('cantidad')) {
            $updateData['cantidad'] = $request->cantidad;
        }
        
        $calendario->update($updateData);
        $this->reservaService->actualizarReservaFormatoAntiguo($request, $id, $calendario);
    }

    /**
     * Obtiene los items actuales del calendario agrupados por inventario.
     */
    private function obtenerItemsActuales(Calendario $calendario): array
    {
        $itemsActuales = [];
        foreach ($calendario->items as $itemActual) {
            if (!$itemActual->movimientoInventario) {
                continue;
            }
            $invId = $itemActual->movimientoInventario->inventario_id;
            $itemsActuales[$invId] = ($itemsActuales[$invId] ?? 0) + $itemActual->cantidad;
        }
        return $itemsActuales;
    }


    /**
     * Devuelve el stock actual antes de aplicar cambios.
     */
    private function devolverStockActual(array $itemsActuales, int $calendarioId): void
    {
        foreach ($itemsActuales as $invId => $cant) {
            if ($cant <= 0) {
                continue;
            }

            Inventario::where('id', $invId)->increment('stock', $cant);

            MovimientosInventario::create([
                'inventario_id' => $invId,
                'tipo_movimiento' => 'devuelto',
                'cantidad' => $cant,
                'fecha_movimiento' => now(),
                'descripcion' => 'Ajuste de reserva #' . $calendarioId . ' (devolución por actualización)',
            ]);
        }
    }



    // Eliminar evento
    public function eliminar($id)
    {
        $this->ensureAdminLike();

        $calendario = Calendario::with(['items.movimientoInventario', 'reserva'])->findOrFail($id);

        DB::transaction(function () use ($calendario) {
            foreach ($calendario->items as $item) {
                $movimiento = $item->movimientoInventario;

                if ($movimiento && $movimiento->inventario_id) {
                    Inventario::where('id', $movimiento->inventario_id)->increment('stock', $item->cantidad);

                    MovimientosInventario::create([
                        'inventario_id' => $movimiento->inventario_id,
                        'tipo_movimiento' => 'devuelto',
                        'cantidad' => $item->cantidad,
                        'fecha_movimiento' => now(),
                        'descripcion' => 'Devolución automática: Calendario #' . $calendario->id,
                    ]);
                }
            }

            $reserva = Reserva::where('calendario_id', $calendario->id)->first();

            if ($reserva) {
                $reserva->update([
                    'estado' => 'devuelta',
                    'calendario_id' => null,
                    'meta' => array_merge($reserva->meta ?? [], [
                        'devuelta_en' => now()->toDateTimeString(),
                    ]),
                ]);

                Historial::create([
                    'reserva_id' => $reserva->id,
                    'accion' => 'devuelta',
                    'confirmado_en' => now(),
                    'observaciones' => 'Reserva devuelta y evento eliminado del calendario.',
                ]);
            }

            $calendario->delete();
        });

        if (request()->ajax() || request()->wantsJson()) {
            return response()->json(['success' => true, 'message' => 'Evento eliminado y stock restaurado.']);
        }

        return back()->with('ok', 'Evento eliminado y stock restaurado.');
    }
}
