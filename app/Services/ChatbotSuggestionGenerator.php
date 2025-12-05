<?php

namespace App\Services;

class ChatbotSuggestionGenerator
{
    private ChatbotTextProcessor $textProcessor;

    private const STOPWORDS = ['para', 'por', 'con', 'sin', 'del', 'de', 'la', 'las', 'el', 'los', 'una', 'unos', 'unas', 'que', 'y', 'o', 'en', 'al'];

    private const STOPWORDS_EXT = ['para', 'por', 'con', 'sin', 'del', 'de', 'la', 'las', 'el', 'los', 'una', 'unos', 'unas', 'que', 'y', 'o', 'en', 'al', 'par'];

    private const TOKENS_GENERICOS = ['necesito', 'nececito', 'nesecito', 'necesitar', 'requiero', 'quiero', 'busco', 'hola', 'buenas', 'gracias', 'dias', 'dia'];

    private const REGEX_WHITESPACE = '/\s+/';

    public function __construct(ChatbotTextProcessor $textProcessor)
    {
        $this->textProcessor = $textProcessor;
    }

    public function generarSugerencias(string $mensajeCorregido): array
    {
        $sugerenciasNombres = $this->generarSugerenciasNombresCompletos($mensajeCorregido);
        if (!empty($sugerenciasNombres)) {
            return array_slice($sugerenciasNombres, 0, 5);
        }
        
        $vocab = $this->obtenerVocabularioCorreccion();
        if (empty($vocab)) {
            return [];
        }
        $tokens = $this->extraerTokensDelMensaje($mensajeCorregido);
        if (empty($tokens)) {
            $tokens = [$this->textProcessor->normalizarTexto($mensajeCorregido)];
        }
        $scores = $this->calcularScoresSugerencias($tokens, $vocab);
        arsort($scores);

        return array_slice(array_keys($scores), 0, 5);
    }

    public function generarSugerenciasPorToken(string $mensajeOriginal): array
    {
        $sugerenciasNombres = $this->generarSugerenciasPorTokenNombresCompletos($mensajeOriginal);
        if (!empty($sugerenciasNombres)) {
            return $sugerenciasNombres;
        }
        
        $vocab = $this->obtenerVocabularioCorreccion();
        $pairs = $this->filtrarTokensValidos($mensajeOriginal);
        $targetToken = !empty($pairs) ? $this->encontrarTokenMasRaro($pairs, $vocab) : null;
        
        if (empty($pairs) || $targetToken === null) {
            return [];
        }
        
        return [['token' => $targetToken, 'sugerencias' => $this->generarSugerenciasParaToken($targetToken, $vocab)]];
    }

    public function fallbackTokenHints(string $mensajeCorregido): array
    {
        $tokens = array_values(array_filter(preg_split(self::REGEX_WHITESPACE, $this->textProcessor->normalizarTexto($mensajeCorregido)), function ($t) {
            return trim($t) !== '' && mb_strlen(trim($t)) >= 3;
        }));
        if (empty($tokens)) {
            return [];
        }

        $subServicios = $this->buscarSubServiciosPorTokens($tokens);
        if ($subServicios->isNotEmpty()) {
            $nombres = $subServicios->pluck('nombre')->unique()->values()->toArray();
            return [['token' => $tokens[0], 'sugerencias' => array_slice($nombres, 0, 6)]];
        }

        $vocab = $this->obtenerVocabularioCorreccion();
        return [['token' => $tokens[0], 'sugerencias' => !empty($vocab) ? array_slice($vocab, 0, 6) : []]];
    }

    public function extraerMejorSugerencia(array $tokenHints): array
    {
        if (empty($tokenHints) || ! isset($tokenHints[0]['token'])) {
            return [];
        }
        $token = $tokenHints[0]['token'];
        $sugs = (array) ($tokenHints[0]['sugerencias'] ?? []);
        $best = $sugs[0] ?? null;

        return $best ? ['token' => $token, 'sugerencia' => $best] : [];
    }

