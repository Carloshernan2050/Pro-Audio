<?php

namespace Tests\Unit;

use App\Models\Calendario;
use App\Models\CalendarioItem;
use App\Models\Inventario;
use App\Models\MovimientosInventario;
use App\Models\Usuario;
use App\Services\CalendarioEventService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Collection;
use Tests\TestCase;

class CalendarioEventServiceTest extends TestCase
{
    use RefreshDatabase;

    private const EVENTO_DE_PRUEBA = 'Evento de prueba';
    private const PRODUCTO_TEST = 'Producto Test';
    private const FECHA_INICIO = '2024-01-01';
    private const FECHA_FIN = '2024-01-05';
    private const MOVIMIENTO_DE_PRUEBA = 'Movimiento de prueba';

    private CalendarioEventService $service;
    private Usuario $persona;

    protected function setUp(): void
    {
        parent::setUp();
        $this->service = new CalendarioEventService();
        $this->persona = Usuario::create([
            'primer_nombre' => 'Test',
            'primer_apellido' => 'User',
            'correo' => 'test@example.com',
            'telefono' => '1234567890',
            'contrasena' => 'password123',
            'fecha_registro' => now(),
            'estado' => 1,
        ]);
    }

    // ============================================
    // TESTS PARA construirEvento()
    // ============================================

    public function test_construir_evento_desde_items(): void
    {
        $inventario = Inventario::create([
            'descripcion' => self::PRODUCTO_TEST,
            'stock' => 10,
        ]);

        $movimiento = MovimientosInventario::create([
            'inventario_id' => $inventario->id,
            'tipo_movimiento' => 'entrada',
            'cantidad' => 10,
            'fecha_movimiento' => now(),
            'descripcion' => self::MOVIMIENTO_DE_PRUEBA,
        ]);

        $calendario = Calendario::create([
            'personas_id' => $this->persona->id,
            'fecha' => now(),
            'fecha_inicio' => self::FECHA_INICIO,
            'fecha_fin' => self::FECHA_FIN,
            'evento' => self::EVENTO_DE_PRUEBA,
            'descripcion_evento' => 'Descripción del evento',
        ]);

        CalendarioItem::create([
            'calendario_id' => $calendario->id,
            'movimientos_inventario_id' => $movimiento->id,
            'cantidad' => 3,
        ]);

        $calendario->load('items');

        $movimientos = collect([$movimiento->id => $movimiento])->keyBy('id');
        $inventarios = collect([$inventario->id => $inventario])->keyBy('id');

        $resultado = $this->service->construirEvento($calendario, $movimientos, $inventarios);

        $this->assertIsArray($resultado);
        $this->assertArrayHasKey('evento', $resultado);
        $this->assertArrayHasKey('items', $resultado);
        $this->assertStringContainsString(self::PRODUCTO_TEST, $resultado['evento']['title']);
        $this->assertEquals(self::FECHA_INICIO, $resultado['evento']['start']);
        $this->assertEquals(self::FECHA_FIN, $resultado['evento']['end']);
    }

    public function test_construir_evento_formato_antiguo(): void
    {
        $inventario = Inventario::create([
            'descripcion' => self::PRODUCTO_TEST,
            'stock' => 10,
        ]);

        $movimiento = MovimientosInventario::create([
            'inventario_id' => $inventario->id,
            'tipo_movimiento' => 'entrada',
            'cantidad' => 10,
            'fecha_movimiento' => now(),
            'descripcion' => self::MOVIMIENTO_DE_PRUEBA,
        ]);

        $calendario = Calendario::create([
            'personas_id' => $this->persona->id,
            'fecha' => now(),
            'fecha_inicio' => self::FECHA_INICIO,
            'fecha_fin' => self::FECHA_FIN,
            'evento' => self::EVENTO_DE_PRUEBA,
            'descripcion_evento' => 'Descripción del evento',
            'movimientos_inventario_id' => $movimiento->id,
            'cantidad' => 5,
        ]);

        $calendario->setRelation('items', collect());

        $movimientos = collect([$movimiento->id => $movimiento])->keyBy('id');
        $inventarios = collect([$inventario->id => $inventario])->keyBy('id');

        $resultado = $this->service->construirEvento($calendario, $movimientos, $inventarios);

        $this->assertIsArray($resultado);
        $this->assertArrayHasKey('evento', $resultado);
        $this->assertStringContainsString(self::PRODUCTO_TEST, $resultado['evento']['title']);
        $this->assertStringContainsString('(x5)', $resultado['evento']['title']);
    }

    public function test_construir_evento_sin_items_ni_movimiento(): void
    {
        $calendario = Calendario::create([
            'personas_id' => $this->persona->id,
            'fecha' => now(),
            'fecha_inicio' => self::FECHA_INICIO,
            'fecha_fin' => self::FECHA_FIN,
            'evento' => self::EVENTO_DE_PRUEBA,
        ]);

        $calendario->setRelation('items', collect());

        $movimientos = collect();
        $inventarios = collect();

        $resultado = $this->service->construirEvento($calendario, $movimientos, $inventarios);

        $this->assertIsArray($resultado);
        $this->assertArrayHasKey('evento', $resultado);
        $this->assertEquals('Sin producto', $resultado['evento']['title']);
    }

