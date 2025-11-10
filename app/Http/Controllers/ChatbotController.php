<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use App\Models\SubServicios;
use App\Models\Cotizacion;

class ChatbotController extends Controller
{
    private const STOPWORDS = ['para','por','con','sin','del','de','la','las','el','los','una','unos','unas','que','y','o','en','al'];
    private const STOPWORDS_EXT = ['para','por','con','sin','del','de','la','las','el','los','una','unos','unas','que','y','o','en','al','par'];
    private const TOKENS_GENERICOS = ['necesito','nececito','nesecito','necesitar','requiero','quiero','busco','hola','buenas','gracias','dias','dia'];
    private const CUES_AGREGAR = ['tambien','también','ademas','además','y ',' y','sumar','agrega','agregar','junto','ademas de','además de'];

    private const SUGERENCIAS_BASE = ['alquiler','animacion','publicidad','luces','dj','audio'];

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
        // Confirmación de intención (flujo explícito)
        if ($request->boolean('confirm_intencion', false)) {
            $intencionesConfirmadas = (array) $request->input('intenciones', []);
            $diasReq = (int) $request->input('dias', 0);
            $daysForResponse = $diasReq > 0 ? $diasReq : ((int) session('chat.days', 0) ?: null);
            if (!empty($intencionesConfirmadas)) {
                $relSub = $this->obtenerSubServiciosPorIntenciones($intencionesConfirmadas);
                if ($relSub->isNotEmpty()) {
                    session(['chat.intenciones' => $intencionesConfirmadas]);
                    $seleccionesActuales = (array) session('chat.selecciones', []);
                    return $this->responderOpciones(
                        'Perfecto. Estas son las opciones relacionadas. Selecciona los sub-servicios que deseas cotizar:',
                        $relSub,
                        $daysForResponse,
                        $seleccionesActuales
                    );
                }
            }
            return $this->mostrarCatalogoJson(
                'No encontré una coincidencia clara. Aquí tienes el catálogo:',
                $daysForResponse ?? null,
                (array) session('chat.selecciones', [])
            );
        }
        // Manejar limpieza de cotización
        if ($request->has('limpiar_cotizacion') && $request->input('limpiar_cotizacion') === true) {
            $this->limpiarSesionChat();
            return response()->json([
                'respuesta' => 'Cotización limpiada. Puedes empezar una nueva selección.',
                'selecciones' => [],
                'total' => 0,
            ]);
        }

        // Manejar finalización de cotización
        if ($request->has('terminar_cotizacion') && $request->input('terminar_cotizacion') === true) {
            // Obtener datos antes de limpiar la sesión
            $selecciones = (array) session('chat.selecciones', []);
            $dias = (int) session('chat.days', 1);
            $personasId = session('usuario_id'); // ID del usuario autenticado
            $this->guardarCotizacion($personasId, $selecciones, $dias);
            $this->limpiarSesionChat();

            return response()->json([
                'respuesta' => 'Gracias por tu interés. Contacta con un trabajador en <a href="https://w.app/zlxp23" target="_blank" rel="noopener noreferrer">https://w.app/zlxp23</a>. Tu cotización ha sido guardada.',
                'limpiar_chat' => true,
                'selecciones' => [],
                'total' => 0,
            ]);
        }

