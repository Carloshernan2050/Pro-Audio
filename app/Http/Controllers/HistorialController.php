<?php

namespace App\Http\Controllers;

use App\Models\Cotizacion;
use App\Models\Historial;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;

class HistorialController extends Controller
{
    public function index()
    {
        $items = Historial::with(['reserva'])->get();

        return view('usuarios.historial', compact('items'));
    }

    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'calendario_id' => 'nullable|exists:calendario,id',
            'reserva_id' => 'nullable|exists:reservas,id',
            'accion' => 'nullable|string|max:50',
            'confirmado_en' => 'nullable|date',
            'observaciones' => 'nullable|string',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'error' => 'Error de validación',
                'messages' => $validator->errors(),
            ], 422);
        }

        try {
            $historial = Historial::create([
                'calendario_id' => $request->input('calendario_id'),
                'reserva_id' => $request->input('reserva_id'),
                'accion' => $request->input('accion'),
                'confirmado_en' => $request->input('confirmado_en') ? now()->parse($request->input('confirmado_en')) : now(),
                'observaciones' => $request->input('observaciones'),
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Historial creado correctamente.',
                'data' => $historial->load('reserva'),
            ], 201);
        } catch (\Exception $e) {
            return response()->json([
                'error' => 'Error al crear el historial: '.$e->getMessage(),
            ], 500);
        }
    }

    public function update(Request $request, Historial $historial)
    {
        $validator = Validator::make($request->all(), [
            'calendario_id' => 'nullable|exists:calendario,id',
            'reserva_id' => 'nullable|exists:reservas,id',
            'accion' => 'nullable|string|max:50',
            'confirmado_en' => 'nullable|date',
            'observaciones' => 'nullable|string',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'error' => 'Error de validación',
                'messages' => $validator->errors(),
            ], 422);
        }

        try {
            $historial->update([
                'calendario_id' => $request->has('calendario_id') ? $request->input('calendario_id') : $historial->calendario_id,
                'reserva_id' => $request->has('reserva_id') ? $request->input('reserva_id') : $historial->reserva_id,
                'accion' => $request->has('accion') ? $request->input('accion') : $historial->accion,
                'confirmado_en' => $request->has('confirmado_en') ? now()->parse($request->input('confirmado_en')) : $historial->confirmado_en,
                'observaciones' => $request->has('observaciones') ? $request->input('observaciones') : $historial->observaciones,
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Historial actualizado correctamente.',
                'data' => $historial->load('reserva'),
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'error' => 'Error al actualizar el historial: '.$e->getMessage(),
            ], 500);
        }
    }

    /**
     * Exporta el historial de reservas confirmadas a PDF
     */
    public function exportPdfReservas()
    {
        try {
            $reservas = Historial::with(['reserva.persona'])
                ->whereNotNull('confirmado_en')
                ->whereNotNull('reserva_id')
                ->orderBy('confirmado_en', 'desc')
                ->orderBy('id', 'desc')
                ->get();

            Log::info('Generando PDF de reservas. Total de reservas encontradas: '.$reservas->count());

            $pdf = Pdf::loadView('usuarios.historial_reservas_pdf', [
                'reservas' => $reservas,
                'generatedAt' => now(),
            ])->setPaper('a4', 'portrait');

            return $pdf->download('historial_reservas.pdf');
        } catch (\Exception $e) {
            Log::error('Error al generar PDF de reservas: '.$e->getMessage());
            Log::error('Stack trace: '.$e->getTraceAsString());
            
            return response()->json([
                'error' => 'Error al generar el PDF de reservas: '.$e->getMessage(),
            ], 500);
        }
    }

    /**
     * Exporta el historial de cotizaciones a PDF
     */
    public function exportPdfCotizaciones()
    {
        $cotizaciones = Cotizacion::with(['persona', 'subServicio.servicio'])
            ->orderBy('fecha_cotizacion', 'desc')
            ->get();

        $pdf = Pdf::loadView('usuarios.historial_cotizaciones_pdf', [
            'cotizaciones' => $cotizaciones,
            'generatedAt' => now(),
        ])->setPaper('a4', 'landscape');

        return $pdf->download('historial_cotizaciones.pdf');
    }
}
