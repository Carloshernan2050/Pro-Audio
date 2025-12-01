<?php

namespace App\Http\Controllers;

use App\Models\Calendario;
use App\Models\CalendarioItem;
use App\Models\Historial;
use App\Models\Inventario;
use App\Models\MovimientosInventario;
use App\Models\Reserva;
use App\Models\ReservaItem;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class ReservaController extends Controller
{
    private const ESTADO_PENDIENTE = 'pendiente';

    private const ESTADO_CONFIRMADA = 'confirmada';

    private const TIPO_MOVIMIENTO_ALQUILADO = 'alquilado';

    private const EVENTO_RESERVA_CONFIRMADA = 'Reserva confirmada';

    private const META_SOURCE_CALENDARIO = 'calendario';

    private const ACCION_CONFIRMADA = 'confirmada';

    private const MENSAJE_SOLO_PENDIENTES_CONFIRMAR = 'Solo se pueden confirmar reservas pendientes.';

    private const MENSAJE_SOLO_PENDIENTES_CANCELAR = 'Solo se pueden cancelar reservas pendientes.';

    private const MENSAJE_INVENTARIO_NO_ENCONTRADO = 'Inventario asociado no encontrado.';

    public function index()
    {
        $this->authorizeAdminLike();

        $reservas = Reserva::with(['items.inventario'])
            ->orderBy('estado')
            ->orderByDesc('created_at')
            ->get();

        return response()->json($reservas);
    }

    public function store(Request $request)
    {
        $this->authorizeAdminLike();

        $items = $request->input('items', []);
        if (is_array($items)) {
            $items = array_values($items);
        }

        $request->validate([
            'servicio' => 'required|string|max:120',
            'fecha_inicio' => 'required|date',
            'fecha_fin' => 'required|date|after_or_equal:fecha_inicio',
            'descripcion_evento' => 'required|string',
            'items' => 'required|array|min:1',
            'items.*.inventario_id' => 'required|exists:inventario,id',
            'items.*.cantidad' => 'required|integer|min:1',
        ], [
            'items.required' => 'Debe seleccionar al menos un producto.',
            'items.min' => 'Debe seleccionar al menos un producto.',
            'items.*.inventario_id.required' => 'Debe seleccionar un producto del inventario.',
            'items.*.inventario_id.exists' => 'El producto seleccionado no existe.',
            'items.*.cantidad.required' => 'La cantidad es obligatoria para cada producto.',
            'items.*.cantidad.integer' => 'La cantidad debe ser un número entero.',
            'items.*.cantidad.min' => 'La cantidad debe ser mayor a 0.',
        ]);

        // Validar que la cantidad solicitada no supere el stock actual
        foreach ($items as $item) {
            $inventario = Inventario::findOrFail($item['inventario_id']);
            if ($item['cantidad'] > $inventario->stock) {
                return response()->json([
                    'error' => "La cantidad solicitada para '{$inventario->descripcion}' supera el stock disponible ({$inventario->stock}).",
                ], 422);
            }
        }

        return DB::transaction(function () use ($request, $items) {
            $cantidadTotal = array_sum(array_column($items, 'cantidad'));

            $reserva = Reserva::create([
                'personas_id' => session('usuario_id'),
                'servicio' => $request->input('servicio'),
                'fecha_inicio' => $request->fecha_inicio,
                'fecha_fin' => $request->fecha_fin,
                'descripcion_evento' => $request->descripcion_evento,
                'cantidad_total' => $cantidadTotal,
                'estado' => self::ESTADO_PENDIENTE,
                'meta' => [
                    'source' => self::META_SOURCE_CALENDARIO,
                ],
            ]);

            foreach ($items as $item) {
                ReservaItem::create([
                    'reserva_id' => $reserva->id,
                    'inventario_id' => $item['inventario_id'],
                    'cantidad' => $item['cantidad'],
                ]);
            }

            return response()->json([
                'success' => true,
                'message' => 'Reserva registrada correctamente.',
                'reserva_id' => $reserva->id,
            ], 201);
        });
    }

    public function confirm(Reserva $reserva)
    {
        $this->authorizeAdminLike();

        if ($reserva->estado !== self::ESTADO_PENDIENTE) {
            return response()->json(['error' => self::MENSAJE_SOLO_PENDIENTES_CONFIRMAR], 422);
        }

        return DB::transaction(function () use ($reserva) {
            $reserva->load('items.inventario');

            $validationError = $this->validateReservaItems($reserva);
            if ($validationError) {
                return $validationError;
            }

            $calendario = $this->createCalendarioFromReserva($reserva);
            $this->processReservaItems($reserva, $calendario);
            $this->updateReservaAndCreateHistorial($reserva, $calendario);

            return response()->json([
                'success' => true,
                'message' => 'Reserva confirmada correctamente.',
                'calendario_id' => $calendario->id,
            ]);
        });
    }

    private function validateReservaItems(Reserva $reserva)
    {
        if ($reserva->items->isEmpty()) {
            return response()->json(['error' => 'La reserva no tiene items asociados.'], 422);
        }

        $errorMessage = null;
        foreach ($reserva->items as $item) {
            $inventario = $item->inventario;
            if (! $inventario) {
                $errorMessage = self::MENSAJE_INVENTARIO_NO_ENCONTRADO;
                break;
            }

            if ($inventario->stock < $item->cantidad) {
                $errorMessage = "Stock insuficiente para '{$inventario->descripcion}'. Disponible: {$inventario->stock}, requerido: {$item->cantidad}";
                break;
            }
        }

        if ($errorMessage) {
            return response()->json(['error' => $errorMessage], 422);
        }

        return null;
    }

    private function createCalendarioFromReserva(Reserva $reserva): Calendario
    {
        return Calendario::create([
            'personas_id' => $reserva->personas_id,
            'movimientos_inventario_id' => null,
            'fecha' => now(),
            'descripcion_evento' => $reserva->descripcion_evento,
            'fecha_inicio' => $reserva->fecha_inicio,
            'fecha_fin' => $reserva->fecha_fin,
            'evento' => self::EVENTO_RESERVA_CONFIRMADA,
            'cantidad' => $reserva->cantidad_total,
        ]);
    }

    private function processReservaItems(Reserva $reserva, Calendario $calendario): void
    {
        foreach ($reserva->items as $item) {
            $inventario = $item->inventario;
            if (! $inventario) {
                continue;
            }

            $inventario->decrement('stock', $item->cantidad);

            $movimiento = MovimientosInventario::create([
                'inventario_id' => $inventario->id,
                'tipo_movimiento' => self::TIPO_MOVIMIENTO_ALQUILADO,
                'cantidad' => $item->cantidad,
                'fecha_movimiento' => now(),
                'descripcion' => self::EVENTO_RESERVA_CONFIRMADA.' #'.$reserva->id,
            ]);

            CalendarioItem::create([
                'calendario_id' => $calendario->id,
                'movimientos_inventario_id' => $movimiento->id,
                'cantidad' => $item->cantidad,
            ]);
        }
    }

    private function updateReservaAndCreateHistorial(Reserva $reserva, Calendario $calendario): void
    {
        $reserva->update([
            'estado' => self::ESTADO_CONFIRMADA,
            'calendario_id' => $calendario->id,
            'meta' => array_merge($reserva->meta ?? [], [
                'confirmada_en' => now()->toDateTimeString(),
                'calendario_id' => $calendario->id,
            ]),
        ]);

        Historial::create([
            'reserva_id' => $reserva->id,
            'accion' => self::ACCION_CONFIRMADA,
            'confirmado_en' => now(),
        ]);
    }

    public function destroy(Reserva $reserva)
    {
        $this->authorizeAdminLike();

        if ($reserva->estado !== self::ESTADO_PENDIENTE) {
            return response()->json(['error' => self::MENSAJE_SOLO_PENDIENTES_CANCELAR], 422);
        }

        $reserva->delete();

        return response()->json([
            'success' => true,
            'message' => 'Reserva cancelada correctamente.',
        ]);
    }

    private function authorizeAdminLike(): void
    {
        $roles = session('roles') ?? [session('role')];
        if (! is_array($roles)) {
            $roles = [$roles];
        }
        $roles = array_map(fn ($role) => is_string($role) ? strtolower($role) : $role, $roles);
        $isAllowed = in_array('superadmin', $roles, true) || in_array('admin', $roles, true) || in_array('administrador', $roles, true);
        if (! $isAllowed) {
            abort(403, 'No autorizado');
        }
    }
}
