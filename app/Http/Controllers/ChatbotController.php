<?php

namespace App\Http\Controllers;

use App\Services\ChatbotIntentionDetector;
use App\Services\ChatbotMessageProcessor;
use App\Services\ChatbotResponseBuilder;
use App\Services\ChatbotSessionManager;
use App\Services\ChatbotSubServicioService;
use App\Services\ChatbotSuggestionGenerator;
use App\Services\ChatbotTextProcessor;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class ChatbotController extends Controller
{
    private ChatbotTextProcessor $textProcessor;

    private ChatbotIntentionDetector $intentionDetector;

    private ChatbotSuggestionGenerator $suggestionGenerator;

    private ChatbotResponseBuilder $responseBuilder;

    private ChatbotMessageProcessor $messageProcessor;

    private ChatbotSubServicioService $subServicioService;

    private ChatbotSessionManager $sessionManager;

    public function __construct(
        ChatbotTextProcessor $textProcessor,
        ChatbotIntentionDetector $intentionDetector,
        ChatbotSuggestionGenerator $suggestionGenerator,
        ChatbotResponseBuilder $responseBuilder,
        ChatbotMessageProcessor $messageProcessor,
        ChatbotSubServicioService $subServicioService,
        ChatbotSessionManager $sessionManager
    ) {
        $this->textProcessor = $textProcessor;
        $this->intentionDetector = $intentionDetector;
        $this->suggestionGenerator = $suggestionGenerator;
        $this->responseBuilder = $responseBuilder;
        $this->messageProcessor = $messageProcessor;
        $this->subServicioService = $subServicioService;
        $this->sessionManager = $sessionManager;
    }

    /**
     * Muestra la vista principal del chatbot
     */
    public function index()
    {
        return view('usuarios.chatbot');
    }

    /**
     * Flujo de chat SEMÁNTICO basado en la base de datos.
     * - Si llega una selección de sub_servicios, calcula y devuelve la cotización.
     * - En caso contrario, sugiere opciones relevantes o muestra todas para seleccionar.
     */
    public function enviar(Request $request)
    {
        $respuestaAccion = $this->procesarAccionesEspeciales($request);
        if ($respuestaAccion !== null) {
            return $respuestaAccion;
        }

        try {
            return $this->procesarMensajePrincipal($request);
        } catch (\Throwable $e) {
            return $this->manejarErrorEnProcesamiento($request, $e);
        }
    }

    private function procesarAccionesEspeciales(Request $request): ?\Illuminate\Http\JsonResponse
    {
        $acciones = $this->obtenerAccionesEspeciales($request);
        foreach ($acciones as $accion) {
            if ($accion !== null) {
                return $accion;
            }
        }

        return null;
    }

    private function obtenerAccionesEspeciales(Request $request): array
    {
        return [
            $request->boolean('confirm_intencion', false) ? $this->manejarConfirmacionIntencion($request) : null,
            ($request->has('limpiar_cotizacion') && $request->input('limpiar_cotizacion') === true) ? $this->manejarLimpiezaCotizacion() : null,
            ($request->has('terminar_cotizacion') && $request->input('terminar_cotizacion') === true) ? $this->manejarFinalizacionCotizacion() : null,
        ];
    }

    private function manejarConfirmacionIntencion(Request $request)
    {
        $intencionesConfirmadas = (array) $request->input('intenciones', []);
        $diasReq = (int) $request->input('dias', 0);
        $sessionDays = (int) session('chat.days', 0);
        $daysForResponse = null;
        if ($diasReq > 0) {
            $daysForResponse = $diasReq;
        } elseif ($sessionDays > 0) {
            $daysForResponse = $sessionDays;
        }
        if (! empty($intencionesConfirmadas)) {
            $relSub = $this->subServicioService->obtenerSubServiciosPorIntenciones($intencionesConfirmadas);
            if ($relSub->isNotEmpty()) {
                session(['chat.intenciones' => $intencionesConfirmadas]);
                $seleccionesActuales = (array) session('chat.selecciones', []);

                return $this->responseBuilder->responderOpciones(
                    'Perfecto. Estas son las opciones relacionadas. Selecciona los sub-servicios que deseas cotizar:',
                    $relSub,
                    $daysForResponse,
                    $seleccionesActuales
                );
            }
        }

        return $this->responseBuilder->mostrarCatalogoJson(
            'No encontré una coincidencia clara. Aquí tienes el catálogo:',
            $daysForResponse ?? null,
            (array) session('chat.selecciones', [])
        );
    }

    private function procesarMensajePrincipal(Request $request): \Illuminate\Http\JsonResponse
    {
        $mensaje = trim((string) $request->input('mensaje'));
        $seleccion = $request->input('seleccion');
        $mensajeCorregido = $mensaje !== '' ? $this->textProcessor->corregirOrtografia($mensaje) : '';
        $sessionDays = (int) session('chat.days', 0);
        $sessionIntenciones = (array) session('chat.intenciones', []);
        $esContinuacion = $this->textProcessor->esContinuacion($mensaje);
        $dias = (int) $request->input('dias', 0);
        if ($dias <= 0) {
            $dias = $this->sessionManager->extraerDiasDelRequest($mensaje, $esContinuacion, $sessionDays, $this->textProcessor);
        }

        if (is_array($seleccion)) {
            return $this->procesarSeleccionSubServicios($seleccion, $dias);
        }

        return $this->messageProcessor->procesarMensajeTexto($request, $mensaje, $mensajeCorregido, $dias, $sessionDays, $sessionIntenciones, $esContinuacion);
    }

    private function manejarErrorEnProcesamiento(Request $request, \Throwable $e): \Illuminate\Http\JsonResponse
    {
        Log::error('Error en ChatbotController@enviar: '.$e->getMessage(), ['trace' => $e->getTraceAsString()]);
        $respuestaRecuperacion = $this->intentarRecuperacion($request);
        if ($respuestaRecuperacion !== null) {
            return $respuestaRecuperacion;
        }

        return $this->responseBuilder->mostrarCatalogoJson(
            'Estoy teniendo inconvenientes para procesar tu mensaje. Te muestro el catálogo para que puedas continuar:',
            (int) session('chat.days', 1) ?: null,
            (array) session('chat.selecciones', [])
        );
    }

    private function intentarRecuperacion(Request $request): ?\Illuminate\Http\JsonResponse
    {
        try {
            $mensajeFallback = (string) $request->input('mensaje', '');
            $respuestaSugerencia = $this->procesarSugerenciaDeBaseDatos($mensajeFallback);
            if ($respuestaSugerencia !== null) {
                return $respuestaSugerencia;
            }

            return $this->procesarFallbackIntenciones($mensajeFallback);
        } catch (\Throwable $_) {
            return null;
        }
    }

    private function procesarSugerenciaDeBaseDatos(string $mensajeFallback): ?\Illuminate\Http\JsonResponse
    {
        $hint = $this->obtenerSugerenciaDeBaseDatos($mensajeFallback);
        if (empty($hint) || !isset($hint['token']) || empty($hint['sugerencias'])) {
            return null;
        }

        $mejorSugerencia = $hint['sugerencias'][0] ?? null;
        if (empty($mejorSugerencia)) {
            return null;
        }

        $sessionDaysValue = (int) session('chat.days', 0);
        $respuestaTexto = "Tal vez quisiste decir \"{$mejorSugerencia}\"";
        $ints = $this->intentionDetector->detectarIntenciones($mejorSugerencia);
        $actions = $this->construirAccionesRecuperacion($ints, $sessionDaysValue);

        return response()->json([
            'respuesta' => $respuestaTexto,
            'actions' => $actions,
            'days' => $sessionDaysValue > 0 ? $sessionDaysValue : null,
        ]);
    }

    private function obtenerSugerenciaDeBaseDatos(string $mensajeFallback): array
    {
        try {
            return $this->suggestionGenerator->generarSugerenciasPorToken($mensajeFallback)[0] ?? [];
        } catch (\Throwable $_) {
            return [];
        }
    }

    private function procesarFallbackIntenciones(string $mensajeFallback): ?\Illuminate\Http\JsonResponse
    {
        $ints = $this->intentionDetector->detectarIntenciones($mensajeFallback);
        if (empty($ints)) {
            return null;
        }

        $sessionDaysValue = (int) session('chat.days', 0);
        $respuestaTexto = '¿Te refieres a '.implode(' y ', $ints).'?';
        $actions = $this->construirAccionesRecuperacion($ints, $sessionDaysValue);

        return response()->json([
            'respuesta' => $respuestaTexto,
            'actions' => $actions,
            'days' => $sessionDaysValue > 0 ? $sessionDaysValue : null,
        ]);
    }

    private function construirAccionesRecuperacion(array $ints, int $sessionDaysValue): array
    {
        if (empty($ints)) {
            return [
                ['id' => 'reject_intent', 'label' => 'Mostrar catálogo'],
            ];
        }

        $dias = $sessionDaysValue > 0 ? $sessionDaysValue : null;
        return [
            ['id' => 'confirm_intent', 'label' => 'Sí, continuar', 'meta' => ['intenciones' => $ints, 'dias' => $dias]],
            ['id' => 'reject_intent', 'label' => 'No, mostrar catálogo'],
        ];
    }

    private function manejarLimpiezaCotizacion()
    {
        $this->sessionManager->limpiarSesionChat();

        return response()->json([
            'respuesta' => 'Cotización limpiada. Puedes empezar una nueva selección.',
            'selecciones' => [],
            'total' => 0,
        ]);
    }

    private function manejarFinalizacionCotizacion()
    {
        $selecciones = (array) session('chat.selecciones', []);
        $dias = (int) session('chat.days', 1);
        $personasId = session('usuario_id');
        $this->sessionManager->guardarCotizacion($personasId, $selecciones, $dias);
        $this->sessionManager->limpiarSesionChat();

        return response()->json([
            'respuesta' => 'Gracias por tu interés. Contacta con un trabajador en <a href="https://wa.link/isz77x" target="_blank" rel="noopener noreferrer">https://wa.link/isz77x</a>. Tu cotización ha sido guardada.',
            'limpiar_chat' => true,
            'selecciones' => [],
            'total' => 0,
        ]);
    }

    private function procesarSeleccionSubServicios(array $seleccion, int $dias)
    {
        $seleccionNormalizada = array_values(array_filter(array_unique(array_map('intval', $seleccion)), function ($id) {
            return $id > 0;
        }));

        if (empty($seleccionNormalizada)) {
            session()->forget('chat.selecciones');
            $diasParaRespuesta = $this->sessionManager->obtenerDiasParaRespuesta($dias);

            return $this->responseBuilder->mostrarCatalogoJson(
                'No tienes sub-servicios seleccionados. El catálogo está disponible para que agregues los que necesites:',
                $diasParaRespuesta,
                []
            );
        }

        $items = $this->subServicioService->obtenerItemsSeleccionados($seleccionNormalizada);
        if ($items->isEmpty()) {
            session()->forget('chat.selecciones');
            $diasParaRespuesta = $this->sessionManager->obtenerDiasParaRespuesta($dias);

            return $this->responseBuilder->mostrarCatalogoJson(
                'No se encontraron sub-servicios para tu selección. Aquí está el catálogo completo:',
                $diasParaRespuesta,
                []
            );
        }

        session(['chat.selecciones' => $seleccionNormalizada]);
        $sessionDaysValue = (int) session('chat.days', 0);
        if ($dias > 0) {
            $diasCalculo = $dias;
        } else {
            $diasCalculo = max(1, $sessionDaysValue > 0 ? $sessionDaysValue : 1);
        }

        return $this->responseBuilder->responderCotizacion($items, $diasCalculo, $seleccionNormalizada);
    }

    private function obtenerDiasParaRespuesta(int $dias): ?int
    {
        $sessionDaysValue = (int) session('chat.days', 0);
        if ($dias > 0) {
            return $dias;
        } elseif ($sessionDaysValue > 0) {
            return $sessionDaysValue;
        }

        return null;
    }
}
