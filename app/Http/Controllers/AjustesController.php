<?php

namespace App\Http\Controllers;

use App\Repositories\Interfaces\CotizacionRepositoryInterface;
use App\Repositories\Interfaces\HistorialRepositoryInterface;
use App\Repositories\Interfaces\ServicioRepositoryInterface;
use App\Repositories\Interfaces\SubServicioRepositoryInterface;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Request;

class AjustesController extends Controller
{
    private ServicioRepositoryInterface $servicioRepository;

    private SubServicioRepositoryInterface $subServicioRepository;

    private CotizacionRepositoryInterface $cotizacionRepository;

    private HistorialRepositoryInterface $historialRepository;

    public function __construct(
        ServicioRepositoryInterface $servicioRepository,
        SubServicioRepositoryInterface $subServicioRepository,
        CotizacionRepositoryInterface $cotizacionRepository,
        HistorialRepositoryInterface $historialRepository
    ) {
        $this->servicioRepository = $servicioRepository;
        $this->subServicioRepository = $subServicioRepository;
        $this->cotizacionRepository = $cotizacionRepository;
        $this->historialRepository = $historialRepository;
    }
    public function index(Request $request)
    {
        // Usar repositorio en lugar de modelo directo (DIP)
        $servicios = $this->servicioRepository->all();
        $subServicios = $this->subServicioRepository->allWithRelations();

        $groupBy = $request->query('group_by'); // null | 'consulta' | 'dia'
        $requestedTab = $request->query('tab'); // null | servicios | inventario | movimientos | historial | subservicios
        $historialType = $request->query('historial_type', 'cotizaciones'); // 'cotizaciones' | 'reservas'

        // Cargar cotizaciones o reservas según el tipo seleccionado
        $cotizaciones = null;
        $reservas = null;
        $grouped = null;

        if ($historialType === 'reservas') {
            $reservas = $this->getReservas();
        } else {
            $cotizaciones = $this->getCotizaciones();
            $grouped = $this->groupCotizaciones($cotizaciones, $groupBy);
        }

        // Tab activo por defecto: si hay agrupación, abrir historial
        $activeTab = $groupBy ? 'historial' : 'servicios';
        if (in_array($requestedTab, ['servicios', 'subservicios', 'inventario', 'movimientos', 'historial'], true)) {
            $activeTab = $requestedTab;
        }

        return view('usuarios.ajustes', [
            'servicios' => $servicios,
            'subServicios' => $subServicios,
            'cotizaciones' => $cotizaciones,
            'reservas' => $reservas,
            'groupBy' => $groupBy,
            'groupedCotizaciones' => $grouped,
            'activeTab' => $activeTab,
            'historialType' => $historialType,
        ]);
    }

    public function exportHistorialPdf(Request $request)
    {
        $groupBy = $request->query('group_by');
        $historialType = $request->query('historial_type', 'cotizaciones');

        // Si es reservas, generar PDF de reservas
        if ($historialType === 'reservas') {
            $reservas = $this->getReservas();

            $pdf = Pdf::loadView('usuarios.historial_reservas_pdf', [
                'reservas' => $reservas,
                'generatedAt' => now(),
            ])->setPaper('a4', 'portrait');

            return $pdf->download('historial_reservas.pdf');
        }

        // Si es cotizaciones (por defecto), generar PDF de cotizaciones
        $cotizaciones = $this->getCotizaciones();
        $grouped = $this->groupCotizaciones($cotizaciones, $groupBy);

        $pdf = Pdf::loadView('usuarios.ajustes_historial_pdf', [
            'cotizaciones' => $cotizaciones,
            'groupBy' => $groupBy,
            'groupedCotizaciones' => $grouped,
            'generatedAt' => now(),
        ])->setPaper('a4', $groupBy ? 'portrait' : 'landscape');

        return $pdf->download('historial_cotizaciones.pdf');
    }

    public function getSubservicios()
    {
        // Usar repositorio en lugar de modelo directo (DIP)
        $subServicios = $this->subServicioRepository->allWithRelations();

        return response()->json($subServicios);
    }

    /**
     * Obtiene las cotizaciones con sus relaciones cargadas.
     *
     * @return \Illuminate\Database\Eloquent\Collection
     */
    private function getCotizaciones()
    {
        // Usar repositorio en lugar de modelo directo (DIP)
        return $this->cotizacionRepository->allWithRelations();
    }

    /**
     * Obtiene las reservas confirmadas del historial con sus relaciones cargadas.
     * Solo muestra reservas que tienen fecha de confirmación.
     *
     * @return \Illuminate\Database\Eloquent\Collection
     */
    private function getReservas()
    {
        // Usar repositorio en lugar de modelo directo (DIP)
        return $this->historialRepository->getReservasConfirmadas();
    }

    /**
     * Agrupa las cotizaciones según el tipo de agrupación especificado.
     *
     * @param  \Illuminate\Database\Eloquent\Collection  $cotizaciones
     * @param  string|null  $groupBy
     * @return \Illuminate\Support\Collection|null
     */
    private function groupCotizaciones($cotizaciones, $groupBy)
    {
        if ($groupBy === 'dia') {
            return $cotizaciones->groupBy(function ($c) {
                return optional($c->fecha_cotizacion)?->format('Y-m-d');
            })->map(function ($group) {
                return [
                    'items' => $group,
                    'total' => $group->sum('monto'),
                    'count' => $group->count(),
                ];
            });
        }

        if ($groupBy === 'consulta') {
            return $cotizaciones->groupBy(function ($c) {
                $fecha = optional($c->fecha_cotizacion)?->format('Y-m-d H:i:s');

                return ($c->personas_id ?? '0').'|'.($fecha ?? '');
            })->map(function ($group) {
                $first = $group->first();

                return [
                    'items' => $group,
                    'total' => $group->sum('monto'),
                    'count' => $group->count(),
                    'persona' => $first?->persona,
                    'timestamp' => optional($first?->fecha_cotizacion),
                ];
            })->sortByDesc(function ($data) {
                return optional($data['timestamp'])?->timestamp ?? 0;
            });
        }

        return null;
    }
}