        try {
            $mensaje = trim((string) $request->input('mensaje'));
            $seleccion = $request->input('seleccion'); // array de IDs de sub_servicios
            // Corrección de ortografía para mejorar la búsqueda
            $mensajeCorregido = $mensaje !== '' ? $this->corregirOrtografia($mensaje) : '';
            // Continuidad por sesión
            $sessionDays = (int) session('chat.days', 0);
            $sessionIntenciones = (array) session('chat.intenciones', []);
            $intenciones = $sessionIntenciones;
            $esContinuacion = $this->esContinuacion($mensaje);
            // Extraer días: request -> mensaje con números -> palabras -> sesión
            $dias = (int) $request->input('dias', 0);
            if ($dias <= 0 && $mensaje !== '') {
                if (preg_match('/(\d+)\s*d[ií]as?/i', $mensaje, $m)) {
                    $dias = max(1, (int) $m[1]);
                } else {
                    $dias = $this->extraerDiasDesdePalabras($mensaje) ?? 0;
                }
            }
            if ($dias <= 0 && $esContinuacion && $sessionDays > 0) {
                $dias = $sessionDays;
            }
            if ($dias > 0) {
                session(['chat.days' => $dias]);
            } else if (!$esContinuacion) {
                // Si no hay días en el mensaje y no es continuidad, limpiar días en sesión
                session()->forget('chat.days');
            }

        // Si el usuario envía una selección, calculamos el total acumulado
        if (is_array($seleccion) && !empty($seleccion)) {
            $seleccionesPrevias = (array) session('chat.selecciones', []);
            $todasLasSelecciones = array_values(array_unique(array_merge($seleccionesPrevias, $seleccion)));

            $items = $this->obtenerItemsSeleccionados($todasLasSelecciones);

            if ($items->isEmpty()) {
                return response()->json([
                    'respuesta' => 'No se encontraron sub-servicios para tu selección. Intenta de nuevo.',
                ]);
            }

            session(['chat.selecciones' => $todasLasSelecciones]);
            $diasCalculo = $dias > 0 ? $dias : 1;

            return $this->responderCotizacion($items, $diasCalculo, $todasLasSelecciones);
        }

        // Si no hay mensaje, invitamos a cotizar directamente
        if ($mensaje === '') {
            return $this->responderConOpciones();
        }

        // Si el mensaje es "catalogo", devolver catálogo completo directamente
        $esSolicitudCatalogo = in_array(mb_strtolower(trim($mensaje)), ['catalogo','catálogo'], true)
            || in_array(mb_strtolower(trim($mensajeCorregido)), ['catalogo','catálogo'], true);
        if ($esSolicitudCatalogo) {
            $seleccionesActuales = (array) session('chat.selecciones', []);
            $sessionDays = (int) session('chat.days', 1);
            return $this->mostrarCatalogoJson(
                'Catálogo completo. Selecciona los sub-servicios que deseas agregar a tu cotización:',
                $sessionDays > 0 ? $sessionDays : null,
                $seleccionesActuales
            );
        }

        // Si no es relacionado con el dominio, responder fuera de tema (sin catálogo)
        // Permitir excepción si solo define días y ya hay selecciones previas
        if (!$this->esRelacionado($mensajeCorregido)) {
            $seleccionesPrevChk = (array) session('chat.selecciones', []);
            $soloDiasOriginalGate = preg_match('/^(por\s+)?\d+\s*d[ií]as?$/i', trim($mensaje));
            $soloDiasCorregidoGate = preg_match('/^(por\s+)?\d+\s*d[ií]as?$/i', trim($mensajeCorregido));
            $soloDiasNowGate = $soloDiasOriginalGate || $soloDiasCorregidoGate || ($dias > 0);
            if (!($soloDiasNowGate && !empty($seleccionesPrevChk))) {
                // Al no ser relacionado, no conservar contexto previo de intenciones
                session()->forget('chat.intenciones');
                return $this->responderFueraDeTema($mensaje, $mensajeCorregido);
            }
        }

        // Detección de intención basada en sinónimos y palabras clave (soporta múltiples)
        $intencionesDetectadas = $this->detectarIntenciones($mensaje); // p.ej.: ['Alquiler', 'Animación']
        if (empty($intencionesDetectadas)) {
            $mlIntent = $this->clasificarPorTfidf($mensajeCorregido);
            if (!empty($mlIntent)) {
                $intencionesDetectadas = $mlIntent;
            }
        }
        $tokens = array_values(array_filter(preg_split('/\s+/', $mensajeCorregido), function ($t) {
            $t = trim($t);
            if ($t === '') return false;
            if (mb_strlen($t) < 3) return false;
            return !in_array($t, self::STOPWORDS, true);
        }));
        // Determinar si el usuario está agregando (en lugar de reemplazar) intención
        $textoNorm = $this->normalizarTexto($mensajeCorregido);
        $esAgregado = false;
        foreach (self::CUES_AGREGAR as $cue) {
            if (str_contains($textoNorm, $this->normalizarTexto($cue))) { $esAgregado = true; break; }
        }

        // Reutilizar intención previa si no se detectó nada y hay continuidad o solo definición de días
        if (empty($intencionesDetectadas) && !empty($sessionIntenciones)) {
            // Solo reutilizar si el mensaje es relacionado
            if (($esContinuacion || $dias > 0) && $this->esRelacionado($mensajeCorregido)) {
                $intencionesDetectadas = $sessionIntenciones;
            }
        }

        // Verificar si es acción de "añadir más" (mensaje "catalogo")
        $esAnadirMas = $esSolicitudCatalogo;
        
        // Verificar si el mensaje solo contiene definición de días (por ejemplo, "por 3 dias")
        // Verificar tanto en mensaje original como corregido
        $soloDiasOriginal = preg_match('/^(por\s+)?\d+\s*d[ií]as?$/i', trim($mensaje));
        $soloDiasCorregido = preg_match('/^(por\s+)?\d+\s*d[ií]as?$/i', trim($mensajeCorregido));
        $soloDias = $soloDiasOriginal || $soloDiasCorregido;
        
        // Determinar si es una nueva consulta (no es continuación, no es agregar, no es añadir más, y no es solo días)
        $esNuevaConsulta = !$esContinuacion && !$esAgregado && !$esAnadirMas && !$soloDias && (!empty($intencionesDetectadas) || !empty($tokens));

        // Si solo se especifican días y hay selecciones previas, mostrar cotización actualizada
        $seleccionesPrevias = (array) session('chat.selecciones', []);
        if (($soloDias || $dias > 0) && !empty($seleccionesPrevias)) {
            try {
                // Recalcular y mostrar la cotización con los nuevos días
                $items = $this->obtenerItemsSeleccionados($seleccionesPrevias);

                if ($items->isNotEmpty()) {
                    $diasCalculo = (int) $dias;
                    return $this->responderCotizacion($items, $diasCalculo, $seleccionesPrevias, true);
                } else {
                    // Si no hay items pero hay selecciones, puede ser que los IDs sean inválidos
                    // Limpiar selecciones inválidas y mostrar catálogo
                    session()->forget('chat.selecciones');
                    return $this->mostrarCatalogoJson(
                        'No se encontraron los servicios seleccionados. Aquí está el catálogo completo:',
                        $dias > 0 ? $dias : null,
                        []
                    );
                }
            } catch (\Exception $e) {
                // Si hay un error, mostrar catálogo con mensaje
                return $this->mostrarCatalogoJson(
                    'Ocurrió un error al calcular la cotización. Aquí está el catálogo completo:',
                    $dias > 0 ? $dias : null,
                    $seleccionesPrevias
                );
            }
        }

        // Revalidar intenciones detectadas contra el mensaje (evitar falsos positivos)
        if (!empty($intencionesDetectadas)) {
            $intencionesDetectadas = $this->validarIntencionesContraMensaje($intencionesDetectadas, $mensajeCorregido);
        }

        // Política de combinación:
        // - Si hay nuevas intenciones y el usuario NO indicó agregar, se reemplaza el contexto previo.
        // - Si indicó agregar, se fusiona con lo previo.
        // - Si no hay nuevas pero hay continuidad/días, se queda con lo previo (arriba).
        if (!empty($intencionesDetectadas)) {
            if ($esAgregado && !empty($sessionIntenciones)) {
                $intenciones = array_values(array_unique(array_merge($sessionIntenciones, $intencionesDetectadas)));
            } else {
                $intenciones = $intencionesDetectadas;
                // Si es nueva consulta (nuevas intenciones sin agregar), limpiar cotización previa
                if ($esNuevaConsulta) {
                    session()->forget('chat.selecciones');
                }
            }
            session(['chat.intenciones' => $intenciones]);
        } elseif ($esNuevaConsulta) {
            // Nueva búsqueda sin intención detectada también limpia la cotización
            session()->forget('chat.selecciones');
        }

        // Control de días a devolver: solo usar días de sesión si hay continuidad explícita
        $daysForResponse = null;
        if ($mensaje !== '') {
            $daysForResponse = $dias > 0 ? $dias : ($esContinuacion && $sessionDays > 0 ? $sessionDays : null);
        }
        // Si hay intención detectada y el mensaje es relacionado
        if (!empty($intenciones) && $this->esRelacionado($mensajeCorregido)) {
            $relSub = $this->obtenerSubServiciosPorIntenciones($intenciones);
            if ($relSub->isNotEmpty()) {
                $lista = implode(' y ', $intenciones);
                $provieneDeSugerencia = $request->boolean('sugerencia_aplicada', false);
                $esPeticionNueva = !$esContinuacion && !$esAgregado && !$esAnadirMas && !$soloDias;
                if ($provieneDeSugerencia || $esPeticionNueva) {
                    // Intento de obtener el token más relevante para una confirmación más clara
                    $hint = [];
                    try { $hint = $this->generarSugerenciasPorToken($mensaje)[0] ?? []; } catch (\Throwable $_) {}
                    $tok = isset($hint['token']) ? trim((string)$hint['token']) : null;
                    return response()->json([
                        'respuesta' => $tok ? "Por \"{$tok}\" ¿te refieres a {$lista}?" : "¿Te refieres a {$lista}?",
                        'actions' => [
                            ['id' => 'confirm_intent', 'label' => 'Sí, continuar', 'meta' => ['intenciones' => $intenciones, 'dias' => $dias > 0 ? $dias : null]],
                            ['id' => 'reject_intent', 'label' => 'No, mostrar catálogo']
                        ],
                        'days' => $daysForResponse,
                    ]);
                }
                $prefijo = $dias > 0 ? " para {$dias} día" . ($dias > 1 ? 's' : '') : '';
                $seleccionesActuales = (array) session('chat.selecciones', []);
                return $this->responderOpciones(
                    "Estas son nuestras opciones de {$lista}{$prefijo}. Selecciona los sub-servicios que deseas cotizar:",
                    $relSub,
                    $daysForResponse,
                    $seleccionesActuales
                );
            }
        }

        // Búsqueda semántica simple en DB, sin intención forzada
        $relSub = $this->ordenarSubServicios(
            $this->subServiciosQuery()
            ->where(function ($q) use ($mensajeCorregido, $tokens) {
                if ($mensajeCorregido !== '') {
                    $q->where('sub_servicios.nombre', 'like', "%{$mensajeCorregido}%")
                      ->orWhere('sub_servicios.descripcion', 'like', "%{$mensajeCorregido}%");
                }
                foreach ($tokens as $tk) {
                    $tk = trim($tk);
                    if ($tk !== '') {
                        $q->orWhere('sub_servicios.nombre', 'like', "%{$tk}%")
                          ->orWhere('sub_servicios.descripcion', 'like', "%{$tk}%");
                    }
                }
            })
            ->when(!empty($intenciones), function ($q) use ($intenciones) {
                $q->whereIn('servicios.nombre_servicio', $intenciones);
            })
        )->limit(12)->get();

        if ($relSub->isNotEmpty()) {
            $intro = $mensajeCorregido !== ''
                ? 'Con base en tu consulta, estas opciones están relacionadas. '
                : 'He encontrado opciones relacionadas.';
            $seleccionesActuales = (array) session('chat.selecciones', []);
            return $this->responderOpciones(
                $intro . 'Selecciona los sub-servicios que deseas cotizar:',
                $relSub,
                $daysForResponse,
                $seleccionesActuales
            );
        }

        return $this->responderFueraDeTema($mensaje, $mensajeCorregido);
        } catch (\Throwable $e) {
            Log::error('Error en ChatbotController@enviar: ' . $e->getMessage(), [ 'trace' => $e->getTraceAsString() ]);
            // Intento de recuperación: si hay intención detectable, pedir confirmación
            try {
                $mensajeFallback = (string) $request->input('mensaje', '');
                $ints = $this->detectarIntenciones($mensajeFallback);
                if (!empty($ints)) {
                    $hint = [];
                    try { $hint = $this->generarSugerenciasPorToken($mensajeFallback)[0] ?? []; } catch (\Throwable $_) {}
                    $tok = isset($hint['token']) ? trim((string)$hint['token']) : null;
                    return response()->json([
                        'respuesta' => $tok ? ("Por \"{$tok}\" ¿te refieres a " . implode(' y ', $ints) . "?") : ("¿Te refieres a " . implode(' y ', $ints) . "?"),
                        'actions' => [
                            ['id' => 'confirm_intent', 'label' => 'Sí, continuar', 'meta' => ['intenciones' => $ints, 'dias' => (int) session('chat.days', 0) ?: null]],
                            ['id' => 'reject_intent', 'label' => 'No, mostrar catálogo']
                        ],
                        'days' => (int) session('chat.days', 0) ?: null,
                    ]);
                }
            } catch (\Throwable $_) {}
            // Respuesta de contingencia en JSON para evitar romper el frontend
            return $this->mostrarCatalogoJson(
                'Estoy teniendo inconvenientes para procesar tu mensaje. Te muestro el catálogo para que puedas continuar:',
                (int) session('chat.days', 1) ?: null,
                (array) session('chat.selecciones', [])
            );
        }
    }

    private function limpiarSesionChat(): void
    {
        session()->forget('chat.selecciones');
        session()->forget('chat.intenciones');
        session()->forget('chat.days');
    }

    private function guardarCotizacion(int $personasId, array $selecciones, int $dias): void
    {
        if (empty($selecciones) || !$personasId) {
            return;
        }

        try {
            $items = SubServicios::whereIn('id', $selecciones)->get(['id', 'precio']);
            $fechaCotizacion = now();
            $diasValidos = max(1, $dias);

            foreach ($items as $item) {
                $monto = (float) $item->precio * $diasValidos;

                Cotizacion::create([
                    'personas_id' => $personasId,
                    'sub_servicios_id' => $item->id,
                    'monto' => $monto,
                    'fecha_cotizacion' => $fechaCotizacion,
                ]);
            }
        } catch (\Exception $e) {
            Log::error('Error al guardar cotización: ' . $e->getMessage());
        }
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
            $sugerencias = $this->generarSugerencias($mensajeCorregido);
            $tokenHints = $this->generarSugerenciasPorToken($mensajeOriginal);
        } catch (\Throwable $e) {
            $sugerencias = self::SUGERENCIAS_BASE;
            $tokenHints = $this->fallbackTokenHints($mensajeOriginal);
        }

        return [$sugerencias, $tokenHints, $this->extraerMejorSugerencia($tokenHints)];
    }

    private function responderCotizacion($items, int $diasCalculo, array $selecciones, bool $mostrarDiasSiempre = false)
    {
        [$detalle, $total] = $this->construirDetalleCotizacion($items, $diasCalculo, $mostrarDiasSiempre);

        return response()->json([
            'respuesta' => $detalle,
            'days' => $diasCalculo,
            'selecciones' => $selecciones,
            'total' => $total,
            'actions' => [
                ['id' => 'add_more', 'label' => 'Añadir más sub-servicios'],
                ['id' => 'clear', 'label' => 'Limpiar cotización'],
                ['id' => 'finish', 'label' => 'Terminar cotización'],
            ],
        ]);
    }

    private function construirDetalleCotizacion($items, int $diasCalculo, bool $mostrarDiasSiempre): array
    {
        $detalle = "<div style='line-height: 1.6;'>";
        $diasLabel = ($mostrarDiasSiempre || $diasCalculo > 1) ? " ({$diasCalculo} días)" : '';
        $detalle .= "<h3 style='margin-bottom: 12px; font-size: 1.1em;'>Resumen de tu cotización{$diasLabel}</h3>";

        $itemsPorServicio = [];
        foreach ($items as $it) {
            $servicioNombre = $it->servicio->nombre_servicio;
            $itemsPorServicio[$servicioNombre][] = $it;
        }

        $total = 0;
        foreach ($itemsPorServicio as $servicioNombre => $itemsServicio) {
            $subtotalServicio = 0;
            $detalle .= "<div style='margin-bottom: 16px;'>";
            $detalle .= "<strong style='font-size: 1.05em; color: #333;'>{$servicioNombre}</strong><br>";
            foreach ($itemsServicio as $it) {
                $subtotal = (float) $it->precio * $diasCalculo;
                $subtotalServicio += $subtotal;
                $total += $subtotal;
                $detalle .= "<span style='margin-left: 16px; display: block; margin-top: 4px;'>";
                if ($diasCalculo > 1 || $mostrarDiasSiempre) {
                    $detalle .= "{$it->nombre} — $" . number_format($it->precio, 0, ',', '.') . " × {$diasCalculo} = <strong>$" . number_format($subtotal, 0, ',', '.') . "</strong></span>";
                } else {
                    $detalle .= "{$it->nombre} — <strong>$" . number_format($subtotal, 0, ',', '.') . "</strong></span>";
                }
            }
            $detalle .= "<div style='margin-left: 16px; margin-top: 6px; padding-top: 6px; border-top: 1px solid #ddd;'>";
            $detalle .= "Subtotal {$servicioNombre}: <strong style='color: #2563eb;'>$" . number_format($subtotalServicio, 0, ',', '.') . "</strong></div>";
            $detalle .= "</div>";
        }

        $detalle .= "<div style='margin-top: 16px; padding-top: 12px; border-top: 2px solid #333; font-size: 1.1em;'>";
        $detalle .= "<strong style='color: #059669; font-size: 1.15em;'>Total estimado: $" . number_format($total, 0, ',', '.') . "</strong>";
        $detalle .= "</div></div>";

        return [$detalle, $total];
    }

    private function obtenerSubServiciosPorIntenciones(array $intenciones)
    {
        if (empty($intenciones)) {
            return collect();
        }

        return $this->ordenarSubServicios(
            $this->subServiciosQuery()->whereIn('servicios.nombre_servicio', $intenciones)
        )->get();
    }

    private function obtenerItemsSeleccionados(array $ids)
    {
        if (empty($ids)) {
            return collect();
        }

        return SubServicios::query()
            ->whereIn('id', $ids)
            ->with('servicio')
            ->get(['id', 'servicios_id', 'nombre', 'precio']);
    }

    private function validarIntencionesContraMensaje(array $intenciones, string $mensajeCorregido): array
    {
        if (empty($intenciones)) return [];
        $explicitas = [
            'Alquiler' => ['alquiler','alquilar','rentar','arrendar','equipo','sonido','audio','parlante','altavoz','bafle','bocina','consola','mezcladora','mixer','microfono','luces','iluminacion','par led','rack'],
            'Animación' => ['animacion','animación','animador','dj','maestro de ceremonias','presentador','coordinador','fiesta','evento','cumpleanos','cumpleaños'],
            'Publicidad' => ['publicidad','publicitar','anuncio','spot','cuña','locucion','locución','jingle','radio']
        ];
        $texto = $this->normalizarTexto($mensajeCorregido);
        $validadas = [];
        foreach ($intenciones as $svc) {
            $ok = false;
            foreach ($explicitas[$svc] as $kw) {
                $kwNorm = $this->normalizarTexto($kw);
                if (preg_match('/\b' . preg_quote($kwNorm, '/') . '\b/u', $texto)) { $ok = true; break; }
            }
            if ($ok) { $validadas[] = $svc; }
        }
        return $validadas;
    }

    private function clasificarPorTfidf(string $mensajeCorregido): array
    {
        $texto = trim($this->normalizarTexto($mensajeCorregido));
        if ($texto === '') { return []; }
        static $cache = null;
        if ($cache === null) {
            $cache = [ 'docs' => [], 'df' => [], 'N' => 0 ];
            try {
                $rows = SubServicios::query()
                    ->select('sub_servicios.nombre', 'sub_servicios.descripcion', 'servicios.nombre_servicio')
                    ->join('servicios', 'servicios.id', '=', 'sub_servicios.servicios_id')
                    ->get();
                $docsBySvc = [];
                foreach ($rows as $r) {
                    $svc = $r->nombre_servicio;
                    $content = trim(($r->nombre ?? '') . ' ' . ($r->descripcion ?? ''));
                    $docsBySvc[$svc] = ($docsBySvc[$svc] ?? '') . ' ' . $content;
                }
                $stop = self::STOPWORDS;
                $df = [];
                $N = 0;
                foreach ($docsBySvc as $svc => $doc) {
                    $N++;
                    $tokens = array_values(array_filter(preg_split('/[^a-z0-9áéíóúñ]+/u', $this->normalizarTexto($doc)), function($t) use ($stop){
                        $t = trim($t);
                        return $t !== '' && mb_strlen($t) >= 3 && !in_array($t, $stop, true);
                    }));
                    $tf = [];
                    foreach ($tokens as $t) { $tf[$t] = ($tf[$t] ?? 0) + 1; }
                    $cache['docs'][$svc] = $tf;
                    foreach (array_keys($tf) as $term) { $df[$term] = ($df[$term] ?? 0) + 1; }
                }
                $cache['df'] = $df; $cache['N'] = max(1, $N);
            } catch (\Exception $e) {
                return [];
            }
        }
        $stop = self::STOPWORDS;
        $qTokens = array_values(array_filter(preg_split('/[^a-z0-9áéíóúñ]+/u', $texto), function($t) use ($stop){
            $t = trim($t);
            return $t !== '' && mb_strlen($t) >= 3 && !in_array($t, $stop, true);
        }));
        if (empty($qTokens)) { return []; }
        $qtf = [];
        foreach ($qTokens as $t) { $qtf[$t] = ($qtf[$t] ?? 0) + 1; }
        $scores = [];
        foreach ($cache['docs'] as $svc => $tf) {
            $num = 0.0; $normQ = 0.0; $normD = 0.0;
            foreach ($qtf as $term => $fq) {
                $df = $cache['df'][$term] ?? 0;
                if ($df <= 0) { continue; }
                $idf = log(($cache['N'] + 1) / ($df + 0.5));
                $wq = $fq * $idf;
                $wd = ($tf[$term] ?? 0) * $idf;
                $num += $wq * $wd;
                $normQ += $wq * $wq;
            }
            foreach ($tf as $term => $fd) {
                $df = $cache['df'][$term] ?? 0;
                if ($df <= 0) { continue; }
                $idf = log(($cache['N'] + 1) / ($df + 0.5));
                $wd = $fd * $idf;
                $normD += $wd * $wd;
            }
            $den = (sqrt(max(1e-8, $normQ)) * sqrt(max(1e-8, $normD)));
            $scores[$svc] = $den > 0 ? ($num / $den) : 0.0;
        }
        arsort($scores);
        $top = array_key_first($scores);
        if ($top !== null && $scores[$top] >= 0.12) {
            return [$top];
        }
        return [];
    }

    private function responderConOpciones()
    {
        return $this->mostrarCatalogoJson(
            '¡Hola! Soy tu asistente de cotizaciones. Selecciona los sub-servicios que deseas agregar a tu cotización:',
            null,
            (array) session('chat.selecciones', [])
        );
    }

    private function mostrarCatalogoJson(string $mensaje, ?int $dias = null, array $seleccionesPrevias = [])
    {
        $items = $this->ordenarSubServicios($this->subServiciosQuery())->get();

        return $this->responderOpciones($mensaje, $items, $dias, $seleccionesPrevias);
    }

    private function responderOpciones(string $mensaje, $items, ?int $dias = null, array $seleccionesPrevias = [])
    {
        return response()->json([
            'respuesta' => $mensaje,
            'optionGroups' => $this->formatearOpciones($items),
            'days' => $dias,
            'seleccionesPrevias' => $seleccionesPrevias,
        ]);
    }

    private function subServiciosQuery()
    {
        return SubServicios::query()
            ->select('sub_servicios.id', 'sub_servicios.nombre', 'sub_servicios.precio', 'servicios.nombre_servicio')
            ->join('servicios', 'servicios.id', '=', 'sub_servicios.servicios_id');
    }

    private function ordenarSubServicios($query)
    {
        return $query
            ->orderBy('servicios.nombre_servicio')
            ->orderBy('sub_servicios.nombre');
    }

    private function formatearOpciones($items): array
    {
        return collect($items)
            ->groupBy(function ($item) {
                return is_array($item) ? $item['servicio'] : $item->nombre_servicio;
            })
            ->sortKeys()
            ->map(function ($grupo, $servicio) {
                return [
                    'servicio' => $servicio,
                    'items' => collect($grupo)
                        ->map(function ($item) {
                            return [
                                'id' => is_array($item) ? $item['id'] : $item->id,
                                'nombre' => is_array($item) ? $item['nombre'] : $item->nombre,
                                'precio' => (float) (is_array($item) ? $item['precio'] : $item->precio),
                            ];
                        })
                        ->sortBy('nombre')
                        ->values()
                        ->all(),
                ];
            })
            ->values()
            ->all();
    }

    private function extraerDiasDesdePalabras(string $mensaje): ?int
    {
        $texto = $this->normalizarTexto($mensaje);
        $mapa = [
            'uno' => 1, 'una' => 1, 'un' => 1,
            'dos' => 2,
            'tres' => 3,
            'cuatro' => 4,
            'cinco' => 5,
            'seis' => 6,
            'siete' => 7,
            'ocho' => 8,
            'nueve' => 9,
            'diez' => 10,
        ];
        foreach ($mapa as $pal => $num) {
            if (preg_match('/\b' . preg_quote($pal, '/') . '\b\s*dias?/i', $texto)) {
                return $num;
            }
        }
        return null;
    }

    private function detectarIntenciones(string $mensaje): array
    {
        if ($mensaje === '') {
            return [];
        }
        $texto = $this->corregirOrtografia($mensaje);

        // Sinónimos por servicio
        $mapa = [
            'Alquiler' => [
                'alquiler','alquilar','arrendar','rentar','equipo','equipo de sonido','sonido','audio','bafle','parlante','altavoz','bocina','consola','mezcladora','mixer','microfono','microfono','luces','luz','lampara','lámpara','iluminacion','iluminación','rack','par led'
            ],
            'Animación' => [
                'animacion','animación','animador','dj','maestro de ceremonias','presentador','coordinador','cumpleanos','cumpleaños','fiesta','evento'
            ],
            'Publicidad' => [
                'publicidad','publicitar','publicitarlos','publicitarlas','publicitarlo','publicitarla','anuncio','spot','cuña','cuna','jingle','locucion','locución','radio'
            ],
        ];

        $puntajes = [ 'Alquiler' => 0, 'Animación' => 0, 'Publicidad' => 0 ];
        foreach ($mapa as $servicio => $keywords) {
            foreach ($keywords as $kw) {
                $kwNorm = $this->normalizarTexto($kw);
                // Buscar coincidencia exacta o como raíz (palabra que empiece con la clave)
                if (str_contains($texto, $kwNorm) || preg_match('/\b' . preg_quote($kwNorm, '/') . '/i', $texto)) {
                    $puntajes[$servicio]++;
                }
            }
        }

        // Aplicar umbral: aceptar si hay mención explícita del servicio o si el puntaje >= 2
        $explicitas = [
            'Alquiler' => ['alquiler','alquilar','rentar','arrendar'],
            'Animación' => ['animacion','animación','animador','dj'],
            'Publicidad' => ['publicidad','publicitar','anuncio','spot','cuña','locucion','locución']
        ];

        // Palabras fuertes (sinónimos de alto peso) por servicio: 1 coincidencia basta
        $fuertes = [
            'Alquiler' => ['parlante','bafle','altavoz','bocina','microfono','consola','mezcladora','mixer','luces','lampara','par led','rack','equipo','audio','sonido'],
            'Animación' => ['dj','animador','presentador','maestro de ceremonias'],
            'Publicidad' => ['anuncio','spot','cuña','locucion','jingle','radio']
        ];

        $result = [];
        foreach ($puntajes as $servicio => $score) {
            $aceptar = $score >= 2;
            if (!$aceptar) {
                foreach ($explicitas[$servicio] as $kw) {
                    if (str_contains($texto, $this->normalizarTexto($kw))) { $aceptar = true; break; }
                }
            }
            if (!$aceptar) {
                foreach (($fuertes[$servicio] ?? []) as $kw) {
                    $kwNorm = $this->normalizarTexto($kw);
                    // Coincidencia por prefijo para cubrir plurales y variaciones: parlante, parlantes, parlantico
                    if (preg_match('/\b' . preg_quote($kwNorm, '/') . '\w*/u', $texto)) { $aceptar = true; break; }
                }
            }
            if ($aceptar) { $result[] = $servicio; }
        }
        // Ordenar por puntaje desc para consistencia
        arsort($puntajes);
        usort($result, function($a,$b) use ($puntajes){ return ($puntajes[$b] <=> $puntajes[$a]); });
        return $result;
    }

    private function esRelacionado(string $mensajeCorregido): bool
    {
        if ($mensajeCorregido === '') return true;
        $texto = $this->normalizarTexto($mensajeCorregido);
        $explicitas = [
            'alquiler','alquilar','arrendar','rentar','equipo','equipo de sonido','sonido','audio','bafle','parlante','altavoz','bocina','consola','mezcladora','mixer','microfono','luces','luz','lampara','iluminacion','rack','par led',
            'animacion','animación','animador','dj','maestro de ceremonias','presentador','coordinador','fiesta','evento','cumpleanos','cumpleaños',
            'publicidad','publicitar','anuncio','spot','cuña','jingle','locucion','locución','radio'
        ];
        foreach ($explicitas as $kw) {
            $kwNorm = $this->normalizarTexto($kw);
            if (preg_match('/\b' . preg_quote($kwNorm, '/') . '\b/u', $texto)) {
                return true;
            }
        }
        return false;
    }

    private function corregirOrtografia(string $texto): string
    {
        $correcciones = [
            // Errores comunes de ortografía
            'nesecot' => 'necesito',
            'nesecito' => 'necesito',
            'nesesito' => 'necesito',
            'nesito' => 'necesito',
            'necesot' => 'necesito',
            'alquilarr' => 'alquilar',
            'alquiles' => 'alquiler',
            'alqiler' => 'alquiler',
            'alqilar' => 'alquilar',
            'arendar' => 'arrendar',
            'rremtar' => 'rentar',
            'publicitarlos' => 'publicitar',
            'publicitarlas' => 'publicitar',
            'publicitarlo' => 'publicitar',
            'publicitarla' => 'publicitar',
            'publicida' => 'publicidad',
            'publisidad' => 'publicidad',
            'locucion' => 'locución',
            'locuion' => 'locución',
            'anunsio' => 'anuncio',
            'cuna' => 'cuña',
            'cunya' => 'cuña',
            'iluinacion' => 'iluminacion',
            'iluminasion' => 'iluminacion',
            'luces' => 'luces',
            'luz' => 'luces',
            'dj' => 'dj',
            'deejay' => 'dj',
            'mescladora' => 'mezcladora',
            'microphono' => 'microfono',
            'microphono' => 'microfono',
            'microfno' => 'microfono',
            'parled' => 'par led',
        ];
        
        $textoNormalizado = $this->normalizarTexto($texto);
        foreach ($correcciones as $error => $correcto) {
            $errorNorm = $this->normalizarTexto($error);
            // Reemplazar palabra completa o como subcadena al inicio
            $textoNormalizado = preg_replace('/\b' . preg_quote($errorNorm, '/') . '\b/i', $this->normalizarTexto($correcto), $textoNormalizado);
        }
        
        // Corrección difusa (fuzzy) basada en vocabulario dinámico de servicios y sub_servicios
        $vocabulario = $this->obtenerVocabularioCorreccion();
        if (!empty($vocabulario)) {
            $palabras = preg_split('/\s+/', $textoNormalizado);
            $corregidas = [];
            foreach ($palabras as $palabra) {
                $p = trim($palabra);
                if ($p === '' || mb_strlen($p) < 3) { $corregidas[] = $p; continue; }
                $mejor = $this->buscarCorreccionCercana($p, $vocabulario);
                $corregidas[] = $mejor ?? $p;
            }
            $textoNormalizado = trim(implode(' ', array_filter($corregidas, function($w){ return $w !== null && $w !== ''; })));
        }
        
        return $textoNormalizado;
    }

    private function obtenerVocabularioCorreccion(): array
    {
        // Palabras clave desde el mapa de intenciones (evitar términos ambiguos como 'par')
        $keywords = [
            'alquiler','alquilar','arrendar','rentar','equipo','equipo de sonido','sonido','audio','bafle','parlante','parlantes','altavoz','bocina','consola','mezcladora','mixer','microfono','luces','luz','lampara','iluminacion','rack','par led',
            'animacion','animador','dj','maestro','ceremonias','presentador','coordinador','cumpleanos','fiesta','evento',
            'publicidad','publicitar','anuncio','spot','cuña','jingle','locucion','radio'
        ];
        $vocab = [];
        foreach ($keywords as $k) { $vocab[$this->normalizarTexto($k)] = true; }
        
        // Términos desde sub_servicios (nombres y descripciones)
        try {
            $subServicios = SubServicios::query()->select('nombre','descripcion')->limit(500)->get();
            foreach ($subServicios as $ss) {
                $tokens = preg_split('/[^a-zA-Z0-9áéíóúñ]+/u', ($ss->nombre . ' ' . ($ss->descripcion ?? '')));
                foreach ($tokens as $tk) {
                    $tk = trim($tk);
                    if ($tk === '') { continue; }
                    $norm = $this->normalizarTexto($tk);
                    // Filtrar stopwords y términos demasiado cortos (excepto 'dj')
                    if (($norm === 'dj' || mb_strlen($norm) >= 4) && mb_strlen($norm) <= 30 && !in_array($norm, self::STOPWORDS_EXT, true)) {
                        $vocab[$norm] = true;
                    }
                }
            }
        } catch (\Exception $e) {
            // Si falla la DB, seguimos solo con palabras base
        }
        
        return array_keys($vocab);
    }

    private function buscarCorreccionCercana(string $palabra, array $vocabulario): ?string
    {
        $best = null;
        $bestDist = PHP_INT_MAX;
        $bestSim = 0.0;
        $len = mb_strlen($palabra);
        $umbral = max(1, (int) floor($len / 4)); // tolerancia más estricta
        foreach ($vocabulario as $term) {
            // Solo comparar longitudes razonablemente cercanas
            $diff = abs(mb_strlen($term) - $len);
            if ($diff > $umbral + 1) { continue; }
            // Requerir misma letra inicial para evitar saltos semánticos
            if (mb_substr($palabra, 0, 1) !== mb_substr($term, 0, 1)) { continue; }
            $percent = 0.0;
            similar_text($palabra, $term, $percent);
            $d = function_exists('levenshtein') ? levenshtein($palabra, $term) : (int) round((100 - $percent) * $len / 100);
            if ($d < $bestDist || ($d === $bestDist && $percent > $bestSim)) {
                $bestDist = $d;
                $bestSim = $percent;
                $best = $term;
                if ($bestDist === 0) { break; }
            }
        }
        // Aceptar solo si hay buena distancia y similitud alta
        return ($best !== null && $bestDist <= $umbral && $bestSim >= 85.0) ? $best : null;
    }

    private function generarSugerencias(string $mensajeCorregido): array
    {
        $vocab = $this->obtenerVocabularioCorreccion();
        if (empty($vocab)) { return []; }
        $stop = self::STOPWORDS;
        $tokens = array_values(array_filter(preg_split('/\s+/', $this->normalizarTexto($mensajeCorregido)), function($t) use ($stop){
            $t = trim($t);
            return $t !== '' && mb_strlen($t) >= 3 && !in_array($t, $stop, true);
        }));
        if (empty($tokens)) { $tokens = [$this->normalizarTexto($mensajeCorregido)]; }
        $scores = [];
        $stop = self::STOPWORDS_EXT;
        foreach ($tokens as $t) {
            foreach ($vocab as $term) {
                if (in_array($term, $stop, true) || mb_strlen($term) < 3) { continue; }
                $percent = 0.0; similar_text($t, $term, $percent);
                // penalizar si no comparten inicial para reducir falsos positivos
                if (mb_substr($t,0,1) !== mb_substr($term,0,1)) { $percent *= 0.85; }
                if ($percent <= 0) continue;
                $scores[$term] = max($scores[$term] ?? 0.0, $percent);
            }
        }
        arsort($scores);
        $lista = array_slice(array_keys($scores), 0, 5);
        if (empty($lista)) {
            $lista = self::SUGERENCIAS_BASE;
        }
        return $lista;
    }

    private function generarSugerenciasPorToken(string $mensajeOriginal): array
    {
        $vocab = $this->obtenerVocabularioCorreccion();
        $stop = self::STOPWORDS;
        $rawTokens = preg_split('/\s+/', trim($mensajeOriginal));
        $pairs = [];
        $genericos = self::TOKENS_GENERICOS;
        foreach ($rawTokens as $rt) {
            $norm = $this->normalizarTexto($rt);
            if ($norm === '' || mb_strlen($norm) < 3) { continue; }
            if (in_array($norm, $stop, true)) { continue; }
            if (in_array($norm, $genericos, true)) { continue; }
            if (preg_match('/^\d+$/', $norm)) { continue; }
            $pairs[] = [ 'orig' => $rt, 'norm' => $norm ];
        }
        if (empty($pairs)) { return []; }

        // Elegir el token "más raro": el de menor similitud máxima contra vocab
        $tokenScores = [];
        foreach ($pairs as $p) {
            $t = $p['norm'];
            $maxSim = 0.0;
            foreach ($vocab as $term) {
                $percent = 0.0; similar_text($t, $term, $percent);
                if ($percent > $maxSim) { $maxSim = $percent; }
            }
            $tokenScores[$p['orig']] = $maxSim;
        }
        asort($tokenScores); // menor similitud primero
        $targetToken = array_key_first($tokenScores);
        if ($targetToken === null) { return []; }

        // Sugerencias para el token objetivo
        $candidatos = [];
        $targetNorm = $this->normalizarTexto($targetToken);
        foreach ($vocab as $term) {
            $percent = 0.0; similar_text($targetNorm, $term, $percent);
            if ($percent > 0) { $candidatos[$term] = $percent; }
        }
        arsort($candidatos);
        $sugs = array_slice(array_keys($candidatos), 0, 6);
        return [[ 'token' => $targetToken, 'sugerencias' => $sugs ]];
    }

    private function fallbackTokenHints(string $mensajeCorregido): array
    {
        $tokens = array_values(array_filter(preg_split('/\s+/', $this->normalizarTexto($mensajeCorregido)), function($t){
            return trim($t) !== '' && mb_strlen(trim($t)) >= 3;
        }));
        if (empty($tokens)) { return []; }
        return [[ 'token' => $tokens[0], 'sugerencias' => self::SUGERENCIAS_BASE ]];
    }

    private function extraerMejorSugerencia(array $tokenHints): array
    {
        if (empty($tokenHints) || !isset($tokenHints[0]['token'])) { return []; }
        $token = $tokenHints[0]['token'];
        $sugs = (array) ($tokenHints[0]['sugerencias'] ?? []);
        $best = $sugs[0] ?? null;
        return $best ? ['token' => $token, 'sugerencia' => $best] : [];
    }

    private function normalizarTexto(string $t): string
    {
        $t = mb_strtolower($t);
        $reemplazos = [
            'á' => 'a', 'é' => 'e', 'í' => 'i', 'ó' => 'o', 'ú' => 'u', 'ñ' => 'n',
        ];
        return strtr($t, $reemplazos);
    }

    private function esContinuacion(string $mensaje): bool
    {
        if ($mensaje === '') {
            return false;
        }
        $t = $this->normalizarTexto($mensaje);
        $cues = [
            'tambien', 'también', 'ademas', 'además', 'eso', 'esa', 'ese', 'esos', 'esas',
            'lo mismo', 'igual', 'continuar', 'sigue', 'seguimos', 'por esos dias', 'mismos dias', 'mismos días'
        ];
        foreach ($cues as $c) {
            if (str_contains($t, $this->normalizarTexto($c))) {
                return true;
            }
        }
        return false;
    }
}
