<?php

namespace App\Http\Controllers;

use App\Models\Reserva;
use App\Models\ReservaItem;
use App\Models\Inventario;
use App\Models\Historial;
use App\Models\Calendario;
use App\Models\CalendarioItem;
use App\Models\MovimientosInventario;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class ReservaController extends Controller
{
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
                    'error' => "La cantidad solicitada para '{$inventario->descripcion}' supera el stock disponible ({$inventario->stock})."
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
                'estado' => 'pendiente',
                'meta' => [
                    'source' => 'calendario',
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

        if ($reserva->estado !== 'pendiente') {
            return response()->json(['error' => 'Solo se pueden confirmar reservas pendientes.'], 422);
        }

        return DB::transaction(function () use ($reserva) {
            $reserva->load('items.inventario');

            foreach ($reserva->items as $item) {
                $inventario = $item->inventario;
                if (!$inventario) {
                    return response()->json(['error' => 'Inventario asociado no encontrado.'], 422);
                }

                if ($inventario->stock < $item->cantidad) {
                    return response()->json([
                        'error' => "Stock insuficiente para '{$inventario->descripcion}'. Disponible: {$inventario->stock}, requerido: {$item->cantidad}"
                    ], 422);
                }
            }

            $calendario = Calendario::create([
                'personas_id' => $reserva->personas_id,
                'movimientos_inventario_id' => null,
                'fecha' => now(),
                'descripcion_evento' => $reserva->descripcion_evento,
                'fecha_inicio' => $reserva->fecha_inicio,
                'fecha_fin' => $reserva->fecha_fin,
                'evento' => 'Reserva confirmada',
                'cantidad' => $reserva->cantidad_total,
            ]);

            foreach ($reserva->items as $item) {
                $inventario = $item->inventario;
                if (!$inventario) {
                    continue;
                }

                $inventario->decrement('stock', $item->cantidad);

                $movimiento = MovimientosInventario::create([
                    'inventario_id' => $inventario->id,
                    'tipo_movimiento' => 'alquilado',
                    'cantidad' => $item->cantidad,
                    'fecha_movimiento' => now(),
                    'descripcion' => 'Reserva confirmada #' . $reserva->id,
                ]);

                CalendarioItem::create([
                    'calendario_id' => $calendario->id,
                    'movimientos_inventario_id' => $movimiento->id,
                    'cantidad' => $item->cantidad,
                ]);
            }

            $reserva->update([
                'estado' => 'confirmada',
                'calendario_id' => $calendario->id,
                'meta' => array_merge($reserva->meta ?? [], [
                    'confirmada_en' => now()->toDateTimeString(),
                    'calendario_id' => $calendario->id,
                ]),
            ]);

            Historial::create([
                'reserva_id' => $reserva->id,
                'accion' => 'confirmada',
                'confirmado_en' => now(),
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Reserva confirmada correctamente.',
                'calendario_id' => $calendario->id,
            ]);
        });
    }

    public function destroy(Reserva $reserva)
    {
        $this->authorizeAdminLike();

        if ($reserva->estado !== 'pendiente') {
            return response()->json(['error' => 'Solo se pueden cancelar reservas pendientes.'], 422);
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
        if (!is_array($roles)) {
            $roles = [$roles];
        }
        $roles = array_map(fn ($role) => is_string($role) ? strtolower($role) : $role, $roles);
        $isAllowed = in_array('superadmin', $roles, true) || in_array('admin', $roles, true) || in_array('administrador', $roles, true);
        if (!$isAllowed) {
            abort(403, 'No autorizado');
        }
    }
}

