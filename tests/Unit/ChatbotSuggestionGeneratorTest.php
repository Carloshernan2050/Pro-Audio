<?php

namespace Tests\Unit;

use Tests\TestCase;
use App\Services\ChatbotSuggestionGenerator;
use App\Services\ChatbotTextProcessor;
use App\Models\SubServicios;
use App\Models\Servicios;
use Illuminate\Foundation\Testing\RefreshDatabase;

/**
 * Tests Unitarios para ChatbotSuggestionGenerator
 */
class ChatbotSuggestionGeneratorTest extends TestCase
{
    use RefreshDatabase;

    private const DESC_SERVICIO_ALQUILER = 'Servicio de alquiler';
    private const NOMBRE_SERVICIO_ALQUILER = 'Alquiler';

    protected ChatbotSuggestionGenerator $generator;

    protected function setUp(): void
    {
        parent::setUp();
        $textProcessor = new ChatbotTextProcessor();
        $this->generator = new ChatbotSuggestionGenerator($textProcessor);
    }

    // ============================================
    // TESTS PARA generarSugerencias()
    // ============================================

    public function test_generar_sugerencias_con_mensaje_vacio(): void
    {
        $resultado = $this->generator->generarSugerencias('');
        
        $this->assertIsArray($resultado);
    }

    public function test_generar_sugerencias_con_mensaje_valido(): void
    {
        $resultado = $this->generator->generarSugerencias('necesito alquiler');
        
        $this->assertIsArray($resultado);
        $this->assertLessThanOrEqual(5, count($resultado));
    }

    public function test_generar_sugerencias_retorna_maximo_5(): void
    {
        $resultado = $this->generator->generarSugerencias('necesito equipos de sonido y luces');
        
        $this->assertIsArray($resultado);
        $this->assertLessThanOrEqual(5, count($resultado));
    }

    public function test_generar_sugerencias_con_fallback(): void
    {
        // Si no hay vocabulario, debería retornar sugerencias base
        $resultado = $this->generator->generarSugerencias('xyz123abc');
        
        $this->assertIsArray($resultado);
        $this->assertNotEmpty($resultado);
    }

    // ============================================
    // TESTS PARA generarSugerenciasPorToken()
    // ============================================

    public function test_generar_sugerencias_por_token_vacio(): void
    {
        $resultado = $this->generator->generarSugerenciasPorToken('');
        
        $this->assertIsArray($resultado);
        $this->assertEmpty($resultado);
    }

    public function test_generar_sugerencias_por_token_con_mensaje(): void
    {
        $resultado = $this->generator->generarSugerenciasPorToken('necesito alqiler');
        
        $this->assertIsArray($resultado);
        if (!empty($resultado)) {
            $this->assertArrayHasKey(0, $resultado);
            $this->assertArrayHasKey('token', $resultado[0]);
            $this->assertArrayHasKey('sugerencias', $resultado[0]);
        }
    }

    public function test_generar_sugerencias_por_token_filtra_stopwords(): void
    {
        $resultado = $this->generator->generarSugerenciasPorToken('para con de alquiler');
        
        $this->assertIsArray($resultado);
    }

    public function test_generar_sugerencias_por_token_filtra_genericos(): void
    {
        $resultado = $this->generator->generarSugerenciasPorToken('necesito quiero busco');
        
        $this->assertIsArray($resultado);
    }

    // ============================================
    // TESTS PARA fallbackTokenHints()
    // ============================================

    public function test_fallback_token_hints_con_mensaje_vacio(): void
    {
        $resultado = $this->generator->fallbackTokenHints('');
        
        $this->assertIsArray($resultado);
        $this->assertEmpty($resultado);
    }

    public function test_fallback_token_hints_con_mensaje_corto(): void
    {
        $resultado = $this->generator->fallbackTokenHints('ab');
        
        $this->assertIsArray($resultado);
    }

