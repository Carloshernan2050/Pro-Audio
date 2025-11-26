<?php

namespace App\Services;

class ChatbotTextProcessor
{
    private const STOPWORDS = ['para','por','con','sin','del','de','la','las','el','los','una','unos','unas','que','y','o','en','al'];
    private const STOPWORDS_EXT = ['para','por','con','sin','del','de','la','las','el','los','una','unos','unas','que','y','o','en','al','par'];
    private const CUES_AGREGAR = ['tambien','también','ademas','además','y ',' y','sumar','agrega','agregar','junto','ademas de','además de'];
    private const REGEX_WHITESPACE = '/\s+/';
    private const REGEX_DIAS = '/^(por\s+)?\d+\s*d[ií]as?$/i';
    private const PAR_LED = 'par led';
    private const LOCUCION = 'locución';
    private const EQUIPO_SONIDO = 'equipo de sonido';

    public function normalizarTexto(string $t): string
    {
        $t = mb_strtolower($t);
        $reemplazos = [
            'á' => 'a', 'é' => 'e', 'í' => 'i', 'ó' => 'o', 'ú' => 'u', 'ñ' => 'n',
        ];
        return strtr($t, $reemplazos);
    }

    public function corregirOrtografia(string $texto): string
    {
        $correcciones = [
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
            'locucion' => self::LOCUCION,
            'locuion' => self::LOCUCION,
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
            'microfno' => 'microfono',
            'parled' => self::PAR_LED,
        ];
        
        $textoNormalizado = $this->normalizarTexto($texto);
        foreach ($correcciones as $error => $correcto) {
            $errorNorm = $this->normalizarTexto($error);
            $textoNormalizado = preg_replace('/\b' . preg_quote($errorNorm, '/') . '\b/i', $this->normalizarTexto($correcto), $textoNormalizado);
        }
        
        $vocabulario = $this->obtenerVocabularioCorreccion();
        if (!empty($vocabulario)) {
            $palabras = preg_split(self::REGEX_WHITESPACE, $textoNormalizado);
            $corregidas = [];
            foreach ($palabras as $palabra) {
                $p = trim($palabra);
                if ($p === '' || mb_strlen($p) < 3) {
                    $corregidas[] = $p;
                    continue;
                }
                $mejor = $this->buscarCorreccionCercana($p, $vocabulario);
                $corregidas[] = $mejor ?? $p;
            }
            $textoNormalizado = trim(implode(' ', array_filter($corregidas, function($w){ return $w !== null && $w !== ''; })));
        }
        
        return $textoNormalizado;
    }

    public function extraerTokens(string $mensajeCorregido): array
    {
        return array_values(array_filter(preg_split(self::REGEX_WHITESPACE, $mensajeCorregido), function ($t) {
            $t = trim($t);
            if ($t === '') {
                return false;
            }
            if (mb_strlen($t) < 3) {
                return false;
            }
            return !in_array($t, self::STOPWORDS, true);
        }));
    }

    public function esContinuacion(string $mensaje): bool
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

    public function verificarSiEsAgregado(string $mensajeCorregido): bool
    {
        $textoNorm = $this->normalizarTexto($mensajeCorregido);
        foreach (self::CUES_AGREGAR as $cue) {
            if (str_contains($textoNorm, $this->normalizarTexto($cue))) {
                return true;
            }
        }
        return false;
    }

    public function verificarSoloDias(string $mensaje, string $mensajeCorregido): bool
    {
        $soloDiasOriginal = preg_match(self::REGEX_DIAS, trim($mensaje));
        $soloDiasCorregido = preg_match(self::REGEX_DIAS, trim($mensajeCorregido));
        return $soloDiasOriginal || $soloDiasCorregido;
    }

    public function extraerDiasDesdePalabras(string $mensaje): ?int
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

    private function obtenerVocabularioCorreccion(): array
    {
        $keywords = [
            'alquiler','alquilar','arrendar','rentar','equipo',self::EQUIPO_SONIDO,'sonido','audio','bafle','parlante','parlantes','altavoz','bocina','consola','mezcladora','mixer','microfono','luces','luz','lampara','iluminacion','rack',self::PAR_LED,
            'animacion','animador','dj','maestro','ceremonias','presentador','coordinador','cumpleanos','fiesta','evento',
            'publicidad','publicitar','anuncio','spot','cuña','jingle','locucion','radio'
        ];
        $vocab = [];
        foreach ($keywords as $k) {
            $vocab[$this->normalizarTexto($k)] = true;
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
                    $norm = $this->normalizarTexto($tk);
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
        $len = mb_strlen($palabra);
        $umbral = max(1, (int) floor($len / 4));
        $best = null;
        $bestDist = PHP_INT_MAX;
        $bestSim = 0.0;
        foreach ($vocabulario as $term) {
            if (!$this->esTerminoValidoParaCorreccion($palabra, $term, $len, $umbral)) {
                continue;
            }
            [$dist, $sim] = $this->calcularSimilitud($palabra, $term, $len);
            if ($this->esMejorCorreccion($dist, $sim, $bestDist, $bestSim)) {
                $bestDist = $dist;
                $bestSim = $sim;
                $best = $term;
                if ($bestDist === 0) {
                    break;
                }
            }
        }
        return $this->validarCorreccion($best, $bestDist, $umbral, $bestSim);
    }

    private function esTerminoValidoParaCorreccion(string $palabra, string $term, int $len, int $umbral): bool
    {
        $diff = abs(mb_strlen($term) - $len);
        if ($diff > $umbral + 1) {
            return false;
        }
        return mb_substr($palabra, 0, 1) === mb_substr($term, 0, 1);
    }

    private function calcularSimilitud(string $palabra, string $term, int $len): array
    {
        $percent = 0.0;
        similar_text($palabra, $term, $percent);
        $dist = function_exists('levenshtein') ? levenshtein($palabra, $term) : (int) round((100 - $percent) * $len / 100);
        return [$dist, $percent];
    }

    private function esMejorCorreccion(int $dist, float $sim, int $bestDist, float $bestSim): bool
    {
        return $dist < $bestDist || ($dist === $bestDist && $sim > $bestSim);
    }

    private function validarCorreccion(?string $best, int $bestDist, int $umbral, float $bestSim): ?string
    {
        if ($best !== null && $bestDist <= $umbral && $bestSim >= 85.0) {
            return $best;
        }
        return null;
    }
}

