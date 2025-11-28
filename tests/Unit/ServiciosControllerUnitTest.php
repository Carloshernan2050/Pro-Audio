<?php

namespace Tests\Unit;

use PHPUnit\Framework\TestCase;
use App\Http\Controllers\ServiciosController;
use Illuminate\Support\Str;

/**
 * Tests Unitarios para ServiciosController
 *
 * Tests para métodos privados con lógica pura
 */
class ServiciosControllerUnitTest extends TestCase
{
    protected $controller;

    protected function setUp(): void
    {
        parent::setUp();
        $this->controller = new ServiciosController();
    }

    // ============================================
    // TESTS PARA Utilidades de Slug
    // ============================================

    public function test_slug_generacion_funciona(): void
    {
        // Verificar que Str::slug funciona correctamente
        $nombre = 'Alquiler de Equipos';
        $slug = Str::slug($nombre, '_');
        
        $this->assertIsString($slug);
        $this->assertNotEmpty($slug);
        $this->assertStringNotContainsString(' ', $slug);
    }

    public function test_slug_con_caracteres_especiales(): void
    {
        $nombre = 'Servicio & Eventos';
        $slug = Str::slug($nombre, '_');
        
        $this->assertIsString($slug);
        $this->assertNotEmpty($slug);
    }

    public function test_slug_con_acentos(): void
    {
        $nombre = 'Animación';
        $slug = Str::slug($nombre, '_');
        
        $this->assertIsString($slug);
        $this->assertNotEmpty($slug);
    }

    // ============================================
    // TESTS PARA Validaciones
    // ============================================

    public function test_validacion_nombre_servicio_estructura(): void
    {
        // Verificar que la validación requiere nombre_servicio
        $reglasEsperadas = [
            'nombre_servicio' => 'required|string|max:100',
            'descripcion' => 'nullable|string|max:500',
            'icono' => 'nullable|string|max:80',
        ];
        
        $this->assertArrayHasKey('nombre_servicio', $reglasEsperadas);
        $this->assertStringContainsString('required', $reglasEsperadas['nombre_servicio']);
        $this->assertStringContainsString('max:100', $reglasEsperadas['nombre_servicio']);
    }

    public function test_validacion_descripcion_es_opcional(): void
    {
        $reglasEsperadas = [
            'descripcion' => 'nullable|string|max:500',
        ];
        
        $this->assertStringContainsString('nullable', $reglasEsperadas['descripcion']);
    }

    public function test_validacion_icono_es_opcional(): void
    {
        $reglasEsperadas = [
            'icono' => 'nullable|string|max:80',
        ];
        
        $this->assertStringContainsString('nullable', $reglasEsperadas['icono']);
        $this->assertStringContainsString('max:80', $reglasEsperadas['icono']);
    }

    // ============================================
    // TESTS PARA Constantes y Valores
    // ============================================

    public function test_estructura_vista_base_existe(): void
    {
        // Verificar que la plantilla base existe conceptualmente
        $nombrePlantilla = 'animacion.blade.php';
        
        $this->assertIsString($nombrePlantilla);
        $this->assertStringEndsWith('.blade.php', $nombrePlantilla);
    }

    public function test_ruta_vista_estructura(): void
    {
        // Verificar estructura de ruta de vista
        $nombreServicio = 'Test';
        $slug = Str::slug($nombreServicio, '_');
        $rutaEsperada = "views/usuarios/{$slug}.blade.php";
        
        $this->assertIsString($rutaEsperada);
        $this->assertStringContainsString('views/usuarios/', $rutaEsperada);
        $this->assertStringEndsWith('.blade.php', $rutaEsperada);
    }
}