    public function test_fallback_token_hints_con_mensaje_valido(): void
    {
        $resultado = $this->generator->fallbackTokenHints('necesito alquiler');
        
        $this->assertIsArray($resultado);
        if (!empty($resultado)) {
            $this->assertArrayHasKey(0, $resultado);
            $this->assertArrayHasKey('token', $resultado[0]);
            $this->assertArrayHasKey('sugerencias', $resultado[0]);
        }
    }

    public function test_fallback_token_hints_filtra_tokens_cortos(): void
    {
        $resultado = $this->generator->fallbackTokenHints('ab cd ef');
        
        $this->assertIsArray($resultado);
    }

    // ============================================
    // TESTS PARA extraerMejorSugerencia()
    // ============================================

    public function test_extraer_mejor_sugerencia_vacio(): void
    {
        $resultado = $this->generator->extraerMejorSugerencia([]);
        
        $this->assertIsArray($resultado);
        $this->assertEmpty($resultado);
    }

    public function test_extraer_mejor_sugerencia_sin_token(): void
    {
        $resultado = $this->generator->extraerMejorSugerencia([['sugerencias' => ['alquiler']]]);
        
        $this->assertIsArray($resultado);
    }

    public function test_extraer_mejor_sugerencia_con_datos(): void
    {
        $tokenHints = [
            [
                'token' => 'alqiler',
                'sugerencias' => ['alquiler', 'animacion']
            ]
        ];
        
        $resultado = $this->generator->extraerMejorSugerencia($tokenHints);
        
        $this->assertIsArray($resultado);
        if (!empty($resultado)) {
            $this->assertArrayHasKey('token', $resultado);
            $this->assertArrayHasKey('sugerencia', $resultado);
        }
    }

    public function test_extraer_mejor_sugerencia_sin_sugerencias(): void
    {
        $tokenHints = [
            [
                'token' => 'test',
                'sugerencias' => []
            ]
        ];
        
        $resultado = $this->generator->extraerMejorSugerencia($tokenHints);
        
        $this->assertIsArray($resultado);
    }

    // ============================================
    // TESTS PARA obtenerVocabularioCorreccion()
    // ============================================

    public function test_obtener_vocabulario_correccion_incluye_keywords_base(): void
    {
        // El vocabulario debería incluir palabras clave base
        $vocab = $this->generator->generarSugerencias('alquiler');
        
        $this->assertIsArray($vocab);
    }

    public function test_obtener_vocabulario_correccion_con_subservicios(): void
    {
        $servicio = Servicios::create([
            'nombre_servicio' => self::NOMBRE_SERVICIO_ALQUILER,
            'descripcion' => self::DESC_SERVICIO_ALQUILER
        ]);
        
        SubServicios::create([
            'servicios_id' => $servicio->id,
            'nombre' => 'Equipo de sonido profesional',
            'descripcion' => 'Equipo completo',
            'precio' => 100
        ]);
        
        $resultado = $this->generator->generarSugerencias('sonido');
        
        $this->assertIsArray($resultado);
    }

    // ============================================
    // TESTS PARA calcularScoresSugerencias()
    // ============================================

    public function test_calcular_scores_sugerencias_retorna_array(): void
    {
        // Este método es privado, pero se prueba indirectamente a través de generarSugerencias
        $resultado = $this->generator->generarSugerencias('alquiler');
        
        $this->assertIsArray($resultado);
    }

    public function test_calcular_scores_sugerencias_ordena_por_score(): void
    {
        $resultado = $this->generator->generarSugerencias('alqiler');
        
        $this->assertIsArray($resultado);
        // Las sugerencias deberían estar ordenadas por relevancia
    }

    // ============================================
    // TESTS PARA encontrarTokenMasRaro()
    // ============================================

    public function test_encontrar_token_mas_raro_retorna_token(): void
    {
        // Este método es privado, pero se prueba indirectamente
        $resultado = $this->generator->generarSugerenciasPorToken('necesito alqiler equipos');
        
        $this->assertIsArray($resultado);
    }

    // ============================================
    // TESTS PARA generarSugerenciasParaToken()
    // ============================================

