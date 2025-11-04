<?php

namespace App\Http\Controllers;

use App\Models\Calendario;
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
        $registros   = Calendario::all();
        // Cargar movimientos e inventarios para resolver correctamente la relación
        $movimientos = DB::table('movimientos_inventario')->get()->keyBy('id');
        $inventarios = DB::table('inventario')->get()->keyBy('id');

        $eventos = [];
        foreach ($registros as $r) {
            $mov = $movimientos->get($r->movimientos_inventario_id);
            $invId = $mov->inventario_id ?? null;
            $nombre = ($invId && isset($inventarios[$invId]))
                ? ($inventarios[$invId]->descripcion ?? 'Sin producto')
                : 'Sin producto';

            $eventos[] = [
                'title'       => $nombre,
                'start'       => $r->fecha_inicio,
                'end'         => $r->fecha_fin,
                'description' => trim(($r->cantidad ? ('Cantidad solicitada: '.$r->cantidad.'. ') : '').($r->descripcion_evento ?? '')),
                'movId'       => $r->movimientos_inventario_id,
                'inventarioId'=> $invId,
            ];
        }

        return view('usuarios.calendario', [
            'registros'   => $registros,
            'inventarios' => $inventarios,
            'movimientos' => $movimientos->values(), // para la vista (no keyBy)
            'eventos'     => $eventos,
        ]);
    }

    // Guardar nuevo evento
    public function guardar(Request $request)
    {
        $this->ensureAdminLike();
        
        // Validar si se envía como items múltiples (nuevo formato) o campo único (formato antiguo)
        $items = $request->input('items', []);
        
        if (!empty($items)) {
            // Nuevo formato: múltiples items
            $request->validate([
                'items'                      => 'required|array|min:1',
                'items.*.movimientos_inventario_id' => 'required|exists:movimientos_inventario,id',
                'items.*.cantidad'           => 'required|integer|min:1',
                'fecha_inicio'              => 'required|date',
                'fecha_fin'                 => 'required|date|after_or_equal:fecha_inicio',
                'descripcion_evento'        => 'required|string'
            ], [
                'items.required' => 'Debe seleccionar al menos un producto.',
                'items.min' => 'Debe seleccionar al menos un producto.',
                'items.*.movimientos_inventario_id.required' => 'El campo de movimiento de inventario es obligatorio.',
                'items.*.movimientos_inventario_id.exists' => 'El movimiento de inventario seleccionado no existe.',
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

            // Crear un registro por cada item
            foreach ($items as $item) {
                $movimientoId = $item['movimientos_inventario_id'];
                
                // Validar que el movimiento existe y obtener el inventario_id
                $movimiento = DB::table('movimientos_inventario')->where('id', $movimientoId)->first();
                if (!$movimiento) {
                    return response()->json([
                        'error' => 'El movimiento de inventario seleccionado no existe.'
                    ], 422);
                }
                
                $inventarioId = $movimiento->inventario_id;
                $cantidadSolicitada = $item['cantidad'] ?? 1;
                
                // Validar stock disponible en el rango de fechas
                $stockTotal = DB::table('inventario')->where('id', $inventarioId)->value('stock') ?? 0;
                
                // Calcular cuántas unidades ya están reservadas en esas fechas
                $reservadas = DB::table('calendario')
                    ->join('movimientos_inventario', 'calendario.movimientos_inventario_id', '=', 'movimientos_inventario.id')
                    ->where('movimientos_inventario.inventario_id', $inventarioId)
                    ->where(function($query) use ($request) {
                        // Reservas que se solapan con el rango solicitado
                        $query->where(function($q) use ($request) {
                            $q->where('calendario.fecha_inicio', '<=', $request->fecha_fin)
                              ->where('calendario.fecha_fin', '>=', $request->fecha_inicio);
                        });
                    })
                    ->sum('calendario.cantidad') ?? 0;
                
                $disponible = $stockTotal - $reservadas;
                
                if ($disponible < $cantidadSolicitada) {
                    $producto = DB::table('inventario')->where('id', $inventarioId)->first();
                    $nombreProducto = $producto->descripcion ?? 'Producto';
                    return response()->json([
                        'error' => "No hay suficiente stock disponible para '{$nombreProducto}'. Disponible: {$disponible}, Solicitado: {$cantidadSolicitada}"
                    ], 422);
                }
                
                Calendario::create([
                    'personas_id'               => session('usuario_id'),
                    'movimientos_inventario_id' => $movimientoId,
                    'fecha'                     => now(),
                    'fecha_inicio'              => $request->fecha_inicio,
                    'fecha_fin'                 => $request->fecha_fin,
                    'descripcion_evento'        => $request->descripcion_evento . ' (Cantidad: ' . $cantidadSolicitada . ')',
                    'cantidad'                  => $cantidadSolicitada,
                    'evento'                    => 'Alquiler'
                ]);
            }
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

            Calendario::create([
                'personas_id'               => session('usuario_id'),
                'movimientos_inventario_id' => $request->movimientos_inventario_id,
                'fecha'                     => now(),
                'fecha_inicio'              => $request->fecha_inicio,
                'fecha_fin'                 => $request->fecha_fin,
                'descripcion_evento'        => $request->descripcion_evento,
                'evento'                    => 'Alquiler'
            ]);
        }

        return response()->json(['success' => true, 'message' => 'Alquiler registrado correctamente']);
    }

    // Actualizar evento existente
    public function actualizar(Request $request, $id)
    {
        $this->ensureAdminLike();
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

        $updateData = [
            'movimientos_inventario_id' => $request->movimientos_inventario_id,
            'fecha_inicio'              => $request->fecha_inicio,
            'fecha_fin'                 => $request->fecha_fin,
            'descripcion_evento'        => $request->descripcion_evento,
        ];
        
        if ($request->has('cantidad')) {
            $updateData['cantidad'] = $request->cantidad;
        }
        
        Calendario::findOrFail($id)->update($updateData);

        return redirect()->route('usuarios.calendario')->with('ok','Evento actualizado correctamente');
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
        Calendario::findOrFail($id)->delete();
        return back()->with('ok','Evento eliminado');
    }
}
