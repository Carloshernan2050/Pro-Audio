<?php

namespace Tests\Unit;

use App\Models\Servicios;
use App\Models\SubServicios;
use App\Services\ChatbotResponseBuilder;
use App\Services\ChatbotSubServicioService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Tests Unitarios para ChatbotSubServicioService
 */
class ChatbotSubServicioServiceTest extends TestCase
{
    use RefreshDatabase;

    private const DESC_SERVICIO_ALQUILER = 'Servicio de alquiler';

    private const NOMBRE_EQUIPO_SONIDO = 'Equipo de sonido';

    private const DESC_EQUIPO_COMPLETO = 'Equipo completo';

    private const DESC_EQUIPO_COMPLETO_AUDIO = 'Equipo completo de audio';

    protected ChatbotSubServicioService $service;

    protected function setUp(): void
    {
        parent::setUp();
        $responseBuilder = new ChatbotResponseBuilder;
        $textProcessor = new \App\Services\ChatbotTextProcessor();
        $this->service = new ChatbotSubServicioService($responseBuilder, $textProcessor);
    }

    // ============================================
    // TESTS PARA obtenerSubServiciosPorIntenciones()
    // ============================================

    public function test_obtener_sub_servicios_por_intenciones_vacio(): void
    {
        $resultado = $this->service->obtenerSubServiciosPorIntenciones([]);

        $this->assertInstanceOf(\Illuminate\Support\Collection::class, $resultado);
        $this->assertTrue($resultado->isEmpty());
    }

    public function test_obtener_sub_servicios_por_intenciones_con_servicio(): void
    {
        $servicio = Servicios::create([
            'nombre_servicio' => 'Alquiler',
            'descripcion' => self::DESC_SERVICIO_ALQUILER,
        ]);

        SubServicios::create([
            'servicios_id' => $servicio->id,
            'nombre' => self::NOMBRE_EQUIPO_SONIDO,
            'descripcion' => self::DESC_EQUIPO_COMPLETO,
            'precio' => 100,
        ]);

        $resultado = $this->service->obtenerSubServiciosPorIntenciones(['Alquiler']);

        $this->assertInstanceOf(\Illuminate\Support\Collection::class, $resultado);
        $this->assertFalse($resultado->isEmpty());
    }

    public function test_obtener_sub_servicios_por_intenciones_multiples(): void
    {
        $servicio1 = Servicios::create([
            'nombre_servicio' => 'Alquiler',
            'descripcion' => self::DESC_SERVICIO_ALQUILER,
        ]);

        $servicio2 = Servicios::create([
            'nombre_servicio' => 'Animación',
            'descripcion' => 'Servicio de animación',
        ]);

        SubServicios::create([
            'servicios_id' => $servicio1->id,
            'nombre' => self::NOMBRE_EQUIPO_SONIDO,
            'descripcion' => self::DESC_EQUIPO_COMPLETO,
            'precio' => 100,
        ]);

        SubServicios::create([
            'servicios_id' => $servicio2->id,
            'nombre' => 'DJ',
            'descripcion' => 'Servicio de DJ',
            'precio' => 200,
        ]);

        $resultado = $this->service->obtenerSubServiciosPorIntenciones(['Alquiler', 'Animación']);

        $this->assertInstanceOf(\Illuminate\Support\Collection::class, $resultado);
        $this->assertGreaterThanOrEqual(1, $resultado->count());
    }

    // ============================================
    // TESTS PARA obtenerItemsSeleccionados()
    // ============================================

    public function test_obtener_items_seleccionados_vacio(): void
    {
        $resultado = $this->service->obtenerItemsSeleccionados([]);

        $this->assertInstanceOf(\Illuminate\Support\Collection::class, $resultado);
        $this->assertTrue($resultado->isEmpty());
    }

    public function test_obtener_items_seleccionados_con_ids(): void
    {
        $servicio = Servicios::create([
            'nombre_servicio' => 'Alquiler',
            'descripcion' => self::DESC_SERVICIO_ALQUILER,
        ]);

        $subServicio1 = SubServicios::create([
            'servicios_id' => $servicio->id,
            'nombre' => 'Item1',
            'descripcion' => 'Descripción Item1',
            'precio' => 100,
        ]);

        $subServicio2 = SubServicios::create([
            'servicios_id' => $servicio->id,
            'nombre' => 'Item2',
            'descripcion' => 'Descripción Item2',
            'precio' => 200,
        ]);

        $resultado = $this->service->obtenerItemsSeleccionados([$subServicio1->id, $subServicio2->id]);

        $this->assertInstanceOf(\Illuminate\Support\Collection::class, $resultado);
        $this->assertCount(2, $resultado);
        $this->assertEquals($subServicio1->id, $resultado->first()->id);
    }

