<?php

namespace App\Http\Controllers;

use App\Models\Servicios;
use App\Models\Cotizacion;
use App\Models\SubServicios;
use Barryvdh\DomPDF\Facade\Pdf;

class AjustesController extends Controller
{
    public function index()
    {
        $servicios = Servicios::all();
        $subServicios = SubServicios::with('servicio')->orderBy('id', 'asc')->get();
        
        // Cargar cotizaciones con relaciones para el historial
        $cotizaciones = Cotizacion::with(['persona', 'subServicio.servicio'])
            ->orderBy('fecha_cotizacion', 'desc')
            ->get();
        
        return view('usuarios.ajustes', compact('servicios', 'subServicios', 'cotizaciones'));
    }

    public function exportHistorialPdf()
    {
        $cotizaciones = Cotizacion::with(['persona', 'subServicio.servicio'])
            ->orderBy('fecha_cotizacion', 'desc')
            ->get();

        $pdf = Pdf::loadView('usuarios.ajustes_historial_pdf', [
            'cotizaciones' => $cotizaciones,
            'generatedAt' => now(),
        ])->setPaper('a4', 'landscape');

        return $pdf->download('historial_cotizaciones.pdf');
    }
}