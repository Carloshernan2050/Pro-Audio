<?php

namespace Tests\Unit;

use Tests\TestCase;
use App\Services\ChatbotResponseBuilder;
use App\Models\SubServicios;
use App\Models\Servicios;
use Illuminate\Foundation\Testing\RefreshDatabase;

/**
 * Tests Unitarios para ChatbotResponseBuilder
 */
class ChatbotResponseBuilderTest extends TestCase
{
    use RefreshDatabase;

    private const NOMBRE_EQUIPO_1 = 'Equipo 1';

    protected ChatbotResponseBuilder $builder;

    protected function setUp(): void
    {
        parent::setUp();
        $this->builder = new ChatbotResponseBuilder();
    }

    // ============================================
    // TESTS PARA responderConOpciones()
    // ============================================

    public function test_responder_con_opciones(): void
    {
        session(['chat.selecciones' => [1, 2]]);
        
        $resultado = $this->builder->responderConOpciones();
        
        $this->assertInstanceOf(\Illuminate\Http\JsonResponse::class, $resultado);
        $data = json_decode($resultado->getContent(), true);
        $this->assertArrayHasKey('respuesta', $data);
        $this->assertArrayHasKey('optionGroups', $data);
    }

    // ============================================
    // TESTS PARA mostrarCatalogoJson()
    // ============================================

    public function test_mostrar_catalogo_json_sin_dias(): void
    {
        $resultado = $this->builder->mostrarCatalogoJson('Mensaje de prueba', null, []);
        
        $this->assertInstanceOf(\Illuminate\Http\JsonResponse::class, $resultado);
        $data = json_decode($resultado->getContent(), true);
        $this->assertEquals('Mensaje de prueba', $data['respuesta']);
        $this->assertNull($data['days']);
        $this->assertArrayHasKey('optionGroups', $data);
    }

    public function test_mostrar_catalogo_json_con_dias(): void
    {
        $resultado = $this->builder->mostrarCatalogoJson('Mensaje', 5, [1, 2]);
        
        $this->assertInstanceOf(\Illuminate\Http\JsonResponse::class, $resultado);
        $data = json_decode($resultado->getContent(), true);
        $this->assertEquals(5, $data['days']);
        $this->assertEquals([1, 2], $data['seleccionesPrevias']);
    }

    // ============================================
    // TESTS PARA responderOpciones()
    // ============================================

    public function test_responder_opciones(): void
    {
        $items = collect([
            (object)['id' => 1, 'nombre' => 'Test', 'precio' => 100, 'nombre_servicio' => 'Servicio1'],
            (object)['id' => 2, 'nombre' => 'Test2', 'precio' => 200, 'nombre_servicio' => 'Servicio1'],
        ]);
        
        $resultado = $this->builder->responderOpciones('Mensaje', $items, 3, [1]);
        
        $this->assertInstanceOf(\Illuminate\Http\JsonResponse::class, $resultado);
        $data = json_decode($resultado->getContent(), true);
        $this->assertEquals('Mensaje', $data['respuesta']);
        $this->assertEquals(3, $data['days']);
        $this->assertEquals([1], $data['seleccionesPrevias']);
        $this->assertArrayHasKey('optionGroups', $data);
    }

    // ============================================
    // TESTS PARA solicitarConfirmacionIntencion()
    // ============================================

    public function test_solicitar_confirmacion_intencion_sin_hint(): void
    {
        $resultado = $this->builder->solicitarConfirmacionIntencion(
            'Alquiler',
            ['Alquiler'],
            0,
            5,
            null
        );
        
        $this->assertInstanceOf(\Illuminate\Http\JsonResponse::class, $resultado);
        $data = json_decode($resultado->getContent(), true);
        $this->assertStringContainsString('Alquiler', $data['respuesta']);
        $this->assertArrayHasKey('actions', $data);
        $this->assertEquals(5, $data['days']);
    }

    public function test_solicitar_confirmacion_intencion_con_hint(): void
    {
        $hint = ['token' => 'alqiler', 'sugerencias' => ['alquiler']];
        
        $resultado = $this->builder->solicitarConfirmacionIntencion(
            'Alquiler',
            ['Alquiler'],
            0,
            5,
            $hint
        );
        
        $this->assertInstanceOf(\Illuminate\Http\JsonResponse::class, $resultado);
        $data = json_decode($resultado->getContent(), true);
        $this->assertStringContainsString('alqiler', $data['respuesta']);
    }

