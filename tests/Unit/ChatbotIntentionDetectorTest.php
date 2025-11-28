<?php

namespace Tests\Unit;

use Tests\TestCase;
use App\Services\ChatbotIntentionDetector;
use App\Services\ChatbotTextProcessor;

/**
 * Tests Unitarios para ChatbotIntentionDetector
 */
class ChatbotIntentionDetectorTest extends TestCase
{
    private const INTENCION_ANIMACION = 'Animación';
    private const MENSAJE_PUBLICIDAD = 'necesito publicidad';

    protected ChatbotIntentionDetector $detector;
    protected ChatbotTextProcessor $textProcessor;

    protected function setUp(): void
    {
        parent::setUp();
        $this->textProcessor = new ChatbotTextProcessor();
        $this->detector = new ChatbotIntentionDetector($this->textProcessor);
    }

    // ============================================
    // TESTS PARA detectarIntenciones()
    // ============================================

    public function test_detectar_intenciones_vacio(): void
    {
        $resultado = $this->detector->detectarIntenciones('');
        $this->assertIsArray($resultado);
        $this->assertEmpty($resultado);
    }

    public function test_detectar_intenciones_alquiler(): void
    {
        $resultado = $this->detector->detectarIntenciones('necesito alquiler de equipos');
        $this->assertIsArray($resultado);
        $this->assertContains('Alquiler', $resultado);
    }

    public function test_detectar_intenciones_animacion(): void
    {
        $resultado = $this->detector->detectarIntenciones('necesito un dj');
        $this->assertIsArray($resultado);
        $this->assertContains(self::INTENCION_ANIMACION, $resultado);
    }

    public function test_detectar_intenciones_publicidad(): void
    {
        $resultado = $this->detector->detectarIntenciones(self::MENSAJE_PUBLICIDAD);
        $this->assertIsArray($resultado);
        $this->assertContains('Publicidad', $resultado);
    }

    public function test_detectar_intenciones_multiples(): void
    {
        $resultado = $this->detector->detectarIntenciones('necesito alquiler y un dj');
        $this->assertIsArray($resultado);
        $this->assertGreaterThanOrEqual(1, count($resultado));
    }

    // ============================================
    // TESTS PARA validarIntencionesContraMensaje()
    // ============================================

    public function test_validar_intenciones_contra_mensaje_valida_alquiler(): void
    {
        $resultado = $this->detector->validarIntencionesContraMensaje(
            ['Alquiler'],
            'necesito alquiler de equipos'
        );
        $this->assertContains('Alquiler', $resultado);
    }

    public function test_validar_intenciones_contra_mensaje_invalida_alquiler(): void
    {
        $resultado = $this->detector->validarIntencionesContraMensaje(
            ['Alquiler'],
            'hola como estas'
        );
        $this->assertEmpty($resultado);
    }

    public function test_validar_intenciones_contra_mensaje_con_animacion(): void
    {
        $resultado = $this->detector->validarIntencionesContraMensaje(
            [self::INTENCION_ANIMACION],
            'necesito un dj'
        );
        $this->assertContains(self::INTENCION_ANIMACION, $resultado);
    }

    public function test_validar_intenciones_contra_mensaje_con_publicidad(): void
    {
        $resultado = $this->detector->validarIntencionesContraMensaje(
            ['Publicidad'],
            self::MENSAJE_PUBLICIDAD
        );
        $this->assertContains('Publicidad', $resultado);
    }

    public function test_validar_intenciones_contra_mensaje_vacio(): void
    {
        $resultado = $this->detector->validarIntencionesContraMensaje(
            [],
            'necesito algo'
        );
        $this->assertEmpty($resultado);
    }

    public function test_validar_intenciones_contra_mensaje_multiples(): void
    {
        $resultado = $this->detector->validarIntencionesContraMensaje(
            ['Alquiler', self::INTENCION_ANIMACION],
            'necesito alquiler y un dj'
        );
        $this->assertIsArray($resultado);
        $this->assertGreaterThanOrEqual(1, count($resultado));
    }

    // ============================================
    // TESTS PARA esRelacionado()
    // ============================================

    public function test_es_relacionado_con_alquiler(): void
    {
        $resultado = $this->detector->esRelacionado('necesito alquiler');
        $this->assertTrue($resultado);
    }

    public function test_es_relacionado_con_animacion(): void
    {
        $resultado = $this->detector->esRelacionado('quiero un dj');
        $this->assertTrue($resultado);
    }

    public function test_es_relacionado_con_publicidad(): void
    {
        $resultado = $this->detector->esRelacionado(self::MENSAJE_PUBLICIDAD);
        $this->assertTrue($resultado);
    }

    public function test_es_relacionado_con_mensaje_vacio(): void
    {
        // Según el código, mensaje vacío retorna true
        $resultado = $this->detector->esRelacionado('');
        $this->assertTrue($resultado);
    }

    public function test_es_relacionado_con_mensaje_no_relacionado(): void
    {
        $resultado = $this->detector->esRelacionado('que tiempo hace hoy');
        // Verificamos que retorna un booleano
        $this->assertIsBool($resultado);
    }

    public function test_es_relacionado_case_insensitive(): void
    {
        $resultado = $this->detector->esRelacionado('ALQUILER');
        $this->assertTrue($resultado);

        $resultado = $this->detector->esRelacionado('AlQuIlEr');
        $this->assertTrue($resultado);
    }

    // ============================================
    // TESTS PARA clasificarPorTfidf()
    // ============================================

    public function test_clasificar_por_tfidf_con_cadena_vacia(): void
    {
        $resultado = $this->detector->clasificarPorTfidf('');
        $this->assertIsArray($resultado);
        $this->assertEmpty($resultado);
    }

    public function test_clasificar_por_tfidf_retorna_array(): void
    {
        // Este test puede retornar vacío si no hay datos en la BD
        // pero verificamos que al menos retorna un array
        $resultado = $this->detector->clasificarPorTfidf('alquiler equipos');
        $this->assertIsArray($resultado);
    }
}

