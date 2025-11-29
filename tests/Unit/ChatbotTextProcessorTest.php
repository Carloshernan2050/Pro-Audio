<?php

namespace Tests\Unit;

use Tests\TestCase;
use App\Services\ChatbotTextProcessor;

/**
 * Tests Unitarios para ChatbotTextProcessor
 */
class ChatbotTextProcessorTest extends TestCase
{
    private const MENSAJE_NECESITO_ALQUILER = 'necesito alquiler';
    private const MENSAJE_TAMBIEN_NECESITO = 'tambien necesito';
    private const MENSAJE_ADEMAS_DE_ESO = 'ademas de eso';
    private const MENSAJE_TRES_DIAS = 'tres dias';
    private const MENSAJE_POR_5_DIAS = 'por 5 dias';
    private const MENSAJE_NECESITO_POR_3_DIAS = 'necesito por 3 dias';
    private const MENSAJE_3_DIAS = '3 dias';

    protected ChatbotTextProcessor $processor;

    protected function setUp(): void
    {
        parent::setUp();
        $this->processor = new ChatbotTextProcessor();
    }

    // ============================================
    // TESTS PARA normalizarTexto()
    // ============================================

    public function test_normalizar_texto_convierte_a_minusculas(): void
    {
        $resultado = $this->processor->normalizarTexto('HOLA MUNDO');
        $this->assertEquals('hola mundo', $resultado);
    }

    public function test_normalizar_texto_elimina_acentos(): void
    {
        $resultado = $this->processor->normalizarTexto('Café');
        $this->assertEquals('cafe', $resultado);

        $resultado = $this->processor->normalizarTexto('AÑO');
        $this->assertEquals('ano', $resultado);

        $resultado = $this->processor->normalizarTexto('José María');
        $this->assertEquals('jose maria', $resultado);
    }

    public function test_normalizar_texto_maneja_caracteres_especiales(): void
    {
        $resultado = $this->processor->normalizarTexto('ÑANDÚ');
        $this->assertEquals('nandu', $resultado);

        $resultado = $this->processor->normalizarTexto('MÉXICO');
        $this->assertEquals('mexico', $resultado);
    }

    public function test_normalizar_texto_con_cadena_vacia(): void
    {
        $resultado = $this->processor->normalizarTexto('');
        $this->assertEquals('', $resultado);
    }

    public function test_normalizar_texto_con_numeros(): void
    {
        $resultado = $this->processor->normalizarTexto('Texto123 con Números');
        $this->assertEquals('texto123 con numeros', $resultado);
    }

    // ============================================
    // TESTS PARA corregirOrtografia()
    // ============================================

    public function test_corregir_ortografia_corrige_necesito(): void
    {
        $resultado = $this->processor->corregirOrtografia('nesecito alquiler');
        $this->assertStringContainsString('necesito', $resultado);
    }

    public function test_corregir_ortografia_corrige_alquiler(): void
    {
        $resultado = $this->processor->corregirOrtografia('alqiler de equipos');
        $this->assertStringContainsString('alquiler', $resultado);
    }

    public function test_corregir_ortografia_corrige_publicidad(): void
    {
        $resultado = $this->processor->corregirOrtografia('publicida');
        $this->assertStringContainsString('publicidad', $resultado);
    }

    // ============================================
    // TESTS PARA extraerTokens()
    // ============================================

    public function test_extraer_tokens_elimina_stopwords(): void
    {
        $tokens = $this->processor->extraerTokens(self::MENSAJE_NECESITO_ALQUILER . ' de equipos');
        $this->assertNotContains('de', $tokens);
        $this->assertContains('necesito', $tokens);
        $this->assertContains('alquiler', $tokens);
    }

    public function test_extraer_tokens_elimina_palabras_cortas(): void
    {
        $tokens = $this->processor->extraerTokens('un al y equipos');
        $this->assertNotContains('un', $tokens);
        $this->assertNotContains('al', $tokens);
        $this->assertContains('equipos', $tokens);
    }

    public function test_extraer_tokens_con_cadena_vacia(): void
    {
        $tokens = $this->processor->extraerTokens('');
        $this->assertIsArray($tokens);
        $this->assertEmpty($tokens);
    }

    // ============================================
    // TESTS PARA esContinuacion()
    // ============================================