    public function test_construir_evento_con_multiples_items(): void
    {
        $inventario1 = Inventario::create([
            'descripcion' => self::PRODUCTO_TEST . ' 1',
            'stock' => 10,
        ]);

        $inventario2 = Inventario::create([
            'descripcion' => self::PRODUCTO_TEST . ' 2',
            'stock' => 10,
        ]);

        $movimiento1 = MovimientosInventario::create([
            'inventario_id' => $inventario1->id,
            'tipo_movimiento' => 'entrada',
            'cantidad' => 10,
            'fecha_movimiento' => now(),
            'descripcion' => 'Movimiento 1',
        ]);

        $movimiento2 = MovimientosInventario::create([
            'inventario_id' => $inventario2->id,
            'tipo_movimiento' => 'entrada',
            'cantidad' => 10,
            'fecha_movimiento' => now(),
            'descripcion' => 'Movimiento 2',
        ]);

        $calendario = Calendario::create([
            'personas_id' => $this->persona->id,
            'fecha' => now(),
            'fecha_inicio' => self::FECHA_INICIO,
            'fecha_fin' => self::FECHA_FIN,
            'evento' => self::EVENTO_DE_PRUEBA,
        ]);

        CalendarioItem::create([
            'calendario_id' => $calendario->id,
            'movimientos_inventario_id' => $movimiento1->id,
            'cantidad' => 2,
        ]);

        CalendarioItem::create([
            'calendario_id' => $calendario->id,
            'movimientos_inventario_id' => $movimiento2->id,
            'cantidad' => 3,
        ]);

        $calendario->load('items');

        $movimientos = collect([$movimiento1->id => $movimiento1, $movimiento2->id => $movimiento2])->keyBy('id');
        $inventarios = collect([$inventario1->id => $inventario1, $inventario2->id => $inventario2])->keyBy('id');

        $resultado = $this->service->construirEvento($calendario, $movimientos, $inventarios);

        $this->assertIsArray($resultado);
        $this->assertCount(2, $resultado['items']);
        $this->assertStringContainsString(self::PRODUCTO_TEST . ' 1', $resultado['evento']['title']);
        $this->assertStringContainsString(self::PRODUCTO_TEST . ' 2', $resultado['evento']['title']);
    }

    public function test_construir_evento_con_item_sin_inventario_en_coleccion(): void
    {
        $inventario = Inventario::create([
            'descripcion' => self::PRODUCTO_TEST,
            'stock' => 10,
        ]);

        $movimiento = MovimientosInventario::create([
            'inventario_id' => $inventario->id,
            'tipo_movimiento' => 'entrada',
            'cantidad' => 10,
            'fecha_movimiento' => now(),
            'descripcion' => self::MOVIMIENTO_DE_PRUEBA,
        ]);

        $calendario = Calendario::create([
            'personas_id' => $this->persona->id,
            'fecha' => now(),
            'fecha_inicio' => self::FECHA_INICIO,
            'fecha_fin' => self::FECHA_FIN,
            'evento' => self::EVENTO_DE_PRUEBA,
        ]);

        CalendarioItem::create([
            'calendario_id' => $calendario->id,
            'movimientos_inventario_id' => $movimiento->id,
            'cantidad' => 3,
        ]);

        $calendario->load('items');

        // Inventario no está en la colección de inventarios
        $movimientos = collect([$movimiento->id => $movimiento])->keyBy('id');
        $inventarios = collect(); // Colección vacía

        $resultado = $this->service->construirEvento($calendario, $movimientos, $inventarios);

        $this->assertIsArray($resultado);
        $this->assertEquals('Alquiler', $resultado['evento']['title']);
    }

    public function test_construir_evento_formato_antiguo_sin_cantidad(): void
    {
        $inventario = Inventario::create([
            'descripcion' => self::PRODUCTO_TEST,
            'stock' => 10,
        ]);

        $movimiento = MovimientosInventario::create([
            'inventario_id' => $inventario->id,
            'tipo_movimiento' => 'entrada',
            'cantidad' => 10,
            'fecha_movimiento' => now(),
            'descripcion' => self::MOVIMIENTO_DE_PRUEBA,
        ]);

        $calendario = Calendario::create([
            'personas_id' => $this->persona->id,
            'fecha' => now(),
            'fecha_inicio' => self::FECHA_INICIO,
            'fecha_fin' => self::FECHA_FIN,
            'evento' => self::EVENTO_DE_PRUEBA,
            'movimientos_inventario_id' => $movimiento->id,
            'cantidad' => null,
        ]);

        $calendario->setRelation('items', collect());

        $movimientos = collect([$movimiento->id => $movimiento])->keyBy('id');
        $inventarios = collect([$inventario->id => $inventario])->keyBy('id');

        $resultado = $this->service->construirEvento($calendario, $movimientos, $inventarios);

        $this->assertIsArray($resultado);
        $this->assertStringContainsString(self::PRODUCTO_TEST, $resultado['evento']['title']);
        $this->assertStringNotContainsString('(x', $resultado['evento']['title']);
    }