    public function test_obtener_items_seleccionados_con_ids_inexistentes(): void
    {
        $resultado = $this->service->obtenerItemsSeleccionados([999, 998]);

        $this->assertInstanceOf(\Illuminate\Support\Collection::class, $resultado);
        $this->assertTrue($resultado->isEmpty());
    }

    // ============================================
    // TESTS PARA buscarSubServiciosRelacionados()
    // ============================================

    public function test_buscar_sub_servicios_relacionados_por_mensaje(): void
    {
        $servicio = Servicios::create([
            'nombre_servicio' => 'Alquiler',
            'descripcion' => self::DESC_SERVICIO_ALQUILER,
        ]);

        SubServicios::create([
            'servicios_id' => $servicio->id,
            'nombre' => self::NOMBRE_EQUIPO_SONIDO,
            'descripcion' => self::DESC_EQUIPO_COMPLETO_AUDIO,
            'precio' => 100,
        ]);

        $resultado = $this->service->buscarSubServiciosRelacionados(
            'sonido',
            ['sonido'],
            []
        );

        $this->assertInstanceOf(\Illuminate\Support\Collection::class, $resultado);
    }

    public function test_buscar_sub_servicios_relacionados_por_tokens(): void
    {
        $servicio = Servicios::create([
            'nombre_servicio' => 'Alquiler',
            'descripcion' => self::DESC_SERVICIO_ALQUILER,
        ]);

        SubServicios::create([
            'servicios_id' => $servicio->id,
            'nombre' => 'Microfono',
            'descripcion' => 'Microfono profesional',
            'precio' => 50,
        ]);

        $resultado = $this->service->buscarSubServiciosRelacionados(
            '',
            ['microfono'],
            []
        );

        $this->assertInstanceOf(\Illuminate\Support\Collection::class, $resultado);
    }

    public function test_buscar_sub_servicios_relacionados_por_intenciones(): void
    {
        $servicio = Servicios::create([
            'nombre_servicio' => 'Alquiler',
            'descripcion' => self::DESC_SERVICIO_ALQUILER,
        ]);

        SubServicios::create([
            'servicios_id' => $servicio->id,
            'nombre' => 'Equipo',
            'descripcion' => self::DESC_EQUIPO_COMPLETO,
            'precio' => 100,
        ]);

        $resultado = $this->service->buscarSubServiciosRelacionados(
            '',
            [],
            ['Alquiler']
        );

        $this->assertInstanceOf(\Illuminate\Support\Collection::class, $resultado);
    }

    public function test_buscar_sub_servicios_relacionados_limite_12(): void
    {
        $servicio = Servicios::create([
            'nombre_servicio' => 'Alquiler',
            'descripcion' => self::DESC_SERVICIO_ALQUILER,
        ]);

        // Crear más de 12 subservicios
        for ($i = 1; $i <= 15; $i++) {
            SubServicios::create([
                'servicios_id' => $servicio->id,
                'nombre' => "Item {$i}",
                'descripcion' => "Descripción Item {$i}",
                'precio' => 100,
            ]);
        }

        $resultado = $this->service->buscarSubServiciosRelacionados(
            '',
            [],
            ['Alquiler']
        );

        $this->assertInstanceOf(\Illuminate\Support\Collection::class, $resultado);
        $this->assertLessThanOrEqual(12, $resultado->count());
    }

    public function test_buscar_sub_servicios_relacionados_sin_resultados(): void
    {
        $resultado = $this->service->buscarSubServiciosRelacionados(
            'xyz123',
            ['xyz123'],
            []
        );

        $this->assertInstanceOf(\Illuminate\Support\Collection::class, $resultado);
        $this->assertTrue($resultado->isEmpty());
    }

    public function test_buscar_sub_servicios_relacionados_por_mensaje_y_tokens(): void
    {
        $servicio = Servicios::create([
            'nombre_servicio' => 'Alquiler',
            'descripcion' => self::DESC_SERVICIO_ALQUILER,
        ]);

        SubServicios::create([
            'servicios_id' => $servicio->id,
            'nombre' => self::NOMBRE_EQUIPO_SONIDO,
            'descripcion' => self::DESC_EQUIPO_COMPLETO,
            'precio' => 100,
        ]);

        $resultado = $this->service->buscarSubServiciosRelacionados(
            'sonido',
            ['equipo', 'sonido'],
            []
        );

        $this->assertInstanceOf(\Illuminate\Support\Collection::class, $resultado);
    }

