<?php

namespace App\Services;

use App\Models\SubServicios;

class ChatbotIntentionDetector
{
    private ChatbotTextProcessor $textProcessor;
    
    private const PAR_LED = 'par led';
    private const ANIMACION = 'animación';
    private const CUMPLEANOS = 'cumpleaños';
    private const MAESTRO_CEREMONIAS = 'maestro de ceremonias';
    private const LOCUCION = 'locución';
    private const EQUIPO_SONIDO = 'equipo de sonido';
    private const STOPWORDS = ['para','por','con','sin','del','de','la','las','el','los','una','unos','unas','que','y','o','en','al'];

    public function __construct(ChatbotTextProcessor $textProcessor)
    {
        $this->textProcessor = $textProcessor;
    }

    public function detectarIntenciones(string $mensaje): array
    {
        if ($mensaje === '') {
            return [];
        }
        $texto = $this->textProcessor->corregirOrtografia($mensaje);
        $mapa = $this->obtenerMapaSinonimos();
        $puntajes = $this->calcularPuntajesIntenciones($texto, $mapa);
        $explicitas = $this->obtenerPalabrasExplicitas();
        $fuertes = $this->obtenerPalabrasFuertes();
        $result = $this->filtrarIntencionesPorUmbral($puntajes, $texto, $explicitas, $fuertes);
        return $this->ordenarIntencionesPorPuntaje($result, $puntajes);
    }

    public function clasificarPorTfidf(string $mensajeCorregido): array
    {
        $texto = trim($this->textProcessor->normalizarTexto($mensajeCorregido));
        if ($texto === '') {
            return [];
        }
        $cache = $this->obtenerCacheTfidf();
        $qTokens = $this->extraerTokensParaTfidf($texto);
        if (empty($cache) || empty($qTokens)) {
            return [];
        }
        return $this->calcularYObtenerMejorIntencion($cache, $qTokens);
    }

    public function validarIntencionesContraMensaje(array $intenciones, string $mensajeCorregido): array
    {
        if (empty($intenciones)) {
            return [];
        }
        $explicitas = [
            'Alquiler' => ['alquiler','alquilar','rentar','arrendar','equipo','sonido','audio','parlante','altavoz','bafle','bocina','consola','mezcladora','mixer','microfono','luces','iluminacion',self::PAR_LED,'rack'],
            'Animación' => ['animacion',self::ANIMACION,'animador','dj',self::MAESTRO_CEREMONIAS,'presentador','coordinador','fiesta','evento','cumpleanos',self::CUMPLEANOS],
            'Publicidad' => ['publicidad','publicitar','anuncio','spot','cuña','locucion',self::LOCUCION,'jingle','radio']
        ];
        $texto = $this->textProcessor->normalizarTexto($mensajeCorregido);
        $validadas = [];
        foreach ($intenciones as $svc) {
            $ok = false;
            foreach ($explicitas[$svc] as $kw) {
                $kwNorm = $this->textProcessor->normalizarTexto($kw);
                if (preg_match('/\b' . preg_quote($kwNorm, '/') . '\b/u', $texto)) {
                    $ok = true;
                    break;
                }
            }
            if ($ok) {
                $validadas[] = $svc;
            }
        }
        return $validadas;
    }

    public function esRelacionado(string $mensajeCorregido): bool
    {
        if ($mensajeCorregido === '') {
            return true;
        }
        $texto = $this->textProcessor->normalizarTexto($mensajeCorregido);
        $explicitas = [
            'alquiler','alquilar','arrendar','rentar','equipo',self::EQUIPO_SONIDO,'sonido','audio','bafle','parlante','altavoz','bocina','consola','mezcladora','mixer','microfono','luces','luz','lampara','iluminacion','rack',self::PAR_LED,
            'animacion',self::ANIMACION,'animador','dj',self::MAESTRO_CEREMONIAS,'presentador','coordinador','fiesta','evento','cumpleanos',self::CUMPLEANOS,
            'publicidad','publicitar','anuncio','spot','cuña','jingle','locucion',self::LOCUCION,'radio'
        ];
        foreach ($explicitas as $kw) {
            $kwNorm = $this->textProcessor->normalizarTexto($kw);
            if (preg_match('/\b' . preg_quote($kwNorm, '/') . '\b/u', $texto)) {
                return true;
            }
        }
        return false;
    }

