<?php

namespace App\Http\Controllers;

use App\Models\Servicios;
use App\Models\SubServicios;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class SubServiciosController extends Controller
{
    private const STORAGE_PATH = 'subservicios/';

    /**
     * Handle AJAX or redirect response for success messages.
     */
    private function handleSuccessResponse(Request $request, string $message)
    {
        if ($request->ajax() || $request->wantsJson()) {
            return response()->json(['success' => $message]);
        }

        return redirect()->route('subservicios.index')->with('success', $message);
    }

    /**
     * Handle AJAX or redirect response for validation errors.
     */
    private function handleValidationError(Request $request, \Illuminate\Validation\ValidationException $e)
    {
        if ($request->ajax() || $request->wantsJson()) {
            return response()->json(['errors' => $e->errors(), 'error' => 'Error de validación'], 422);
        }

        return redirect()->route('subservicios.index')
            ->withErrors($e->errors())
            ->withInput();
    }

    /**
     * Handle AJAX or redirect response for general exceptions.
     */
    private function handleExceptionError(Request $request, \Exception $e, string $action)
    {
        if ($request->ajax() || $request->wantsJson()) {
            return response()->json(['error' => "Error al {$action}: ".$e->getMessage()], 422);
        }

        return redirect()->route('subservicios.index')
            ->with('error', "Error al {$action}: ".$e->getMessage());
    }

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
            \Log::error('SubServiciosController@index Error: '.$e->getMessage());

            return view('usuarios.subservicios', [
                'subServicios' => collect(),
                'servicios' => collect(),
            ])->with('error', 'Error al cargar los subservicios: '.$e->getMessage());
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
                $filename = 'subservicio_'.time().'_'.uniqid().'.'.$file->getClientOriginalExtension();
                $file->storeAs(self::STORAGE_PATH, $filename, 'public');
                $imagenPath = $filename;
            }

            SubServicios::create([
                'servicios_id' => $request->servicios_id,
                'nombre' => $request->nombre,
                'descripcion' => $request->descripcion ?? '',
                'precio' => $request->precio,
                'imagen' => $imagenPath,
            ]);

            return $this->handleSuccessResponse($request, 'Subservicio creado exitosamente.');
        } catch (\Illuminate\Validation\ValidationException $e) {
            return $this->handleValidationError($request, $e);
        } catch (\Exception $e) {
            return $this->handleExceptionError($request, $e, 'crear el subservicio');
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
                if ($subServicio->imagen && Storage::disk('public')->exists(self::STORAGE_PATH.$subServicio->imagen)) {
                    Storage::disk('public')->delete(self::STORAGE_PATH.$subServicio->imagen);
                }

                // Guardar nueva imagen
                $file = $request->file('imagen');
                $filename = 'subservicio_'.time().'_'.uniqid().'.'.$file->getClientOriginalExtension();
                $file->storeAs(self::STORAGE_PATH, $filename, 'public');
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

            return $this->handleSuccessResponse($request, 'Subservicio actualizado exitosamente.');
        } catch (\Illuminate\Validation\ValidationException $e) {
            return $this->handleValidationError($request, $e);
        } catch (\Exception $e) {
            return $this->handleExceptionError($request, $e, 'actualizar el subservicio');
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
            if ($subServicio->imagen && Storage::disk('public')->exists(self::STORAGE_PATH.$subServicio->imagen)) {
                Storage::disk('public')->delete(self::STORAGE_PATH.$subServicio->imagen);
            }

            $subServicio->delete();

            return $this->handleSuccessResponse($request, 'Subservicio eliminado exitosamente.');
        } catch (\Exception $e) {
            return $this->handleExceptionError(request(), $e, 'eliminar el subservicio');
        }
    }
}
