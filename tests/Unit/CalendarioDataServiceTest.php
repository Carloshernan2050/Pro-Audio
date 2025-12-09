<?php

namespace Tests\Unit;

use App\Models\Calendario;
use App\Models\CalendarioItem;
use App\Models\Inventario;
use App\Models\MovimientosInventario;
use App\Models\Usuario;
use App\Services\CalendarioDataService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Collection;
use Tests\TestCase;

class CalendarioDataServiceTest extends TestCase
{
    use RefreshDatabase;

    private const EVENTO_DE_PRUEBA = 'Evento de prueba';

    private const PRODUCTO_TEST = 'Producto Test';

    private const FECHA_INICIO = '2024-01-01';

    private const FECHA_FIN = '2024-01-05';

    private CalendarioDataService $service;

    private Usuario $persona;

    protected function setUp(): void
    {
        parent::setUp();
        $this->service = app(CalendarioDataService::class);
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
    // TESTS PARA eliminarDuplicadosRegistros()
    // ============================================

    public function test_eliminar_duplicados_registros_sin_duplicados(): void
    {
        $calendario1 = Calendario::create([
            'personas_id' => $this->persona->id,
            'fecha' => now(),
            'fecha_inicio' => self::FECHA_INICIO,
            'fecha_fin' => self::FECHA_FIN,
            'evento' => self::EVENTO_DE_PRUEBA,
            'descripcion_evento' => 'Evento 1',
        ]);

        $calendario2 = Calendario::create([
            'personas_id' => $this->persona->id,
            'fecha' => now(),
            'fecha_inicio' => '2024-02-01',
            'fecha_fin' => '2024-02-05',
            'evento' => self::EVENTO_DE_PRUEBA,
            'descripcion_evento' => 'Evento 2',
        ]);

        $calendario1->setRelation('items', collect());
        $calendario2->setRelation('items', collect());

        $registros = collect([$calendario1, $calendario2]);
        $resultado = $this->service->eliminarDuplicadosRegistros($registros);

        $this->assertCount(2, $resultado);
    }

    public function test_eliminar_duplicados_registros_con_duplicados(): void
    {
        $calendario1 = Calendario::create([
            'personas_id' => $this->persona->id,
            'fecha' => now(),
            'fecha_inicio' => self::FECHA_INICIO,
            'fecha_fin' => self::FECHA_FIN,
            'evento' => self::EVENTO_DE_PRUEBA,
            'descripcion_evento' => 'Evento',
        ]);

        $calendario2 = Calendario::create([
            'personas_id' => $this->persona->id,
            'fecha' => now(),
            'fecha_inicio' => self::FECHA_INICIO,
            'fecha_fin' => self::FECHA_FIN,
            'evento' => self::EVENTO_DE_PRUEBA,
            'descripcion_evento' => 'Evento',
        ]);

        $calendario1->setRelation('items', collect());
        $calendario2->setRelation('items', collect());

        $registros = collect([$calendario1, $calendario2]);
        $resultado = $this->service->eliminarDuplicadosRegistros($registros);

        $this->assertCount(1, $resultado);
    }

    public function test_eliminar_duplicados_registros_con_items_diferentes(): void
    {
        $inventario = Inventario::create([
            'descripcion' => self::PRODUCTO_TEST,
            'stock' => 10,
        ]);

        $movimiento1 = MovimientosInventario::create([
            'inventario_id' => $inventario->id,
            'tipo_movimiento' => 'entrada',
            'cantidad' => 10,
            'fecha_movimiento' => now(),
            'descripcion' => 'Movimiento 1',
        ]);

        $movimiento2 = MovimientosInventario::create([
            'inventario_id' => $inventario->id,
            'tipo_movimiento' => 'entrada',
            'cantidad' => 10,
            'fecha_movimiento' => now(),
            'descripcion' => 'Movimiento 2',
        ]);

        $calendario1 = Calendario::create([
            'personas_id' => $this->persona->id,
            'fecha' => now(),
            'fecha_inicio' => self::FECHA_INICIO,
            'fecha_fin' => self::FECHA_FIN,
            'evento' => self::EVENTO_DE_PRUEBA,
            'descripcion_evento' => 'Evento',
        ]);

        $calendario2 = Calendario::create([
            'personas_id' => $this->persona->id,
            'fecha' => now(),
            'fecha_inicio' => self::FECHA_INICIO,
            'fecha_fin' => self::FECHA_FIN,
            'evento' => self::EVENTO_DE_PRUEBA,
            'descripcion_evento' => 'Evento',
        ]);

        CalendarioItem::create([
            'calendario_id' => $calendario1->id,
            'movimientos_inventario_id' => $movimiento1->id,
            'cantidad' => 3,
        ]);

        CalendarioItem::create([
            'calendario_id' => $calendario2->id,
            'movimientos_inventario_id' => $movimiento2->id,
            'cantidad' => 3,
        ]);

        $calendario1->load('items');
        $calendario2->load('items');

        $registros = collect([$calendario1, $calendario2]);
        $resultado = $this->service->eliminarDuplicadosRegistros($registros);

        // Deberían ser diferentes porque tienen diferentes movimientos_inventario_id
        $this->assertCount(2, $resultado);
    }

    // ============================================
    // TESTS PARA generarClaveUnicaRegistro()
    // ============================================

    public function test_generar_clave_unica_registro(): void
    {
        $calendario = Calendario::create([
            'personas_id' => $this->persona->id,
            'fecha' => now(),
            'fecha_inicio' => self::FECHA_INICIO,
            'fecha_fin' => self::FECHA_FIN,
            'evento' => self::EVENTO_DE_PRUEBA,
            'descripcion_evento' => 'Evento',
        ]);

        $calendario->setRelation('items', collect());

        $clave = $this->service->generarClaveUnicaRegistro($calendario);

        $this->assertIsString($clave);
        $this->assertStringContainsString(self::FECHA_INICIO, $clave);
        $this->assertStringContainsString(self::FECHA_FIN, $clave);
        $this->assertStringContainsString('Evento', $clave);
    }

    public function test_generar_clave_unica_registro_con_items(): void
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
            'descripcion' => 'Movimiento',
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

        $clave = $this->service->generarClaveUnicaRegistro($calendario);

        $this->assertIsString($clave);
        $this->assertStringContainsString((string) $movimiento->id, $clave);
        $this->assertStringContainsString('3', $clave);
    }

