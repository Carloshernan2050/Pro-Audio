<?php

namespace App\Http\Controllers;

use App\Models\Calendario;
use App\Models\CalendarioItem;
use App\Models\MovimientosInventario;
use App\Models\Historial;
use App\Models\Inventario;
use App\Models\Reserva;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class CalendarioController extends Controller
{
    private const DEFAULT_COLOR = '#e91c1c';
    private const DEFAULT_TEXT_COLOR = '#ffffff';
    private const DEFAULT_PRODUCT_LABEL = 'Sin producto';
    private const DEFAULT_EVENT_TITLE = 'Alquiler';

    /**
     * Paleta de colores utilizada para diferenciar reservas en el calendario.
     */
    private array $calendarColorPalette = [
        ['bg' => '#ff6f00', 'border' => '#ffb74d', 'text' => '#1f1200'], // naranja intenso
        ['bg' => '#ff4081', 'border' => '#ff80ab', 'text' => '#fff3f7'], // rosado vibrante
        ['bg' => '#00c853', 'border' => '#69f0ae', 'text' => '#002310'], // verde brillante
        ['bg' => '#9c27b0', 'border' => '#ce93d8', 'text' => '#fdf2ff'], // morado vivo
        ['bg' => '#f44336', 'border' => '#ff7961', 'text' => '#fff5f5'], // rojo intenso
        ['bg' => '#ffd600', 'border' => '#ffef62', 'text' => '#3a2f00'], // amarillo luminoso
        ['bg' => '#ff1744', 'border' => '#ff616f', 'text' => '#fff2f4'], // rojo neón
        ['bg' => '#d500f9', 'border' => '#ea80fc', 'text' => '#fff2ff'], // magenta brillante
    ];

    private int $paletteCursor = 0;

    private function nextPaletteColor(): array
    {
        if (empty($this->calendarColorPalette)) {
            return ['bg' => self::DEFAULT_COLOR, 'border' => self::DEFAULT_COLOR, 'text' => self::DEFAULT_TEXT_COLOR];
        }

        $paletteIndex = $this->paletteCursor % count($this->calendarColorPalette);
        $this->paletteCursor++;

        $color = $this->calendarColorPalette[$paletteIndex] ?? [];

        return [
            'bg' => $color['bg'] ?? self::DEFAULT_COLOR,
            'border' => $color['border'] ?? ($color['bg'] ?? self::DEFAULT_COLOR),
            'text' => $color['text'] ?? self::DEFAULT_TEXT_COLOR,
        ];
    }

    private function resetPaletteCursor(): void
    {
        $this->paletteCursor = 0;
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
        $registros = $this->obtenerRegistrosUnicos();
        [$movimientos, $inventarios] = $this->cargarMovimientosEInventarios();

        $this->resetPaletteCursor();

        $eventos = [];
        $eventosItems = [];

        foreach ($registros as $registro) {
            $eventoData = $this->construirEvento($registro, $movimientos, $inventarios);
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
        $registros = $this->obtenerRegistrosUnicos();
        [$movimientos, $inventarios] = $this->cargarMovimientosEInventarios();

        $this->resetPaletteCursor();

        $eventos = [];

        foreach ($registros as $registro) {
            $eventos[] = $this->construirEvento($registro, $movimientos, $inventarios)['evento'];
        }

        return response()->json($eventos);
    }

    // Obtener registros para el sidebar en formato JSON
    public function getRegistros()
    {
        // Obtener registros y eliminar duplicados reales (mismo contenido)
        $registros = Calendario::with(['items', 'reserva'])
            ->orderBy('fecha', 'desc')
            ->orderBy('id', 'desc')
            ->get();
        
        // Eliminar duplicados basándose en contenido (fecha_inicio, fecha_fin, descripcion, items)
        $registrosUnicos = [];
        $vistos = [];
        
        foreach ($registros as $r) {
            // Crear una clave única basada en el contenido
            $itemsKey = $r->items->map(function($item) {
                return $item->movimientos_inventario_id . ':' . $item->cantidad;
            })->sort()->implode(',');
            
            $clave = $r->fecha_inicio . '|' . $r->fecha_fin . '|' . $r->descripcion_evento . '|' . $itemsKey;
            
            // Solo agregar si no hemos visto este contenido antes
            if (!isset($vistos[$clave])) {
                $vistos[$clave] = true;
                $registrosUnicos[] = $r;
            }
        }
        
        $registros = collect($registrosUnicos);
        // Cargar movimientos e inventarios para resolver correctamente la relación
        $movimientos = DB::table('movimientos_inventario')->get()->keyBy('id');
        $inventarios = DB::table('inventario')->orderBy('descripcion', 'asc')->get()->keyBy('id');

        $registrosData = [];
        
        foreach ($registros as $r) {
            // Si tiene items (nuevo formato), mostrar todos los productos
            $productosLista = [];
            $cantidadTotal = 0;
            if ($r->items && $r->items->count() > 0) {
                foreach ($r->items as $item) {
                    $mov = $movimientos->get($item->movimientos_inventario_id);
                    if ($mov && isset($inventarios[$mov->inventario_id])) {
                        $productosLista[] = [
                            'nombre' => $inventarios[$mov->inventario_id]->descripcion,
                            'cantidad' => $item->cantidad
                        ];
                        $cantidadTotal += $item->cantidad;
                    }
                }
            } else {
                // Formato antiguo: un solo producto
                $movimiento = collect($movimientos)->first(function($m) use ($r) {
                    return $m->id == $r->movimientos_inventario_id;
                });
                if ($movimiento && isset($inventarios[$movimiento->inventario_id])) {
                    $cant = $r->cantidad ?? 1;
                    $productosLista[] = [
                        'nombre' => $inventarios[$movimiento->inventario_id]->descripcion ?? self::DEFAULT_PRODUCT_LABEL,
                        'cantidad' => $cant
                    ];
                    $cantidadTotal = $cant;
                }
            }
            
            $fechaInicio = \Carbon\Carbon::parse($r->fecha_inicio);
            $fechaFin = \Carbon\Carbon::parse($r->fecha_fin);
            $diasReserva = $fechaInicio->diffInDays($fechaFin) + 1;
            
            $registrosData[] = [
                'id' => $r->id,
                'fecha_inicio' => $fechaInicio->format('d/m/Y'),
                'fecha_fin' => $fechaFin->format('d/m/Y'),
                'dias_reserva' => $diasReserva,
                'productos' => $productosLista,
                'cantidad_total' => $cantidadTotal,
                'descripcion_evento' => $r->descripcion_evento,
                'tiene_items' => $r->items && $r->items->count() > 0
            ];
        }

        return response()->json([
            'registros' => $registrosData,
            'total' => count($registrosData)
        ]);
    }

    private function obtenerRegistrosUnicos(): Collection
    {
        $registros = Calendario::with(['items', 'reserva'])
            ->orderBy('fecha', 'desc')
            ->orderBy('id', 'desc')
            ->get();

        $vistos = [];
        $registrosUnicos = [];

        foreach ($registros as $registro) {
            $itemsKey = $registro->items->map(function ($item) {
                return $item->movimientos_inventario_id . ':' . $item->cantidad;
            })->sort()->implode(',');

            $clave = $registro->fecha_inicio . '|' . $registro->fecha_fin . '|' . $registro->descripcion_evento . '|' . $itemsKey;

            if (!isset($vistos[$clave])) {
                $vistos[$clave] = true;
                $registrosUnicos[] = $registro;
            }
        }

        return collect($registrosUnicos);
    }

    /**
     * @return array{Collection, Collection}
     */
    private function cargarMovimientosEInventarios(): array
    {
        $movimientos = DB::table('movimientos_inventario')->get()->keyBy('id');
        $inventarios = DB::table('inventario')->orderBy('descripcion', 'asc')->get()->keyBy('id');

        return [$movimientos, $inventarios];
    }

    /**
     * Construye el evento y los datos relacionados para un registro.
     *
     * @return array{evento: array<string, mixed>, items: array<int, array<string, mixed>>}
     */
    private function construirEvento($registro, Collection $movimientos, Collection $inventarios): array
    {
        if ($registro->items && $registro->items->count() > 0) {
            [$titulo, $descripcion, $inventarioIds, $itemsData] = $this->construirEventoDesdeItems(
                $registro,
                $movimientos,
                $inventarios
            );
        } else {
            [$titulo, $descripcion, $inventarioIds, $itemsData] = $this->construirEventoFormatoAntiguo(
                $registro,
                $movimientos,
                $inventarios
            );
        }

        $colorSet = $this->nextPaletteColor();

        return [
            'evento' => [
                'title' => $titulo,
                'start' => $registro->fecha_inicio,
                'end' => $registro->fecha_fin,
                'description' => $descripcion,
                'calendarioId' => $registro->id,
                'inventarioIds' => $inventarioIds,
                'backgroundColor' => $colorSet['bg'],
                'borderColor' => $colorSet['border'],
                'textColor' => $colorSet['text'],
            ],
            'items' => $itemsData,
        ];
    }

    private function construirEventoDesdeItems($registro, Collection $movimientos, Collection $inventarios): array
    {
        $productos = [];
        $inventarioIds = [];
        $itemsData = [];

        foreach ($registro->items as $item) {
            $mov = $movimientos->get($item->movimientos_inventario_id);
            $inventarioId = $mov->inventario_id ?? null;
            if ($inventarioId && isset($inventarios[$inventarioId])) {
                $nombreProducto = $inventarios[$inventarioId]->descripcion ?? self::DEFAULT_PRODUCT_LABEL;
                $productos[] = $nombreProducto . ' (x' . $item->cantidad . ')';
                $inventarioIds[] = $inventarioId;
                $itemsData[] = [
                    'inventario_id' => $inventarioId,
                    'cantidad' => $item->cantidad,
                ];
            }
        }

        $titulo = !empty($productos) ? implode(', ', $productos) : self::DEFAULT_EVENT_TITLE;
        $descripcion = ($registro->descripcion_evento ?? '') . ' | Productos: ' . implode(', ', $productos);

        return [$titulo, $descripcion, $inventarioIds, $itemsData];
    }

    private function construirEventoFormatoAntiguo($registro, Collection $movimientos, Collection $inventarios): array
    {
        $mov = $movimientos->get($registro->movimientos_inventario_id);
        $inventarioId = $mov->inventario_id ?? null;

        $titulo = ($inventarioId && isset($inventarios[$inventarioId]))
            ? ($inventarios[$inventarioId]->descripcion ?? self::DEFAULT_PRODUCT_LABEL)
            : self::DEFAULT_PRODUCT_LABEL;

        if ($registro->cantidad) {
            $titulo .= ' (x' . $registro->cantidad . ')';
        }

        $descripcion = trim(
            ($registro->cantidad ? ('Cantidad solicitada: ' . $registro->cantidad . '. ') : '') .
            ($registro->descripcion_evento ?? '')
        );

        $inventarioIds = $inventarioId ? [$inventarioId] : [];
        $itemsData = [];

        if ($inventarioId) {
            $itemsData[] = [
                'inventario_id' => $inventarioId,
                'cantidad' => $registro->cantidad ?? 1,
            ];
        }

        return [$titulo, $descripcion, $inventarioIds, $itemsData];
    }

    // Guardar nuevo evento
    public function guardar(Request $request)
    {
        $this->ensureAdminLike();
        
        // Prevenir doble procesamiento - usar transacción para asegurar atomicidad
        DB::beginTransaction();
        
        try {
            // Validar si se envía como items múltiples (nuevo formato) o campo único (formato antiguo)
            $items = $request->input('items', []);
        
        // Asegurarse de que items sea un array indexado numéricamente (0, 1, 2, ...)
        // Laravel puede convertir items[0], items[1] a un array asociativo si hay problemas
        if (is_array($items)) {
            // Reindexar el array para asegurar índices numéricos secuenciales
            $items = array_values($items);
        }
        
        if (!empty($items)) {
            // Nuevo formato: múltiples items - AHORA USAMOS INVENTARIO_ID DIRECTAMENTE
            $request->validate([
                'items'                      => 'required|array|min:1',
                'items.*.inventario_id'      => 'required|exists:inventario,id',
                'items.*.cantidad'           => 'required|integer|min:1',
                'fecha_inicio'              => 'required|date',
                'fecha_fin'                 => 'required|date|after_or_equal:fecha_inicio',
                'descripcion_evento'        => 'required|string'
            ], [
                'items.required' => 'Debe seleccionar al menos un producto.',
                'items.min' => 'Debe seleccionar al menos un producto.',
                'items.*.inventario_id.required' => 'Debe seleccionar un producto del inventario.',
                'items.*.inventario_id.exists' => 'El producto seleccionado no existe.',
                'items.*.cantidad.required' => 'La cantidad es obligatoria para cada producto.',
                'items.*.cantidad.integer' => 'La cantidad debe ser un número entero.',
                'items.*.cantidad.min' => 'La cantidad debe ser mayor a 0.',
                'fecha_inicio.required' => 'La fecha de inicio es obligatoria.',
                'fecha_inicio.date' => 'La fecha de inicio debe ser una fecha válida.',
                'fecha_fin.required' => 'La fecha de fin es obligatoria.',
                'fecha_fin.date' => 'La fecha de fin debe ser una fecha válida.',
                'fecha_fin.after_or_equal' => 'La fecha de fin debe ser posterior o igual a la fecha de inicio.',
                'descripcion_evento.required' => 'La descripción del evento es obligatoria.',
                'descripcion_evento.string' => 'La descripción del evento debe ser texto.'
            ]);

            // Validar stock de todos los items antes de crear nada
            foreach ($items as $item) {
                // Ahora recibimos inventario_id directamente
                $inventarioId = $item['inventario_id'];
                
                // Verificar que el inventario existe
                $inventario = DB::table('inventario')->where('id', $inventarioId)->first();
                if (!$inventario) {
                    DB::rollBack();
                    return response()->json([
                        'error' => 'El producto del inventario seleccionado no existe.'
                    ], 422);
                }
                $cantidadSolicitada = $item['cantidad'] ?? 1;
                
                // Validar stock disponible en el rango de fechas
                $stockTotal = DB::table('inventario')->where('id', $inventarioId)->value('stock') ?? 0;
                
                // Calcular cuántas unidades ya están reservadas en esas fechas
                // Lógica de solapamiento: dos reservas se solapan si:
                // - La reserva existente empieza ANTES de que termine la nueva (fecha_inicio_existente < fecha_fin_nueva)
                // - Y la reserva existente termina DESPUÉS de que empiece la nueva (fecha_fin_existente > fecha_inicio_nueva)
                // Esto significa que si una reserva termina exactamente cuando otra empieza, NO se solapan
                $reservadas = DB::table('calendario')
                    ->join('calendario_items', 'calendario.id', '=', 'calendario_items.calendario_id')
                    ->join('movimientos_inventario', 'calendario_items.movimientos_inventario_id', '=', 'movimientos_inventario.id')
                    ->where('movimientos_inventario.inventario_id', $inventarioId)
                    // Reservas que se solapan con el rango solicitado (considerando horas)
                    // La reserva existente debe empezar ANTES de que termine la nueva
                    // Y debe terminar DESPUÉS de que empiece la nueva
                    ->where('calendario.fecha_inicio', '<', $request->fecha_fin)
                    ->where('calendario.fecha_fin', '>', $request->fecha_inicio)
                    ->sum('calendario_items.cantidad') ?? 0;
                
                $disponible = $stockTotal - $reservadas;
                
                if ($disponible < $cantidadSolicitada) {
                    $producto = DB::table('inventario')->where('id', $inventarioId)->first();
                    $nombreProducto = $producto->descripcion ?? 'Producto';
                    DB::rollBack();
                    return response()->json([
                        'message' => "No hay suficiente stock disponible para '{$nombreProducto}'. Disponible: {$disponible}, Solicitado: {$cantidadSolicitada}",
                        'errors' => [
                            'items' => ["No hay suficiente stock disponible para '{$nombreProducto}'. Disponible: {$disponible}, Solicitado: {$cantidadSolicitada}"]
                        ]
                    ], 422);
                }
            }
            
            // Crear UN SOLO registro de calendario (alquiler)
            // Calcular cantidad total de todos los items
            $cantidadTotal = array_sum(array_column($items, 'cantidad'));
            
            $calendario = Calendario::create([
                'personas_id'               => session('usuario_id'),
                'movimientos_inventario_id' => null, // Ya no es necesario, se guarda en items
                'fecha'                     => now(),
                'fecha_inicio'              => $request->fecha_inicio,
                'fecha_fin'                 => $request->fecha_fin,
                'descripcion_evento'        => $request->descripcion_evento,
                'cantidad'                  => $cantidadTotal, // Cantidad total de todos los productos
                'evento'                    => self::DEFAULT_EVENT_TITLE
            ]);
            
            // Crear los items (productos) asociados al alquiler
            foreach ($items as $item) {
                $inventarioId = $item['inventario_id'];
                
                // Buscar o crear un movimiento para este inventario
                $movimiento = DB::table('movimientos_inventario')
                    ->where('inventario_id', $inventarioId)
                    ->first();
                
                // Si no existe movimiento, crear uno automáticamente
                if (!$movimiento) {
                    $inventario = DB::table('inventario')->where('id', $inventarioId)->first();
                    $stockActual = $inventario->stock ?? 0;
                    
                    // Crear movimiento usando el modelo para asegurar validación
                    $movimientoId = MovimientosInventario::create([
                        'inventario_id' => $inventarioId,
                        'tipo_movimiento' => 'entrada',
                        'cantidad' => $stockActual > 0 ? $stockActual : 1,
                        'fecha_movimiento' => now(),
                        'descripcion' => 'Movimiento automático al crear alquiler'
                    ])->id;
                    $movimientoIdFinal = $movimientoId;
                } else {
                    $movimientoIdFinal = $movimiento->id;
                }
                
                CalendarioItem::create([
                    'calendario_id'           => $calendario->id,
                    'movimientos_inventario_id' => $movimientoIdFinal,
                    'cantidad'                => $item['cantidad'] ?? 1
                ]);
            }
            
            // Confirmar transacción
            DB::commit();
            
            return response()->json([
                'success' => true,
                'message' => 'Alquiler registrado correctamente',
                'calendario_id' => $calendario->id
            ]);
        } else {
            // Formato antiguo: un solo campo
            $request->validate([
                'movimientos_inventario_id' => 'required|exists:movimientos_inventario,id',
                'fecha_inicio'              => 'required|date',
                'fecha_fin'                 => 'required|date|after_or_equal:fecha_inicio',
                'descripcion_evento'        => 'required|string'
            ], [
                'movimientos_inventario_id.required' => 'Debe seleccionar un producto del inventario.',
                'movimientos_inventario_id.exists' => 'El producto seleccionado no existe.',
                'fecha_inicio.required' => 'La fecha de inicio es obligatoria.',
                'fecha_inicio.date' => 'La fecha de inicio debe ser una fecha válida.',
                'fecha_fin.required' => 'La fecha de fin es obligatoria.',
                'fecha_fin.date' => 'La fecha de fin debe ser una fecha válida.',
                'fecha_fin.after_or_equal' => 'La fecha de fin debe ser posterior o igual a la fecha de inicio.',
                'descripcion_evento.required' => 'La descripción del evento es obligatoria.',
                'descripcion_evento.string' => 'La descripción del evento debe ser texto.'
            ]);

            $calendario = Calendario::create([
                'personas_id'               => session('usuario_id'),
                'movimientos_inventario_id' => $request->movimientos_inventario_id,
                'fecha'                     => now(),
                'fecha_inicio'              => $request->fecha_inicio,
                'fecha_fin'                 => $request->fecha_fin,
                'descripcion_evento'        => $request->descripcion_evento,
                'evento'                    => self::DEFAULT_EVENT_TITLE
            ]);
            
            // Confirmar transacción
            DB::commit();
            
            return response()->json([
                'success' => true,
                'message' => 'Alquiler registrado correctamente',
                'calendario_id' => $calendario->id
            ]);
        }
        
        } catch (\Exception $e) {
            // Revertir transacción en caso de error
            DB::rollBack();
            
            return response()->json([
                'error' => 'Error al guardar el alquiler: ' . $e->getMessage()
            ], 500);
        }
    }

    // Actualizar evento existente
    public function actualizar(Request $request, $id)
    {
        $this->ensureAdminLike();
        
        DB::beginTransaction();
        
        try {
            $calendario = Calendario::with(['items.movimientoInventario', 'reserva'])->findOrFail($id);
            
            // Verificar si es un registro con items (nuevo formato) o formato antiguo
            $tieneItems = $calendario->items && $calendario->items->count() > 0;
            
            if ($tieneItems) {
                // Es un registro nuevo con múltiples items - validar items, fechas y descripción
                $items = $request->input('items', []);
                
                if (is_array($items)) {
                    $items = array_values($items);
                }
                
                $request->validate([
                    'servicio'                  => 'required|string|max:120',
                    'items'                      => 'required|array|min:1',
                    'items.*.inventario_id'      => 'required|exists:inventario,id',
                    'items.*.cantidad'           => 'required|integer|min:1',
                    'fecha_inicio'              => 'required|date',
                    'fecha_fin'                 => 'required|date|after_or_equal:fecha_inicio',
                    'descripcion_evento'        => 'required|string'
                ], [
                    'servicio.required' => 'Debe seleccionar un servicio.',
                    'items.required' => 'Debe seleccionar al menos un producto.',
                    'items.min' => 'Debe seleccionar al menos un producto.',
                    'items.*.inventario_id.required' => 'Debe seleccionar un producto del inventario.',
                    'items.*.inventario_id.exists' => 'El producto seleccionado no existe.',
                    'items.*.cantidad.required' => 'La cantidad es obligatoria para cada producto.',
                    'items.*.cantidad.integer' => 'La cantidad debe ser un número entero.',
                    'items.*.cantidad.min' => 'La cantidad debe ser mayor a 0.',
                    'fecha_inicio.required' => 'La fecha de inicio es obligatoria.',
                    'fecha_inicio.date' => 'La fecha de inicio debe ser una fecha válida.',
                    'fecha_fin.required' => 'La fecha de fin es obligatoria.',
                    'fecha_fin.date' => 'La fecha de fin debe ser una fecha válida.',
                    'fecha_fin.after_or_equal' => 'La fecha de fin debe ser posterior o igual a la fecha de inicio.',
                    'descripcion_evento.required' => 'La descripción del evento es obligatoria.',
                    'descripcion_evento.string' => 'La descripción del evento debe ser texto.'
                ]);

                // Cantidades actuales del calendario (para devolver stock temporalmente)
                $itemsActuales = [];
                foreach ($calendario->items as $itemActual) {
                    if (!$itemActual->movimientoInventario) {
                        continue;
                    }
                    $invId = $itemActual->movimientoInventario->inventario_id;
                    $itemsActuales[$invId] = ($itemsActuales[$invId] ?? 0) + $itemActual->cantidad;
                }

                // Validar stock de todos los items antes de actualizar nada
                foreach ($items as $item) {
                    $inventarioId = $item['inventario_id'];
                    
                    $inventario = DB::table('inventario')->where('id', $inventarioId)->first();
                    if (!$inventario) {
                        DB::rollBack();
                        return redirect()->back()->withErrors(['items' => 'El producto del inventario seleccionado no existe.'])->withInput();
                    }
                    
                    $cantidadSolicitada = $item['cantidad'] ?? 1;
                    $stockTotal = DB::table('inventario')->where('id', $inventarioId)->value('stock') ?? 0;
                    $cantidadActual = $itemsActuales[$inventarioId] ?? 0;
                    
                    // Calcular reservas que se solapan (excluyendo el registro actual que se está editando)
                    $reservadas = DB::table('calendario')
                        ->join('calendario_items', 'calendario.id', '=', 'calendario_items.calendario_id')
                        ->join('movimientos_inventario', 'calendario_items.movimientos_inventario_id', '=', 'movimientos_inventario.id')
                        ->where('movimientos_inventario.inventario_id', $inventarioId)
                        ->where('calendario.id', '!=', $id) // Excluir el registro actual
                        ->where('calendario.fecha_inicio', '<', $request->fecha_fin)
                        ->where('calendario.fecha_fin', '>', $request->fecha_inicio)
                        ->sum('calendario_items.cantidad') ?? 0;
                    
                    $disponible = ($stockTotal + $cantidadActual) - $reservadas;
                    
                    if ($disponible < $cantidadSolicitada) {
                        $producto = DB::table('inventario')->where('id', $inventarioId)->first();
                        $nombreProducto = $producto->descripcion ?? 'Producto';
                        DB::rollBack();
                        return redirect()->back()->withErrors(['items' => "No hay suficiente stock disponible para '{$nombreProducto}'. Disponible: {$disponible}, Solicitado: {$cantidadSolicitada}"])->withInput();
                    }
                }
                
                // Devolver stock actual antes de aplicar los nuevos cambios
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
                        'descripcion' => 'Ajuste de reserva #' . $id . ' (devolución por actualización)',
                    ]);
                }

                // Actualizar el registro principal
                $cantidadTotal = array_sum(array_column($items, 'cantidad'));
                
                $calendario->update([
                    'fecha_inicio'       => $request->fecha_inicio,
                    'fecha_fin'         => $request->fecha_fin,
                    'descripcion_evento' => $request->descripcion_evento,
                    'cantidad'           => $cantidadTotal,
                ]);
                
                // Eliminar items antiguos
                CalendarioItem::where('calendario_id', $id)->delete();
                
                // Crear los nuevos items
                $nuevosItemsReserva = [];
                foreach ($items as $item) {
                    $inventarioId = $item['inventario_id'];
                    $cantidad = $item['cantidad'] ?? 1;
                    
                    // Buscar o crear un movimiento para este inventario
                    $movimiento = DB::table('movimientos_inventario')
                        ->where('inventario_id', $inventarioId)
                        ->first();
                    
                    if (!$movimiento) {
                        $inventario = DB::table('inventario')->where('id', $inventarioId)->first();
                        $stockActual = $inventario->stock ?? 0;
                        
                        $movimientoId = MovimientosInventario::create([
                            'inventario_id' => $inventarioId,
                            'tipo_movimiento' => 'entrada',
                            'cantidad' => $stockActual > 0 ? $stockActual : 1,
                            'fecha_movimiento' => now(),
                            'descripcion' => 'Movimiento automático al actualizar alquiler'
                        ])->id;
                        $movimientoIdFinal = $movimientoId;
                    } else {
                        $movimientoIdFinal = $movimiento->id;
                    }

                    // Descontar nuevamente el stock con la cantidad nueva
                    Inventario::where('id', $inventarioId)->decrement('stock', $cantidad);

                    MovimientosInventario::create([
                        'inventario_id' => $inventarioId,
                        'tipo_movimiento' => 'alquilado',
                        'cantidad' => $cantidad,
                        'fecha_movimiento' => now(),
                        'descripcion' => 'Ajuste de reserva #' . $id . ' (nueva cantidad)',
                    ]);
                    
                    CalendarioItem::create([
                        'calendario_id'           => $id,
                        'movimientos_inventario_id' => $movimientoIdFinal,
                        'cantidad'                => $cantidad
                    ]);

                    $nuevosItemsReserva[] = [
                        'inventario_id' => $inventarioId,
                        'cantidad' => $cantidad,
                    ];
                }

                // Actualizar información de la reserva vinculada (si existe)
                $reserva = Reserva::with('items')->where('calendario_id', $id)->first();
                if ($reserva) {
                    $reserva->update([
                        'servicio' => $request->input('servicio'),
                        'fecha_inicio' => $request->fecha_inicio,
                        'fecha_fin' => $request->fecha_fin,
                        'descripcion_evento' => $request->descripcion_evento,
                        'cantidad_total' => $cantidadTotal,
                        'meta' => array_merge($reserva->meta ?? [], [
                            'actualizada_en' => now()->toDateTimeString(),
                        ]),
                    ]);

                    // Reemplazar items de la reserva
                    $reserva->items()->delete();
                    foreach ($nuevosItemsReserva as $item) {
                        $reserva->items()->create($item);
                    }
                }
                
                DB::commit();
                return redirect()->route('usuarios.calendario')->with('ok', 'Alquiler actualizado correctamente.');
            } else {
                // Formato antiguo: validar con movimientos_inventario_id
                $request->validate([
                    'servicio'                  => 'required|string|max:120',
                    'movimientos_inventario_id' => 'required|exists:movimientos_inventario,id',
                    'fecha_inicio'              => 'required|date',
                    'fecha_fin'                 => 'required|date|after_or_equal:fecha_inicio',
                    'descripcion_evento'        => 'required|string',
                    'cantidad'                  => 'nullable|integer|min:1'
                ], [
                    'servicio.required' => 'Debe seleccionar un servicio.',
                    'movimientos_inventario_id.required' => 'Debe seleccionar un producto del inventario.',
                    'movimientos_inventario_id.exists' => 'El producto seleccionado no existe.',
                    'fecha_inicio.required' => 'La fecha de inicio es obligatoria.',
                    'fecha_inicio.date' => 'La fecha de inicio debe ser una fecha válida.',
                    'fecha_fin.required' => 'La fecha de fin es obligatoria.',
                    'fecha_fin.date' => 'La fecha de fin debe ser una fecha válida.',
                    'fecha_fin.after_or_equal' => 'La fecha de fin debe ser posterior o igual a la fecha de inicio.',
                    'descripcion_evento.required' => 'La descripción del evento es obligatoria.',
                    'descripcion_evento.string' => 'La descripción del evento debe ser texto.',
                    'cantidad.integer' => 'La cantidad debe ser un número entero.',
                    'cantidad.min' => 'La cantidad debe ser mayor a 0.'
                ]);
                
                // Formato antiguo: actualizar normalmente
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

                $reserva = Reserva::where('calendario_id', $id)->first();
                if ($reserva) {
                    $reserva->update([
                        'servicio' => $request->input('servicio'),
                        'fecha_inicio' => $request->fecha_inicio,
                        'fecha_fin' => $request->fecha_fin,
                        'descripcion_evento' => $request->descripcion_evento,
                        'cantidad_total' => $request->cantidad ?? $calendario->cantidad,
                        'meta' => array_merge($reserva->meta ?? [], [
                            'actualizada_en' => now()->toDateTimeString(),
                        ]),
                    ]);
                }
                
                DB::commit();
                return redirect()->route('usuarios.calendario')->with('ok', 'Alquiler actualizado correctamente.');
            }
        } catch (\Illuminate\Validation\ValidationException $e) {
            DB::rollBack();
            return redirect()->back()->withErrors($e->errors())->withInput();
        } catch (\Exception $e) {
            DB::rollBack();
            return redirect()->route('usuarios.calendario')->with('error', 'Error al actualizar: ' . $e->getMessage());
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
