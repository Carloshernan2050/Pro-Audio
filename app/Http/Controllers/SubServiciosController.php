<?php

namespace App\Http\Controllers;

use App\Models\SubServicios;
use App\Models\Servicios;
use Illuminate\Http\Request;

class SubServiciosController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        try {
            $subServicios = SubServicios::with('servicio')
                ->orderBy('servicios_id')
                ->orderBy('nombre')
                ->get();
            
            $servicios = Servicios::orderBy('nombre_servicio')->get();
            
            return view('usuarios.subservicios', compact('subServicios', 'servicios'));
        } catch (\Exception $e) {
            \Log::error('SubServiciosController@index Error: ' . $e->getMessage());
            return view('usuarios.subservicios', [
                'subServicios' => collect(),
                'servicios' => collect()
            ])->with('error', 'Error al cargar los subservicios: ' . $e->getMessage());
        }
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        $servicios = Servicios::orderBy('nombre_servicio')->get();
        return view('usuarios.subservicios', compact('servicios'));
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $request->validate([
            'servicios_id' => 'required|exists:servicios,id',
            'nombre' => 'required|string|max:100',
            'descripcion' => 'nullable|string',
            'precio' => 'required|numeric|min:0',
        ]);

        try {
            SubServicios::create([
                'servicios_id' => $request->servicios_id,
                'nombre' => $request->nombre,
                'descripcion' => $request->descripcion ?? '',
                'precio' => $request->precio,
            ]);

            return redirect()->route('subservicios.index')
                ->with('success', 'Subservicio creado exitosamente.');
        } catch (\Exception $e) {
            return redirect()->route('subservicios.index')
                ->with('error', 'Error al crear el subservicio: ' . $e->getMessage());
        }
    }

    /**
     * Display the specified resource.
     */
    public function show($id)
    {
        $subServicio = SubServicios::with('servicio')->findOrFail($id);
        return view('usuarios.subservicios', compact('subServicio'));
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit($id)
    {
        $subServicio = SubServicios::findOrFail($id);
        $servicios = Servicios::orderBy('nombre_servicio')->get();
        
        return view('usuarios.subservicios', compact('subServicio', 'servicios'));
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, $id)
    {
        $subServicio = SubServicios::findOrFail($id);
        
        $request->validate([
            'servicios_id' => 'required|exists:servicios,id',
            'nombre' => 'required|string|max:100',
            'descripcion' => 'nullable|string',
            'precio' => 'required|numeric|min:0',
        ]);

        try {
            $subServicio->update([
                'servicios_id' => $request->servicios_id,
                'nombre' => $request->nombre,
                'descripcion' => $request->descripcion ?? '',
                'precio' => $request->precio,
            ]);

            return redirect()->route('subservicios.index')
                ->with('success', 'Subservicio actualizado exitosamente.');
        } catch (\Exception $e) {
            return redirect()->route('subservicios.index')
                ->with('error', 'Error al actualizar el subservicio: ' . $e->getMessage());
        }
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy($id)
    {
        try {
            $subServicio = SubServicios::findOrFail($id);
            $subServicio->delete();

            return redirect()->route('subservicios.index')
                ->with('success', 'Subservicio eliminado exitosamente.');
        } catch (\Exception $e) {
            return redirect()->route('subservicios.index')
                ->with('error', 'Error al eliminar el subservicio: ' . $e->getMessage());
        }
    }
}
