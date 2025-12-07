<?php

namespace App\Http\Controllers;

use App\Services\ChatbotSessionManager;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class CotizacionController extends Controller
{
    private ChatbotSessionManager $sessionManager;

    public function __construct(ChatbotSessionManager $sessionManager)
    {
        $this->sessionManager = $sessionManager;
    }

    /**
     * Store a newly created cotización in storage.
     */
    public function store(Request $request)
    {
        $selecciones = (array) $request->input('selecciones', session('chat.selecciones', []));
        $dias = (int) $request->input('dias', session('chat.days', 1));
        $personasId = $request->input('personas_id', session('usuario_id'));

        if (empty($selecciones) || ! $personasId) {
            return response()->json([
                'error' => 'No se pueden guardar cotizaciones sin selecciones o sin usuario identificado.',
            ], 422);
        }

        try {
            $this->sessionManager->guardarCotizacion($personasId, $selecciones, $dias);

            return response()->json([
                'success' => true,
                'message' => 'Cotización guardada correctamente.',
            ], 201);
        } catch (\Exception $e) {
            Log::error('Error al guardar cotización: '.$e->getMessage());

            return response()->json([
                'error' => 'Error al guardar la cotización.',
            ], 500);
        }
    }
}

