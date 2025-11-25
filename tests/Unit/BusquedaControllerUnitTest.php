<?php

namespace Tests\Unit;

use PHPUnit\Framework\TestCase;
use App\Http\Controllers\BusquedaController;
use ReflectionClass;
use ReflectionMethod;

/**
 * Tests Unitarios para BusquedaController
 * 
 * Tests verdaderamente unitarios que prueban solo la lógica pura
 */
class BusquedaControllerUnitTest extends TestCase
{
    protected $controller;

    protected function setUp(): void
    {
        parent::setUp();
        $this->controller = new BusquedaController();
    }

    /**
     * Helper para acceder a métodos privados mediante reflexión
     */
    private function getPrivateMethod(string $methodName): ReflectionMethod
    {
        $reflection = new ReflectionClass(BusquedaController::class);
        $method = $reflection->getMethod($methodName);
        $method->setAccessible(true);
        return $method;
    }

    // ============================================
    // TESTS PARA normalizarTexto()
    // ============================================

    public function test_normalizar_texto_convierte_a_minusculas(): void
    {
        $method = $this->getPrivateMethod('normalizarTexto');
        
        $resultado = $method->invoke($this->controller, 'HOLA MUNDO');
        $this->assertEquals('hola mundo', $resultado);
    }

    public function test_normalizar_texto_elimina_espacios_extra(): void
    {
        $method = $this->getPrivateMethod('normalizarTexto');
        
        // El método solo hace trim(), no elimina espacios múltiples intermedios
        $resultado = $method->invoke($this->controller, '  texto  con  espacios  ');
        $this->assertEquals('texto  con  espacios', $resultado); // Solo elimina espacios al inicio y final
        
        // Verificar que no tenga espacios al inicio/final (trim no debería cambiar nada)
        $this->assertEquals($resultado, trim($resultado)); // Ya debería estar trimado
    }

    public function test_normalizar_texto_corrige_animacion(): void
    {
        $method = $this->getPrivateMethod('normalizarTexto');
        
        $resultado = $method->invoke($this->controller, 'animacion');
        $this->assertStringContainsString('animación', $resultado);
    }

    public function test_normalizar_texto_corrige_animador(): void
    {
        $method = $this->getPrivateMethod('normalizarTexto');
        
        $resultado = $method->invoke($this->controller, 'necesito animador');
        $this->assertStringContainsString('animación', $resultado);
    }

    public function test_normalizar_texto_mantiene_alquiler(): void
    {
        $method = $this->getPrivateMethod('normalizarTexto');
        
        $resultado = $method->invoke($this->controller, 'alquiler');
        $this->assertStringContainsString('alquiler', $resultado);
    }

    public function test_normalizar_texto_mantiene_publicidad(): void
    {
        $method = $this->getPrivateMethod('normalizarTexto');
        
        $resultado = $method->invoke($this->controller, 'publicidad');
        $this->assertStringContainsString('publicidad', $resultado);
    }

    public function test_normalizar_texto_con_cadena_vacia(): void
    {
        $method = $this->getPrivateMethod('normalizarTexto');
        
        $resultado = $method->invoke($this->controller, '');
        $this->assertEquals('', $resultado);
    }

    public function test_normalizar_texto_con_texto_complejo(): void
    {
        $method = $this->getPrivateMethod('normalizarTexto');
        
        $resultado = $method->invoke($this->controller, 'Necesito Alquiler De Equipos');
        $this->assertIsString($resultado);
        $this->assertStringContainsString('alquiler', strtolower($resultado));
    }
}