    public function test_solicitar_confirmacion_intencion_con_dias(): void
    {
        $resultado = $this->builder->solicitarConfirmacionIntencion(
            'Alquiler y Animación',
            ['Alquiler', 'Animación'],
            3,
            3,
            null
        );
        
        $this->assertInstanceOf(\Illuminate\Http\JsonResponse::class, $resultado);
        $data = json_decode($resultado->getContent(), true);
        $this->assertEquals(3, $data['actions'][0]['meta']['dias']);
    }

    // ============================================
    // TESTS PARA mostrarOpcionesConIntenciones()
    // ============================================

    public function test_mostrar_opciones_con_intenciones_sin_dias(): void
    {
        $items = collect([
            (object)['id' => 1, 'nombre' => 'Test', 'precio' => 100, 'nombre_servicio' => 'Alquiler'],
        ]);
        
        session(['chat.selecciones' => []]);
        
        $resultado = $this->builder->mostrarOpcionesConIntenciones(
            ['Alquiler'],
            $items,
            0,
            null
        );
        
        $this->assertInstanceOf(\Illuminate\Http\JsonResponse::class, $resultado);
        $data = json_decode($resultado->getContent(), true);
        $this->assertStringContainsString('Alquiler', $data['respuesta']);
    }

    public function test_mostrar_opciones_con_intenciones_con_dias(): void
    {
        $items = collect([
            (object)['id' => 1, 'nombre' => 'Test', 'precio' => 100, 'nombre_servicio' => 'Alquiler'],
        ]);
        
        session(['chat.selecciones' => []]);
        
        $resultado = $this->builder->mostrarOpcionesConIntenciones(
            ['Alquiler'],
            $items,
            3,
            3
        );
        
        $this->assertInstanceOf(\Illuminate\Http\JsonResponse::class, $resultado);
        $data = json_decode($resultado->getContent(), true);
        $this->assertStringContainsString('3 día', $data['respuesta']);
    }

    public function test_mostrar_opciones_con_intenciones_multiples_dias(): void
    {
        $items = collect([
            (object)['id' => 1, 'nombre' => 'Test', 'precio' => 100, 'nombre_servicio' => 'Alquiler'],
        ]);
        
        session(['chat.selecciones' => []]);
        
        $resultado = $this->builder->mostrarOpcionesConIntenciones(
            ['Alquiler'],
            $items,
            5,
            5
        );
        
        $this->assertInstanceOf(\Illuminate\Http\JsonResponse::class, $resultado);
        $data = json_decode($resultado->getContent(), true);
        $this->assertStringContainsString('5 días', $data['respuesta']);
    }

    // ============================================
    // TESTS PARA responderCotizacion()
    // ============================================

    public function test_responder_cotizacion_un_dia(): void
    {
        $items = collect([
            (object)['id' => 1, 'nombre' => 'Item1', 'precio' => 100],
            (object)['id' => 2, 'nombre' => 'Item2', 'precio' => 200],
        ]);
        
        $resultado = $this->builder->responderCotizacion($items, 1, [1, 2], false);
        
        $this->assertInstanceOf(\Illuminate\Http\JsonResponse::class, $resultado);
        $data = json_decode($resultado->getContent(), true);
        $this->assertArrayHasKey('cotizacion', $data);
        $this->assertEquals(300, $data['cotizacion']['total']);
        $this->assertEquals(1, $data['cotizacion']['dias']);
        $this->assertCount(2, $data['cotizacion']['items']);
    }

    public function test_responder_cotizacion_multiples_dias(): void
    {
        $items = collect([
            (object)['id' => 1, 'nombre' => 'Item1', 'precio' => 100],
        ]);
        
        $resultado = $this->builder->responderCotizacion($items, 3, [1], false);
        
        $this->assertInstanceOf(\Illuminate\Http\JsonResponse::class, $resultado);
        $data = json_decode($resultado->getContent(), true);
        $this->assertEquals(300, $data['cotizacion']['total']);
        $this->assertEquals(3, $data['cotizacion']['dias']);
        $this->assertEquals(100, $data['cotizacion']['items'][0]['precio_unitario']);
        $this->assertEquals(300, $data['cotizacion']['items'][0]['subtotal']);
    }

    // ============================================
    // TESTS PARA construirDetalleCotizacion()
    // ============================================

    public function test_construir_detalle_cotizacion(): void
    {
        $items = collect([
            (object)['id' => 1, 'nombre' => 'Item1', 'precio' => 100],
            (object)['id' => 2, 'nombre' => 'Item2', 'precio' => 200],
        ]);
        
        $detalle = $this->builder->construirDetalleCotizacion($items, 2, false);
        
        $this->assertArrayHasKey('items', $detalle);
        $this->assertArrayHasKey('total', $detalle);
        $this->assertArrayHasKey('mensaje', $detalle);
        $this->assertEquals(600, $detalle['total']);
        $this->assertCount(2, $detalle['items']);
        $this->assertEquals(200, $detalle['items'][0]['subtotal']);
        $this->assertEquals(400, $detalle['items'][1]['subtotal']);
    }

