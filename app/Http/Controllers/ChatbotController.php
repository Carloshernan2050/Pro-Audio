<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use App\Models\Servicios;
use App\Models\SubServicios;

class ChatbotController extends Controller
{
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
        // Manejar limpieza de cotización
        if ($request->has('limpiar_cotizacion') && $request->input('limpiar_cotizacion') === true) {
            session()->forget('chat.selecciones');
            session()->forget('chat.intenciones');
            session()->forget('chat.days');
            return response()->json([
                'respuesta' => 'Cotización limpiada. Puedes empezar una nueva selección.',
                'selecciones' => [],
                'total' => 0,
            ]);
        }

        // Manejar finalización de cotización
        if ($request->has('terminar_cotizacion') && $request->input('terminar_cotizacion') === true) {
            // Limpiar toda la sesión de chat
            session()->forget('chat.selecciones');
            session()->forget('chat.intenciones');
            session()->forget('chat.days');
            return response()->json([
                'respuesta' => 'Gracias por tu interés. Contacta con un trabajador mediante este correo <strong>ejemplo@gmail.com</strong>.',
                'limpiar_chat' => true,
                'selecciones' => [],
                'total' => 0,
            ]);
        }

        $mensaje = trim((string) $request->input('mensaje'));
        $seleccion = $request->input('seleccion'); // array de IDs de sub_servicios
        // Corrección de ortografía para mejorar la búsqueda
        $mensajeCorregido = $mensaje !== '' ? $this->corregirOrtografia($mensaje) : '';
        // Continuidad por sesión
        $sessionDays = (int) session('chat.days', 0);
        $sessionIntenciones = (array) session('chat.intenciones', []);
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
            // Obtener selecciones previas de la sesión
            $seleccionesPrevias = (array) session('chat.selecciones', []);
            
            // Combinar nuevas selecciones con previas, eliminando duplicados
            $todasLasSelecciones = array_values(array_unique(array_merge($seleccionesPrevias, $seleccion)));
            
            // Obtener todos los items seleccionados (previos + nuevos)
            $items = SubServicios::query()
                ->whereIn('id', $todasLasSelecciones)
                ->with('servicio')
                ->get(['id', 'servicios_id', 'nombre', 'precio']);

            if ($items->isEmpty()) {
                return response()->json([
                    'respuesta' => 'No se encontraron sub-servicios para tu selección. Intenta de nuevo.',
                ]);
            }

            // Guardar selecciones acumuladas en sesión
            session(['chat.selecciones' => $todasLasSelecciones]);

            $diasCalculo = $dias > 0 ? $dias : 1;
            $total = 0;
            $detalle = "<div style='line-height: 1.6;'>";
            $detalle .= "<h3 style='margin-bottom: 12px; font-size: 1.1em;'>Resumen de tu cotización" . ($diasCalculo > 1 ? " ({$diasCalculo} días)" : "") . "</h3>";
            
            // Agrupar por servicio para mejor visualización
            $itemsPorServicio = [];
            foreach ($items as $it) {
                $servicioNombre = $it->servicio->nombre_servicio;
                if (!isset($itemsPorServicio[$servicioNombre])) {
                    $itemsPorServicio[$servicioNombre] = [];
                }
                $itemsPorServicio[$servicioNombre][] = $it;
            }
            
