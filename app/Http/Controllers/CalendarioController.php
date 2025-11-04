<?php

namespace App\Http\Controllers;

use App\Models\Calendario;
use App\Models\CalendarioItem;
use App\Models\MovimientosInventario;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class CalendarioController extends Controller
{
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
        // Obtener registros y eliminar duplicados reales (mismo contenido)
        $registros = Calendario::with('items')
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

        $eventos = [];
        $eventosItems = []; // Para pasar información detallada de items a JavaScript
        
        foreach ($registros as $r) {
            // Si tiene items (nuevo formato), mostrar todos los productos
            if ($r->items && $r->items->count() > 0) {
                $productos = [];
                $inventarioIds = [];
                $itemsData = [];
                
                foreach ($r->items as $item) {
                    $mov = $movimientos->get($item->movimientos_inventario_id);
                    $invId = $mov->inventario_id ?? null;
                    if ($invId && isset($inventarios[$invId])) {
                        $nombreProducto = $inventarios[$invId]->descripcion ?? 'Sin producto';
                        $productos[] = $nombreProducto . ' (x' . $item->cantidad . ')';
                        $inventarioIds[] = $invId;
                        // Guardar información detallada para JavaScript
                        $itemsData[] = [
                            'inventario_id' => $invId,
                            'cantidad' => $item->cantidad
                        ];
                    }
                }
                $titulo = count($productos) > 0 ? implode(', ', $productos) : 'Alquiler';
                $descripcion = ($r->descripcion_evento ?? '') . ' | Productos: ' . implode(', ', $productos);
                
                // Guardar items para el cálculo de disponibilidad
                $eventosItems[$r->id] = $itemsData;
            } else {
                // Formato antiguo: un solo producto
                $mov = $movimientos->get($r->movimientos_inventario_id);
                $invId = $mov->inventario_id ?? null;
                $titulo = ($invId && isset($inventarios[$invId]))
                    ? ($inventarios[$invId]->descripcion ?? 'Sin producto')
                    : 'Sin producto';
                if ($r->cantidad) {
                    $titulo .= ' (x' . $r->cantidad . ')';
                }
                $descripcion = trim(($r->cantidad ? ('Cantidad solicitada: '.$r->cantidad.'. ') : '').($r->descripcion_evento ?? ''));
                $inventarioIds = $invId ? [$invId] : [];
                // Para formato antiguo, guardar también
                if ($invId) {
                    $eventosItems[$r->id] = [['inventario_id' => $invId, 'cantidad' => $r->cantidad ?? 1]];
                }
            }

            $eventos[] = [
                'title'       => $titulo,
                'start'       => $r->fecha_inicio,
                'end'         => $r->fecha_fin,
                'description' => $descripcion,
                'calendarioId'=> $r->id,
                'inventarioIds'=> $inventarioIds,
            ];
        }

        return view('usuarios.calendario', [
            'registros'   => $registros,
            'inventarios' => $inventarios,
            'movimientos' => $movimientos->values(), // para la vista (no keyBy)
            'eventos'     => $eventos,
            'eventosItems' => $eventosItems, // Items detallados para JavaScript
        ]);
    }

    // Obtener eventos en formato JSON para recargar el calendario
    public function getEventos()
    {
        // Obtener registros y eliminar duplicados reales (mismo contenido)
        $registros = Calendario::with('items')
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

        $eventos = [];
        
        foreach ($registros as $r) {
            // Si tiene items (nuevo formato), mostrar todos los productos
            if ($r->items && $r->items->count() > 0) {
                $productos = [];
                $inventarioIds = [];
                
                foreach ($r->items as $item) {
                    $mov = $movimientos->get($item->movimientos_inventario_id);
                    $invId = $mov->inventario_id ?? null;
                    if ($invId && isset($inventarios[$invId])) {
                        $nombreProducto = $inventarios[$invId]->descripcion ?? 'Sin producto';
                        $productos[] = $nombreProducto . ' (x' . $item->cantidad . ')';
                        $inventarioIds[] = $invId;
                    }
                }
                $titulo = count($productos) > 0 ? implode(', ', $productos) : 'Alquiler';
                $descripcion = ($r->descripcion_evento ?? '') . ' | Productos: ' . implode(', ', $productos);
            } else {
                // Formato antiguo: un solo producto
                $mov = $movimientos->get($r->movimientos_inventario_id);
                $invId = $mov->inventario_id ?? null;
                $titulo = ($invId && isset($inventarios[$invId]))
                    ? ($inventarios[$invId]->descripcion ?? 'Sin producto')
                    : 'Sin producto';
                if ($r->cantidad) {
                    $titulo .= ' (x' . $r->cantidad . ')';
                }
                $descripcion = trim(($r->cantidad ? ('Cantidad solicitada: '.$r->cantidad.'. ') : '').($r->descripcion_evento ?? ''));
                $inventarioIds = $invId ? [$invId] : [];
            }

            $eventos[] = [
                'title'       => $titulo,
                'start'       => $r->fecha_inicio,
                'end'         => $r->fecha_fin,
                'description' => $descripcion,
                'calendarioId'=> $r->id,
                'inventarioIds'=> $inventarioIds,
            ];
        }

        return response()->json($eventos);
    }

    // Obtener registros para el sidebar en formato JSON
    public function getRegistros()
    {
        // Obtener registros y eliminar duplicados reales (mismo contenido)
        $registros = Calendario::with('items')
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
                        'nombre' => $inventarios[$movimiento->inventario_id]->descripcion,
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
                'evento'                    => 'Alquiler'
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
                'evento'                    => 'Alquiler'
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
            $calendario = Calendario::findOrFail($id);
            
            // Verificar si es un registro con items (nuevo formato) o formato antiguo
            $tieneItems = $calendario->items && $calendario->items->count() > 0;
            
            if ($tieneItems) {
                // Es un registro nuevo con múltiples items - validar items, fechas y descripción
                $items = $request->input('items', []);
                
                if (is_array($items)) {
                    $items = array_values($items);
                }
                
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
                    
                    // Calcular reservas que se solapan (excluyendo el registro actual que se está editando)
                    $reservadas = DB::table('calendario')
                        ->join('calendario_items', 'calendario.id', '=', 'calendario_items.calendario_id')
                        ->join('movimientos_inventario', 'calendario_items.movimientos_inventario_id', '=', 'movimientos_inventario.id')
                        ->where('movimientos_inventario.inventario_id', $inventarioId)
                        ->where('calendario.id', '!=', $id) // Excluir el registro actual
                        ->where('calendario.fecha_inicio', '<', $request->fecha_fin)
                        ->where('calendario.fecha_fin', '>', $request->fecha_inicio)
                        ->sum('calendario_items.cantidad') ?? 0;
                    
                    $disponible = $stockTotal - $reservadas;
                    
                    if ($disponible < $cantidadSolicitada) {
                        $producto = DB::table('inventario')->where('id', $inventarioId)->first();
                        $nombreProducto = $producto->descripcion ?? 'Producto';
                        DB::rollBack();
                        return redirect()->back()->withErrors(['items' => "No hay suficiente stock disponible para '{$nombreProducto}'. Disponible: {$disponible}, Solicitado: {$cantidadSolicitada}"])->withInput();
                    }
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
                foreach ($items as $item) {
                    $inventarioId = $item['inventario_id'];
                    
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
                    
                    CalendarioItem::create([
                        'calendario_id'           => $id,
                        'movimientos_inventario_id' => $movimientoIdFinal,
                        'cantidad'                => $item['cantidad'] ?? 1
                    ]);
                }
                
                DB::commit();
                return redirect()->route('usuarios.calendario')->with('ok', 'Alquiler actualizado correctamente.');
            } else {
                // Formato antiguo: validar con movimientos_inventario_id
                $request->validate([
                    'movimientos_inventario_id' => 'required|exists:movimientos_inventario,id',
                    'fecha_inicio'              => 'required|date',
                    'fecha_fin'                 => 'required|date|after_or_equal:fecha_inicio',
                    'descripcion_evento'        => 'required|string',
                    'cantidad'                  => 'nullable|integer|min:1'
                ], [
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
        // Borrar dependencias en historial para evitar error de clave foránea
        try {
            DB::table('historial')->where('calendario_id', $id)->delete();
        } catch (\Throwable $e) {
            // continuar; si no existe la tabla/columna, se ignora silenciosamente
        }
        // Los items se eliminan automáticamente por cascade delete
        Calendario::findOrFail($id)->delete();
        
        // Si es una petición AJAX, devolver JSON
        if (request()->ajax() || request()->wantsJson()) {
            return response()->json(['success' => true, 'message' => 'Evento eliminado']);
        }
        
        return back()->with('ok','Evento eliminado');
    }
}
