<?php

namespace App\Http\Controllers;

use App\Repositories\Interfaces\CalendarioItemRepositoryInterface;
use App\Repositories\Interfaces\CalendarioRepositoryInterface;
use App\Repositories\Interfaces\HistorialRepositoryInterface;
use App\Repositories\Interfaces\InventarioRepositoryInterface;
use App\Repositories\Interfaces\MovimientoInventarioRepositoryInterface;
use App\Repositories\Interfaces\ReservaItemRepositoryInterface;
use App\Repositories\Interfaces\ReservaRepositoryInterface;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class ReservaController extends Controller
{
    private ReservaRepositoryInterface $reservaRepository;

    private ReservaItemRepositoryInterface $reservaItemRepository;

    private InventarioRepositoryInterface $inventarioRepository;

    private CalendarioRepositoryInterface $calendarioRepository;

    private CalendarioItemRepositoryInterface $calendarioItemRepository;

    private MovimientoInventarioRepositoryInterface $movimientoInventarioRepository;

    private HistorialRepositoryInterface $historialRepository;

    public function __construct(
        ReservaRepositoryInterface $reservaRepository,
        ReservaItemRepositoryInterface $reservaItemRepository,
        InventarioRepositoryInterface $inventarioRepository,
        CalendarioRepositoryInterface $calendarioRepository,
        CalendarioItemRepositoryInterface $calendarioItemRepository,
        MovimientoInventarioRepositoryInterface $movimientoInventarioRepository,
        HistorialRepositoryInterface $historialRepository
    ) {
        $this->reservaRepository = $reservaRepository;
        $this->reservaItemRepository = $reservaItemRepository;
        $this->inventarioRepository = $inventarioRepository;
        $this->calendarioRepository = $calendarioRepository;
        $this->calendarioItemRepository = $calendarioItemRepository;
        $this->movimientoInventarioRepository = $movimientoInventarioRepository;
        $this->historialRepository = $historialRepository;
    }
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

        // Usar repositorio en lugar de modelo directo (DIP)
        $reservas = $this->reservaRepository->allWithRelations();

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

        // Validar que la cantidad solicitada no supere el stock actual usando repositorio (DIP)
        foreach ($items as $item) {
            $inventario = $this->inventarioRepository->find($item['inventario_id']);
            if ($item['cantidad'] > $inventario->stock) {
                return response()->json([
                    'error' => "La cantidad solicitada para '{$inventario->descripcion}' supera el stock disponible ({$inventario->stock}).",
                ], 422);
            }
        }

        return DB::transaction(function () use ($request, $items) {
            $cantidadTotal = array_sum(array_column($items, 'cantidad'));

            // Usar repositorio en lugar de modelo directo (DIP)
            $reserva = $this->reservaRepository->create([
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

            // Usar repositorio en lugar de modelo directo (DIP)
            foreach ($items as $item) {
                $this->reservaItemRepository->create([
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

    public function confirm(\App\Models\Reserva $reserva)
    {
        $this->authorizeAdminLike();

        // Cargar relaciones si es necesario
        if (! $reserva->relationLoaded('items')) {
            $reserva = $this->reservaRepository->findWithRelations($reserva->id);
        }

        if ($reserva->estado !== self::ESTADO_PENDIENTE) {
            return response()->json(['error' => self::MENSAJE_SOLO_PENDIENTES_CONFIRMAR], 422);
        }

        return DB::transaction(function () use ($reserva) {
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

    private function validateReservaItems($reserva)
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

    private function createCalendarioFromReserva($reserva)
    {
        // Usar repositorio en lugar de modelo directo (DIP)
        return $this->calendarioRepository->create([
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

    private function processReservaItems($reserva, $calendario): void
    {
        foreach ($reserva->items as $item) {
            $inventario = $item->inventario;
            if (! $inventario) {
                continue;
            }

            // Usar repositorio en lugar de modelo directo (DIP)
            $this->inventarioRepository->decrementStock($inventario->id, $item->cantidad);

            // Usar repositorio en lugar de modelo directo (DIP)
            $movimiento = $this->movimientoInventarioRepository->create([
                'inventario_id' => $inventario->id,
                'tipo_movimiento' => self::TIPO_MOVIMIENTO_ALQUILADO,
                'cantidad' => $item->cantidad,
                'fecha_movimiento' => now(),
                'descripcion' => self::EVENTO_RESERVA_CONFIRMADA.' #'.$reserva->id,
            ]);

            // Usar repositorio en lugar de modelo directo (DIP)
            $this->calendarioItemRepository->create([
                'calendario_id' => $calendario->id,
                'movimientos_inventario_id' => $movimiento->id,
                'cantidad' => $item->cantidad,
            ]);
        }
    }

    private function updateReservaAndCreateHistorial($reserva, $calendario): void
    {
        // Usar repositorio en lugar de modelo directo (DIP)
        $this->reservaRepository->update($reserva->id, [
            'estado' => self::ESTADO_CONFIRMADA,
            'calendario_id' => $calendario->id,
            'meta' => array_merge($reserva->meta ?? [], [
                'confirmada_en' => now()->toDateTimeString(),
                'calendario_id' => $calendario->id,
            ]),
        ]);

        // Usar repositorio en lugar de modelo directo (DIP)
        $this->historialRepository->create([
            'calendario_id' => $calendario->id,
            'reserva_id' => $reserva->id,
            'accion' => self::ACCION_CONFIRMADA,
            'confirmado_en' => now(),
        ]);
    }

    public function destroy($reserva)
    {
        $this->authorizeAdminLike();

        // Laravel puede pasar el modelo directamente o el ID
        if (is_numeric($reserva)) {
            $reserva = $this->reservaRepository->findWithRelations($reserva);
        } elseif (is_object($reserva) && isset($reserva->id)) {
            // Si ya es un modelo, cargar relaciones si es necesario
            if (! $reserva->relationLoaded('items')) {
                $reserva = $this->reservaRepository->findWithRelations($reserva->id);
            }
        }

        if ($reserva->estado !== self::ESTADO_PENDIENTE) {
            return response()->json(['error' => self::MENSAJE_SOLO_PENDIENTES_CANCELAR], 422);
        }

        // Usar repositorio en lugar de modelo directo (DIP)
        $this->reservaRepository->delete($reserva->id);

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
