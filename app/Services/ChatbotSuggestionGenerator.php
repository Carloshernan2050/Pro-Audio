<?php

namespace App\Services;

class ChatbotSuggestionGenerator
{
    private ChatbotTextProcessor $textProcessor;
    
    private const SUGERENCIAS_BASE = ['alquiler','animacion','publicidad','luces','dj','audio'];
    private const STOPWORDS = ['para','por','con','sin','del','de','la','las','el','los','una','unos','unas','que','y','o','en','al'];
    private const STOPWORDS_EXT = ['para','por','con','sin','del','de','la','las','el','los','una','unos','unas','que','y','o','en','al','par'];
    private const TOKENS_GENERICOS = ['necesito','nececito','nesecito','necesitar','requiero','quiero','busco','hola','buenas','gracias','dias','dia'];
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
        $lista = array_slice(array_keys($scores), 0, 5);
        if (empty($lista)) {
            $lista = self::SUGERENCIAS_BASE;
        }
        return $lista;
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
        return [[ 'token' => $targetToken, 'sugerencias' => $sugs ]];
    }

    public function fallbackTokenHints(string $mensajeCorregido): array
    {
        $tokens = array_values(array_filter(preg_split(self::REGEX_WHITESPACE, $this->textProcessor->normalizarTexto($mensajeCorregido)), function($t){
            return trim($t) !== '' && mb_strlen(trim($t)) >= 3;
        }));
        if (empty($tokens)) {
            return [];
        }
        return [[ 'token' => $tokens[0], 'sugerencias' => self::SUGERENCIAS_BASE ]];
    }

    public function extraerMejorSugerencia(array $tokenHints): array
    {
        if (empty($tokenHints) || !isset($tokenHints[0]['token'])) {
            return [];
        }
        $token = $tokenHints[0]['token'];
        $sugs = (array) ($tokenHints[0]['sugerencias'] ?? []);
        $best = $sugs[0] ?? null;
        return $best ? ['token' => $token, 'sugerencia' => $best] : [];
    }

    private function obtenerVocabularioCorreccion(): array
    {
        $keywords = [
            'alquiler','alquilar','arrendar','rentar','equipo','equipo de sonido','sonido','audio','bafle','parlante','parlantes','altavoz','bocina','consola','mezcladora','mixer','microfono','luces','luz','lampara','iluminacion','rack','par led',
            'animacion','animador','dj','maestro','ceremonias','presentador','coordinador','cumpleanos','fiesta','evento',
            'publicidad','publicitar','anuncio','spot','cuña','jingle','locucion','radio'
        ];
        $vocab = [];
        foreach ($keywords as $k) {
            $vocab[$this->textProcessor->normalizarTexto($k)] = true;
        }
        
        try {
            $subServicios = \App\Models\SubServicios::query()->select('nombre','descripcion')->limit(500)->get();
            foreach ($subServicios as $ss) {
                $tokens = preg_split('/[^a-zA-Z0-9áéíóúñ]+/u', ($ss->nombre . ' ' . ($ss->descripcion ?? '')));
                foreach ($tokens as $tk) {
                    $tk = trim($tk);
                    if ($tk === '') {
                        continue;
                    }
                    $norm = $this->textProcessor->normalizarTexto($tk);
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

    private function extraerTokensDelMensaje(string $mensajeCorregido): array
    {
        $stop = self::STOPWORDS;
        return array_values(array_filter(preg_split(self::REGEX_WHITESPACE, $this->textProcessor->normalizarTexto($mensajeCorregido)), function($t) use ($stop){
            $t = trim($t);
            return $t !== '' && mb_strlen($t) >= 3 && !in_array($t, $stop, true);
        }));
    }

    private function calcularScoresSugerencias(array $tokens, array $vocab): array
    {
        $scores = [];
        $stop = self::STOPWORDS_EXT;
        foreach ($tokens as $t) {
            foreach ($vocab as $term) {
                if (in_array($term, $stop, true) || mb_strlen($term) < 3) {
                    continue;
                }
                $percent = 0.0;
                similar_text($t, $term, $percent);
                if (mb_substr($t,0,1) !== mb_substr($term,0,1)) {
                    $percent *= 0.85;
                }
                if ($percent <= 0) {
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
            $pairs[] = [ 'orig' => $rt, 'norm' => $norm ];
        }
        return $pairs;
    }

    private function encontrarTokenMasRaro(array $pairs, array $vocab): ?string
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
        foreach ($vocab as $term) {
            $percent = 0.0;
            similar_text($targetNorm, $term, $percent);
            if ($percent > 0) {
                $candidatos[$term] = $percent;
            }
        }
        arsort($candidatos);
        return array_slice(array_keys($candidatos), 0, 6);
    }
}