    public function generarSugerenciasNombresCompletos(string $mensajeCorregido): array
    {
        $tokens = $this->extraerTokensDelMensaje($mensajeCorregido);
        if (empty($tokens)) {
            $tokens = [$this->textProcessor->normalizarTexto($mensajeCorregido)];
        }
        
        $subServicios = $this->buscarSubServiciosPorTokens($tokens);
        return $subServicios->isEmpty() ? [] : $subServicios->pluck('nombre')->unique()->values()->toArray();
    }

    public function generarSugerenciasPorTokenNombresCompletos(string $mensajeOriginal): array
    {
        $pairs = $this->filtrarTokensValidos($mensajeOriginal);
        $vocab = $this->obtenerVocabularioCorreccion();
        $targetToken = !empty($pairs) ? $this->encontrarTokenMasRaro($pairs, $vocab) : null;
        
        if (empty($pairs) || $targetToken === null) {
            return [];
        }
        
        $subServicios = $this->buscarSubServiciosPorTokens([$targetToken]);
        $nombres = $subServicios->isEmpty() ? [] : $subServicios->pluck('nombre')->unique()->values()->toArray();
        
        return empty($nombres) ? [] : [['token' => $targetToken, 'sugerencias' => array_slice($nombres, 0, 6)]];
    }

    private function obtenerVocabularioCorreccion(): array
    {
        $vocab = [];
        try {
            $servicios = \App\Models\Servicios::query()->select('nombre_servicio')->get();
            foreach ($servicios as $servicio) {
                $tokens = preg_split('/[^a-zA-Z0-9áéíóúñ]+/u', $servicio->nombre_servicio ?? '');
                foreach ($tokens as $tk) {
                    $this->agregarTokenAlVocabulario($vocab, $tk, 3);
                }
            }
            
            $subServicios = \App\Models\SubServicios::query()->select('nombre')->limit(500)->get();
            foreach ($subServicios as $ss) {
                $tokensNombre = preg_split('/[^a-zA-Z0-9áéíóúñ]+/u', $ss->nombre ?? '');
                foreach ($tokensNombre as $tk) {
                    $this->agregarTokenAlVocabulario($vocab, $tk, 3, true);
                }
            }
        } catch (\Exception $e) {
            return [];
        }
        return array_keys($vocab);
    }

    private function agregarTokenAlVocabulario(array &$vocab, string $token, int $longitudMinima, bool $permitirDj = false): void
    {
        $tk = trim($token);
        if ($tk === '') {
            return;
        }

        $norm = $this->textProcessor->normalizarTexto($tk);
        $longitudValida = ($permitirDj && $norm === 'dj') || mb_strlen($norm) >= $longitudMinima;
        
        if ($longitudValida && mb_strlen($norm) <= 30 && !in_array($norm, self::STOPWORDS_EXT, true)) {
            $vocab[$norm] = true;
        }
    }

    private function extraerTokensDelMensaje(string $mensajeCorregido): array
    {
        $stop = self::STOPWORDS;
        return array_values(array_filter(preg_split(self::REGEX_WHITESPACE, $this->textProcessor->normalizarTexto($mensajeCorregido)), function ($t) use ($stop) {
            $t = trim($t);
            return $t !== '' && mb_strlen($t) >= 3 && ! in_array($t, $stop, true);
        }));
    }

    private function calcularScoresSugerencias(array $tokens, array $vocab): array
    {
        $scores = [];
        $stop = self::STOPWORDS_EXT;
        $umbralMinimo = 30.0;
        
        foreach ($tokens as $t) {
            foreach ($vocab as $term) {
                if (in_array($term, $stop, true) || mb_strlen($term) < 3) {
                    continue;
                }
                
                $percent = 0.0;
                similar_text($t, $term, $percent);
                if (mb_substr($t, 0, 1) !== mb_substr($term, 0, 1)) {
                    $percent *= 0.85;
                }
                if ($percent < $umbralMinimo) {
                    continue;
                }
                $scores[$term] = max($scores[$term] ?? 0.0, $percent);
            }
        }

        return $scores;
    }