            // Mostrar items agrupados por servicio con subtotales
            foreach ($itemsPorServicio as $servicioNombre => $itemsServicio) {
                $subtotalServicio = 0;
                $detalle .= "<div style='margin-bottom: 16px;'>";
                $detalle .= "<strong style='font-size: 1.05em; color: #333;'>{$servicioNombre}</strong><br>";
                foreach ($itemsServicio as $it) {
                    $subtotal = (float) $it->precio * $diasCalculo;
                    $subtotalServicio += $subtotal;
                    $total += $subtotal;
                    if ($diasCalculo > 1) {
                        $detalle .= "<span style='margin-left: 16px; display: block; margin-top: 4px;'>";
                        $detalle .= "{$it->nombre} — $" . number_format($it->precio, 0, ',', '.') . " × {$diasCalculo} = <strong>$" . number_format($subtotal, 0, ',', '.') . "</strong></span>";
                    } else {
                        $detalle .= "<span style='margin-left: 16px; display: block; margin-top: 4px;'>";
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

            return response()->json([
                'respuesta' => $detalle, // No escapar HTML para que los estilos funcionen
                'days' => $diasCalculo,
                'selecciones' => $todasLasSelecciones,
                'total' => $total,
                'actions' => [
                    ['id' => 'add_more', 'label' => 'Añadir más sub-servicios'],
                    ['id' => 'clear', 'label' => 'Limpiar cotización'],
                    ['id' => 'finish', 'label' => 'Terminar cotización'],
                ],
            ]);
        }

        // Si no hay mensaje, invitamos a cotizar directamente
        if ($mensaje === '') {
            return $this->responderConOpciones();
        }

        // Si el mensaje es "catalogo", devolver catálogo completo directamente
        $mensajeLower = strtolower(trim($mensaje));
        $mensajeCorregidoLower = strtolower(trim($mensajeCorregido));
        if ($mensajeLower === 'catalogo' || $mensajeLower === 'catálogo' || 
            $mensajeCorregidoLower === 'catalogo' || $mensajeCorregidoLower === 'catálogo') {
            $seleccionesActuales = (array) session('chat.selecciones', []);
            $sessionDays = (int) session('chat.days', 1);
            return response()->json([
                'respuesta' => 'Catálogo completo. Selecciona los sub-servicios que deseas agregar a tu cotización:',
                'optionGroups' => $this->catalogoOpcionesAgrupado(),
                'days' => $sessionDays > 0 ? $sessionDays : null,
                'seleccionesPrevias' => $seleccionesActuales,
            ]);
        }

        // Detección de intención basada en sinónimos y palabras clave (soporta múltiples)
        $intencionesDetectadas = $this->detectarIntenciones($mensaje); // p.ej.: ['Alquiler', 'Animación']
        $tokens = array_values(array_filter(preg_split('/\s+/', $mensajeCorregido), function ($t) {
            return mb_strlen(trim($t)) >= 3;
        }));
        // Determinar si el usuario está agregando (en lugar de reemplazar) intención
        $textoNorm = $mensajeCorregido;
        $cuesAgregar = ['tambien','también','ademas','además','y ',' y','sumar','agrega','agregar','junto','ademas de','además de'];
        $esAgregado = false;
        foreach ($cuesAgregar as $cue) {
            if (str_contains($textoNorm, $this->normalizarTexto($cue))) { $esAgregado = true; break; }
        }

        // Reutilizar intención previa si no se detectó nada y hay continuidad o solo definición de días
        if (empty($intencionesDetectadas) && !empty($sessionIntenciones)) {
            if ($esContinuacion || $dias > 0) {
                $intencionesDetectadas = $sessionIntenciones;
            }
        }

        // Verificar si es acción de "añadir más" (mensaje "catalogo")
        // Verificar tanto en mensaje original como corregido
        $mensajeLower = strtolower(trim($mensaje));
        $mensajeCorregidoLower = strtolower(trim($mensajeCorregido));
        $esAnadirMas = ($mensajeLower === 'catalogo' || $mensajeLower === 'catálogo' || 
                       $mensajeCorregidoLower === 'catalogo' || $mensajeCorregidoLower === 'catálogo');
        
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
                $items = SubServicios::query()
                    ->whereIn('id', $seleccionesPrevias)
                    ->with('servicio')
                    ->get(['id', 'servicios_id', 'nombre', 'precio']);

                if ($items->isNotEmpty()) {
                    $diasCalculo = $dias;
                    $total = 0;
                    $detalle = "<div style='line-height: 1.6;'>";
                    $detalle .= "<h3 style='margin-bottom: 12px; font-size: 1.1em;'>Resumen de tu cotización ({$diasCalculo} días)</h3>";
                    
                    $itemsPorServicio = [];
                    foreach ($items as $it) {
                        $servicioNombre = $it->servicio->nombre_servicio;
                        if (!isset($itemsPorServicio[$servicioNombre])) {
                            $itemsPorServicio[$servicioNombre] = [];
                        }
                        $itemsPorServicio[$servicioNombre][] = $it;
                    }
                    
                    foreach ($itemsPorServicio as $servicioNombre => $itemsServicio) {
                        $subtotalServicio = 0;
                        $detalle .= "<div style='margin-bottom: 16px;'>";
                        $detalle .= "<strong style='font-size: 1.05em; color: #333;'>{$servicioNombre}</strong><br>";
                        foreach ($itemsServicio as $it) {
                            $subtotal = (float) $it->precio * $diasCalculo;
                            $subtotalServicio += $subtotal;
                            $total += $subtotal;
                            $detalle .= "<span style='margin-left: 16px; display: block; margin-top: 4px;'>";
                            $detalle .= "{$it->nombre} — $" . number_format($it->precio, 0, ',', '.') . " × {$diasCalculo} = <strong>$" . number_format($subtotal, 0, ',', '.') . "</strong></span>";
                        }
                        $detalle .= "<div style='margin-left: 16px; margin-top: 6px; padding-top: 6px; border-top: 1px solid #ddd;'>";
                        $detalle .= "Subtotal {$servicioNombre}: <strong style='color: #2563eb;'>$" . number_format($subtotalServicio, 0, ',', '.') . "</strong></div>";
                        $detalle .= "</div>";
                    }
                    
                    $detalle .= "<div style='margin-top: 16px; padding-top: 12px; border-top: 2px solid #333; font-size: 1.1em;'>";
                    $detalle .= "<strong style='color: #059669; font-size: 1.15em;'>Total estimado: $" . number_format($total, 0, ',', '.') . "</strong>";
                    $detalle .= "</div></div>";

                    return response()->json([
                        'respuesta' => $detalle,
                        'days' => $diasCalculo,
                        'selecciones' => $seleccionesPrevias,
                        'total' => $total,
                        'actions' => [
                            ['id' => 'add_more', 'label' => 'Añadir más sub-servicios'],
                            ['id' => 'clear', 'label' => 'Limpiar cotización'],
                            ['id' => 'finish', 'label' => 'Terminar cotización'],
                        ],
                    ]);
                } else {
                    // Si no hay items pero hay selecciones, puede ser que los IDs sean inválidos
                    // Limpiar selecciones inválidas y mostrar catálogo
                    session()->forget('chat.selecciones');
                    $seleccionesActuales = [];
                    return response()->json([
                        'respuesta' => 'No se encontraron los servicios seleccionados. Aquí está el catálogo completo:',
                        'optionGroups' => $this->catalogoOpcionesAgrupado(),
                        'days' => $dias > 0 ? $dias : null,
                        'seleccionesPrevias' => $seleccionesActuales,
                    ]);
                }
            } catch (\Exception $e) {
                // Si hay un error, mostrar catálogo con mensaje
                return response()->json([
                    'respuesta' => 'Ocurrió un error al calcular la cotización. Aquí está el catálogo completo:',
                    'optionGroups' => $this->catalogoOpcionesAgrupado(),
                    'days' => $dias > 0 ? $dias : null,
                    'seleccionesPrevias' => $seleccionesPrevias,
                ]);
            }
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
        $forzarAlquiler = in_array('Alquiler', $intenciones, true);

        // Si la intención es alquiler, devolvemos sub-servicios de Alquiler incluso sin coincidencias de texto
        if (!empty($intenciones)) {
            $relSub = SubServicios::query()
                ->select('sub_servicios.id', 'sub_servicios.nombre', 'sub_servicios.precio', 'servicios.nombre_servicio')
                ->join('servicios', 'servicios.id', '=', 'sub_servicios.servicios_id')
                ->whereIn('servicios.nombre_servicio', $intenciones)
                ->orderBy('servicios.nombre_servicio')
                ->orderBy('sub_servicios.nombre')
                ->get();

            if ($relSub->isNotEmpty()) {
                $prefijo = $dias > 0 ? " para {$dias} día" . ($dias > 1 ? 's' : '') : '';
                $lista = implode(' y ', $intenciones);
                $seleccionesActuales = (array) session('chat.selecciones', []);
                return response()->json([
                    'respuesta' => "Estas son nuestras opciones de {$lista}{$prefijo}. Selecciona los sub-servicios que deseas cotizar:",
                    'optionGroups' => $this->agruparOpciones($relSub),
                    'days' => $daysForResponse,
                    'seleccionesPrevias' => $seleccionesActuales,
                ]);
            }
        }

        // Búsqueda semántica simple en DB, sin intención forzada
        $relSub = SubServicios::query()
            ->select('sub_servicios.id', 'sub_servicios.nombre', 'sub_servicios.precio', 'servicios.nombre_servicio')
            ->join('servicios', 'servicios.id', '=', 'sub_servicios.servicios_id')
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
            ->orderBy('servicios.nombre_servicio')
            ->orderBy('sub_servicios.nombre')
            ->limit(12)
            ->get();

        if ($relSub->isNotEmpty()) {
            $intro = $mensajeCorregido !== ''
                ? 'Con base en tu consulta, estas opciones están relacionadas. '
                : 'He encontrado opciones relacionadas.';
            $seleccionesActuales = (array) session('chat.selecciones', []);
            return response()->json([
                'respuesta' => $intro . 'Selecciona los sub-servicios que deseas cotizar:',
                'optionGroups' => $this->agruparOpciones($relSub),
                'days' => $daysForResponse,
                'seleccionesPrevias' => $seleccionesActuales,
            ]);
        }

        // Si no encontramos relación, mensaje fuera de tema + catálogo
        $seleccionesActuales = (array) session('chat.selecciones', []);
        return response()->json([
            'respuesta' => 'Tu mensaje no está relacionado con nuestros servicios. ¿Deseas ver el catálogo para seleccionar opciones?',
            'optionGroups' => $this->catalogoOpcionesAgrupado(),
            'days' => $daysForResponse,
            'seleccionesPrevias' => $seleccionesActuales,
        ]);
    }