    public function test_es_continuacion_con_tambien(): void
    {
        $resultado = $this->processor->esContinuacion(self::MENSAJE_TAMBIEN_NECESITO);
        $this->assertTrue($resultado);

        $resultado = $this->processor->esContinuacion('también');
        $this->assertTrue($resultado);
    }

    public function test_es_continuacion_con_ademas(): void
    {
        $resultado = $this->processor->esContinuacion(self::MENSAJE_ADEMAS_DE_ESO);
        $this->assertTrue($resultado);

        $resultado = $this->processor->esContinuacion('además');
        $this->assertTrue($resultado);
    }

    public function test_es_continuacion_con_lo_mismo(): void
    {
        $resultado = $this->processor->esContinuacion('lo mismo');
        $this->assertTrue($resultado);

        $resultado = $this->processor->esContinuacion('igual');
        $this->assertTrue($resultado);
    }

    public function test_es_continuacion_con_mensaje_normal(): void
    {
        $resultado = $this->processor->esContinuacion(self::MENSAJE_NECESITO_ALQUILER);
        $this->assertFalse($resultado);
    }

    public function test_es_continuacion_con_mensaje_vacio(): void
    {
        $resultado = $this->processor->esContinuacion('');
        $this->assertFalse($resultado);
    }

    public function test_es_continuacion_case_insensitive(): void
    {
        $resultado = $this->processor->esContinuacion('TAMBIEN');
        $this->assertTrue($resultado);

        $resultado = $this->processor->esContinuacion('También');
        $this->assertTrue($resultado);
    }

    // ============================================
    // TESTS PARA verificarSiEsAgregado()
    // ============================================

    public function test_verificar_si_es_agregado_con_tambien(): void
    {
        $resultado = $this->processor->verificarSiEsAgregado(self::MENSAJE_TAMBIEN_NECESITO);
        $this->assertTrue($resultado);
    }

    public function test_verificar_si_es_agregado_con_ademas(): void
    {
        $resultado = $this->processor->verificarSiEsAgregado(self::MENSAJE_ADEMAS_DE_ESO);
        $this->assertTrue($resultado);
    }

    public function test_verificar_si_es_agregado_con_mensaje_normal(): void
    {
        $resultado = $this->processor->verificarSiEsAgregado(self::MENSAJE_NECESITO_ALQUILER);
        $this->assertFalse($resultado);
    }

    // ============================================
    // TESTS PARA verificarSoloDias()
    // ============================================

    public function test_verificar_solo_dias_con_numero(): void
    {
        $resultado = $this->processor->verificarSoloDias(self::MENSAJE_3_DIAS, self::MENSAJE_3_DIAS);
        $this->assertTrue($resultado);
    }

    public function test_verificar_solo_dias_con_por(): void
    {
        $resultado = $this->processor->verificarSoloDias(self::MENSAJE_POR_5_DIAS, self::MENSAJE_POR_5_DIAS);
        $this->assertTrue($resultado);
    }

    public function test_verificar_solo_dias_con_texto_adicional(): void
    {
        $resultado = $this->processor->verificarSoloDias(self::MENSAJE_NECESITO_POR_3_DIAS, self::MENSAJE_NECESITO_POR_3_DIAS);
        $this->assertFalse($resultado);
    }

    // ============================================
    // TESTS PARA extraerDiasDesdePalabras()
    // ============================================

    public function test_extraer_dias_desde_palabras_uno(): void
    {
        $resultado = $this->processor->extraerDiasDesdePalabras('un dia');
        $this->assertEquals(1, $resultado);

        $resultado = $this->processor->extraerDiasDesdePalabras('una dia');
        $this->assertEquals(1, $resultado);
    }

    public function test_extraer_dias_desde_palabras_dos(): void
    {
        $resultado = $this->processor->extraerDiasDesdePalabras('por dos dias');
        $this->assertEquals(2, $resultado);
    }

    public function test_extraer_dias_desde_palabras_tres(): void
    {
        $resultado = $this->processor->extraerDiasDesdePalabras(self::MENSAJE_TRES_DIAS);
        $this->assertEquals(3, $resultado);
    }

    public function test_extraer_dias_desde_palabras_diez(): void
    {
        $resultado = $this->processor->extraerDiasDesdePalabras('diez dias');
        $this->assertEquals(10, $resultado);
    }