    private function filtrarTokensValidos(string $mensajeOriginal): array
    {
        $stop = self::STOPWORDS;
        $rawTokens = preg_split(self::REGEX_WHITESPACE, trim($mensajeOriginal));
        $pairs = [];
        $genericos = self::TOKENS_GENERICOS;
        foreach ($rawTokens as $rt) {
            $norm = $this->textProcessor->normalizarTexto($rt);
            if ($norm === '' || mb_strlen($norm) < 3 || in_array($norm, $stop, true) || in_array($norm, $genericos, true) || preg_match('/^\d+$/', $norm)) {
                continue;
            }
            $pairs[] = ['orig' => $rt, 'norm' => $norm];
        }
        return $pairs;
    }

    private function encontrarTokenMasRaro(array $pairs, array $vocab): ?string
    {
        if (empty($vocab)) {
            return null;
        }
        $tokenScores = [];
        foreach ($pairs as $p) {
            $maxSim = 0.0;
            foreach ($vocab as $term) {
                $percent = 0.0;
                similar_text($p['norm'], $term, $percent);
                if ($percent > $maxSim) {
                    $maxSim = $percent;
                }
            }
            $tokenScores[$p['orig']] = $maxSim;
        }
        asort($tokenScores);
        $result = array_key_first($tokenScores);
        return $result !== null ? $result : null;
    }

    private function generarSugerenciasParaToken(string $targetToken, array $vocab): array
    {
        $candidatos = [];
        $targetNorm = $this->textProcessor->normalizarTexto($targetToken);
        $targetLen = mb_strlen($targetNorm);
        $conceptosRelacionados = $this->obtenerConceptosRelacionados();
        
        foreach ($vocab as $term) {
            $score = $this->calcularScoreParaTermino($targetNorm, $targetLen, $term, $conceptosRelacionados);
            if ($score !== null) {
                $candidatos[$term] = $score;
            }
        }
        arsort($candidatos);
        return array_slice(array_keys($candidatos), 0, 6);
    }

    private function calcularScoreParaTermino(string $targetNorm, int $targetLen, string $term, array $conceptosRelacionados): ?float
    {
        $termNorm = $term;
        $termLen = mb_strlen($termNorm);
        
        $percent = 0.0;
        similar_text($targetNorm, $termNorm, $percent);
        
        $esRelacionado = $this->verificarConceptosRelacionados($targetNorm, $termNorm, $conceptosRelacionados);
        if ($esRelacionado) {
            $percent = 100.0;
        }
        
        $percent = $this->aplicarAjustesScore($targetNorm, $targetLen, $termNorm, $termLen, $percent);
        
        $umbralMinimo = $targetLen <= 5 ? 30.0 : 35.0;
        if (!$esRelacionado && $percent < 30.0) {
            return null;
        }
        
        if ($percent >= $umbralMinimo || $esRelacionado) {
            return $esRelacionado ? 1000.0 + $percent : $percent;
        }
        
        return null;
    }

    private function verificarConceptosRelacionados(string $targetNorm, string $termNorm, array $conceptosRelacionados): bool
    {
        $p1 = mb_strtolower($targetNorm);
        $p2 = mb_strtolower($termNorm);
        return (isset($conceptosRelacionados[$p1]) && in_array($p2, $conceptosRelacionados[$p1], true)) ||
               (isset($conceptosRelacionados[$p2]) && in_array($p1, $conceptosRelacionados[$p2], true));
    }

    private function aplicarAjustesScore(string $targetNorm, int $targetLen, string $termNorm, int $termLen, float $percent): float
    {
        if (mb_substr($targetNorm, 0, 1) === mb_substr($termNorm, 0, 1)) {
            $percent *= 1.15;
        }
        
        $subcadenaComun = $this->encontrarSubcadenaComunMasLarga($targetNorm, $termNorm);
        if (mb_strlen($subcadenaComun) >= 3) {
            $percent += 15.0;
        }
        
        $lenDiff = abs($targetLen - $termLen);
        if ($lenDiff > max($targetLen, $termLen) * 0.6) {
            $percent *= 0.7;
        }
        
        return $percent;
    }

