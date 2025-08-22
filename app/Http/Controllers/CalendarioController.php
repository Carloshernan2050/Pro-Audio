<?php

namespace App\Http\Controllers;

use App\Models\Calendario;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class CalendarioController extends Controller
{
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
        Calendario::findOrFail($id)->delete();
        return back()->with('ok','Evento eliminado');
    }
}
