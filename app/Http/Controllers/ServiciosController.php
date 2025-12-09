<?php

namespace App\Http\Controllers;

use App\Exceptions\ServiceCreationException;
use App\Repositories\Interfaces\CotizacionRepositoryInterface;
use App\Repositories\Interfaces\ServicioRepositoryInterface;
use App\Repositories\Interfaces\SubServicioRepositoryInterface;
use App\Services\BladeGeneratorService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

class ServiciosController extends Controller
{
    private ServicioRepositoryInterface $servicioRepository;

    private SubServicioRepositoryInterface $subServicioRepository;

    private CotizacionRepositoryInterface $cotizacionRepository;

    private BladeGeneratorService $bladeGeneratorService;

    public function __construct(
        ServicioRepositoryInterface $servicioRepository,
        SubServicioRepositoryInterface $subServicioRepository,
        CotizacionRepositoryInterface $cotizacionRepository,
        BladeGeneratorService $bladeGeneratorService
    ) {
        $this->servicioRepository = $servicioRepository;
        $this->subServicioRepository = $subServicioRepository;
        $this->cotizacionRepository = $cotizacionRepository;
        $this->bladeGeneratorService = $bladeGeneratorService;
    }
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        try {
            // Usar repositorio en lugar de modelo directo (DIP)
            $servicios = $this->servicioRepository->all();

            // Debug: verificar que hay servicios
            if ($servicios->isEmpty()) {
                Log::info('ServiciosController@index: No hay servicios en la base de datos');
            } else {
                Log::info('ServiciosController@index: Se encontraron '.$servicios->count().' servicios');
            }

            return view('usuarios.ajustes', compact('servicios'));
        } catch (\Exception $e) {
            Log::error('ServiciosController@index Error: '.$e->getMessage());

            return view('usuarios.ajustes', ['servicios' => collect()])
                ->with('error', 'Error al cargar los servicios: '.$e->getMessage());
        }
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        return view('usuarios.ajustes');
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $request->validate([
            'nombre_servicio' => 'required|string|max:100|unique:servicios,nombre_servicio',
            'descripcion' => 'nullable|string|max:500',
            'icono' => 'nullable|string|max:80',
        ]);

        try {
            // PASO 1: Primero guardar en la base de datos usando repositorio (DIP)
            $servicio = $this->servicioRepository->create([
                'nombre_servicio' => $request->nombre_servicio,
                'descripcion' => $request->descripcion ?? '',
                'icono' => $request->icono ?: null,
            ]);

            // Verificar que se guardó correctamente
            if (! $servicio || ! $servicio->id) {
                throw new ServiceCreationException('El servicio no se guardó correctamente en la base de datos.');
            }

            // PASO 2: Después de guardar en DB, generar el archivo blade usando servicio (SRP)
            try {
                $this->bladeGeneratorService->generar($servicio);
            } catch (\Exception $bladeError) {
                // Si falla la generación del blade, el servicio ya está guardado en DB
                // Solo mostramos un mensaje de advertencia
                $redirect = redirect()->route('servicios.index')
                    ->with('success', 'Servicio creado exitosamente en la base de datos.')
                    ->with('warning', 'Advertencia: No se pudo generar la vista automáticamente. '.$bladeError->getMessage());
            }

            $redirect = $redirect ?? redirect()->route('servicios.index')
                ->with('success', 'Servicio creado exitosamente y vista generada automáticamente.');
        } catch (ServiceCreationException $e) {
            $redirect = redirect()->route('servicios.index')
                ->with('error', 'Error al crear el servicio: '.$e->getMessage());
        } catch (\Exception $e) {
            $redirect = redirect()->route('servicios.index')
                ->with('error', 'Error inesperado al crear el servicio: '.$e->getMessage());
        }

        return $redirect;
    }

    /**
     * Display the specified resource.
     */
    public function show($id)
    {
        // Usar repositorio en lugar de modelo directo (DIP)
        $servicio = $this->servicioRepository->findWithRelations($id);
        $subServicios = $servicio->subServicios;

        // Nombre normalizado para la vista
        $nombreVista = Str::slug($servicio->nombre_servicio, '_');

        return view("usuarios.{$nombreVista}", compact('subServicios', 'servicio'));
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit($id)
    {
        // Usar repositorio en lugar de modelo directo (DIP)
        $servicio = $this->servicioRepository->find($id);

        return view('usuarios.ajustes', compact('servicio'));
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, $id)
    {
        // Usar repositorio en lugar de modelo directo (DIP)
        $servicio = $this->servicioRepository->find($id);

        $request->validate([
            'nombre_servicio' => 'required|string|max:100|unique:servicios,nombre_servicio,'.$id,
            'descripcion' => 'nullable|string|max:500',
            'icono' => 'nullable|string|max:80',
        ]);

        try {
            $nombreAnterior = $servicio->nombre_servicio;

            // Usar repositorio para actualizar (DIP)
            $this->servicioRepository->update($id, [
                'nombre_servicio' => $request->nombre_servicio,
                'descripcion' => $request->descripcion ?? '',
                'icono' => $request->icono ?: null,
            ]);

            // Recargar el servicio actualizado
            $servicio = $this->servicioRepository->find($id);

            // Si cambió el nombre, regenerar el blade con el nuevo nombre usando servicio (SRP)
            if ($nombreAnterior !== $request->nombre_servicio) {
                $nombreVistaAnterior = Str::slug($nombreAnterior, '_');
                $this->bladeGeneratorService->eliminar($nombreVistaAnterior);
                $this->bladeGeneratorService->generar($servicio);
            } else {
                // Si solo cambió la descripción, actualizar el blade existente usando servicio (SRP)
                $this->bladeGeneratorService->actualizarDescripcion($servicio);
            }

            return redirect()->route('servicios.index')
                ->with('success', 'Servicio actualizado exitosamente.');
        } catch (\Exception $e) {
            return redirect()->route('servicios.index')
                ->with('error', 'Error al actualizar el servicio: '.$e->getMessage());
        }
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy($id)
    {
        try {
            // Usar repositorio en lugar de modelo directo (DIP)
            $servicio = $this->servicioRepository->findWithRelations($id);
            $nombreVista = Str::slug($servicio->nombre_servicio, '_');

            DB::transaction(function () use ($servicio) {
                $subServicioIds = $servicio->subServicios->pluck('id')->toArray();

                if (! empty($subServicioIds)) {
                    // Usar repositorios en lugar de modelos directos (DIP)
                    $this->cotizacionRepository->deleteBySubServicioIds($subServicioIds);
                    $this->subServicioRepository->deleteByServicioId($servicio->id);
                }

                // Usar repositorio para eliminar (DIP)
                $this->servicioRepository->delete($servicio->id);
            });

            // Eliminar el archivo blade asociado usando servicio (SRP)
            $this->bladeGeneratorService->eliminar($nombreVista);

            return redirect()->route('servicios.index')
                ->with('success', 'Servicio eliminado exitosamente.');
        } catch (\Exception $e) {
            return redirect()->route('servicios.index')
                ->with('error', 'Error al eliminar el servicio: '.$e->getMessage());
        }
    }

}