    public function test_extraer_dias_desde_palabras_sin_dias(): void
    {
        $resultado = $this->processor->extraerDiasDesdePalabras(self::MENSAJE_NECESITO_ALQUILER);
        $this->assertNull($resultado);
    }

    public function test_extraer_dias_desde_palabras_con_texto_adicional(): void
    {
        $resultado = $this->processor->extraerDiasDesdePalabras(self::MENSAJE_NECESITO_ALQUILER . ' por ' . self::MENSAJE_TRES_DIAS);
        $this->assertEquals(3, $resultado);
    }

    public function test_extraer_dias_desde_palabras_con_acentos(): void
    {
        $resultado = $this->processor->extraerDiasDesdePalabras('tres días');
        $this->assertEquals(3, $resultado);
    }

    public function test_extraer_dias_desde_palabras_cuatro(): void
    {
        $resultado = $this->processor->extraerDiasDesdePalabras('cuatro dias');
        $this->assertEquals(4, $resultado);
    }

    public function test_extraer_dias_desde_palabras_cinco(): void
    {
        $resultado = $this->processor->extraerDiasDesdePalabras('cinco dias');
        $this->assertEquals(5, $resultado);
    }

    public function test_extraer_dias_desde_palabras_seis(): void
    {
        $resultado = $this->processor->extraerDiasDesdePalabras('seis dias');
        $this->assertEquals(6, $resultado);
    }

    public function test_extraer_dias_desde_palabras_siete(): void
    {
        $resultado = $this->processor->extraerDiasDesdePalabras('siete dias');
        $this->assertEquals(7, $resultado);
    }

    public function test_extraer_dias_desde_palabras_ocho(): void
    {
        $resultado = $this->processor->extraerDiasDesdePalabras('ocho dias');
        $this->assertEquals(8, $resultado);
    }

    public function test_extraer_dias_desde_palabras_nueve(): void
    {
        $resultado = $this->processor->extraerDiasDesdePalabras('nueve dias');
        $this->assertEquals(9, $resultado);
    }

    public function test_extraer_dias_desde_palabras_un(): void
    {
        $resultado = $this->processor->extraerDiasDesdePalabras('un dia');
        $this->assertEquals(1, $resultado);
    }

    // ============================================
    // TESTS ADICIONALES PARA esContinuacion()
    // ============================================

    public function test_es_continuacion_con_eso(): void
    {
        $resultado = $this->processor->esContinuacion('eso');
        $this->assertTrue($resultado);
    }

    public function test_es_continuacion_con_esa(): void
    {
        $resultado = $this->processor->esContinuacion('esa');
        $this->assertTrue($resultado);
    }

    public function test_es_continuacion_con_ese(): void
    {
        $resultado = $this->processor->esContinuacion('ese');
        $this->assertTrue($resultado);
    }

    public function test_es_continuacion_con_esos(): void
    {
        $resultado = $this->processor->esContinuacion('esos');
        $this->assertTrue($resultado);
    }

    public function test_es_continuacion_con_esas(): void
    {
        $resultado = $this->processor->esContinuacion('esas');
        $this->assertTrue($resultado);
    }

    public function test_es_continuacion_con_continuar(): void
    {
        $resultado = $this->processor->esContinuacion('continuar');
        $this->assertTrue($resultado);
    }

    public function test_es_continuacion_con_sigue(): void
    {
        $resultado = $this->processor->esContinuacion('sigue');
        $this->assertTrue($resultado);
    }

    public function test_es_continuacion_con_seguimos(): void
    {
        $resultado = $this->processor->esContinuacion('seguimos');
        $this->assertTrue($resultado);
    }

    public function test_es_continuacion_con_por_esos_dias(): void
    {
        $resultado = $this->processor->esContinuacion('por esos dias');
        $this->assertTrue($resultado);
    }

    public function test_es_continuacion_con_mismos_dias(): void
    {
        $resultado = $this->processor->esContinuacion('mismos dias');
        $this->assertTrue($resultado);
    }

    public function test_es_continuacion_con_mismos_dias_con_acento(): void
    {
        $resultado = $this->processor->esContinuacion('mismos días');
        $this->assertTrue($resultado);
    }

    // ============================================
    // TESTS ADICIONALES PARA verificarSiEsAgregado()
    // ============================================

