<?php

namespace App\Http\Controllers;

use App\Models\Historial;
use Illuminate\Http\Request;
use Barryvdh\DomPDF\Facade\Pdf;

class HistorialController extends Controller
{
    public function index()
    {
        $items = Historial::with('calendario')->get();
        return view('usuarios.historial', compact('items'));
    }

    public function exportPdf(Request $request)
    {
        $items = Historial::with('calendario')->get();

        $pdf = Pdf::loadView('usuarios.historial', [
            'items' => $items,
            'generatedAt' => now(),
        ])->setPaper('a4', 'portrait');

        return $pdf->download('historial.pdf');
    }
}


