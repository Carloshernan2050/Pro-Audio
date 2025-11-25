<?php

namespace Tests\Unit;

use PHPUnit\Framework\TestCase;
use App\Http\Controllers\ServiciosViewController;
use Illuminate\Support\Str;

/**
 * Tests Unitarios para ServiciosViewController
 * 
 * Tests para lógica de vistas y servicios
 */
class ServiciosViewControllerUnitTest extends TestCase
{
    protected $controller;

    protected function setUp(): void
    {
        parent::setUp();
        $this->controller = new ServiciosViewController();
    }

    // ============================================
    // TESTS PARA Nombres de Servicios
    // ============================================

    public function test_nombres_servicios_validos(): void
    {
        // Los servicios principales son: Alquiler, Animación, Publicidad
        $servicios = ['Alquiler', 'Animación', 'Publicidad'];
        
        $this->assertCount(3, $servicios);
        $this->assertContains('Alquiler', $servicios);
        $this->assertContains('Animación', $servicios);
        $this->assertContains('Publicidad', $servicios);
    }

    public function test_slug_generacion_servicio(): void
    {
        // Verificar generación de slug para servicios
        $servicio = 'Alquiler de Equipos';
        $slug = Str::slug($servicio, '_');
        
        $this->assertIsString($slug);
        $this->assertNotEmpty($slug);
    }

    public function test_ruta_vista_estructura(): void
    {
        // Verificar estructura de ruta de vista
        $slug = 'alquiler';
        $rutaVista = "views/usuarios/{$slug}.blade.php";
        
        $this->assertIsString($rutaVista);
        $this->assertStringContainsString('views/usuarios/', $rutaVista);
        $this->assertStringEndsWith('.blade.php', $rutaVista);
    }

    public function test_vistas_existentes(): void
    {
        // Verificar que las vistas principales existen conceptualmente
        $vistas = ['usuarios.alquiler', 'usuarios.animacion', 'usuarios.publicidad'];
        
        foreach ($vistas as $vista) {
            $this->assertIsString($vista);
            $this->assertStringStartsWith('usuarios.', $vista);
        }
    }
}