    public function test_construir_detalle_cotizacion_con_array(): void
    {
        $items = [
            ['id' => 1, 'nombre' => 'Item1', 'precio' => 100],
            ['id' => 2, 'nombre' => 'Item2', 'precio' => 200],
        ];
        
        $detalle = $this->builder->construirDetalleCotizacion($items, 2, false);
        
        $this->assertEquals(600, $detalle['total']);
        $this->assertCount(2, $detalle['items']);
    }

    public function test_construir_detalle_cotizacion_mensaje_plural(): void
    {
        $items = collect([
            (object)['id' => 1, 'nombre' => 'Item1', 'precio' => 100],
        ]);
        
        $detalle = $this->builder->construirDetalleCotizacion($items, 5, false);
        
        $this->assertStringContainsString('5 días', $detalle['mensaje']);
        $this->assertStringContainsString('Total:', $detalle['mensaje']);
    }

    public function test_construir_detalle_cotizacion_mensaje_singular(): void
    {
        $items = collect([
            (object)['id' => 1, 'nombre' => 'Item1', 'precio' => 100],
        ]);
        
        $detalle = $this->builder->construirDetalleCotizacion($items, 1, false);
        
        $this->assertStringContainsString('1 día', $detalle['mensaje']);
    }

    // ============================================
    // TESTS PARA subServiciosQuery() y ordenarSubServicios()
    // ============================================

    public function test_sub_servicios_query_estructura(): void
    {
        $query = $this->builder->subServiciosQuery();
        
        $this->assertInstanceOf(\Illuminate\Database\Eloquent\Builder::class, $query);
    }

    public function test_ordenar_sub_servicios_estructura(): void
    {
        $query = $this->builder->subServiciosQuery();
        $orderedQuery = $this->builder->ordenarSubServicios($query);
        
        $this->assertInstanceOf(\Illuminate\Database\Eloquent\Builder::class, $orderedQuery);
    }

    public function test_formatear_opciones_con_items(): void
    {
        $servicio = Servicios::create(['nombre_servicio' => 'Alquiler']);
        SubServicios::create([
            'servicios_id' => $servicio->id,
            'nombre' => self::NOMBRE_EQUIPO_1,
            'precio' => 100
        ]);
        SubServicios::create([
            'servicios_id' => $servicio->id,
            'nombre' => 'Equipo 2',
            'precio' => 200
        ]);

        $items = $this->builder->subServiciosQuery()->get();
        $resultado = $this->builder->responderOpciones('Mensaje', $items, null, []);
        
        $data = json_decode($resultado->getContent(), true);
        $this->assertArrayHasKey('optionGroups', $data);
        $this->assertIsArray($data['optionGroups']);
    }

    public function test_formatear_opciones_con_array(): void
    {
        $items = [
            ['id' => 1, 'nombre' => 'Item1', 'precio' => 100, 'servicio' => 'Alquiler'],
            ['id' => 2, 'nombre' => 'Item2', 'precio' => 200, 'servicio' => 'Alquiler'],
        ];
        
        $resultado = $this->builder->responderOpciones('Mensaje', $items, null, []);
        
        $data = json_decode($resultado->getContent(), true);
        $this->assertArrayHasKey('optionGroups', $data);
    }

    public function test_construir_detalle_cotizacion_mostrar_dias_siempre(): void
    {
        $items = collect([
            (object)['id' => 1, 'nombre' => 'Item1', 'precio' => 100],
        ]);
        
        $detalle = $this->builder->construirDetalleCotizacion($items, 1, true);
        
        $this->assertStringContainsString('Total:', $detalle['mensaje']);
    }

    public function test_responder_cotizacion_con_mostrar_dias_siempre(): void
    {
        $items = collect([
            (object)['id' => 1, 'nombre' => 'Item1', 'precio' => 100],
        ]);
        
        $resultado = $this->builder->responderCotizacion($items, 1, [1], true);
        
        $data = json_decode($resultado->getContent(), true);
        $this->assertStringContainsString('Total:', $data['respuesta']);
    }