    private function responderConOpciones()
    {
        $seleccionesActuales = (array) session('chat.selecciones', []);
        return response()->json([
            'respuesta' => '¡Hola! Soy tu asistente de cotizaciones. Selecciona los sub-servicios que deseas agregar a tu cotización:',
            'optionGroups' => $this->catalogoOpcionesAgrupado(),
            'seleccionesPrevias' => $seleccionesActuales,
        ]);
    }

    private function catalogoOpciones()
    {
        $todos = SubServicios::query()
            ->select('sub_servicios.id', 'sub_servicios.nombre', 'sub_servicios.precio', 'servicios.nombre_servicio')
            ->join('servicios', 'servicios.id', '=', 'sub_servicios.servicios_id')
            ->orderBy('servicios.nombre_servicio')
            ->orderBy('sub_servicios.nombre')
            ->get();

        return $todos->map(function ($r) {
            return [
                'id' => $r->id,
                'nombre' => $r->nombre,
                'precio' => (float) $r->precio,
                'servicio' => $r->nombre_servicio,
            ];
        })->values();
    }

    private function catalogoOpcionesAgrupado()
    {
        $todos = $this->catalogoOpciones();
        return $this->agruparOpciones(collect($todos));
    }

    private function agruparOpciones($collection)
    {
        // $collection es una colección de arrays con claves: id, nombre, precio, servicio
        $grupos = [];
        foreach ($collection as $item) {
            $svc = is_array($item) ? $item['servicio'] : $item->nombre_servicio;
            $grupos[$svc] = $grupos[$svc] ?? ['servicio' => $svc, 'items' => []];
            $grupos[$svc]['items'][] = [
                'id' => is_array($item) ? $item['id'] : $item->id,
                'nombre' => is_array($item) ? $item['nombre'] : $item->nombre,
                'precio' => (float) (is_array($item) ? $item['precio'] : $item->precio),
            ];
        }
        // Ordenar por nombre de servicio e items por nombre
        ksort($grupos);
        foreach ($grupos as &$g) {
            usort($g['items'], function ($a, $b) { return strcmp($a['nombre'], $b['nombre']); });
        }
        return array_values($grupos);
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

        // Devuelve todas con puntaje > 0, ordenadas desc
        arsort($puntajes);
        return array_values(array_filter(array_keys($puntajes), function ($k) use ($puntajes) {
            return $puntajes[$k] > 0;
        }));
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
            'publicitarlos' => 'publicitar',
            'publicitarlas' => 'publicitar',
            'publicitarlo' => 'publicitar',
            'publicitarla' => 'publicitar',
            'luces' => 'luces',
            'luz' => 'luces',
            'dj' => 'dj',
            'deejay' => 'dj',
        ];
        
        $textoNormalizado = $this->normalizarTexto($texto);
        foreach ($correcciones as $error => $correcto) {
            $errorNorm = $this->normalizarTexto($error);
            // Reemplazar palabra completa o como subcadena al inicio
            $textoNormalizado = preg_replace('/\b' . preg_quote($errorNorm, '/') . '\b/i', $this->normalizarTexto($correcto), $textoNormalizado);
        }
        
        return $textoNormalizado;
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
