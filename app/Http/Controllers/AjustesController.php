<?php

namespace App\Http\Controllers;

use App\Models\Servicios;
use App\Models\Cotizacion;
use App\Models\SubServicios;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Request;

class AjustesController extends Controller
{
    public function index(Request $request)
    {
        $servicios = Servicios::all();
        $subServicios = SubServicios::with('servicio')->orderBy('id', 'asc')->get();

        $groupBy = $request->query('group_by'); // null | 'consulta' | 'dia'
        $requestedTab = $request->query('tab'); // null | servicios | inventario | movimientos | historial | subservicios

        // Cargar cotizaciones con relaciones para el historial
        $cotizaciones = $this->getCotizaciones();
        $grouped = $this->groupCotizaciones($cotizaciones, $groupBy);

        // Tab activo por defecto: si hay agrupación, abrir historial
        $activeTab = $groupBy ? 'historial' : 'servicios';
        if (in_array($requestedTab, ['servicios', 'subservicios', 'inventario', 'movimientos', 'historial'], true)) {
            $activeTab = $requestedTab;
        }

        return view('usuarios.ajustes', [
            'servicios' => $servicios,
            'subServicios' => $subServicios,
            'cotizaciones' => $cotizaciones,
            'groupBy' => $groupBy,
            'groupedCotizaciones' => $grouped,
            'activeTab' => $activeTab,
        ]);
    }

    public function exportHistorialPdf(Request $request)
    {
        $groupBy = $request->query('group_by');

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
        $subServicios = SubServicios::with('servicio')->orderBy('id', 'asc')->get();
        return response()->json($subServicios);
    }

    /**
     * Obtiene las cotizaciones con sus relaciones cargadas.
     *
     * @return \Illuminate\Database\Eloquent\Collection
     */
    private function getCotizaciones()
    {
        return Cotizacion::with(['persona', 'subServicio.servicio'])
            ->orderBy('fecha_cotizacion', 'desc')
            ->get();
    }

    /**
     * Agrupa las cotizaciones según el tipo de agrupación especificado.
     *
     * @param \Illuminate\Database\Eloquent\Collection $cotizaciones
     * @param string|null $groupBy
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
                return ($c->personas_id ?? '0') . '|' . ($fecha ?? '');
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