    public function test_solicitar_confirmacion_intencion_sin_dias(): void
    {
        $resultado = $this->builder->solicitarConfirmacionIntencion(
            'Alquiler',
            ['Alquiler'],
            0,
            null,
            null
        );
        
        $data = json_decode($resultado->getContent(), true);
        $this->assertNull($data['actions'][0]['meta']['dias']);
    }

    public function test_solicitar_confirmacion_intencion_con_hint_vacio(): void
    {
        $resultado = $this->builder->solicitarConfirmacionIntencion(
            'Alquiler',
            ['Alquiler'],
            0,
            5,
            []
        );
        
        $data = json_decode($resultado->getContent(), true);
        $this->assertStringContainsString('Alquiler', $data['respuesta']);
    }

    public function test_solicitar_confirmacion_intencion_con_hint_token_vacio(): void
    {
        $hint = ['token' => '', 'sugerencias' => []];
        
        $resultado = $this->builder->solicitarConfirmacionIntencion(
            'Alquiler',
            ['Alquiler'],
            0,
            5,
            $hint
        );
        
        $data = json_decode($resultado->getContent(), true);
        $this->assertStringContainsString('Alquiler', $data['respuesta']);
    }

    public function test_responder_opciones_sin_dias(): void
    {
        $items = collect([
            (object)['id' => 1, 'nombre' => 'Test', 'precio' => 100, 'nombre_servicio' => 'Servicio1'],
        ]);
        
        $resultado = $this->builder->responderOpciones('Mensaje', $items, null, []);
        
        $data = json_decode($resultado->getContent(), true);
        $this->assertNull($data['days']);
    }

    public function test_construir_detalle_cotizacion_un_dia_sin_mostrar_total(): void
    {
        $items = collect([
            (object)['id' => 1, 'nombre' => 'Item1', 'precio' => 100],
        ]);
        
        $detalle = $this->builder->construirDetalleCotizacion($items, 1, false);
        
        $this->assertStringContainsString('1 día', $detalle['mensaje']);
        $this->assertStringNotContainsString('Total:', $detalle['mensaje']);
    }

    public function test_construir_detalle_cotizacion_multiples_dias_sin_mostrar_dias_siempre(): void
    {
        $items = collect([
            (object)['id' => 1, 'nombre' => 'Item1', 'precio' => 100],
        ]);
        
        $detalle = $this->builder->construirDetalleCotizacion($items, 3, false);
        
        $this->assertStringContainsString('Total:', $detalle['mensaje']);
    }

    public function test_mostrar_catalogo_json_con_selecciones(): void
    {
        $servicio = Servicios::create(['nombre_servicio' => 'Alquiler']);
        SubServicios::create([
            'servicios_id' => $servicio->id,
            'nombre' => self::NOMBRE_EQUIPO_1,
            'precio' => 100
        ]);
        
        $resultado = $this->builder->mostrarCatalogoJson('Mensaje', 5, [1]);
        
        $data = json_decode($resultado->getContent(), true);
        $this->assertEquals([1], $data['seleccionesPrevias']);
    }

    public function test_formatear_opciones_con_items_vacios(): void
    {
        $items = collect([]);
        
        $resultado = $this->builder->responderOpciones('Mensaje', $items, null, []);
        
        $data = json_decode($resultado->getContent(), true);
        $this->assertIsArray($data['optionGroups']);
        $this->assertEmpty($data['optionGroups']);
    }

    public function test_formatear_opciones_con_multiple_servicios(): void
    {
        $servicio1 = Servicios::create(['nombre_servicio' => 'Alquiler']);
        $servicio2 = Servicios::create(['nombre_servicio' => 'Animación']);
        
        SubServicios::create([
            'servicios_id' => $servicio1->id,
            'nombre' => self::NOMBRE_EQUIPO_1,
            'precio' => 100
        ]);
        
        SubServicios::create([
            'servicios_id' => $servicio2->id,
            'nombre' => 'DJ',
            'precio' => 200
        ]);

        $items = $this->builder->subServiciosQuery()->get();
        $resultado = $this->builder->responderOpciones('Mensaje', $items, null, []);
        
        $data = json_decode($resultado->getContent(), true);
        $this->assertGreaterThanOrEqual(2, count($data['optionGroups']));
    }

    public function test_responder_cotizacion_con_items_array(): void
    {
        $items = [
            ['id' => 1, 'nombre' => 'Item1', 'precio' => 100],
            ['id' => 2, 'nombre' => 'Item2', 'precio' => 200],
        ];
        
        $resultado = $this->builder->responderCotizacion($items, 2, [1, 2], false);
        
        $data = json_decode($resultado->getContent(), true);
        $this->assertEquals(600, $data['cotizacion']['total']);
    }
}