    // ============================================
    // TESTS PARA transformarRegistroAData()
    // ============================================

    public function test_transformar_registro_a_data_desde_items(): void
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
            'descripcion' => 'Movimiento',
        ]);

        $calendario = Calendario::create([
            'personas_id' => $this->persona->id,
            'fecha' => now(),
            'fecha_inicio' => self::FECHA_INICIO,
            'fecha_fin' => self::FECHA_FIN,
            'evento' => self::EVENTO_DE_PRUEBA,
            'descripcion_evento' => 'Descripción',
        ]);

        CalendarioItem::create([
            'calendario_id' => $calendario->id,
            'movimientos_inventario_id' => $movimiento->id,
            'cantidad' => 3,
        ]);

        $calendario->load('items');

        $movimientos = collect([$movimiento->id => $movimiento])->keyBy('id');
        $inventarios = collect([$inventario->id => $inventario])->keyBy('id');

        $resultado = $this->service->transformarRegistroAData($calendario, $movimientos, $inventarios);

        $this->assertIsArray($resultado);
        $this->assertEquals($calendario->id, $resultado['id']);
        $this->assertArrayHasKey('fecha_inicio', $resultado);
        $this->assertArrayHasKey('fecha_fin', $resultado);
        $this->assertArrayHasKey('dias_reserva', $resultado);
        $this->assertArrayHasKey('productos', $resultado);
        $this->assertArrayHasKey('cantidad_total', $resultado);
        $this->assertEquals(5, $resultado['dias_reserva']); // 5 días entre 01-01 y 01-05
        $this->assertEquals(3, $resultado['cantidad_total']);
    }

    public function test_transformar_registro_a_data_formato_antiguo(): void
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
            'descripcion' => 'Movimiento',
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

        $resultado = $this->service->transformarRegistroAData($calendario, $movimientos, $inventarios);

        $this->assertIsArray($resultado);
        $this->assertEquals(5, $resultado['cantidad_total']);
        $this->assertCount(1, $resultado['productos']);
        $this->assertEquals(self::PRODUCTO_TEST, $resultado['productos'][0]['nombre']);
    }

    public function test_transformar_registro_a_data_sin_productos(): void
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

        $resultado = $this->service->transformarRegistroAData($calendario, $movimientos, $inventarios);

        $this->assertIsArray($resultado);
        $this->assertEquals(0, $resultado['cantidad_total']);
        $this->assertIsArray($resultado['productos']);
    }

    public function test_transformar_registro_a_data_con_multiples_items(): void
    {
        $inventario1 = Inventario::create([
            'descripcion' => self::PRODUCTO_TEST.' 1',
            'stock' => 10,
        ]);

        $inventario2 = Inventario::create([
            'descripcion' => self::PRODUCTO_TEST.' 2',
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

        $resultado = $this->service->transformarRegistroAData($calendario, $movimientos, $inventarios);

        $this->assertIsArray($resultado);
        $this->assertEquals(5, $resultado['cantidad_total']); // 2 + 3
        $this->assertCount(2, $resultado['productos']);
    }

    // ============================================
    // TESTS PARA obtenerRegistrosUnicos()
    // ============================================

    public function test_obtener_registros_unicos(): void
    {
        Calendario::create([
            'personas_id' => $this->persona->id,
            'fecha' => now(),
            'fecha_inicio' => self::FECHA_INICIO,
            'fecha_fin' => self::FECHA_FIN,
            'evento' => self::EVENTO_DE_PRUEBA,
        ]);

        $resultado = $this->service->obtenerRegistrosUnicos();

        $this->assertInstanceOf(Collection::class, $resultado);
        $this->assertGreaterThanOrEqual(1, $resultado->count());
    }

    // ============================================
    // TESTS PARA cargarMovimientosEInventarios()
    // ============================================

    public function test_cargar_movimientos_e_inventarios(): void
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
            'descripcion' => 'Movimiento',
        ]);

        [$movimientos, $inventarios] = $this->service->cargarMovimientosEInventarios();

        $this->assertInstanceOf(Collection::class, $movimientos);
        $this->assertInstanceOf(Collection::class, $inventarios);
        $this->assertTrue($movimientos->has($movimiento->id));
        $this->assertTrue($inventarios->has($inventario->id));
    }

    public function test_cargar_movimientos_e_inventarios_vacios(): void
    {
        [$movimientos, $inventarios] = $this->service->cargarMovimientosEInventarios();

        $this->assertInstanceOf(Collection::class, $movimientos);
        $this->assertInstanceOf(Collection::class, $inventarios);
        $this->assertCount(0, $movimientos);
        $this->assertCount(0, $inventarios);
    }

    // ============================================
    // TESTS ADICIONALES
    // ============================================

    public function test_transformar_registro_a_data_incluye_tiene_items(): void
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
            'descripcion' => 'Movimiento',
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

        $movimientos = collect([$movimiento->id => $movimiento])->keyBy('id');
        $inventarios = collect([$inventario->id => $inventario])->keyBy('id');

        $resultado = $this->service->transformarRegistroAData($calendario, $movimientos, $inventarios);

        $this->assertTrue($resultado['tiene_items']);
    }

    public function test_transformar_registro_a_data_sin_items(): void
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

        $resultado = $this->service->transformarRegistroAData($calendario, $movimientos, $inventarios);

        $this->assertFalse($resultado['tiene_items']);
    }

    public function test_transformar_registro_a_data_formato_antiguo_sin_cantidad(): void
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
            'descripcion' => 'Movimiento',
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

        $resultado = $this->service->transformarRegistroAData($calendario, $movimientos, $inventarios);

        $this->assertEquals(1, $resultado['cantidad_total']); // Default a 1
    }
}
