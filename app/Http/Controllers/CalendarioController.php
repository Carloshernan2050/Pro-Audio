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
        $inventarios = DB::table('inventario')->get()->keyBy('id'); 

        $eventos = [];
        foreach ($registros as $r) {
            $nombre = $inventarios->has($r->movimientos_inventario_id) 
                        ? $inventarios[$r->movimientos_inventario_id]->descripcion 
                        : 'Sin inventario';

            $eventos[] = [
                'title'       => $nombre,
                'start'       => $r->fecha_inicio,
                'end'         => $r->fecha_fin,
                'description' => $r->descripcion_evento
            ];
        }

        return view('usuarios.calendario', compact('registros','inventarios','eventos'));
    }

    // Guardar nuevo evento
    public function guardar(Request $request)
    {
        $this->ensureAdminLike();
        $request->validate([
            'movimientos_inventario_id' => 'required|exists:movimientos_inventario,id',
            'fecha_inicio'              => 'required|date',
            'fecha_fin'                 => 'required|date|after_or_equal:fecha_inicio',
            'descripcion_evento'        => 'required'
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

        return back()->with('ok','Alquiler registrado');
    }

    // Actualizar evento existente
    public function actualizar(Request $request, $id)
    {
        $this->ensureAdminLike();
        $request->validate([
            'movimientos_inventario_id' => 'required|exists:movimientos_inventario,id',
            'fecha_inicio'              => 'required|date',
            'fecha_fin'                 => 'required|date|after_or_equal:fecha_inicio',
            'descripcion_evento'        => 'required'
        ]);

        Calendario::findOrFail($id)->update([
            'movimientos_inventario_id' => $request->movimientos_inventario_id,
            'fecha_inicio'              => $request->fecha_inicio,
            'fecha_fin'                 => $request->fecha_fin,
            'descripcion_evento'        => $request->descripcion_evento,
        ]);

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
