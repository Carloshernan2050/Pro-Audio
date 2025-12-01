<?php

namespace App\Services;

use Illuminate\Http\Request;

class ChatbotMessageProcessor
{
    private ChatbotTextProcessor $textProcessor;

    private ChatbotIntentionDetector $intentionDetector;

    private ChatbotSuggestionGenerator $suggestionGenerator;

    private ChatbotResponseBuilder $responseBuilder;

    private ChatbotSubServicioService $subServicioService;

    private const REGEX_DIAS = '/^(por\s+)?\d+\s*d[ií]as?$/i';

    public function __construct(
        ChatbotTextProcessor $textProcessor,
        ChatbotIntentionDetector $intentionDetector,
        ChatbotSuggestionGenerator $suggestionGenerator,
        ChatbotResponseBuilder $responseBuilder,
        ChatbotSubServicioService $subServicioService
    ) {
        $this->textProcessor = $textProcessor;
        $this->intentionDetector = $intentionDetector;
        $this->suggestionGenerator = $suggestionGenerator;
        $this->responseBuilder = $responseBuilder;
        $this->subServicioService = $subServicioService;
    }

    public function procesarMensajeTexto(Request $request, string $mensaje, string $mensajeCorregido, int $dias, int $sessionDays, array $sessionIntenciones, bool $esContinuacion): \Illuminate\Http\JsonResponse
    {
        if ($mensaje === '') {
            return $this->responseBuilder->responderConOpciones();
        }

        $respuestaEspecial = $this->procesarMensajesEspeciales($mensaje, $mensajeCorregido, $dias);
        if ($respuestaEspecial !== null) {
            return $respuestaEspecial;
        }

        return $this->procesarMensajeNormal($request, $mensaje, $mensajeCorregido, $dias, $sessionDays, $sessionIntenciones, $esContinuacion);
    }

    private function procesarMensajesEspeciales(string $mensaje, string $mensajeCorregido, int $dias): ?\Illuminate\Http\JsonResponse
    {
        $esSolicitudCatalogo = $this->esSolicitudCatalogo($mensaje, $mensajeCorregido);
        if ($esSolicitudCatalogo) {
            $seleccionesActuales = (array) session('chat.selecciones', []);
            $sessionDaysValue = (int) session('chat.days', 1);

            return $this->responseBuilder->mostrarCatalogoJson(
                'Catálogo completo. Selecciona los sub-servicios que deseas agregar a tu cotización:',
                $sessionDaysValue > 0 ? $sessionDaysValue : null,
                $seleccionesActuales
            );
        }

        return $this->verificarMensajeFueraDeTema($mensaje, $mensajeCorregido, $dias);
    }

    private function procesarMensajeNormal(Request $request, string $mensaje, string $mensajeCorregido, int $dias, int $sessionDays, array $sessionIntenciones, bool $esContinuacion): \Illuminate\Http\JsonResponse
    {
        $intencionesDetectadas = $this->detectarIntencionesDelMensaje($mensaje, $mensajeCorregido, $sessionIntenciones, $esContinuacion, $dias);
        $tokens = $this->textProcessor->extraerTokens($mensajeCorregido);
        $esAgregado = $this->textProcessor->verificarSiEsAgregado($mensajeCorregido);
        $soloDias = $this->textProcessor->verificarSoloDias($mensaje, $mensajeCorregido);
        $esSolicitudCatalogo = $this->esSolicitudCatalogo($mensaje, $mensajeCorregido);
        $esNuevaConsulta = ! $esContinuacion && ! $esAgregado && ! $esSolicitudCatalogo && ! $soloDias && (! empty($intencionesDetectadas) || ! empty($tokens));

        $respuestaActualizacionDias = $this->procesarActualizacionDias($dias, $soloDias);
        if ($respuestaActualizacionDias !== null) {
            return $respuestaActualizacionDias;
        }

        if (! empty($intencionesDetectadas)) {
            $intencionesDetectadas = $this->intentionDetector->validarIntencionesContraMensaje($intencionesDetectadas, $mensajeCorregido);
        }

        $intenciones = $this->combinarIntenciones($intencionesDetectadas, $sessionIntenciones, $esAgregado, $esNuevaConsulta);
        $daysForResponse = $this->calcularDiasParaRespuesta($mensaje, $dias, $esContinuacion, $sessionDays);

        $contexto = [
            'request' => $request,
            'mensaje' => $mensaje,
            'mensajeCorregido' => $mensajeCorregido,
            'dias' => $dias,
            'daysForResponse' => $daysForResponse,
            'esContinuacion' => $esContinuacion,
            'esAgregado' => $esAgregado,
            'esSolicitudCatalogo' => $esSolicitudCatalogo,
            'soloDias' => $soloDias,
        ];
        $respuestaIntenciones = $this->procesarIntencionesDetectadas($intenciones, $contexto);
        if ($respuestaIntenciones !== null) {
            return $respuestaIntenciones;
        }

        return $this->buscarSubServiciosRelacionados($mensaje, $mensajeCorregido, $tokens, $intenciones, $daysForResponse);
    }

