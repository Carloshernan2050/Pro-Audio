<?php

namespace App\Http\Controllers;

use App\Models\Servicios;
use Illuminate\Http\Request;

class ServiciosController extends Controller
{
    /**
     * Mostrar lista de servicios
     */
    public function index()
    {
        $servicios = Servicios::all();
        return view('usuarios.ajustes', compact('servicios'));
    }

    /**
     * Formulario de creación
     */
    public function create()
    {
        return view('servicios.create');
    }

    /**
     * Guardar servicio en BD
     */
    public function store(Request $request)
    {
        $request->validate([
            'nombre_servicio' => 'required|string|max:255'
        ]);

        Servicios::create($request->only('nombre_servicio'));

        return redirect()->route('usuarios.ajustes')->with('success', 'Servicio creado correctamente.');
    }

    /**
     * Mostrar servicio específico
     */
    public function show($id)
    {
        $servicio = Servicios::findOrFail($id);
        return view('servicios.show', compact('servicio'));
    }

    /**
     * Formulario de edición
     */
    public function edit($id)
    {
        $servicio = Servicios::findOrFail($id);
        return view('servicios.edit', compact('servicio'));
    }

    /**
     * Actualizar servicio
     */
    public function update(Request $request, $id)
    {
        $request->validate([
            'nombre_servicio' => 'required|string|max:255'
        ]);

        $servicio = Servicios::findOrFail($id);
        $servicio->update($request->only('nombre_servicio'));

        return redirect()->route('usuarios.ajustes')->with('success', 'Servicio actualizado correctamente.');
    }

    /**
     * Eliminar servicio
     */
    public function destroy($id)
    {
        $servicio = Servicios::findOrFail($id);
        $servicio->delete();

        return redirect()->route('usuarios.ajustes')->with('success', 'Servicio eliminado correctamente.');
    }
}