    private function obtenerConceptosRelacionados(): array
    {
        return [
            'lampara' => ['luces', 'luz', 'iluminacion', 'audioritmicas'],
            'luz' => ['luces', 'lampara', 'iluminacion', 'audioritmicas'],
            'luces' => ['luz', 'lampara', 'iluminacion', 'audioritmicas'],
            'iluminacion' => ['luces', 'luz', 'lampara', 'audioritmicas'],
            'microfono' => ['microfono', 'inalambrico'],
            'parlante' => ['bafle', 'altavoz', 'bocina', 'audio', 'sonido'],
            'bafle' => ['parlante', 'altavoz', 'bocina', 'audio', 'sonido'],
            'altavoz' => ['parlante', 'bafle', 'bocina', 'audio', 'sonido'],
            'bocina' => ['parlante', 'bafle', 'altavoz', 'audio', 'sonido'],
            'consola' => ['mezcladora', 'mixer', 'audio'],
            'mezcladora' => ['consola', 'mixer', 'audio'],
            'mixer' => ['consola', 'mezcladora', 'audio'],
            'dj' => ['animador', 'animacion', 'eventos'],
            'animador' => ['dj', 'animacion', 'eventos'],
            'animacion' => ['dj', 'animador', 'eventos'],
            'publicidad' => ['anuncio', 'spot', 'cuna', 'locucion', 'jingle'],
            'anuncio' => ['publicidad', 'spot', 'cuna', 'locucion'],
            'spot' => ['publicidad', 'anuncio', 'cuna', 'locucion'],
        ];
    }

    private function encontrarSubcadenaComunMasLarga(string $str1, string $str2): string
    {
        $maxLen = 0;
        $maxSubstr = '';
        $len1 = mb_strlen($str1);
        $len2 = mb_strlen($str2);
        
        for ($i = 0; $i < $len1; $i++) {
            for ($j = 0; $j < $len2; $j++) {
                $k = 0;
                while ($i + $k < $len1 && $j + $k < $len2 &&
                       mb_substr($str1, $i + $k, 1) === mb_substr($str2, $j + $k, 1)) {
                    $k++;
                }
                if ($k > $maxLen) {
                    $maxLen = $k;
                    $maxSubstr = mb_substr($str1, $i, $k);
                }
            }
        }
        
        return $maxSubstr;
    }

    private function buscarSubServiciosPorTokens(array $tokens): \Illuminate\Support\Collection
    {
        if (empty($tokens)) {
            return collect();
        }
        
        $tokensNormalizados = array_map(function ($tk) {
            return $this->textProcessor->normalizarTexto(trim($tk));
        }, array_filter($tokens, function ($tk) {
            return trim($tk) !== '';
        }));
        
        if (empty($tokensNormalizados)) {
            return collect();
        }
        
        $grammar = \Illuminate\Support\Facades\DB::getQueryGrammar();
        $columnaWrapped = $grammar->wrap('sub_servicios.nombre');
        $columnaNormalizada = "LOWER(REPLACE(REPLACE(REPLACE(REPLACE(REPLACE(REPLACE({$columnaWrapped}, 'á', 'a'), 'é', 'e'), 'í', 'i'), 'ó', 'o'), 'ú', 'u'), 'ñ', 'n'))";
        
        return \App\Models\SubServicios::query()
            ->select('sub_servicios.nombre')
            ->where(function ($q) use ($tokensNormalizados, $columnaNormalizada) {
                foreach ($tokensNormalizados as $index => $termino) {
                    if ($termino !== '') {
                        if ($index === 0) {
                            $q->whereRaw("{$columnaNormalizada} LIKE ?", ["%{$termino}%"]);
                        } else {
                            $q->orWhereRaw("{$columnaNormalizada} LIKE ?", ["%{$termino}%"]);
                        }
                    }
                }
            })
            ->limit(12)
            ->get();
    }
}