    private function esSolicitudCatalogo(string $mensaje, string $mensajeCorregido): bool
    {
        return in_array(mb_strtolower(trim($mensaje)), ['catalogo', 'catálogo'], true)
            || in_array(mb_strtolower(trim($mensajeCorregido)), ['catalogo', 'catálogo'], true);
    }

    private function verificarMensajeFueraDeTema(string $mensaje, string $mensajeCorregido, int $dias): ?\Illuminate\Http\JsonResponse
    {
        if (! $this->intentionDetector->esRelacionado($mensajeCorregido)) {
            $seleccionesPrevChk = (array) session('chat.selecciones', []);
            $soloDiasOriginalGate = preg_match(self::REGEX_DIAS, trim($mensaje));
            $soloDiasCorregidoGate = preg_match(self::REGEX_DIAS, trim($mensajeCorregido));
            $soloDiasNowGate = $soloDiasOriginalGate || $soloDiasCorregidoGate || ($dias > 0);
            if (! ($soloDiasNowGate && ! empty($seleccionesPrevChk))) {
                session()->forget('chat.intenciones');

                return $this->responderFueraDeTema($mensaje, $mensajeCorregido);
            }
        }

        return null;
    }

    private function detectarIntencionesDelMensaje(string $mensaje, string $mensajeCorregido, array $sessionIntenciones, bool $esContinuacion, int $dias): array
    {
        $intencionesDetectadas = $this->intentionDetector->detectarIntenciones($mensaje);
        if (empty($intencionesDetectadas)) {
            $mlIntent = $this->intentionDetector->clasificarPorTfidf($mensajeCorregido);
            if (! empty($mlIntent)) {
                $intencionesDetectadas = $mlIntent;
            }
        }
        if (empty($intencionesDetectadas) && ! empty($sessionIntenciones)
            && ($esContinuacion || $dias > 0) && $this->intentionDetector->esRelacionado($mensajeCorregido)) {
            $intencionesDetectadas = $sessionIntenciones;
        }

        return $intencionesDetectadas;
    }

    private function procesarActualizacionDias(int $dias, bool $soloDias): ?\Illuminate\Http\JsonResponse
    {
        $seleccionesPrevias = (array) session('chat.selecciones', []);
        if (! ($soloDias || $dias > 0) || empty($seleccionesPrevias)) {
            return null;
        }

        return $this->actualizarCotizacionConDias($dias, $seleccionesPrevias);
    }

    private function actualizarCotizacionConDias(int $dias, array $seleccionesPrevias): \Illuminate\Http\JsonResponse
    {
        try {
            $items = $this->subServicioService->obtenerItemsSeleccionados($seleccionesPrevias);
            if ($items->isNotEmpty()) {
                $diasCalculo = (int) $dias;

                return $this->responseBuilder->responderCotizacion($items, $diasCalculo, $seleccionesPrevias, true);
            }
            session()->forget('chat.selecciones');
            $diasParaRespuesta = $dias > 0 ? $dias : null;

            return $this->responseBuilder->mostrarCatalogoJson(
                'No se encontraron los servicios seleccionados. Aquí está el catálogo completo:',
                $diasParaRespuesta,
                []
            );
        } catch (\Exception $e) {
            $diasParaRespuesta = $dias > 0 ? $dias : null;

            return $this->responseBuilder->mostrarCatalogoJson(
                'Ocurrió un error al calcular la cotización. Aquí está el catálogo completo:',
                $diasParaRespuesta,
                $seleccionesPrevias
            );
        }
    }

    private function combinarIntenciones(array $intencionesDetectadas, array $sessionIntenciones, bool $esAgregado, bool $esNuevaConsulta): array
    {
        if (! empty($intencionesDetectadas)) {
            if ($esAgregado && ! empty($sessionIntenciones)) {
                $intenciones = array_values(array_unique(array_merge($sessionIntenciones, $intencionesDetectadas)));
            } else {
                $intenciones = $intencionesDetectadas;
                if ($esNuevaConsulta) {
                    session()->forget('chat.selecciones');
                }
            }
            session(['chat.intenciones' => $intenciones]);

            return $intenciones;
        }
        if ($esNuevaConsulta) {
            session()->forget('chat.selecciones');
        }

        return $sessionIntenciones;
    }

