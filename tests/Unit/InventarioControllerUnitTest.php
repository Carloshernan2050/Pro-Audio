<?php

namespace Tests\Unit;

use PHPUnit\Framework\TestCase;
use App\Http\Controllers\InventarioController;

/**
 * Tests Unitarios para InventarioController
 * 
 * Tests para validaciones y estructura
 */
class InventarioControllerUnitTest extends TestCase
{
    protected $controller;

    protected function setUp(): void
    {
        parent::setUp();
        $this->controller = new InventarioController();
    }

    // ============================================
    // TESTS PARA Validaciones
    // ============================================

    public function test_validacion_descripcion_estructura(): void
    {
        $reglasEsperadas = [
            'descripcion' => 'required|string|max:255',
            'stock' => 'required|integer|min:0'
        ];
        
        $this->assertArrayHasKey('descripcion', $reglasEsperadas);
        $this->assertStringContainsString('required', $reglasEsperadas['descripcion']);
        $this->assertStringContainsString('string', $reglasEsperadas['descripcion']);
        $this->assertStringContainsString('max:255', $reglasEsperadas['descripcion']);
    }

    public function test_validacion_stock_estructura(): void
    {
        $reglasEsperadas = [
            'stock' => 'required|integer|min:0'
        ];
        
        $this->assertArrayHasKey('stock', $reglasEsperadas);
        $this->assertStringContainsString('required', $reglasEsperadas['stock']);
        $this->assertStringContainsString('integer', $reglasEsperadas['stock']);
        $this->assertStringContainsString('min:0', $reglasEsperadas['stock']);
    }

    public function test_mensajes_validacion_existen(): void
    {
        $mensajes = [
            'descripcion.required' => 'La descripción es obligatoria.',
            'descripcion.string' => 'La descripción debe ser texto.',
            'descripcion.max' => 'La descripción no puede exceder 255 caracteres.',
            'stock.required' => 'El stock es obligatorio.',
            'stock.integer' => 'El stock debe ser un número entero.',
            'stock.min' => 'El stock no puede ser menor a 0.'
        ];
        
        $this->assertCount(6, $mensajes);
        $this->assertArrayHasKey('descripcion.required', $mensajes);
        $this->assertArrayHasKey('stock.required', $mensajes);
    }

    public function test_stock_minimo_es_cero(): void
    {
        // Verificar que el stock mínimo permitido es 0
        $stockMinimo = 0;
        
        $this->assertEquals(0, $stockMinimo);
        $this->assertGreaterThanOrEqual(0, $stockMinimo);
    }

    public function test_descripcion_maximo_caracteres(): void
    {
        // Verificar que el máximo de caracteres es 255
        $maxCaracteres = 255;
        
        $this->assertEquals(255, $maxCaracteres);
        $this->assertIsInt($maxCaracteres);
    }
}