    // ============================================
    // TESTS PARA nextPaletteColor()
    // ============================================

    public function test_next_palette_color_retorna_colores(): void
    {
        $color = $this->service->nextPaletteColor();

        $this->assertIsArray($color);
        $this->assertArrayHasKey('bg', $color);
        $this->assertArrayHasKey('border', $color);
        $this->assertArrayHasKey('text', $color);
        $this->assertNotEmpty($color['bg']);
    }

    public function test_next_palette_color_rota_colores(): void
    {
        $color1 = $this->service->nextPaletteColor();
        $color2 = $this->service->nextPaletteColor();
        $color3 = $this->service->nextPaletteColor();

        $this->assertIsArray($color1);
        $this->assertIsArray($color2);
        $this->assertIsArray($color3);
    }

    public function test_next_palette_color_usa_default_si_palette_vacia(): void
    {
        $service = new class extends CalendarioEventService {
            private array $calendarColorPalette = [];
        };

        $color = $service->nextPaletteColor();

        $this->assertIsArray($color);
        $this->assertArrayHasKey('bg', $color);
    }

    // ============================================
    // TESTS PARA resetPaletteCursor()
    // ============================================

    public function test_reset_palette_cursor(): void
    {
        $this->service->nextPaletteColor();
        $this->service->nextPaletteColor();
        $this->service->resetPaletteCursor();

        // Después de reset, el siguiente color debería ser el primero de nuevo
        $color = $this->service->nextPaletteColor();
        $this->assertIsArray($color);
    }

    // ============================================
    // TESTS ADICIONALES
    // ============================================

    public function test_construir_evento_incluye_calendario_id(): void
    {
        $calendario = Calendario::create([
            'personas_id' => $this->persona->id,
            'fecha' => now(),
            'fecha_inicio' => self::FECHA_INICIO,
            'fecha_fin' => self::FECHA_FIN,
            'evento' => self::EVENTO_DE_PRUEBA,
        ]);

        $calendario->setRelation('items', collect());

        $movimientos = collect();
        $inventarios = collect();

        $resultado = $this->service->construirEvento($calendario, $movimientos, $inventarios);

        $this->assertEquals($calendario->id, $resultado['evento']['calendarioId']);
    }

    public function test_construir_evento_incluye_inventario_ids(): void
    {
        $inventario = Inventario::create([
            'descripcion' => self::PRODUCTO_TEST,
            'stock' => 10,
        ]);

        $movimiento = MovimientosInventario::create([
            'inventario_id' => $inventario->id,
            'tipo_movimiento' => 'entrada',
            'cantidad' => 10,
            'fecha_movimiento' => now(),
            'descripcion' => self::MOVIMIENTO_DE_PRUEBA,
        ]);

        $calendario = Calendario::create([
            'personas_id' => $this->persona->id,
            'fecha' => now(),
            'fecha_inicio' => self::FECHA_INICIO,
            'fecha_fin' => self::FECHA_FIN,
            'evento' => self::EVENTO_DE_PRUEBA,
            'movimientos_inventario_id' => $movimiento->id,
            'cantidad' => 5,
        ]);

        $calendario->setRelation('items', collect());

        $movimientos = collect([$movimiento->id => $movimiento])->keyBy('id');
        $inventarios = collect([$inventario->id => $inventario])->keyBy('id');

        $resultado = $this->service->construirEvento($calendario, $movimientos, $inventarios);

        $this->assertIsArray($resultado['evento']['inventarioIds']);
        $this->assertContains($inventario->id, $resultado['evento']['inventarioIds']);
    }

    public function test_construir_evento_con_descripcion_evento_vacia(): void
    {
        $inventario = Inventario::create([
            'descripcion' => self::PRODUCTO_TEST,
            'stock' => 10,
        ]);

        $movimiento = MovimientosInventario::create([
            'inventario_id' => $inventario->id,
            'tipo_movimiento' => 'entrada',
            'cantidad' => 10,
            'fecha_movimiento' => now(),
            'descripcion' => self::MOVIMIENTO_DE_PRUEBA,
        ]);

        $calendario = Calendario::create([
            'personas_id' => $this->persona->id,
            'fecha' => now(),
            'fecha_inicio' => self::FECHA_INICIO,
            'fecha_fin' => self::FECHA_FIN,
            'evento' => self::EVENTO_DE_PRUEBA,
            'descripcion_evento' => null,
            'movimientos_inventario_id' => $movimiento->id,
            'cantidad' => 5,
        ]);

        $calendario->setRelation('items', collect());

        $movimientos = collect([$movimiento->id => $movimiento])->keyBy('id');
        $inventarios = collect([$inventario->id => $inventario])->keyBy('id');

        $resultado = $this->service->construirEvento($calendario, $movimientos, $inventarios);

        $this->assertIsArray($resultado);
        $this->assertIsString($resultado['evento']['description']);
    }
}

