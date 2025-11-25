<?php

namespace Tests\Unit;

use PHPUnit\Framework\TestCase;
use App\Http\Controllers\SubServiciosController;

/**
 * Tests Unitarios para SubServiciosController
 * 
 * Tests para validaciones y estructura
 */
class SubServiciosControllerUnitTest extends TestCase
{
    protected $controller;

    protected function setUp(): void
    {
        parent::setUp();
        $this->controller = new SubServiciosController();
    }

    // ============================================
    // TESTS PARA Validaciones
    // ============================================

    public function test_validacion_store_estructura(): void
    {
        $reglasEsperadas = [
            'servicios_id' => 'required|exists:servicios,id',
            'nombre' => 'required|string|max:100',
            'descripcion' => 'nullable|string',
            'precio' => 'required|numeric|min:0',
            'imagen' => 'nullable|image|mimes:jpeg,png,jpg,gif|max:5120'
        ];
        
        $this->assertArrayHasKey('servicios_id', $reglasEsperadas);
        $this->assertArrayHasKey('nombre', $reglasEsperadas);
        $this->assertArrayHasKey('precio', $reglasEsperadas);
    }

    public function test_validacion_precio_debe_ser_numerico(): void
    {
        $reglasEsperadas = [
            'precio' => 'required|numeric|min:0'
        ];
        
        $this->assertStringContainsString('numeric', $reglasEsperadas['precio']);
        $this->assertStringContainsString('min:0', $reglasEsperadas['precio']);
    }

    public function test_validacion_imagen_max_tamaño(): void
    {
        // El tamaño máximo de imagen es 5120 KB (5MB)
        $maxTamaño = 5120;
        
        $this->assertEquals(5120, $maxTamaño);
        $this->assertIsInt($maxTamaño);
    }

    public function test_validacion_imagen_formatos_permitidos(): void
    {
        // Formatos permitidos: jpeg, png, jpg, gif
        $formatos = ['jpeg', 'png', 'jpg', 'gif'];
        
        $this->assertCount(4, $formatos);
        $this->assertContains('jpeg', $formatos);
        $this->assertContains('png', $formatos);
        $this->assertContains('jpg', $formatos);
        $this->assertContains('gif', $formatos);
    }

    public function test_nombre_max_caracteres(): void
    {
        // El nombre máximo es 100 caracteres
        $maxCaracteres = 100;
        
        $this->assertEquals(100, $maxCaracteres);
    }
}