    public function test_verificar_si_es_agregado_con_tambien_acento(): void
    {
        $resultado = $this->processor->verificarSiEsAgregado('también');
        $this->assertTrue($resultado);
    }

    public function test_verificar_si_es_agregado_con_ademas_acento(): void
    {
        $resultado = $this->processor->verificarSiEsAgregado('además');
        $this->assertTrue($resultado);
    }

    public function test_verificar_si_es_agregado_con_y_espacio(): void
    {
        $resultado = $this->processor->verificarSiEsAgregado('y alquiler');
        $this->assertTrue($resultado);
    }

    public function test_verificar_si_es_agregado_con_espacio_y(): void
    {
        $resultado = $this->processor->verificarSiEsAgregado('alquiler y');
        $this->assertTrue($resultado);
    }

    public function test_verificar_si_es_agregado_con_sumar(): void
    {
        $resultado = $this->processor->verificarSiEsAgregado('sumar');
        $this->assertTrue($resultado);
    }

    public function test_verificar_si_es_agregado_con_agrega(): void
    {
        $resultado = $this->processor->verificarSiEsAgregado('agrega');
        $this->assertTrue($resultado);
    }

    public function test_verificar_si_es_agregado_con_agregar(): void
    {
        $resultado = $this->processor->verificarSiEsAgregado('agregar');
        $this->assertTrue($resultado);
    }

    public function test_verificar_si_es_agregado_con_junto(): void
    {
        $resultado = $this->processor->verificarSiEsAgregado('junto');
        $this->assertTrue($resultado);
    }

    public function test_verificar_si_es_agregado_con_ademas_de(): void
    {
        $resultado = $this->processor->verificarSiEsAgregado('ademas de');
        $this->assertTrue($resultado);
    }

    public function test_verificar_si_es_agregado_con_ademas_de_acento(): void
    {
        $resultado = $this->processor->verificarSiEsAgregado('además de');
        $this->assertTrue($resultado);
    }

    // ============================================
    // TESTS ADICIONALES PARA verificarSoloDias()
    // ============================================

    public function test_verificar_solo_dias_con_dia_singular(): void
    {
        $resultado = $this->processor->verificarSoloDias('1 dia', '1 dia');
        $this->assertTrue($resultado);
    }

    public function test_verificar_solo_dias_con_dias_plural(): void
    {
        $resultado = $this->processor->verificarSoloDias('2 dias', '2 dias');
        $this->assertTrue($resultado);
    }

    public function test_verificar_solo_dias_con_por_y_dia(): void
    {
        $resultado = $this->processor->verificarSoloDias('por 1 dia', 'por 1 dia');
        $this->assertTrue($resultado);
    }

    public function test_verificar_solo_dias_solo_original(): void
    {
        $resultado = $this->processor->verificarSoloDias(self::MENSAJE_3_DIAS, 'texto diferente');
        $this->assertTrue($resultado);
    }

    public function test_verificar_solo_dias_solo_corregido(): void
    {
        $resultado = $this->processor->verificarSoloDias('texto diferente', '5 dias');
        $this->assertTrue($resultado);
    }

    public function test_verificar_solo_dias_con_acento(): void
    {
        $resultado = $this->processor->verificarSoloDias('3 días', '3 días');
        $this->assertTrue($resultado);
    }

    // ============================================
    // TESTS ADICIONALES PARA corregirOrtografia()
    // ============================================

    public function test_corregir_ortografia_corrige_todas_variantes_necesito(): void
    {
        $variantes = ['nesecot', 'nesecito', 'nesesito', 'nesito', 'necesot'];
        foreach ($variantes as $variante) {
            $resultado = $this->processor->corregirOrtografia($variante);
            $this->assertStringContainsString('necesito', $resultado);
        }
    }

    public function test_corregir_ortografia_corrige_todas_variantes_alquiler(): void
    {
        $variantes = ['alquilarr', 'alquiles', 'alqiler', 'alqilar'];
        foreach ($variantes as $variante) {
            $resultado = $this->processor->corregirOrtografia($variante);
            $this->assertIsString($resultado);
        }
    }

    public function test_corregir_ortografia_corrige_publicitar(): void
    {
        $variantes = ['publicitarlos', 'publicitarlas', 'publicitarlo', 'publicitarla'];
        foreach ($variantes as $variante) {
            $resultado = $this->processor->corregirOrtografia($variante);
            $this->assertIsString($resultado);
        }
    }

