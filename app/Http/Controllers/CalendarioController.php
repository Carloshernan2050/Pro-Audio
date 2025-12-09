<?php

namespace App\Http\Controllers;

use App\Services\CalendarioDependencies;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class CalendarioController extends Controller
{
    private CalendarioDependencies $dependencies;

    public const DEFAULT_EVENT_TITLE = 'Alquiler';

    public function __construct(CalendarioDependencies $dependencies)
    {
        $this->dependencies = $dependencies;
    }

    private function isAdminLike(): bool
    {
        $roles = session('roles') ?? [session('role')];
        if (! is_array($roles)) {
            $roles = [$roles];
        }
        $roles = array_map(function ($r) {
            return is_string($r) ? strtolower($r) : $r;
        }, $roles);

        return in_array('administrador', $roles, true) || in_array('admin', $roles, true) || in_array('superadmin', $roles, true);
    }

    private function ensureAdminLike(): void
    {
        if (! $this->isAdminLike()) {
            abort(403, 'No autorizado');
        }
    }

    // Mostrar el calendario
    public function inicio()
    {
        $registros = $this->dependencies->dataService->obtenerRegistrosUnicos();
        [$movimientos, $inventarios] = $this->dependencies->dataService->cargarMovimientosEInventarios();

        $this->dependencies->eventService->resetPaletteCursor();

        $eventos = [];
        $eventosItems = [];

        foreach ($registros as $registro) {
            $eventoData = $this->dependencies->eventService->construirEvento($registro, $movimientos, $inventarios);
            $eventos[] = $eventoData['evento'];
            if (! empty($eventoData['items'])) {
                $eventosItems[$registro->id] = $eventoData['items'];
            }
        }

        // Usar repositorio en lugar de modelo directo (DIP)
        $reservasPendientes = $this->dependencies->reservaRepository->getPendientes();

        return view('usuarios.calendario', [
            'registros' => $registros,
            'inventarios' => $inventarios,
            'movimientos' => $movimientos->values(),
            'eventos' => $eventos,
            'eventosItems' => $eventosItems,
            'reservasPendientes' => $reservasPendientes,
        ]);
    }

    // Obtener eventos en formato JSON para recargar el calendario
    public function getEventos()
    {
        $registros = $this->dependencies->dataService->obtenerRegistrosUnicos();
        [$movimientos, $inventarios] = $this->dependencies->dataService->cargarMovimientosEInventarios();

        $this->dependencies->eventService->resetPaletteCursor();

        $eventos = [];

        foreach ($registros as $registro) {
            $eventos[] = $this->dependencies->eventService->construirEvento($registro, $movimientos, $inventarios)['evento'];
        }

        return response()->json($eventos);
    }

    // Obtener registros para el sidebar en formato JSON
    public function getRegistros()
    {
        // Usar repositorio en lugar de modelo directo (DIP)
        $registros = $this->dependencies->calendarioRepository->allWithRelations();

        $registrosUnicos = $this->dependencies->dataService->eliminarDuplicadosRegistros($registros);
        [$movimientos, $inventarios] = $this->dependencies->dataService->cargarMovimientosEInventarios();

        $registrosData = [];
        foreach ($registrosUnicos as $registro) {
            $registrosData[] = $this->dependencies->dataService->transformarRegistroAData($registro, $movimientos, $inventarios);
        }

        return response()->json([
            'registros' => $registrosData,
            'total' => count($registrosData),
        ]);
    }

    // Guardar nuevo evento
    public function guardar(Request $request)
    {
        $this->ensureAdminLike();

        DB::beginTransaction();

        try {
            $items = $this->normalizarItems($request->input('items', []));

            if (! empty($items)) {
                $calendario = $this->guardarConItems($request, $items);
            } else {
                $calendario = $this->guardarFormatoAntiguo($request);
            }

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Alquiler registrado correctamente',
                'calendario_id' => $calendario->id,
            ]);
        } catch (\Illuminate\Validation\ValidationException $e) {
            DB::rollBack();
            throw $e;
        } catch (\Exception $e) {
            DB::rollBack();

            return response()->json([
                'error' => 'Error al guardar el alquiler: '.$e->getMessage(),
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
    private function guardarConItems(Request $request, array $items): \App\Models\Calendario
    {
        $request->validate($this->dependencies->validationService->getValidationRulesForItems(), $this->dependencies->validationService->getValidationMessagesForItems());

        $this->dependencies->validationService->validarStockParaItems($request, $items);

        $cantidadTotal = array_sum(array_column($items, 'cantidad'));

        // Usar repositorio en lugar de modelo directo (DIP)
        $calendario = $this->dependencies->calendarioRepository->create([
            'personas_id' => session('usuario_id'),
            'movimientos_inventario_id' => null,
            'fecha' => now(),
            'fecha_inicio' => $request->fecha_inicio,
            'fecha_fin' => $request->fecha_fin,
            'descripcion_evento' => $request->descripcion_evento,
            'cantidad' => $cantidadTotal,
            'evento' => self::DEFAULT_EVENT_TITLE,
        ]);

        $this->dependencies->itemService->crearItemsCalendario($calendario->id, $items);

        return $calendario;
    }

    /**
     * Guarda un calendario en formato antiguo.
     */
    private function guardarFormatoAntiguo(Request $request): \App\Models\Calendario
    {
        $request->validate($this->dependencies->validationService->getValidationRulesForOldFormat(), $this->dependencies->validationService->getValidationMessagesForOldFormat());

        // Usar repositorio en lugar de modelo directo (DIP)
        return $this->dependencies->calendarioRepository->create([
            'personas_id' => session('usuario_id'),
            'movimientos_inventario_id' => $request->movimientos_inventario_id,
            'fecha' => now(),
            'fecha_inicio' => $request->fecha_inicio,
            'fecha_fin' => $request->fecha_fin,
            'descripcion_evento' => $request->descripcion_evento,
            'evento' => self::DEFAULT_EVENT_TITLE,
        ]);
    }

    // Actualizar evento existente
    public function actualizar(Request $request, $id)
    {
        $this->ensureAdminLike();

        DB::beginTransaction();

        try {
            // Usar repositorio en lugar de modelo directo (DIP)
            $calendario = $this->dependencies->calendarioRepository->findWithRelations($id);
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

            return redirect()->route('usuarios.calendario')->with('error', 'Error al actualizar: '.$e->getMessage());
        }
    }

    /**
     * Actualiza un calendario con items (nuevo formato).
     */
    private function actualizarConItems(Request $request, $calendario, int $id): void
    {
        $items = $this->normalizarItems($request->input('items', []));

        $request->validate(
            $this->dependencies->validationService->getValidationRulesForUpdateItems(),
            $this->dependencies->validationService->getValidationMessagesForUpdateItems()
        );

        $itemsActuales = $this->obtenerItemsActuales($calendario);
        $this->dependencies->validationService->validarStockParaActualizacion($request, $items, $itemsActuales, $id);
        $this->devolverStockActual($itemsActuales, $id);

        $cantidadTotal = array_sum(array_column($items, 'cantidad'));
        
        // Usar repositorio en lugar de modelo directo (DIP)
        $this->dependencies->calendarioRepository->update($id, [
            'fecha_inicio' => $request->fecha_inicio,
            'fecha_fin' => $request->fecha_fin,
            'descripcion_evento' => $request->descripcion_evento,
            'cantidad' => $cantidadTotal,
        ]);

        // Usar repositorio en lugar de modelo directo (DIP)
        $this->dependencies->calendarioItemRepository->deleteByCalendarioId($id);
        $nuevosItemsReserva = $this->dependencies->itemService->crearItemsCalendarioParaActualizacion($id, $items);
        $this->dependencies->reservaService->actualizarReservaVinculada($request, $id, $cantidadTotal, $nuevosItemsReserva);
    }

    /**
     * Actualiza un calendario en formato antiguo.
     */
    private function actualizarFormatoAntiguo(Request $request, $calendario, int $id): void
    {
        $request->validate(
            $this->dependencies->validationService->getValidationRulesForUpdateOldFormat(),
            $this->dependencies->validationService->getValidationMessagesForUpdateOldFormat()
        );

        $updateData = [
            'movimientos_inventario_id' => $request->movimientos_inventario_id,
            'fecha_inicio' => $request->fecha_inicio,
            'fecha_fin' => $request->fecha_fin,
            'descripcion_evento' => $request->descripcion_evento,
        ];

        if ($request->has('cantidad')) {
            $updateData['cantidad'] = $request->cantidad;
        }

        // Usar repositorio en lugar de modelo directo (DIP)
        $this->dependencies->calendarioRepository->update($id, $updateData);
        $this->dependencies->reservaService->actualizarReservaFormatoAntiguo($request, $id, $calendario);
    }

    /**
     * Obtiene los items actuales del calendario agrupados por inventario.
     */
    private function obtenerItemsActuales($calendario): array
    {
        $itemsActuales = [];
        foreach ($calendario->items as $itemActual) {
            if (! $itemActual->movimientoInventario) {
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

            // Usar repositorio en lugar de modelo directo (DIP)
            $this->dependencies->inventarioRepository->incrementStock($invId, $cant);

            // Usar repositorio en lugar de modelo directo (DIP)
            $this->dependencies->movimientoInventarioRepository->create([
                'inventario_id' => $invId,
                'tipo_movimiento' => 'devuelto',
                'cantidad' => $cant,
                'fecha_movimiento' => now(),
                'descripcion' => 'Ajuste de reserva #'.$calendarioId.' (devolución por actualización)',
            ]);
        }
    }

    // Eliminar evento
    public function eliminar($id)
    {
        $this->ensureAdminLike();

        // Usar repositorio en lugar de modelo directo (DIP)
        $calendario = $this->dependencies->calendarioRepository->findWithRelations($id);

        DB::transaction(function () use ($calendario) {
            foreach ($calendario->items as $item) {
                $movimiento = $item->movimientoInventario;

                if ($movimiento && $movimiento->inventario_id) {
                    // Usar repositorio en lugar de modelo directo (DIP)
                    $this->dependencies->inventarioRepository->incrementStock($movimiento->inventario_id, $item->cantidad);

                    // Usar repositorio en lugar de modelo directo (DIP)
                    $this->dependencies->movimientoInventarioRepository->create([
                        'inventario_id' => $movimiento->inventario_id,
                        'tipo_movimiento' => 'devuelto',
                        'cantidad' => $item->cantidad,
                        'fecha_movimiento' => now(),
                        'descripcion' => 'Devolución automática: Calendario #'.$calendario->id,
                    ]);
                }
            }

            // Usar repositorio en lugar de modelo directo (DIP)
            $reserva = $this->dependencies->reservaRepository->findByCalendarioId($calendario->id);

            if ($reserva) {
                // Usar repositorio en lugar de modelo directo (DIP)
                $this->dependencies->reservaRepository->update($reserva->id, [
                    'estado' => 'finalizada',
                    'calendario_id' => null,
                    'meta' => array_merge($reserva->meta ?? [], [
                        'finalizada_en' => now()->toDateTimeString(),
                    ]),
                ]);

                // Usar repositorio en lugar de modelo directo (DIP)
                $this->dependencies->historialRepository->create([
                    'calendario_id' => $calendario->id,
                    'reserva_id' => $reserva->id,
                    'accion' => 'finalizada',
                    'confirmado_en' => now(),
                    'observaciones' => 'Reserva finalizada y evento eliminado del calendario.',
                ]);
            }

            // Usar repositorio en lugar de modelo directo (DIP)
            $this->dependencies->calendarioRepository->delete($calendario->id);
        });

        if (request()->ajax() || request()->wantsJson()) {
            return response()->json(['success' => true, 'message' => 'Evento eliminado y stock restaurado.']);
        }

        return back()->with('ok', 'Evento eliminado y stock restaurado.');
    }
}
