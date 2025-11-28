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
        $resultado = $this->processor->verificarSoloDias('3 dias', '3 dias');
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
}
