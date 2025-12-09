<?php

namespace App\Http\Controllers;

use App\Exceptions\ImageStorageException;
use App\Repositories\Interfaces\ServicioRepositoryInterface;
use App\Repositories\Interfaces\SubServicioRepositoryInterface;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpKernel\Exception\UnprocessableEntityHttpException;

class SubServiciosController extends Controller
{
    private const STORAGE_PATH = 'subservicios/';
    private const CONTENT_TYPE_JSON = 'application/json';

    private SubServicioRepositoryInterface $subServicioRepository;

    private ServicioRepositoryInterface $servicioRepository;

    public function __construct(
        SubServicioRepositoryInterface $subServicioRepository,
        ServicioRepositoryInterface $servicioRepository
    ) {
        $this->subServicioRepository = $subServicioRepository;
        $this->servicioRepository = $servicioRepository;
    }

    /**
     * Handle AJAX or redirect response for success messages.
     */
    private function handleSuccessResponse(Request $request, string $message)
    {
        // Verificar múltiples formas de detectar peticiones AJAX/JSON
        if ($request->ajax() ||
            $request->wantsJson() ||
            $request->expectsJson() ||
            $request->header('X-Requested-With') === 'XMLHttpRequest' ||
            $request->header('Accept') === self::CONTENT_TYPE_JSON ||
            str_contains($request->header('Accept', ''), self::CONTENT_TYPE_JSON)) {
            return response()->json(['success' => $message]);
        }

        return redirect()->route('subservicios.index')->with('success', $message);
    }

