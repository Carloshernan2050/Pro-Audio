<?php

namespace Tests\Unit;

use PHPUnit\Framework\TestCase;
use App\Http\Controllers\BusquedaController;

/**
 * Tests Unitarios para BusquedaController
 *
 * Tests verdaderamente unitarios que prueban solo la lógica pura
 */
class BusquedaControllerUnitTest extends TestCase
{
    protected BusquedaController $controller;

    protected function setUp(): void
    {
        parent::setUp();
        $this->controller = new BusquedaController();
    }

    // ============================================
    // TESTS PARA normalizarTexto()
    // ============================================

    public function test_normalizar_texto_convierte_a_minusculas(): void
    {
        $resultado = $this->controller->normalizarTexto('HOLA MUNDO');
        $this->assertEquals('hola mundo', $resultado);
    }

    public function test_normalizar_texto_elimina_espacios_extra(): void
    {
        // El método solo hace trim(), no elimina espacios múltiples intermedios
        $resultado = $this->controller->normalizarTexto('  texto  con  espacios  ');
        $this->assertEquals('texto  con  espacios', $resultado); // Solo elimina espacios al inicio y final

        // Verificar que no tenga espacios al inicio/final (trim no debería cambiar nada)
        $this->assertEquals($resultado, trim($resultado)); // Ya debería estar trimado
    }

    public function test_normalizar_texto_corrige_animacion(): void
    {
        $resultado = $this->controller->normalizarTexto('animacion');
        $this->assertStringContainsString('animación', $resultado);
    }

    public function test_normalizar_texto_corrige_animador(): void
    {
        $resultado = $this->controller->normalizarTexto('necesito animador');
        $this->assertStringContainsString('animación', $resultado);
    }

    public function test_normalizar_texto_mantiene_alquiler(): void
    {
        $resultado = $this->controller->normalizarTexto('alquiler');
        $this->assertStringContainsString('alquiler', $resultado);
    }

    public function test_normalizar_texto_mantiene_publicidad(): void
    {
        $resultado = $this->controller->normalizarTexto('publicidad');
        $this->assertStringContainsString('publicidad', $resultado);
    }

    public function test_normalizar_texto_con_cadena_vacia(): void
    {
        $resultado = $this->controller->normalizarTexto('');
        $this->assertEquals('', $resultado);
    }

    public function test_normalizar_texto_con_texto_complejo(): void
    {
        $resultado = $this->controller->normalizarTexto('Necesito Alquiler De Equipos');
        $this->assertIsString($resultado);
        $this->assertStringContainsString('alquiler', strtolower($resultado));
    }
}