    private function obtenerMapaSinonimos(): array
    {
        return [
            'Alquiler' => [
                'alquiler','alquilar','arrendar','rentar','equipo',self::EQUIPO_SONIDO,'sonido','audio','bafle','parlante','altavoz','bocina','consola','mezcladora','mixer','microfono','microfono','luces','luz','lampara','lámpara','iluminacion','iluminación','rack',self::PAR_LED
            ],
            'Animación' => [
                'animacion',self::ANIMACION,'animador','dj',self::MAESTRO_CEREMONIAS,'presentador','coordinador','cumpleanos',self::CUMPLEANOS,'fiesta','evento'
            ],
            'Publicidad' => [
                'publicidad','publicitar','publicitarlos','publicitarlas','publicitarlo','publicitarla','anuncio','spot','cuña','cuna','jingle','locucion',self::LOCUCION,'radio'
            ],
        ];
    }

    private function calcularPuntajesIntenciones(string $texto, array $mapa): array
    {
        $puntajes = [ 'Alquiler' => 0, 'Animación' => 0, 'Publicidad' => 0 ];
        foreach ($mapa as $servicio => $keywords) {
            foreach ($keywords as $kw) {
                $kwNorm = $this->textProcessor->normalizarTexto($kw);
                if (str_contains($texto, $kwNorm) || preg_match('/\b' . preg_quote($kwNorm, '/') . '/i', $texto)) {
                    $puntajes[$servicio]++;
                }
            }
        }
        return $puntajes;
    }

    private function obtenerPalabrasExplicitas(): array
    {
        return [
            'Alquiler' => ['alquiler','alquilar','rentar','arrendar'],
            'Animación' => ['animacion',self::ANIMACION,'animador','dj'],
            'Publicidad' => ['publicidad','publicitar','anuncio','spot','cuña','locucion',self::LOCUCION]
        ];
    }

    private function obtenerPalabrasFuertes(): array
    {
        return [
            'Alquiler' => ['parlante','bafle','altavoz','bocina','microfono','consola','mezcladora','mixer','luces','lampara',self::PAR_LED,'rack','equipo','audio','sonido'],
            'Animación' => ['dj','animador','presentador',self::MAESTRO_CEREMONIAS],
            'Publicidad' => ['anuncio','spot','cuña','locucion','jingle','radio']
        ];
    }

    private function filtrarIntencionesPorUmbral(array $puntajes, string $texto, array $explicitas, array $fuertes): array
    {
        $result = [];
        foreach ($puntajes as $servicio => $score) {
            $aceptar = $score >= 2;
            if (!$aceptar) {
                $aceptar = $this->verificarPalabrasExplicitas($texto, $explicitas[$servicio]);
            }
            if (!$aceptar) {
                $aceptar = $this->verificarPalabrasFuertes($texto, $fuertes[$servicio] ?? []);
            }
            if ($aceptar) {
                $result[] = $servicio;
            }
        }
        return $result;
    }

    private function verificarPalabrasExplicitas(string $texto, array $explicitas): bool
    {
        foreach ($explicitas as $kw) {
            if (str_contains($texto, $this->textProcessor->normalizarTexto($kw))) {
                return true;
            }
        }
        return false;
    }

    private function verificarPalabrasFuertes(string $texto, array $fuertes): bool
    {
        foreach ($fuertes as $kw) {
            $kwNorm = $this->textProcessor->normalizarTexto($kw);
            if (preg_match('/\b' . preg_quote($kwNorm, '/') . '\w*/u', $texto)) {
                return true;
            }
        }
        return false;
    }

    private function ordenarIntencionesPorPuntaje(array $result, array $puntajes): array
    {
        arsort($puntajes);
        usort($result, function($a,$b) use ($puntajes){ return $puntajes[$b] <=> $puntajes[$a]; });
        return $result;
    }

    private function obtenerCacheTfidf(): array
    {
        static $cache = null;
        if ($cache === null) {
            $cache = $this->inicializarCacheTfidf();
        }
        return $cache;
    }

