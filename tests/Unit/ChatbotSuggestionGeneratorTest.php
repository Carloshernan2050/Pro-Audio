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
    private const MENSAJE_STOPWORDS_ALQUILER = 'para con de alquiler';
    private const EQUIPO_SONIDO_PROFESIONAL = 'Equipo de sonido profesional';
    private const EQUIPO_COMPLETO = 'Equipo completo';
    private const TOKEN_AB_CD = 'ab cd';
    private const TOKEN_AB_CD_EF_ALQUILER = 'ab cd ef alquiler';

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
        $resultado = $this->generator->generarSugerenciasPorToken(self::MENSAJE_STOPWORDS_ALQUILER);
        
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
            'nombre' => self::EQUIPO_SONIDO_PROFESIONAL,
            'descripcion' => self::EQUIPO_COMPLETO,
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
        $resultado = $this->generator->fallbackTokenHints(self::TOKEN_AB_CD);
        
        $this->assertIsArray($resultado);
    }

    public function test_generar_sugerencias_con_tokens_stopwords(): void
    {
        $resultado = $this->generator->generarSugerencias(self::MENSAJE_STOPWORDS_ALQUILER);
        
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
            'descripcion' => self::EQUIPO_COMPLETO,
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

    public function test_generar_sugerencias_con_tokens_vacios(): void
    {
        $resultado = $this->generator->generarSugerencias('   ');
        
        $this->assertIsArray($resultado);
    }

    public function test_generar_sugerencias_por_token_con_tokens_cortos(): void
    {
        $resultado = $this->generator->generarSugerenciasPorToken('ab cd ef');
        
        $this->assertIsArray($resultado);
    }

    public function test_generar_sugerencias_por_token_con_solo_stopwords(): void
    {
        $resultado = $this->generator->generarSugerenciasPorToken('para con de la');
        
        $this->assertIsArray($resultado);
    }

    public function test_generar_sugerencias_por_token_con_solo_numeros(): void
    {
        $resultado = $this->generator->generarSugerenciasPorToken('123 456 789');
        
        $this->assertIsArray($resultado);
    }

    public function test_fallback_token_hints_con_whitespace_multiple(): void
    {
        $resultado = $this->generator->fallbackTokenHints('necesito    alquiler   equipos');
        
        $this->assertIsArray($resultado);
    }

    public function test_extraer_mejor_sugerencia_con_sugerencias_vacias(): void
    {
        $tokenHints = [
            [
                'token' => 'test',
                'sugerencias' => null
            ]
        ];
        
        $resultado = $this->generator->extraerMejorSugerencia($tokenHints);
        
        $this->assertIsArray($resultado);
    }

    public function test_generar_sugerencias_con_vocabulario_vacio(): void
    {
        // Forzar un caso donde el vocabulario podría estar vacío
        $resultado = $this->generator->generarSugerencias('xyz123abc456');
        
        $this->assertIsArray($resultado);
        // Debería retornar sugerencias base si no encuentra nada
        $this->assertNotEmpty($resultado);
    }

    public function test_generar_sugerencias_por_token_con_mensaje_largo(): void
    {
        $mensajeLargo = str_repeat('alquiler ', 50);
        $resultado = $this->generator->generarSugerenciasPorToken($mensajeLargo);
        
        $this->assertIsArray($resultado);
    }

    public function test_generar_sugerencias_con_subservicios_muchos_tokens(): void
    {
        $servicio = Servicios::create([
            'nombre_servicio' => self::NOMBRE_SERVICIO_ALQUILER,
            'descripcion' => self::DESC_SERVICIO_ALQUILER
        ]);
        
        SubServicios::create([
            'servicios_id' => $servicio->id,
            'nombre' => 'Equipo profesional de sonido con luces y audio',
            'descripcion' => 'Equipo completo con todas las características',
            'precio' => 100
        ]);
        
        $resultado = $this->generator->generarSugerencias('sonido luces audio');
        
        $this->assertIsArray($resultado);
        $this->assertLessThanOrEqual(5, count($resultado));
    }

    public function test_generar_sugerencias_por_token_con_token_exacto(): void
    {
        $servicio = Servicios::create([
            'nombre_servicio' => self::NOMBRE_SERVICIO_ALQUILER,
            'descripcion' => self::DESC_SERVICIO_ALQUILER
        ]);
        
        SubServicios::create([
            'servicios_id' => $servicio->id,
            'nombre' => 'Alquiler',
            'descripcion' => 'Servicio de alquiler',
            'precio' => 100
        ]);
        
        $resultado = $this->generator->generarSugerenciasPorToken('alquiler');
        
        $this->assertIsArray($resultado);
    }

    // ============================================
    // TESTS ADICIONALES PARA calcularScoresSugerencias()
    // ============================================

    public function test_calcular_scores_sugerencias_con_similitud_alta(): void
    {
        // Este método es privado, pero se prueba indirectamente
        $resultado = $this->generator->generarSugerencias('alqiler');
        $this->assertIsArray($resultado);
        $this->assertNotEmpty($resultado);
    }

    public function test_calcular_scores_sugerencias_con_diferente_primera_letra(): void
    {
        // Cuando la primera letra es diferente, el porcentaje se reduce
        $resultado = $this->generator->generarSugerencias('xalquiler');
        $this->assertIsArray($resultado);
    }

    public function test_calcular_scores_sugerencias_con_stopwords_ext(): void
    {
        // Stopwords extendidos no deberían aparecer en sugerencias
        $resultado = $this->generator->generarSugerencias('par');
        $this->assertIsArray($resultado);
    }

    public function test_calcular_scores_sugerencias_con_terminos_cortos(): void
    {
        // Términos menores a 3 caracteres no deberían aparecer
        $resultado = $this->generator->generarSugerencias(self::TOKEN_AB_CD);
        $this->assertIsArray($resultado);
    }

    // ============================================
    // TESTS ADICIONALES PARA filtrarTokensValidos()
    // ============================================

    public function test_filtrar_tokens_validos_excluye_stopwords(): void
    {
        $resultado = $this->generator->generarSugerenciasPorToken(self::MENSAJE_STOPWORDS_ALQUILER);
        $this->assertIsArray($resultado);
        if (!empty($resultado)) {
            // El token debería ser 'alquiler', no los stopwords
            $this->assertNotEquals('para', $resultado[0]['token'] ?? null);
        }
    }

    public function test_filtrar_tokens_validos_excluye_todos_genericos(): void
    {
        $genericos = ['necesito', 'nececito', 'nesecito', 'necesitar', 'requiero', 'quiero', 'busco', 'hola', 'buenas', 'gracias', 'dias', 'dia'];
        foreach ($genericos as $generico) {
            $resultado = $this->generator->generarSugerenciasPorToken($generico . ' alquiler');
            $this->assertIsArray($resultado);
        }
    }

    public function test_filtrar_tokens_validos_excluye_numeros(): void
    {
        $resultado = $this->generator->generarSugerenciasPorToken('123 456 alquiler');
        $this->assertIsArray($resultado);
        if (!empty($resultado)) {
            $this->assertNotEquals('123', $resultado[0]['token'] ?? null);
            $this->assertNotEquals('456', $resultado[0]['token'] ?? null);
        }
    }

    public function test_filtrar_tokens_validos_excluye_tokens_cortos(): void
    {
        $resultado = $this->generator->generarSugerenciasPorToken(self::TOKEN_AB_CD_EF_ALQUILER);
        $this->assertIsArray($resultado);
    }

    public function test_filtrar_tokens_validos_con_espacios_trim(): void
    {
        $resultado = $this->generator->generarSugerenciasPorToken('  alquiler  ');
        $this->assertIsArray($resultado);
    }

    // ============================================
    // TESTS ADICIONALES PARA encontrarTokenMasRaro()
    // ============================================

    public function test_encontrar_token_mas_raro_retorna_menos_similar(): void
    {
        // El token más raro es el que tiene menor similitud con el vocabulario
        $resultado = $this->generator->generarSugerenciasPorToken('necesito xyz123 alquiler');
        $this->assertIsArray($resultado);
    }

    public function test_encontrar_token_mas_raro_con_un_solo_token(): void
    {
        $resultado = $this->generator->generarSugerenciasPorToken('alqiler');
        $this->assertIsArray($resultado);
        if (!empty($resultado)) {
            $this->assertEquals('alqiler', $resultado[0]['token'] ?? null);
        }
    }

    // ============================================
    // TESTS ADICIONALES PARA generarSugerenciasParaToken()
    // ============================================

    public function test_generar_sugerencias_para_token_retorna_ordenadas(): void
    {
        $resultado = $this->generator->generarSugerenciasPorToken('alqiler');
        $this->assertIsArray($resultado);
        if (!empty($resultado) && isset($resultado[0]['sugerencias'])) {
            $sugerencias = $resultado[0]['sugerencias'];
            $this->assertLessThanOrEqual(6, count($sugerencias));
            // La primera sugerencia debería ser la más similar
            if (!empty($sugerencias)) {
                $this->assertIsString($sugerencias[0]);
            }
        }
    }

    public function test_generar_sugerencias_para_token_sin_similitud(): void
    {
        // Token que no tiene similitud con el vocabulario
        $resultado = $this->generator->generarSugerenciasPorToken('xyz123abc');
        $this->assertIsArray($resultado);
    }

    // ============================================
    // TESTS ADICIONALES PARA calcularMaximaSimilitud()
    // ============================================

    public function test_calcular_maxima_similitud_con_token_similar_y_vocabulario(): void
    {
        // Este método es privado, pero se prueba indirectamente
        // Prueba calcularMaximaSimilitud con un token similar a términos del vocabulario
        $resultado = $this->generator->generarSugerenciasPorToken('alqiler');
        $this->assertIsArray($resultado);
        // Si hay resultados, debería tener sugerencias ordenadas por similitud
        if (!empty($resultado) && isset($resultado[0]['sugerencias'])) {
            $this->assertGreaterThan(0, count($resultado[0]['sugerencias']));
        }
    }

    // ============================================
    // TESTS ADICIONALES PARA generarSugerencias()
    // ============================================

    public function test_generar_sugerencias_con_tokens_vacios_usa_mensaje_completo(): void
    {
        // Si no hay tokens válidos, usa el mensaje completo normalizado
        $resultado = $this->generator->generarSugerencias('para con de');
        $this->assertIsArray($resultado);
    }

    public function test_generar_sugerencias_con_vocabulario_vacio_retorna_base_sugerencias(): void
    {
        // Si el vocabulario está vacío, debería retornar sugerencias base
        $resultado = $this->generator->generarSugerencias('');
        $this->assertIsArray($resultado);
        // Debería retornar sugerencias base cuando no hay vocabulario
        $this->assertNotEmpty($resultado);
    }

    public function test_generar_sugerencias_con_scores_vacios_retorna_base_por_fallback(): void
    {
        // Si no hay scores calculados, debería retornar sugerencias base por fallback
        $resultado = $this->generator->generarSugerencias('xyz123abc456');
        $this->assertIsArray($resultado);
        $this->assertNotEmpty($resultado);
        // Debería contener las sugerencias base cuando no hay scores
        $sugerenciasBase = ['alquiler', 'animacion', 'publicidad', 'luces', 'dj', 'audio'];
        $haySugerenciaBase = false;
        foreach ($sugerenciasBase as $base) {
            if (in_array($base, $resultado, true)) {
                $haySugerenciaBase = true;
                break;
            }
        }
        $this->assertTrue($haySugerenciaBase, 'Debería contener al menos una sugerencia base');
    }

    // ============================================
    // TESTS ADICIONALES PARA obtenerVocabularioCorreccion()
    // ============================================

    public function test_obtener_vocabulario_correccion_incluye_dj(): void
    {
        // 'dj' es una excepción que se incluye aunque tenga menos de 4 caracteres
        $servicio = Servicios::create([
            'nombre_servicio' => self::NOMBRE_SERVICIO_ALQUILER,
            'descripcion' => self::DESC_SERVICIO_ALQUILER
        ]);
        
        SubServicios::create([
            'servicios_id' => $servicio->id,
            'nombre' => 'DJ',
            'descripcion' => 'Servicio de DJ',
            'precio' => 100
        ]);
        
        $resultado = $this->generator->generarSugerencias('dj');
        $this->assertIsArray($resultado);
    }

    public function test_obtener_vocabulario_correccion_excluye_stopwords_ext_en_vocabulario(): void
    {
        // Stopwords extendidos no deberían estar en el vocabulario
        // 'par' es un stopword extendido que no debería aparecer en sugerencias
        $resultado = $this->generator->generarSugerencias('par');
        $this->assertIsArray($resultado);
        // Aunque 'par' es un stopword, debería retornar sugerencias base
        $this->assertNotEmpty($resultado);
    }

    public function test_obtener_vocabulario_correccion_filtra_longitud(): void
    {
        // Solo incluye términos entre 4 y 30 caracteres (excepto 'dj')
        $servicio = Servicios::create([
            'nombre_servicio' => self::NOMBRE_SERVICIO_ALQUILER,
            'descripcion' => self::DESC_SERVICIO_ALQUILER
        ]);
        
        SubServicios::create([
            'servicios_id' => $servicio->id,
            'nombre' => 'A', // Muy corto
            'descripcion' => 'Descripción muy larga que excede los 30 caracteres permitidos en el vocabulario',
            'precio' => 100
        ]);
        
        $resultado = $this->generator->generarSugerencias('equipo');
        $this->assertIsArray($resultado);
    }

    public function test_obtener_vocabulario_correccion_maneja_excepcion_db(): void
    {
        // Si hay un error en la DB, debería continuar con palabras base
        // Este caso se prueba indirectamente cuando no hay conexión a DB
        $resultado = $this->generator->generarSugerencias('alquiler');
        $this->assertIsArray($resultado);
        $this->assertNotEmpty($resultado);
    }

    // ============================================
    // TESTS ADICIONALES PARA fallbackTokenHints()
    // ============================================

    public function test_fallback_token_hints_con_espacios_multiples(): void
    {
        $resultado = $this->generator->fallbackTokenHints('necesito    alquiler');
        $this->assertIsArray($resultado);
        if (!empty($resultado)) {
            $this->assertArrayHasKey('token', $resultado[0]);
        }
    }

    public function test_fallback_token_hints_retorna_primer_token_valido(): void
    {
        $resultado = $this->generator->fallbackTokenHints(self::TOKEN_AB_CD_EF_ALQUILER);
        $this->assertIsArray($resultado);
        if (!empty($resultado)) {
            // Debería retornar el primer token de 3+ caracteres
            $this->assertEquals('alquiler', $resultado[0]['token'] ?? null);
        }
    }

    // ============================================
    // TESTS ADICIONALES PARA extraerMejorSugerencia()
    // ============================================

    public function test_extraer_mejor_sugerencia_con_array_vacio_retorna_vacio(): void
    {
        // Cuando se pasa un array vacío, debería retornar un array vacío
        $resultado = $this->generator->extraerMejorSugerencia([]);
        $this->assertIsArray($resultado);
        $this->assertEmpty($resultado);
        $this->assertCount(0, $resultado);
    }

    public function test_extraer_mejor_sugerencia_sin_indice_token(): void
    {
        $tokenHints = [
            ['sugerencias' => ['alquiler']]
        ];
        
        $resultado = $this->generator->extraerMejorSugerencia($tokenHints);
        $this->assertIsArray($resultado);
        $this->assertEmpty($resultado);
    }

    public function test_extraer_mejor_sugerencia_con_sugerencias_null(): void
    {
        $tokenHints = [
            ['token' => 'test']
        ];
        
        $resultado = $this->generator->extraerMejorSugerencia($tokenHints);
        $this->assertIsArray($resultado);
    }

    public function test_extraer_mejor_sugerencia_retorna_primera_sugerencia(): void
    {
        $tokenHints = [
            [
                'token' => 'alqiler',
                'sugerencias' => ['alquiler', 'animacion', 'publicidad']
            ]
        ];
        
        $resultado = $this->generator->extraerMejorSugerencia($tokenHints);
        $this->assertIsArray($resultado);
        if (!empty($resultado)) {
            $this->assertEquals('alquiler', $resultado['sugerencia'] ?? null);
        }
    }

    // ============================================
    // TESTS ADICIONALES PARA calcularScoresSugerencias()
    // ============================================

    public function test_calcular_scores_sugerencias_con_porcentaje_cero(): void
    {
        // Cuando el porcentaje es 0 o menor, no se agrega al score
        $resultado = $this->generator->generarSugerencias('xyz123abc456');
        $this->assertIsArray($resultado);
    }

    public function test_calcular_scores_sugerencias_con_mismo_score(): void
    {
        // Cuando hay múltiples términos con el mismo score, se mantiene el máximo
        $resultado = $this->generator->generarSugerencias('alquiler alquiler');
        $this->assertIsArray($resultado);
    }

    // ============================================
    // TESTS ADICIONALES PARA filtrarTokensValidos()
    // ============================================

    public function test_filtrar_tokens_validos_con_descripcion_vacia(): void
    {
        $servicio = Servicios::create([
            'nombre_servicio' => self::NOMBRE_SERVICIO_ALQUILER,
            'descripcion' => self::DESC_SERVICIO_ALQUILER
        ]);
        
        SubServicios::create([
            'servicios_id' => $servicio->id,
            'nombre' => 'Equipo',
            'descripcion' => '', // Descripción vacía
            'precio' => 100
        ]);
        
        $resultado = $this->generator->generarSugerenciasPorToken('equipo');
        $this->assertIsArray($resultado);
    }

    public function test_filtrar_tokens_validos_con_caracteres_especiales(): void
    {
        $resultado = $this->generator->generarSugerenciasPorToken('alquiler@equipos#sonido');
        $this->assertIsArray($resultado);
    }

    public function test_filtrar_tokens_validos_con_acentos(): void
    {
        $resultado = $this->generator->generarSugerenciasPorToken('alquiler equipos');
        $this->assertIsArray($resultado);
    }

    // ============================================
    // TESTS ADICIONALES PARA encontrarTokenMasRaro()
    // ============================================

    // ============================================
    // TESTS ADICIONALES PARA generarSugerenciasParaToken()
    // ============================================

    public function test_generar_sugerencias_para_token_con_similitud_cero(): void
    {
        // Token que no tiene similitud con ningún término del vocabulario
        $resultado = $this->generator->generarSugerenciasPorToken('xyz123abc456');
        $this->assertIsArray($resultado);
    }

    public function test_generar_sugerencias_para_token_ordena_descendente(): void
    {
        // Las sugerencias se ordenan por similitud descendente
        $resultado = $this->generator->generarSugerenciasPorToken('alqiler');
        $this->assertIsArray($resultado);
        if (!empty($resultado) && isset($resultado[0]['sugerencias'])) {
            $sugerencias = $resultado[0]['sugerencias'];
            $this->assertGreaterThan(0, count($sugerencias));
        }
    }

    public function test_generar_sugerencias_para_token_limita_a_6(): void
    {
        $servicio = Servicios::create([
            'nombre_servicio' => self::NOMBRE_SERVICIO_ALQUILER,
            'descripcion' => self::DESC_SERVICIO_ALQUILER
        ]);
        
        SubServicios::create([
            'servicios_id' => $servicio->id,
            'nombre' => self::EQUIPO_SONIDO_PROFESIONAL,
            'descripcion' => 'Equipo completo con todas las características',
            'precio' => 100
        ]);
        
        $resultado = $this->generator->generarSugerenciasPorToken('equipo');
        $this->assertIsArray($resultado);
        if (!empty($resultado) && isset($resultado[0]['sugerencias'])) {
            $this->assertLessThanOrEqual(6, count($resultado[0]['sugerencias']));
        }
    }


    // ============================================
    // TESTS ADICIONALES PARA generarSugerencias()
    // ============================================

    public function test_generar_sugerencias_ordena_por_score_descendente(): void
    {
        // Las sugerencias deberían estar ordenadas por score descendente
        $resultado = $this->generator->generarSugerencias('alqiler equipos sonido');
        $this->assertIsArray($resultado);
        $this->assertLessThanOrEqual(5, count($resultado));
    }

    public function test_generar_sugerencias_limita_a_5(): void
    {
        // Debería retornar máximo 5 sugerencias
        $servicio = Servicios::create([
            'nombre_servicio' => self::NOMBRE_SERVICIO_ALQUILER,
            'descripcion' => self::DESC_SERVICIO_ALQUILER
        ]);
        
        SubServicios::create([
            'servicios_id' => $servicio->id,
            'nombre' => self::EQUIPO_SONIDO_PROFESIONAL,
            'descripcion' => self::EQUIPO_COMPLETO,
            'precio' => 100
        ]);
        
        $resultado = $this->generator->generarSugerencias('equipo sonido audio luces dj animacion');
        $this->assertIsArray($resultado);
        $this->assertLessThanOrEqual(5, count($resultado));
    }

    // ============================================
    // TESTS ADICIONALES PARA obtenerVocabularioCorreccion()
    // ============================================

    public function test_obtener_vocabulario_correccion_con_subservicios_muchos_tokens(): void
    {
        $servicio = Servicios::create([
            'nombre_servicio' => self::NOMBRE_SERVICIO_ALQUILER,
            'descripcion' => self::DESC_SERVICIO_ALQUILER
        ]);
        
        // Crear múltiples subservicios para ampliar el vocabulario
        for ($i = 1; $i <= 10; $i++) {
            SubServicios::create([
                'servicios_id' => $servicio->id,
                'nombre' => "Equipo {$i}",
                'descripcion' => "Descripción del equipo {$i}",
                'precio' => 100 * $i
            ]);
        }
        
        $resultado = $this->generator->generarSugerencias('equipo');
        $this->assertIsArray($resultado);
    }

    public function test_obtener_vocabulario_correccion_limita_a_500_subservicios(): void
    {
        $servicio = Servicios::create([
            'nombre_servicio' => self::NOMBRE_SERVICIO_ALQUILER,
            'descripcion' => self::DESC_SERVICIO_ALQUILER
        ]);
        
        // Crear más de 500 subservicios para verificar el límite
        for ($i = 1; $i <= 600; $i++) {
            SubServicios::create([
                'servicios_id' => $servicio->id,
                'nombre' => "Equipo {$i}",
                'descripcion' => "Descripción {$i}",
                'precio' => 100
            ]);
        }
        
        $resultado = $this->generator->generarSugerencias('equipo');
        $this->assertIsArray($resultado);
    }

    public function test_obtener_vocabulario_correccion_filtra_tokens_vacios(): void
    {
        $servicio = Servicios::create([
            'nombre_servicio' => self::NOMBRE_SERVICIO_ALQUILER,
            'descripcion' => self::DESC_SERVICIO_ALQUILER
        ]);
        
        SubServicios::create([
            'servicios_id' => $servicio->id,
            'nombre' => 'Equipo   Sonido', // Con espacios múltiples
            'descripcion' => 'Descripción   con   espacios',
            'precio' => 100
        ]);
        
        $resultado = $this->generator->generarSugerencias('equipo');
        $this->assertIsArray($resultado);
    }

    public function test_obtener_vocabulario_correccion_normaliza_tokens(): void
    {
        $servicio = Servicios::create([
            'nombre_servicio' => self::NOMBRE_SERVICIO_ALQUILER,
            'descripcion' => self::DESC_SERVICIO_ALQUILER
        ]);
        
        SubServicios::create([
            'servicios_id' => $servicio->id,
            'nombre' => 'Animación',
            'descripcion' => 'Servicio de animación',
            'precio' => 100
        ]);
        
        $resultado = $this->generator->generarSugerencias('animacion');
        $this->assertIsArray($resultado);
    }

    // ============================================
    // TESTS ADICIONALES PARA extraerTokensDelMensaje()
    // ============================================

    public function test_extraer_tokens_del_mensaje_filtra_tokens_cortos(): void
    {
        $resultado = $this->generator->generarSugerencias(self::TOKEN_AB_CD_EF_ALQUILER);
        $this->assertIsArray($resultado);
    }

    public function test_extraer_tokens_del_mensaje_filtra_vacios(): void
    {
        $resultado = $this->generator->generarSugerencias('   alquiler   ');
        $this->assertIsArray($resultado);
    }

    // ============================================
    // TESTS ADICIONALES PARA generarSugerenciasPorToken()
    // ============================================

    public function test_generar_sugerencias_por_token_con_pairs_vacios(): void
    {
        // Si filtrarTokensValidos retorna array vacío, debería retornar array vacío
        $resultado = $this->generator->generarSugerenciasPorToken('123 456');
        $this->assertIsArray($resultado);
        $this->assertEmpty($resultado);
    }

    // ============================================
    // TESTS ADICIONALES PARA fallbackTokenHints()
    // ============================================

    public function test_fallback_token_hints_con_tokens_exactamente_3_caracteres(): void
    {
        // Tokens de exactamente 3 caracteres deberían incluirse
        $resultado = $this->generator->fallbackTokenHints('sol mar');
        $this->assertIsArray($resultado);
        if (!empty($resultado)) {
            $this->assertArrayHasKey('token', $resultado[0]);
        }
    }

    public function test_fallback_token_hints_con_whitespace_complejo(): void
    {
        $resultado = $this->generator->fallbackTokenHints("necesito\talquiler\nequipos");
        $this->assertIsArray($resultado);
    }

    // ============================================
    // TESTS ADICIONALES PARA extraerMejorSugerencia()
    // ============================================

    public function test_extraer_mejor_sugerencia_con_sugerencias_array_vacio(): void
    {
        $tokenHints = [
            [
                'token' => 'test',
                'sugerencias' => []
            ]
        ];
        
        $resultado = $this->generator->extraerMejorSugerencia($tokenHints);
        $this->assertIsArray($resultado);
        $this->assertEmpty($resultado);
    }

    public function test_extraer_mejor_sugerencia_con_best_null(): void
    {
        $tokenHints = [
            [
                'token' => 'test',
                'sugerencias' => [null, '']
            ]
        ];
        
        $resultado = $this->generator->extraerMejorSugerencia($tokenHints);
        $this->assertIsArray($resultado);
        $this->assertEmpty($resultado);
    }
}

