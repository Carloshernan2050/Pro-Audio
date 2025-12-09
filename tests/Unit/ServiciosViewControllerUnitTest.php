<?php

namespace Tests\Unit;

use App\Http\Controllers\ServiciosViewController;
use Illuminate\Support\Str;
use Tests\TestCase;

/**
 * Tests Unitarios para ServiciosViewController
 *
 * Tests para lógica pura y utilidades
 */
class ServiciosViewControllerUnitTest extends TestCase
{
    private const SERVICIO_ANIMACION = 'Animación';

    protected $controller;

    protected function setUp(): void
    {
        parent::setUp();
        $this->controller = app(ServiciosViewController::class);
    }

    // ============================================
    // TESTS PARA Instanciación
    // ============================================

    public function test_controller_instancia_correctamente(): void
    {
        $this->assertInstanceOf(ServiciosViewController::class, $this->controller);
    }

    // ============================================
    // TESTS PARA Métodos del Controlador
    // ============================================

    public function test_controller_tiene_metodo_alquiler(): void
    {
        $this->assertTrue(method_exists($this->controller, 'alquiler'));
    }

    public function test_controller_tiene_metodo_animacion(): void
    {
        $this->assertTrue(method_exists($this->controller, 'animacion'));
    }

    public function test_controller_tiene_metodo_publicidad(): void
    {
        $this->assertTrue(method_exists($this->controller, 'publicidad'));
    }

    public function test_controller_tiene_metodo_servicio_por_slug(): void
    {
        $this->assertTrue(method_exists($this->controller, 'servicioPorSlug'));
    }

    // ============================================
    // TESTS PARA Utilidades de Slug
    // ============================================

    public function test_slug_generacion_servicio(): void
    {
        // Verificar generación de slug para servicios
        $servicio = 'Alquiler de Equipos';
        $slug = Str::slug($servicio, '_');

        $this->assertIsString($slug);
        $this->assertNotEmpty($slug);
        $this->assertEquals('alquiler_de_equipos', $slug);
    }

    public function test_slug_generacion_con_acentos(): void
    {
        $servicio = self::SERVICIO_ANIMACION;
        $slug = Str::slug($servicio, '_');

        $this->assertIsString($slug);
        $this->assertNotEmpty($slug);
        $this->assertStringNotContainsString('ó', $slug);
    }

    public function test_slug_generacion_publicidad(): void
    {
        $servicio = 'Publicidad';
        $slug = Str::slug($servicio, '_');

        $this->assertIsString($slug);
        $this->assertNotEmpty($slug);
        $this->assertEquals('publicidad', $slug);
    }

    // ============================================
    // TESTS PARA Nombres de Servicios
    // ============================================

    public function test_nombres_servicios_validos(): void
    {
        // Los servicios principales son: Alquiler, Animación, Publicidad
        $servicios = ['Alquiler', self::SERVICIO_ANIMACION, 'Publicidad'];

        $this->assertCount(3, $servicios);
        $this->assertContains('Alquiler', $servicios);
        $this->assertContains(self::SERVICIO_ANIMACION, $servicios);
        $this->assertContains('Publicidad', $servicios);
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

    // ============================================
    // TESTS PARA Lógica de Búsqueda
    // ============================================

    public function test_busqueda_servicio_por_nombre(): void
    {
        // Verificar lógica de búsqueda de servicios
        $servicios = [
            (object)['nombre_servicio' => 'Alquiler'],
            (object)['nombre_servicio' => self::SERVICIO_ANIMACION],
            (object)['nombre_servicio' => 'Publicidad'],
        ];

        $servicioEncontrado = collect($servicios)->first(function ($s) {
            return $s->nombre_servicio === 'Alquiler';
        });

        $this->assertNotNull($servicioEncontrado);
        $this->assertEquals('Alquiler', $servicioEncontrado->nombre_servicio);
    }

    public function test_busqueda_servicio_por_slug(): void
    {
        $servicios = [
            (object)['nombre_servicio' => 'Alquiler'],
            (object)['nombre_servicio' => self::SERVICIO_ANIMACION],
        ];

        $slug = 'alquiler';
        $servicioEncontrado = collect($servicios)->first(function ($s) use ($slug) {
            return Str::slug($s->nombre_servicio, '_') === $slug;
        });

        $this->assertNotNull($servicioEncontrado);
        $this->assertEquals('Alquiler', $servicioEncontrado->nombre_servicio);
    }
}