    private function calcularDiasParaRespuesta(string $mensaje, int $dias, bool $esContinuacion, int $sessionDays): ?int
    {
        if ($mensaje === '') {
            return null;
        }
        if ($dias > 0) {
            return $dias;
        }

        return ($esContinuacion && $sessionDays > 0) ? $sessionDays : null;
    }

    private function procesarIntencionesDetectadas(array $intenciones, array $contexto): ?\Illuminate\Http\JsonResponse
    {
        if (empty($intenciones) || ! $this->intentionDetector->esRelacionado($contexto['mensajeCorregido'])) {
            return null;
        }
        $relSub = $this->subServicioService->obtenerSubServiciosPorIntenciones($intenciones);
        if ($relSub->isEmpty()) {
            return null;
        }

        return $this->responderConIntenciones($intenciones, $relSub, $contexto);
    }

    private function responderConIntenciones(array $intenciones, $relSub, array $contexto): \Illuminate\Http\JsonResponse
    {
        $lista = implode(' y ', $intenciones);
        $request = $contexto['request'];
        $provieneDeSugerencia = $request->boolean('sugerencia_aplicada', false);
        $esPeticionNueva = ! $contexto['esContinuacion'] && ! $contexto['esAgregado'] && ! $contexto['esSolicitudCatalogo'] && ! $contexto['soloDias'];
        if ($provieneDeSugerencia || $esPeticionNueva) {
            $hint = [];
            try {
                $hint = $this->suggestionGenerator->generarSugerenciasPorToken($contexto['mensaje'])[0] ?? [];
            } catch (\Throwable $_) {
                // Ignorar errores al generar sugerencias
            }

            return $this->responseBuilder->solicitarConfirmacionIntencion($lista, $intenciones, $contexto['dias'], $contexto['daysForResponse'], $hint);
        }

        return $this->responseBuilder->mostrarOpcionesConIntenciones($intenciones, $relSub, $contexto['dias'], $contexto['daysForResponse']);
    }

    private function buscarSubServiciosRelacionados(string $mensaje, string $mensajeCorregido, array $tokens, array $intenciones, ?int $daysForResponse): \Illuminate\Http\JsonResponse
    {
        $relSub = $this->subServicioService->buscarSubServiciosRelacionados($mensajeCorregido, $tokens, $intenciones);

        if ($relSub->isNotEmpty()) {
            $intro = $mensajeCorregido !== ''
                ? 'Con base en tu consulta, estas opciones están relacionadas. '
                : 'He encontrado opciones relacionadas.';
            $seleccionesActuales = (array) session('chat.selecciones', []);

            return $this->responseBuilder->responderOpciones(
                $intro.'Selecciona los sub-servicios que deseas cotizar:',
                $relSub,
                $daysForResponse,
                $seleccionesActuales
            );
        }

        return $this->responderFueraDeTema($mensaje, $mensajeCorregido);
    }

    private function responderFueraDeTema(string $mensajeOriginal, string $mensajeCorregido)
    {
        [$sugerencias, $tokenHints, $best] = $this->obtenerSugerenciasConHints($mensajeOriginal, $mensajeCorregido);

        return response()->json([
            'respuesta' => 'perdon no entiendo, tal vez quisiste decir:',
            'sugerencias' => $sugerencias,
            'tokenHints' => $tokenHints,
            'originalMensaje' => $mensajeOriginal,
            'bestToken' => $best['token'] ?? null,
            'bestSuggestion' => $best['sugerencia'] ?? null,
        ]);
    }

    private function obtenerSugerenciasConHints(string $mensajeOriginal, string $mensajeCorregido): array
    {
        try {
            $sugerencias = $this->suggestionGenerator->generarSugerencias($mensajeCorregido);
            $tokenHints = $this->suggestionGenerator->generarSugerenciasPorToken($mensajeOriginal);
        } catch (\Throwable $e) {
            $sugerencias = ['alquiler', 'animacion', 'publicidad', 'luces', 'dj', 'audio'];
            $tokenHints = $this->suggestionGenerator->fallbackTokenHints($mensajeOriginal);
        }

        return [$sugerencias, $tokenHints, $this->suggestionGenerator->extraerMejorSugerencia($tokenHints)];
    }
}