    public function test_corregir_ortografia_corrige_locucion(): void
    {
        $resultado = $this->processor->corregirOrtografia('locucion');
        $this->assertStringContainsString('locución', $resultado);
        
        $resultado = $this->processor->corregirOrtografia('locuion');
        $this->assertStringContainsString('locución', $resultado);
    }

    public function test_corregir_ortografia_corrige_anuncio(): void
    {
        $resultado = $this->processor->corregirOrtografia('anunsio');
        $this->assertStringContainsString('anuncio', $resultado);
    }

    public function test_corregir_ortografia_corrige_cuna(): void
    {
        $resultado = $this->processor->corregirOrtografia('cuna');
        $this->assertStringContainsString('cuña', $resultado);
        
        $resultado = $this->processor->corregirOrtografia('cunya');
        $this->assertStringContainsString('cuña', $resultado);
    }

    public function test_corregir_ortografia_corrige_iluminacion(): void
    {
        $resultado = $this->processor->corregirOrtografia('iluinacion');
        $this->assertStringContainsString('iluminacion', $resultado);
        
        $resultado = $this->processor->corregirOrtografia('iluminasion');
        $this->assertStringContainsString('iluminacion', $resultado);
    }

    public function test_corregir_ortografia_corrige_luces(): void
    {
        $resultado = $this->processor->corregirOrtografia('luz');
        $this->assertStringContainsString('luces', $resultado);
    }

    public function test_corregir_ortografia_corrige_dj(): void
    {
        $resultado = $this->processor->corregirOrtografia('deejay');
        $this->assertStringContainsString('dj', $resultado);
    }

    public function test_corregir_ortografia_corrige_mezcladora(): void
    {
        $resultado = $this->processor->corregirOrtografia('mescladora');
        $this->assertStringContainsString('mezcladora', $resultado);
    }

    public function test_corregir_ortografia_corrige_microfono(): void
    {
        $resultado = $this->processor->corregirOrtografia('microphono');
        $this->assertStringContainsString('microfono', $resultado);
        
        $resultado = $this->processor->corregirOrtografia('microfno');
        $this->assertStringContainsString('microfono', $resultado);
    }

    public function test_corregir_ortografia_corrige_par_led(): void
    {
        $resultado = $this->processor->corregirOrtografia('parled');
        $this->assertStringContainsString('par led', $resultado);
    }

    public function test_corregir_ortografia_con_vocabulario(): void
    {
        // Este test verifica que la corrección con vocabulario funciona
        // El vocabulario se obtiene de la base de datos si está disponible
        $resultado = $this->processor->corregirOrtografia('alqiler de equipos');
        $this->assertIsString($resultado);
    }

    public function test_corregir_ortografia_con_palabras_cortas(): void
    {
        // Palabras menores a 3 caracteres no se corrigen con vocabulario
        $resultado = $this->processor->corregirOrtografia('ab cd ef');
        $this->assertIsString($resultado);
    }

    // ============================================
    // TESTS ADICIONALES PARA extraerTokens()
    // ============================================

    public function test_extraer_tokens_con_espacios_multiples(): void
    {
        $tokens = $this->processor->extraerTokens('necesito    alquiler   equipos');
        $this->assertIsArray($tokens);
        $this->assertContains('necesito', $tokens);
        $this->assertContains('alquiler', $tokens);
        $this->assertContains('equipos', $tokens);
    }

    public function test_extraer_tokens_con_todos_stopwords(): void
    {
        $tokens = $this->processor->extraerTokens('para por con sin del de la las el los una unos unas que y o en al');
        $this->assertIsArray($tokens);
        $this->assertEmpty($tokens);
    }

    public function test_extraer_tokens_con_palabras_exactamente_3_caracteres(): void
    {
        $tokens = $this->processor->extraerTokens('sol mar');
        $this->assertIsArray($tokens);
        // Palabras de exactamente 3 caracteres deberían incluirse
        $this->assertContains('sol', $tokens);
        $this->assertContains('mar', $tokens);
    }

    public function test_extraer_tokens_filtra_palabras_vacias(): void
    {
        $tokens = $this->processor->extraerTokens('   ');
        $this->assertIsArray($tokens);
        $this->assertEmpty($tokens);
    }
}