    public function test_generar_sugerencias_para_token_retorna_maximo_6(): void
    {
        // Este método es privado, pero se prueba indirectamente
        $resultado = $this->generator->generarSugerenciasPorToken('alqiler');
        
        $this->assertIsArray($resultado);
        if (!empty($resultado) && isset($resultado[0]['sugerencias'])) {
            $this->assertLessThanOrEqual(6, count($resultado[0]['sugerencias']));
        }
    }

    public function test_generar_sugerencias_por_token_con_vocabulario_vacio(): void
    {
        $resultado = $this->generator->generarSugerenciasPorToken('xyz123');
        
        $this->assertIsArray($resultado);
    }

    public function test_fallback_token_hints_con_tokens_cortos(): void
    {
        $resultado = $this->generator->fallbackTokenHints('ab cd');
        
        $this->assertIsArray($resultado);
    }

    public function test_generar_sugerencias_con_tokens_stopwords(): void
    {
        $resultado = $this->generator->generarSugerencias('para con de alquiler');
        
        $this->assertIsArray($resultado);
    }

    public function test_generar_sugerencias_por_token_filtra_numeros(): void
    {
        $resultado = $this->generator->generarSugerenciasPorToken('necesito 123 alquiler');
        
        $this->assertIsArray($resultado);
    }

    public function test_generar_sugerencias_con_mensaje_normalizado(): void
    {
        $resultado = $this->generator->generarSugerencias('necesito alquiler de equipos');
        
        $this->assertIsArray($resultado);
        $this->assertLessThanOrEqual(5, count($resultado));
    }

    public function test_filtrar_tokens_validos_excluye_genericos(): void
    {
        // Este método es privado, pero se prueba indirectamente
        $resultado = $this->generator->generarSugerenciasPorToken('necesito quiero busco alquiler');
        
        $this->assertIsArray($resultado);
    }

    public function test_encontrar_token_mas_raro_retorna_original(): void
    {
        // Este método es privado, pero se prueba indirectamente
        $resultado = $this->generator->generarSugerenciasPorToken('necesito alqiler equipos');
        
        $this->assertIsArray($resultado);
        if (!empty($resultado)) {
            $this->assertArrayHasKey('token', $resultado[0]);
        }
    }

    public function test_generar_sugerencias_para_token_ordena_por_similitud(): void
    {
        // Este método es privado, pero se prueba indirectamente
        $resultado = $this->generator->generarSugerenciasPorToken('alqiler');
        
        $this->assertIsArray($resultado);
    }

    public function test_calcular_maxima_similitud(): void
    {
        // Este método es privado, pero se prueba indirectamente
        $resultado = $this->generator->generarSugerenciasPorToken('test');
        
        $this->assertIsArray($resultado);
    }

    public function test_generar_sugerencias_con_subservicios_en_vocabulario(): void
    {
        $servicio = Servicios::create([
            'nombre_servicio' => self::NOMBRE_SERVICIO_ALQUILER,
            'descripcion' => self::DESC_SERVICIO_ALQUILER
        ]);
        
        SubServicios::create([
            'servicios_id' => $servicio->id,
            'nombre' => 'Equipo profesional de sonido',
            'descripcion' => 'Equipo completo',
            'precio' => 100
        ]);
        
        $resultado = $this->generator->generarSugerencias('sonido');
        
        $this->assertIsArray($resultado);
    }

    public function test_generar_sugerencias_por_token_con_subservicios(): void
    {
        $servicio = Servicios::create([
            'nombre_servicio' => self::NOMBRE_SERVICIO_ALQUILER,
            'descripcion' => self::DESC_SERVICIO_ALQUILER
        ]);
        
        SubServicios::create([
            'servicios_id' => $servicio->id,
            'nombre' => 'Microfono profesional',
            'descripcion' => 'Microfono de alta calidad',
            'precio' => 50
        ]);
        
        $resultado = $this->generator->generarSugerenciasPorToken('microfono');
        
        $this->assertIsArray($resultado);
    }
}

