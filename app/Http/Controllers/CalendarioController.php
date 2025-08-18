<?php

namespace App\Http\Controllers;

use App\Models\Calendario;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class CalendarioController extends Controller
{
   public function index()
{
    $registros   = Calendario::all();    
    $inventarios = DB::table('inventario')->get()->keyBy('id'); // Trae todos los inventarios y los indexa por id

    $eventos = [];
    foreach ($registros as $r) {
        // Obtener el nombre de inventario desde el array indexado
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




    public function store(Request $request)
    {
        $request->validate([
            'movimientos_inventario_id' => 'required',
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

    public function update(Request $request, $id)
    {
        $request->validate([
            'movimientos_inventario_id' => 'required',
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

        return redirect()->route('calendario.index')->with('ok','Actualizado correctamente');

    }

    public function destroy($id)
    {
        Calendario::findOrFail($id)->delete();
        return back()->with('ok','Eliminado');
    }
}