    public function test_buscar_sub_servicios_relacionados_por_mensaje_y_intenciones(): void
    {
        $servicio = Servicios::create([
            'nombre_servicio' => 'Alquiler',
            'descripcion' => self::DESC_SERVICIO_ALQUILER,
        ]);

        SubServicios::create([
            'servicios_id' => $servicio->id,
            'nombre' => 'Equipo',
            'descripcion' => self::DESC_EQUIPO_COMPLETO,
            'precio' => 100,
        ]);

        $resultado = $this->service->buscarSubServiciosRelacionados(
            'equipo',
            [],
            ['Alquiler']
        );

        $this->assertInstanceOf(\Illuminate\Support\Collection::class, $resultado);
    }

    public function test_buscar_sub_servicios_relacionados_con_tokens_vacios(): void
    {
        $servicio = Servicios::create([
            'nombre_servicio' => 'Alquiler',
            'descripcion' => self::DESC_SERVICIO_ALQUILER,
        ]);

        SubServicios::create([
            'servicios_id' => $servicio->id,
            'nombre' => 'Equipo',
            'descripcion' => self::DESC_EQUIPO_COMPLETO,
            'precio' => 100,
        ]);

        $resultado = $this->service->buscarSubServiciosRelacionados(
            'equipo',
            ['', '   '],
            []
        );

        $this->assertInstanceOf(\Illuminate\Support\Collection::class, $resultado);
    }

    public function test_buscar_sub_servicios_relacionados_por_descripcion(): void
    {
        // NOTA: Este test verifica que NO se busque en descripciones, solo en nombres
        $servicio = Servicios::create([
            'nombre_servicio' => 'Alquiler',
            'descripcion' => self::DESC_SERVICIO_ALQUILER,
        ]);

        // Crear subservicio donde "audio" está en la descripción pero NO en el nombre
        SubServicios::create([
            'servicios_id' => $servicio->id,
            'nombre' => 'Equipo',
            'descripcion' => 'Equipo profesional de audio',
            'precio' => 100,
        ]);

        // Buscar "audio" - NO debería encontrar nada porque solo buscamos en nombres
        $resultado = $this->service->buscarSubServiciosRelacionados(
            'audio',
            [],
            []
        );

        // Debe retornar vacío porque "audio" no está en el nombre "Equipo"
        $this->assertInstanceOf(\Illuminate\Support\Collection::class, $resultado);
        $this->assertEmpty($resultado, 'No debe encontrar resultados cuando la palabra solo está en la descripción');
    }

    public function test_obtener_items_seleccionados_con_relacion_servicio(): void
    {
        $servicio = Servicios::create([
            'nombre_servicio' => 'Alquiler',
            'descripcion' => self::DESC_SERVICIO_ALQUILER,
        ]);

        $subServicio = SubServicios::create([
            'servicios_id' => $servicio->id,
            'nombre' => 'Item1',
            'descripcion' => 'Descripción Item1',
            'precio' => 100,
        ]);

        $resultado = $this->service->obtenerItemsSeleccionados([$subServicio->id]);

        $this->assertInstanceOf(\Illuminate\Support\Collection::class, $resultado);
        $this->assertCount(1, $resultado);
        $this->assertNotNull($resultado->first()->servicio);
    }

    public function test_buscar_sub_servicios_relacionados_mensaje_vacio_con_tokens_e_intenciones(): void
    {
        $servicio = Servicios::create([
            'nombre_servicio' => 'Alquiler',
            'descripcion' => self::DESC_SERVICIO_ALQUILER,
        ]);

        SubServicios::create([
            'servicios_id' => $servicio->id,
            'nombre' => self::NOMBRE_EQUIPO_SONIDO,
            'descripcion' => self::DESC_EQUIPO_COMPLETO,
            'precio' => 100,
        ]);

        $resultado = $this->service->buscarSubServiciosRelacionados(
            '',
            ['equipo', 'sonido'],
            ['Alquiler']
        );

        $this->assertInstanceOf(\Illuminate\Support\Collection::class, $resultado);
    }

    public function test_buscar_sub_servicios_relacionados_mensaje_y_tokens_e_intenciones(): void
    {
        $servicio = Servicios::create([
            'nombre_servicio' => 'Alquiler',
            'descripcion' => self::DESC_SERVICIO_ALQUILER,
        ]);

        SubServicios::create([
            'servicios_id' => $servicio->id,
            'nombre' => 'Equipo profesional',
            'descripcion' => self::DESC_EQUIPO_COMPLETO_AUDIO,
            'precio' => 100,
        ]);

        $resultado = $this->service->buscarSubServiciosRelacionados(
            'equipo',
            ['profesional', 'audio'],
            ['Alquiler']
        );

        $this->assertInstanceOf(\Illuminate\Support\Collection::class, $resultado);
    }
}