    private function inicializarCacheTfidf(): array
    {
        try {
            $rows = SubServicios::query()
                ->select('sub_servicios.nombre', 'sub_servicios.descripcion', 'servicios.nombre_servicio')
                ->join('servicios', 'servicios.id', '=', 'sub_servicios.servicios_id')
                ->get();
            $docsBySvc = $this->agruparDocumentosPorServicio($rows);
            return $this->procesarDocumentosParaCache($docsBySvc);
        } catch (\Exception $e) {
            return [];
        }
    }

    private function agruparDocumentosPorServicio($rows): array
    {
        $docsBySvc = [];
        foreach ($rows as $r) {
            $svc = $r->nombre_servicio;
            $content = trim(($r->nombre ?? '') . ' ' . ($r->descripcion ?? ''));
            $docsBySvc[$svc] = ($docsBySvc[$svc] ?? '') . ' ' . $content;
        }
        return $docsBySvc;
    }

    private function procesarDocumentosParaCache(array $docsBySvc): array
    {
        $stop = self::STOPWORDS;
        $df = [];
        $numDocs = 0;
        $docs = [];
        foreach ($docsBySvc as $svc => $doc) {
            $numDocs++;
            $tokens = $this->extraerTokensDeDocumento($doc, $stop);
            $tf = $this->calcularFrecuenciaTerminos($tokens);
            $docs[$svc] = $tf;
            foreach (array_keys($tf) as $term) {
                $df[$term] = ($df[$term] ?? 0) + 1;
            }
        }
        return ['docs' => $docs, 'df' => $df, 'N' => max(1, $numDocs)];
    }

    private function extraerTokensDeDocumento(string $doc, array $stop): array
    {
        return array_values(array_filter(preg_split('/[^a-z0-9áéíóúñ]+/u', $this->textProcessor->normalizarTexto($doc)), function($t) use ($stop){
            $t = trim($t);
            return $t !== '' && mb_strlen($t) >= 3 && !in_array($t, $stop, true);
        }));
    }

    private function extraerTokensParaTfidf(string $texto): array
    {
        $stop = self::STOPWORDS;
        return array_values(array_filter(preg_split('/[^a-z0-9áéíóúñ]+/u', $texto), function($t) use ($stop){
            $t = trim($t);
            return $t !== '' && mb_strlen($t) >= 3 && !in_array($t, $stop, true);
        }));
    }

    private function calcularFrecuenciaTerminos(array $tokens): array
    {
        $freq = [];
        foreach ($tokens as $t) {
            $freq[$t] = ($freq[$t] ?? 0) + 1;
        }
        return $freq;
    }

    private function calcularYObtenerMejorIntencion(array $cache, array $qTokens): array
    {
        $qtf = $this->calcularFrecuenciaTerminos($qTokens);
        $scores = $this->calcularScoresTfidf($cache, $qtf);
        return $this->obtenerMejorIntencion($scores);
    }

    private function calcularScoresTfidf(array $cache, array $qtf): array
    {
        $scores = [];
        foreach ($cache['docs'] as $svc => $tf) {
            $score = $this->calcularScoreServicio($cache, $qtf, $tf);
            $scores[$svc] = $score;
        }
        return $scores;
    }

    private function calcularScoreServicio(array $cache, array $qtf, array $tf): float
    {
        $num = 0.0;
        $normQ = 0.0;
        $normD = 0.0;
        foreach ($qtf as $term => $fq) {
            $df = $cache['df'][$term] ?? 0;
            if ($df <= 0) {
                continue;
            }
            $idf = log(($cache['N'] + 1) / ($df + 0.5));
            $wq = $fq * $idf;
            $wd = ($tf[$term] ?? 0) * $idf;
            $num += $wq * $wd;
            $normQ += $wq * $wq;
        }
        foreach ($tf as $term => $fd) {
            $df = $cache['df'][$term] ?? 0;
            if ($df <= 0) {
                continue;
            }
            $idf = log(($cache['N'] + 1) / ($df + 0.5));
            $wd = $fd * $idf;
            $normD += $wd * $wd;
        }
        $den = (sqrt(max(1e-8, $normQ)) * sqrt(max(1e-8, $normD)));
        return $den > 0 ? ($num / $den) : 0.0;
    }

    private function obtenerMejorIntencion(array $scores): array
    {
        arsort($scores);
        $top = array_key_first($scores);
        if ($top !== null && $scores[$top] >= 0.12) {
            return [$top];
        }
        return [];
    }
}

