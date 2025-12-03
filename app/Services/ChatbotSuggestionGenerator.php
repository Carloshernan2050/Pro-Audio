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
        $vocab = $this->obtenerVocabularioCorreccion();
        $pairs = $this->filtrarTokensValidos($mensajeOriginal);
        if (empty($pairs)) {
            return [];
        }

        $targetToken = $this->encontrarTokenMasRaro($pairs, $vocab);
        if ($targetToken === null) {
            return [];
        }

        $sugs = $this->generarSugerenciasParaToken($targetToken, $vocab);

        return [['token' => $targetToken, 'sugerencias' => $sugs]];
    }

    public function fallbackTokenHints(string $mensajeCorregido): array
    {
        $tokens = array_values(array_filter(preg_split(self::REGEX_WHITESPACE, $this->textProcessor->normalizarTexto($mensajeCorregido)), function ($t) {
            return trim($t) !== '' && mb_strlen(trim($t)) >= 3;
        }));
        if (empty($tokens)) {
            return [];
        }

        // Obtener sugerencias de la BD en lugar de palabras hardcodeadas
        $vocab = $this->obtenerVocabularioCorreccion();
        $sugerencias = !empty($vocab) ? array_slice($vocab, 0, 6) : [];

        return [['token' => $tokens[0], 'sugerencias' => $sugerencias]];
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

    protected function obtenerVocabularioCorreccion(): array
    {
        $vocab = [];
        
        try {
            $this->extraerTokensDeServicios($vocab);
            $this->extraerTokensDeSubServicios($vocab);
        } catch (\Exception $e) {
            // Si falla la DB, retornar array vacío (no usar palabras hardcodeadas)
            return [];
        }

        return array_keys($vocab);
    }

    private function extraerTokensDeServicios(array &$vocab): void
    {
        $servicios = \App\Models\Servicios::query()->select('nombre_servicio')->get();
        foreach ($servicios as $servicio) {
            $tokens = preg_split('/[^a-zA-Z0-9áéíóúñ]+/u', $servicio->nombre_servicio ?? '');
            foreach ($tokens as $tk) {
                $this->agregarTokenAlVocabulario($vocab, $tk, 3);
            }
        }
    }

    private function extraerTokensDeSubServicios(array &$vocab): void
    {
        $subServicios = \App\Models\SubServicios::query()->select('nombre', 'descripcion')->limit(500)->get();
        foreach ($subServicios as $ss) {
            $texto = ($ss->nombre ?? '') . ' ' . ($ss->descripcion ?? '');
            $tokens = preg_split('/[^a-zA-Z0-9áéíóúñ]+/u', $texto);
            foreach ($tokens as $tk) {
                $this->agregarTokenAlVocabulario($vocab, $tk, 4, true);
            }
        }
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
        $umbralMinimo = 25.0; // Umbral mínimo de similitud (25%)
        
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
                // Aplicar umbral mínimo
                if ($percent < $umbralMinimo) {
                    continue;
                }
                $scores[$term] = max($scores[$term] ?? 0.0, $percent);
            }
        }

        return $scores;
    }

    protected function filtrarTokensValidos(string $mensajeOriginal): array
    {
        $stop = self::STOPWORDS;
        $rawTokens = preg_split(self::REGEX_WHITESPACE, trim($mensajeOriginal));
        $pairs = [];
        $genericos = self::TOKENS_GENERICOS;
        foreach ($rawTokens as $rt) {
            $norm = $this->textProcessor->normalizarTexto($rt);
            if ($norm === '' || mb_strlen($norm) < 3) {
                continue;
            }
            if (in_array($norm, $stop, true)) {
                continue;
            }
            if (in_array($norm, $genericos, true)) {
                continue;
            }
            if (preg_match('/^\d+$/', $norm)) {
                continue;
            }
            $pairs[] = ['orig' => $rt, 'norm' => $norm];
        }

        return $pairs;
    }

    protected function encontrarTokenMasRaro(array $pairs, array $vocab): ?string
    {
        $tokenScores = [];
        foreach ($pairs as $p) {
            $t = $p['norm'];
            $maxSim = $this->calcularMaximaSimilitud($t, $vocab);
            $tokenScores[$p['orig']] = $maxSim;
        }
        asort($tokenScores);

        return array_key_first($tokenScores);
    }

    private function calcularMaximaSimilitud(string $token, array $vocab): float
    {
        $maxSim = 0.0;
        foreach ($vocab as $term) {
            $percent = 0.0;
            similar_text($token, $term, $percent);
            if ($percent > $maxSim) {
                $maxSim = $percent;
            }
        }

        return $maxSim;
    }

    private function generarSugerenciasParaToken(string $targetToken, array $vocab): array
    {
        $candidatos = [];
        $targetNorm = $this->textProcessor->normalizarTexto($targetToken);
        $targetLen = mb_strlen($targetNorm);
        
        // Mapeo de conceptos relacionados (para mejorar sugerencias semánticas)
        $conceptosRelacionados = $this->obtenerConceptosRelacionados();
        
        foreach ($vocab as $term) {
            $termNorm = $term;
            $termLen = mb_strlen($termNorm);
            
            // Calcular similitud básica
            $percent = 0.0;
            similar_text($targetNorm, $termNorm, $percent);
            
            // Verificar si son conceptos relacionados semánticamente
            $esRelacionado = $this->sonConceptosRelacionados($targetNorm, $termNorm, $conceptosRelacionados);
            if ($esRelacionado) {
                $percent = max($percent, 50.0); // Boost mínimo para conceptos relacionados
            }
            
            // Bonus si comparten la primera letra
            if (mb_substr($targetNorm, 0, 1) === mb_substr($termNorm, 0, 1)) {
                $percent *= 1.15;
            }
            
            // Bonus si hay subcadena común significativa (mínimo 3 caracteres)
            $subcadenaComun = $this->encontrarSubcadenaComunMasLarga($targetNorm, $termNorm);
            if (mb_strlen($subcadenaComun) >= 3) {
                $percent += 15.0;
            }
            
            // Penalizar si la diferencia de longitud es muy grande
            $lenDiff = abs($targetLen - $termLen);
            if ($lenDiff > max($targetLen, $termLen) * 0.6) {
                $percent *= 0.7;
            }
            
            // Umbral mínimo dinámico: más bajo para palabras cortas, más alto para largas
            $umbralMinimo = $targetLen <= 5 ? 20.0 : 30.0;
            
            if ($percent >= $umbralMinimo) {
                $candidatos[$term] = $percent;
            }
        }
        arsort($candidatos);

        return array_slice(array_keys($candidatos), 0, 6);
    }

    private function obtenerConceptosRelacionados(): array
    {
        return [
            'lampara' => ['luces', 'luz', 'iluminacion', 'iluminacion', 'audioritmicas'],
            'luz' => ['luces', 'lampara', 'iluminacion', 'iluminacion'],
            'luces' => ['luz', 'lampara', 'iluminacion', 'iluminacion', 'audioritmicas'],
            'iluminacion' => ['luces', 'luz', 'lampara', 'audioritmicas'],
            'iluminacion' => ['luces', 'luz', 'lampara', 'audioritmicas'],
            'microfono' => ['microfono', 'inalambrico'],
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

    private function sonConceptosRelacionados(string $palabra1, string $palabra2, array $conceptos): bool
    {
        $p1 = mb_strtolower($palabra1);
        $p2 = mb_strtolower($palabra2);
        
        // Verificar si están en el mismo grupo de conceptos relacionados
        if (isset($conceptos[$p1]) && in_array($p2, $conceptos[$p1], true)) {
            return true;
        }
        if (isset($conceptos[$p2]) && in_array($p1, $conceptos[$p2], true)) {
            return true;
        }
        
        return false;
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
}
