<?php

namespace App\Http\Controllers;

use App\Models\SubServicios;
use App\Models\Servicios;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

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
        try {
            $request->validate([
                'servicios_id' => 'required|exists:servicios,id',
                'nombre' => 'required|string|max:100',
                'descripcion' => 'nullable|string',
                'precio' => 'required|numeric|min:0',
                'imagen' => 'nullable|image|mimes:jpeg,png,jpg,gif|max:5120', // 5MB máximo
            ]);

            $imagenPath = null;
            if ($request->hasFile('imagen')) {
                $file = $request->file('imagen');
                $filename = 'subservicio_' . time() . '_' . uniqid() . '.' . $file->getClientOriginalExtension();
                $path = $file->storeAs('subservicios', $filename, 'public');
                $imagenPath = $filename;
            }

            SubServicios::create([
                'servicios_id' => $request->servicios_id,
                'nombre' => $request->nombre,
                'descripcion' => $request->descripcion ?? '',
                'precio' => $request->precio,
                'imagen' => $imagenPath,
            ]);

            if ($request->ajax() || $request->wantsJson()) {
                return response()->json(['success' => 'Subservicio creado exitosamente.']);
            }

            return redirect()->route('subservicios.index')
                ->with('success', 'Subservicio creado exitosamente.');
        } catch (\Illuminate\Validation\ValidationException $e) {
            if ($request->ajax() || $request->wantsJson()) {
                return response()->json(['errors' => $e->errors(), 'error' => 'Error de validación'], 422);
            }
            return redirect()->route('subservicios.index')
                ->withErrors($e->errors())
                ->withInput();
        } catch (\Exception $e) {
            if ($request->ajax() || $request->wantsJson()) {
                return response()->json(['error' => 'Error al crear el subservicio: ' . $e->getMessage()], 422);
            }
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
        try {
            $subServicio = SubServicios::findOrFail($id);
            
            $request->validate([
                'servicios_id' => 'required|exists:servicios,id',
                'nombre' => 'required|string|max:100',
                'descripcion' => 'nullable|string',
                'precio' => 'required|numeric|min:0',
                'imagen' => 'nullable|image|mimes:jpeg,png,jpg,gif|max:5120', // 5MB máximo
            ]);

            // Manejar la imagen
            if ($request->hasFile('imagen')) {
                // Eliminar imagen anterior si existe
                if ($subServicio->imagen && Storage::disk('public')->exists('subservicios/' . $subServicio->imagen)) {
                    Storage::disk('public')->delete('subservicios/' . $subServicio->imagen);
                }
                
                // Guardar nueva imagen
                $file = $request->file('imagen');
                $filename = 'subservicio_' . time() . '_' . uniqid() . '.' . $file->getClientOriginalExtension();
                $path = $file->storeAs('subservicios', $filename, 'public');
                $imagenPath = $filename;
            } else {
                // Mantener la imagen existente si no se sube una nueva
                $imagenPath = $subServicio->imagen;
            }

            $subServicio->update([
                'servicios_id' => $request->servicios_id,
                'nombre' => $request->nombre,
                'descripcion' => $request->descripcion ?? '',
                'precio' => $request->precio,
                'imagen' => $imagenPath,
            ]);

            if ($request->ajax() || $request->wantsJson()) {
                return response()->json(['success' => 'Subservicio actualizado exitosamente.']);
            }

            return redirect()->route('subservicios.index')
                ->with('success', 'Subservicio actualizado exitosamente.');
        } catch (\Illuminate\Validation\ValidationException $e) {
            if ($request->ajax() || $request->wantsJson()) {
                return response()->json(['errors' => $e->errors(), 'error' => 'Error de validación'], 422);
            }
            return redirect()->route('subservicios.index')
                ->withErrors($e->errors())
                ->withInput();
        } catch (\Exception $e) {
            if ($request->ajax() || $request->wantsJson()) {
                return response()->json(['error' => 'Error al actualizar el subservicio: ' . $e->getMessage()], 422);
            }
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
            $request = request();
            $subServicio = SubServicios::findOrFail($id);
            
            // Eliminar imagen si existe
            if ($subServicio->imagen && Storage::disk('public')->exists('subservicios/' . $subServicio->imagen)) {
                Storage::disk('public')->delete('subservicios/' . $subServicio->imagen);
            }
            
            $subServicio->delete();

            if ($request->ajax() || $request->wantsJson()) {
                return response()->json(['success' => 'Subservicio eliminado exitosamente.']);
            }

            return redirect()->route('subservicios.index')
                ->with('success', 'Subservicio eliminado exitosamente.');
        } catch (\Exception $e) {
            $request = request();
            if ($request->ajax() || $request->wantsJson()) {
                return response()->json(['error' => 'Error al eliminar el subservicio: ' . $e->getMessage()], 422);
            }
            return redirect()->route('subservicios.index')
                ->with('error', 'Error al eliminar el subservicio: ' . $e->getMessage());
        }
    }
}