    /**
     * Handle AJAX or redirect response for validation errors.
     */
    private function handleValidationError(Request $request, \Illuminate\Validation\ValidationException $e)
    {
        // Verificar múltiples formas de detectar peticiones AJAX/JSON
        if ($request->ajax() ||
            $request->wantsJson() ||
            $request->expectsJson() ||
            $request->header('X-Requested-With') === 'XMLHttpRequest' ||
            $request->header('Accept') === self::CONTENT_TYPE_JSON ||
            str_contains($request->header('Accept', ''), self::CONTENT_TYPE_JSON)) {
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
        // Verificar múltiples formas de detectar peticiones AJAX/JSON
        if ($request->ajax() ||
            $request->wantsJson() ||
            $request->expectsJson() ||
            $request->header('X-Requested-With') === 'XMLHttpRequest' ||
            $request->header('Accept') === self::CONTENT_TYPE_JSON ||
            str_contains($request->header('Accept', ''), self::CONTENT_TYPE_JSON)) {
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
            // Usar repositorio en lugar de modelo directo (DIP)
            // Nota: Necesitamos obtener con relaciones, pero el repositorio actual no tiene ese método
            // Por ahora usamos el repositorio básico y cargamos relaciones después si es necesario
            $subServicios = \App\Models\SubServicios::with('servicio')
                ->orderBy('servicios_id')
                ->orderBy('nombre')
                ->get();

            // Usar repositorio en lugar de modelo directo (DIP)
            $servicios = $this->servicioRepository->all();

            return view('usuarios.subservicios', compact('subServicios', 'servicios'));
        } catch (\Exception $e) {
            Log::error('SubServiciosController@index Error: '.$e->getMessage());

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
        // Usar repositorio en lugar de modelo directo (DIP)
        $servicios = $this->servicioRepository->all();

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
                'imagen' => 'nullable|image|mimes:jpeg,png,jpg,gif,webp|max:5120', // 5MB máximo
            ], [
                'imagen.image' => 'El archivo debe ser una imagen válida',
                'imagen.mimes' => 'La imagen debe ser de tipo: jpeg, png, jpg, gif o webp',
                'imagen.max' => 'La imagen no debe ser mayor a 5MB',
            ]);

            // Asegurar que el directorio existe
            if (!Storage::disk('public')->exists(self::STORAGE_PATH)) {
                Storage::disk('public')->makeDirectory(self::STORAGE_PATH);
            }

            $imagenPath = null;
            if ($request->hasFile('imagen')) {
                $file = $request->file('imagen');
                if (!$file || !$file->isValid()) {
                    throw new UnprocessableEntityHttpException('El archivo de imagen no es válido');
                }
                $filename = 'subservicio_'.time().'_'.uniqid().'.'.$file->getClientOriginalExtension();
                $path = $file->storeAs(self::STORAGE_PATH, $filename, 'public');
                
                if (!$path) {
                    throw new ImageStorageException();
                }
                
                $imagenPath = $filename;
            }

            // Usar repositorio en lugar de modelo directo (DIP)
            $this->subServicioRepository->create([
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
        // Usar repositorio en lugar de modelo directo (DIP)
        $subServicio = $this->subServicioRepository->findWithRelations($id);

        return view('usuarios.subservicios', compact('subServicio'));
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit($id)
    {
        // Usar repositorio en lugar de modelo directo (DIP)
        $subServicio = $this->subServicioRepository->find($id);
        $servicios = $this->servicioRepository->all();

        return view('usuarios.subservicios', compact('subServicio', 'servicios'));
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, $id)
    {
        try {
            // Usar repositorio en lugar de modelo directo (DIP)
            $subServicio = $this->subServicioRepository->find($id);

            $request->validate([
                'servicios_id' => 'required|exists:servicios,id',
                'nombre' => 'required|string|max:100',
                'descripcion' => 'nullable|string',
                'precio' => 'required|numeric|min:0',
                'imagen' => 'nullable|image|mimes:jpeg,png,jpg,gif,webp|max:5120', // 5MB máximo
            ], [
                'imagen.image' => 'El archivo debe ser una imagen válida',
                'imagen.mimes' => 'La imagen debe ser de tipo: jpeg, png, jpg, gif o webp',
                'imagen.max' => 'La imagen no debe ser mayor a 5MB',
            ]);

            // Asegurar que el directorio existe
            if (!Storage::disk('public')->exists(self::STORAGE_PATH)) {
                Storage::disk('public')->makeDirectory(self::STORAGE_PATH);
            }

            // Manejar la imagen
            if ($request->hasFile('imagen')) {
                // Eliminar imagen anterior si existe
                if ($subServicio->imagen && Storage::disk('public')->exists(self::STORAGE_PATH.$subServicio->imagen)) {
                    Storage::disk('public')->delete(self::STORAGE_PATH.$subServicio->imagen);
                }

                // Guardar nueva imagen
                $file = $request->file('imagen');
                if (!$file || !$file->isValid()) {
                    throw new UnprocessableEntityHttpException('El archivo de imagen no es válido');
                }
                
                $filename = 'subservicio_'.time().'_'.uniqid().'.'.$file->getClientOriginalExtension();
                $path = $file->storeAs(self::STORAGE_PATH, $filename, 'public');
                
                if (!$path) {
                    throw new ImageStorageException();
                }
                
                $imagenPath = $filename;
            } else {
                // Mantener la imagen existente si no se sube una nueva
                $imagenPath = $subServicio->imagen;
            }

            // Usar repositorio en lugar de modelo directo (DIP)
            $this->subServicioRepository->update($id, [
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
    public function destroy(Request $request, $id)
    {
        try {
            // Usar repositorio en lugar de modelo directo (DIP)
            $subServicio = $this->subServicioRepository->find($id);

            // Eliminar imagen si existe
            if ($subServicio->imagen && Storage::disk('public')->exists(self::STORAGE_PATH.$subServicio->imagen)) {
                Storage::disk('public')->delete(self::STORAGE_PATH.$subServicio->imagen);
            }

            // Usar repositorio en lugar de modelo directo (DIP)
            $this->subServicioRepository->delete($id);

            return $this->handleSuccessResponse($request, 'Subservicio eliminado exitosamente.');
        } catch (\Exception $e) {
            return $this->handleExceptionError($request, $e, 'eliminar el subservicio');
        }
    }
}
