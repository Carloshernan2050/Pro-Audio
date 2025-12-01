<?php

namespace App\Http\Controllers;

use App\Models\Historial;
use Barryvdh\DomPDF\Facade\Pdf;

class HistorialController extends Controller
{
    public function index()
    {
        $items = Historial::with(['reserva'])->get();

        return view('usuarios.historial', compact('items'));
    }

    public function exportPdf()
    {
        $items = Historial::with(['reserva'])->get();

        $pdf = Pdf::loadView('usuarios.historial', [
            'items' => $items,
            'generatedAt' => now(),
        ])->setPaper('a4', 'portrait');

        return $pdf->download('historial.pdf');
    }
}
