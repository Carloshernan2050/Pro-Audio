<?php

namespace Tests\Unit;

use App\Exceptions\InventarioNotFoundException;
use App\Exceptions\StockInsuficienteException;
use App\Models\Calendario;
use App\Models\CalendarioItem;
use App\Models\Inventario;
use App\Models\MovimientosInventario;
use App\Models\Usuario;
use App\Services\CalendarioValidationService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Request;
use Tests\TestCase;

class CalendarioValidationServiceTest extends TestCase
{
    use RefreshDatabase;

    private const TEST_PATH = '/test';

    private const PRODUCTO_TEST = 'Producto Test';

    private const FECHA_INICIO_ORIGINAL = '2024-01-01';

    private const FECHA_FIN_ORIGINAL = '2024-01-05';

    private const FECHA_INICIO_MEDIA = '2024-01-02';

    private const FECHA_FIN_MEDIA = '2024-01-04';

    private const EVENTO_DE_PRUEBA = 'Evento de prueba';

    private const MOVIMIENTO_DE_PRUEBA = 'Movimiento de prueba';

    private CalendarioValidationService $service;

    private Usuario $persona;

    protected function setUp(): void
    {
        parent::setUp();
        $this->service = new CalendarioValidationService;
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

    public function test_get_validation_rules_for_items(): void
    {
        $rules = $this->service->getValidationRulesForItems();

        $this->assertIsArray($rules);
        $this->assertArrayHasKey('items', $rules);
        $this->assertArrayHasKey('fecha_inicio', $rules);
        $this->assertArrayHasKey('fecha_fin', $rules);
        $this->assertArrayHasKey('descripcion_evento', $rules);
    }

    public function test_get_validation_messages_for_items(): void
    {
        $messages = $this->service->getValidationMessagesForItems();

        $this->assertIsArray($messages);
        $this->assertArrayHasKey('items.required', $messages);
        $this->assertArrayHasKey('fecha_inicio.required', $messages);
    }

    public function test_get_validation_rules_for_old_format(): void
    {
        $rules = $this->service->getValidationRulesForOldFormat();

        $this->assertIsArray($rules);
        $this->assertArrayHasKey('movimientos_inventario_id', $rules);
        $this->assertArrayHasKey('fecha_inicio', $rules);
    }

    public function test_get_validation_messages_for_old_format(): void
    {
        $messages = $this->service->getValidationMessagesForOldFormat();

        $this->assertIsArray($messages);
        $this->assertArrayHasKey('movimientos_inventario_id.required', $messages);
    }

    public function test_get_validation_rules_for_update_items(): void
    {
        $rules = $this->service->getValidationRulesForUpdateItems();

        $this->assertIsArray($rules);
        $this->assertArrayHasKey('servicio', $rules);
        $this->assertArrayHasKey('items', $rules);
    }

    public function test_get_validation_messages_for_update_items(): void
    {
        $messages = $this->service->getValidationMessagesForUpdateItems();

        $this->assertIsArray($messages);
        $this->assertArrayHasKey('servicio.required', $messages);
        $this->assertArrayHasKey('items.required', $messages);
    }

    public function test_get_validation_rules_for_update_old_format(): void
    {
        $rules = $this->service->getValidationRulesForUpdateOldFormat();

        $this->assertIsArray($rules);
        $this->assertArrayHasKey('servicio', $rules);
        $this->assertArrayHasKey('cantidad', $rules);
    }

    public function test_get_validation_messages_for_update_old_format(): void
    {
        $messages = $this->service->getValidationMessagesForUpdateOldFormat();

        $this->assertIsArray($messages);
        $this->assertArrayHasKey('servicio.required', $messages);
        $this->assertArrayHasKey('cantidad.integer', $messages);
    }

    public function test_validar_stock_para_items_suficiente(): void
    {
        $inventario = Inventario::create([
            'descripcion' => self::PRODUCTO_TEST,
            'stock' => 10,
        ]);

        $request = Request::create(self::TEST_PATH, 'POST', [
            'fecha_inicio' => self::FECHA_INICIO_ORIGINAL,
            'fecha_fin' => self::FECHA_FIN_ORIGINAL,
        ]);

        $items = [
            ['inventario_id' => $inventario->id, 'cantidad' => 5],
        ];

        $this->service->validarStockParaItems($request, $items);
        $this->assertTrue(true); // Si no lanza excepción, el test pasa
    }

    public function test_validar_stock_para_items_inventario_no_existe(): void
    {
        $request = Request::create(self::TEST_PATH, 'POST', [
            'fecha_inicio' => self::FECHA_INICIO_ORIGINAL,
            'fecha_fin' => self::FECHA_FIN_ORIGINAL,
        ]);

        $items = [
            ['inventario_id' => 999, 'cantidad' => 5],
        ];

        $this->expectException(InventarioNotFoundException::class);
        $this->service->validarStockParaItems($request, $items);
    }

    public function test_validar_stock_para_items_stock_insuficiente(): void
    {
        $inventario = Inventario::create([
            'descripcion' => self::PRODUCTO_TEST,
            'stock' => 5,
        ]);

        $request = Request::create(self::TEST_PATH, 'POST', [
            'fecha_inicio' => self::FECHA_INICIO_ORIGINAL,
            'fecha_fin' => self::FECHA_FIN_ORIGINAL,
        ]);

        $items = [
            ['inventario_id' => $inventario->id, 'cantidad' => 10],
        ];

        $this->expectException(StockInsuficienteException::class);
        $this->service->validarStockParaItems($request, $items);
    }

    public function test_calcular_reservadas(): void
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
            'fecha_inicio' => self::FECHA_INICIO_MEDIA,
            'fecha_fin' => self::FECHA_FIN_MEDIA,
            'evento' => self::EVENTO_DE_PRUEBA,
        ]);

        CalendarioItem::create([
            'calendario_id' => $calendario->id,
            'movimientos_inventario_id' => $movimiento->id,
            'cantidad' => 3,
        ]);

        $reservadas = $this->service->calcularReservadas($inventario->id, self::FECHA_INICIO_ORIGINAL, self::FECHA_FIN_ORIGINAL);

        $this->assertEquals(3, $reservadas);
    }

    public function test_calcular_reservadas_excluyendo(): void
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

        $calendario1 = Calendario::create([
            'personas_id' => $this->persona->id,
            'fecha' => now(),
            'fecha_inicio' => self::FECHA_INICIO_MEDIA,
            'fecha_fin' => self::FECHA_FIN_MEDIA,
            'evento' => self::EVENTO_DE_PRUEBA,
        ]);

        $calendario2 = Calendario::create([
            'personas_id' => $this->persona->id,
            'fecha' => now(),
            'fecha_inicio' => self::FECHA_INICIO_MEDIA,
            'fecha_fin' => self::FECHA_FIN_MEDIA,
            'evento' => self::EVENTO_DE_PRUEBA,
        ]);

        CalendarioItem::create([
            'calendario_id' => $calendario1->id,
            'movimientos_inventario_id' => $movimiento->id,
            'cantidad' => 3,
        ]);

        CalendarioItem::create([
            'calendario_id' => $calendario2->id,
            'movimientos_inventario_id' => $movimiento->id,
            'cantidad' => 2,
        ]);

        $reservadas = $this->service->calcularReservadasExcluyendo($inventario->id, self::FECHA_INICIO_ORIGINAL, self::FECHA_FIN_ORIGINAL, $calendario1->id);

        $this->assertEquals(2, $reservadas);
    }

    // ============================================
    // TESTS ADICIONALES PARA validarStockParaItems()
    // ============================================

    public function test_validar_stock_para_items_con_reservas_existentes(): void
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
            'fecha_inicio' => self::FECHA_INICIO_MEDIA,
            'fecha_fin' => self::FECHA_FIN_MEDIA,
            'evento' => self::EVENTO_DE_PRUEBA,
        ]);

        CalendarioItem::create([
            'calendario_id' => $calendario->id,
            'movimientos_inventario_id' => $movimiento->id,
            'cantidad' => 5, // 5 reservadas
        ]);

        $request = Request::create(self::TEST_PATH, 'POST', [
            'fecha_inicio' => self::FECHA_INICIO_ORIGINAL,
            'fecha_fin' => self::FECHA_FIN_ORIGINAL,
        ]);

        $items = [
            ['inventario_id' => $inventario->id, 'cantidad' => 3], // 10 - 5 = 5 disponible, 3 solicitado
        ];

        $this->service->validarStockParaItems($request, $items);
        $this->assertTrue(true); // Si no lanza excepción, el test pasa
    }

    public function test_validar_stock_para_items_con_reservas_insuficiente(): void
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
            'fecha_inicio' => self::FECHA_INICIO_MEDIA,
            'fecha_fin' => self::FECHA_FIN_MEDIA,
            'evento' => self::EVENTO_DE_PRUEBA,
        ]);

        CalendarioItem::create([
            'calendario_id' => $calendario->id,
            'movimientos_inventario_id' => $movimiento->id,
            'cantidad' => 7, // 7 reservadas
        ]);

        $request = Request::create(self::TEST_PATH, 'POST', [
            'fecha_inicio' => self::FECHA_INICIO_ORIGINAL,
            'fecha_fin' => self::FECHA_FIN_ORIGINAL,
        ]);

        $items = [
            ['inventario_id' => $inventario->id, 'cantidad' => 5], // 10 - 7 = 3 disponible, 5 solicitado
        ];

        $this->expectException(StockInsuficienteException::class);
        $this->service->validarStockParaItems($request, $items);
    }

    public function test_validar_stock_para_items_con_cantidad_default(): void
    {
        $inventario = Inventario::create([
            'descripcion' => self::PRODUCTO_TEST,
            'stock' => 10,
        ]);

        $request = Request::create(self::TEST_PATH, 'POST', [
            'fecha_inicio' => self::FECHA_INICIO_ORIGINAL,
            'fecha_fin' => self::FECHA_FIN_ORIGINAL,
        ]);

        $items = [
            ['inventario_id' => $inventario->id], // Sin cantidad, debería usar 1 por defecto
        ];

        $this->service->validarStockParaItems($request, $items);
        $this->assertTrue(true);
    }

    public function test_validar_stock_para_items_con_stock_cero(): void
    {
        $inventario = Inventario::create([
            'descripcion' => self::PRODUCTO_TEST,
            'stock' => 0, // Stock cero
        ]);

        $request = Request::create(self::TEST_PATH, 'POST', [
            'fecha_inicio' => self::FECHA_INICIO_ORIGINAL,
            'fecha_fin' => self::FECHA_FIN_ORIGINAL,
        ]);

        $items = [
            ['inventario_id' => $inventario->id, 'cantidad' => 1],
        ];

        $this->expectException(StockInsuficienteException::class);
        $this->service->validarStockParaItems($request, $items);
    }

    public function test_validar_stock_para_items_con_multiples_items(): void
    {
        $inventario1 = Inventario::create([
            'descripcion' => self::PRODUCTO_TEST.' 1',
            'stock' => 10,
        ]);

        $inventario2 = Inventario::create([
            'descripcion' => self::PRODUCTO_TEST.' 2',
            'stock' => 10,
        ]);

        $request = Request::create(self::TEST_PATH, 'POST', [
            'fecha_inicio' => self::FECHA_INICIO_ORIGINAL,
            'fecha_fin' => self::FECHA_FIN_ORIGINAL,
        ]);

        $items = [
            ['inventario_id' => $inventario1->id, 'cantidad' => 5],
            ['inventario_id' => $inventario2->id, 'cantidad' => 3],
        ];

        $this->service->validarStockParaItems($request, $items);
        $this->assertTrue(true);
    }

    public function test_validar_stock_para_items_con_producto_descripcion_vacia(): void
    {
        $inventario = Inventario::create([
            'descripcion' => '', // Descripción vacía
            'stock' => 5,
        ]);

        $request = Request::create(self::TEST_PATH, 'POST', [
            'fecha_inicio' => self::FECHA_INICIO_ORIGINAL,
            'fecha_fin' => self::FECHA_FIN_ORIGINAL,
        ]);

        $items = [
            ['inventario_id' => $inventario->id, 'cantidad' => 10],
        ];

        $this->expectException(StockInsuficienteException::class);
        $this->service->validarStockParaItems($request, $items);
    }

    // ============================================
    // TESTS PARA validarStockParaActualizacion()
    // ============================================

    public function test_validar_stock_para_actualizacion_suficiente(): void
    {
        $inventario = Inventario::create([
            'descripcion' => self::PRODUCTO_TEST,
            'stock' => 10,
        ]);

        $calendario = Calendario::create([
            'personas_id' => $this->persona->id,
            'fecha' => now(),
            'fecha_inicio' => self::FECHA_INICIO_MEDIA,
            'fecha_fin' => self::FECHA_FIN_MEDIA,
            'evento' => self::EVENTO_DE_PRUEBA,
        ]);

        $movimiento = MovimientosInventario::create([
            'inventario_id' => $inventario->id,
            'tipo_movimiento' => 'entrada',
            'cantidad' => 10,
            'fecha_movimiento' => now(),
            'descripcion' => self::MOVIMIENTO_DE_PRUEBA,
        ]);

        CalendarioItem::create([
            'calendario_id' => $calendario->id,
            'movimientos_inventario_id' => $movimiento->id,
            'cantidad' => 3, // Cantidad actual reservada
        ]);

        $request = Request::create(self::TEST_PATH, 'PUT', [
            'fecha_inicio' => self::FECHA_INICIO_ORIGINAL,
            'fecha_fin' => self::FECHA_FIN_ORIGINAL,
        ]);

        $items = [
            ['inventario_id' => $inventario->id, 'cantidad' => 5],
        ];

        $itemsActuales = [$inventario->id => 3]; // Cantidad actual en el calendario

        $this->service->validarStockParaActualizacion($request, $items, $itemsActuales, $calendario->id);
        $this->assertTrue(true);
    }

    public function test_validar_stock_para_actualizacion_inventario_no_existe(): void
    {
        $calendario = Calendario::create([
            'personas_id' => $this->persona->id,
            'fecha' => now(),
            'fecha_inicio' => self::FECHA_INICIO_MEDIA,
            'fecha_fin' => self::FECHA_FIN_MEDIA,
            'evento' => self::EVENTO_DE_PRUEBA,
        ]);

        $request = Request::create(self::TEST_PATH, 'PUT', [
            'fecha_inicio' => self::FECHA_INICIO_ORIGINAL,
            'fecha_fin' => self::FECHA_FIN_ORIGINAL,
        ]);

        $items = [
            ['inventario_id' => 999, 'cantidad' => 5],
        ];

        $itemsActuales = [];

        $this->expectException(InventarioNotFoundException::class);
        $this->service->validarStockParaActualizacion($request, $items, $itemsActuales, $calendario->id);
    }

    public function test_validar_stock_para_actualizacion_stock_insuficiente(): void
    {
        $inventario = Inventario::create([
            'descripcion' => self::PRODUCTO_TEST,
            'stock' => 10,
        ]);

        $calendario = Calendario::create([
            'personas_id' => $this->persona->id,
            'fecha' => now(),
            'fecha_inicio' => self::FECHA_INICIO_MEDIA,
            'fecha_fin' => self::FECHA_FIN_MEDIA,
            'evento' => self::EVENTO_DE_PRUEBA,
        ]);

        $movimiento = MovimientosInventario::create([
            'inventario_id' => $inventario->id,
            'tipo_movimiento' => 'entrada',
            'cantidad' => 10,
            'fecha_movimiento' => now(),
            'descripcion' => self::MOVIMIENTO_DE_PRUEBA,
        ]);

        CalendarioItem::create([
            'calendario_id' => $calendario->id,
            'movimientos_inventario_id' => $movimiento->id,
            'cantidad' => 3,
        ]);

        // Crear otra reserva que se solape
        $calendario2 = Calendario::create([
            'personas_id' => $this->persona->id,
            'fecha' => now(),
            'fecha_inicio' => self::FECHA_INICIO_MEDIA,
            'fecha_fin' => self::FECHA_FIN_MEDIA,
            'evento' => self::EVENTO_DE_PRUEBA,
        ]);

        CalendarioItem::create([
            'calendario_id' => $calendario2->id,
            'movimientos_inventario_id' => $movimiento->id,
            'cantidad' => 5, // 5 reservadas en otro calendario
        ]);

        $request = Request::create(self::TEST_PATH, 'PUT', [
            'fecha_inicio' => self::FECHA_INICIO_ORIGINAL,
            'fecha_fin' => self::FECHA_FIN_ORIGINAL,
        ]);

        $items = [
            ['inventario_id' => $inventario->id, 'cantidad' => 10], // 10 + 3 - 5 = 8 disponible, 10 solicitado
        ];

        $itemsActuales = [$inventario->id => 3];

        $this->expectException(StockInsuficienteException::class);
        $this->service->validarStockParaActualizacion($request, $items, $itemsActuales, $calendario->id);
    }

    public function test_validar_stock_para_actualizacion_con_cantidad_default(): void
    {
        $inventario = Inventario::create([
            'descripcion' => self::PRODUCTO_TEST,
            'stock' => 10,
        ]);

        $calendario = Calendario::create([
            'personas_id' => $this->persona->id,
            'fecha' => now(),
            'fecha_inicio' => self::FECHA_INICIO_MEDIA,
            'fecha_fin' => self::FECHA_FIN_MEDIA,
            'evento' => self::EVENTO_DE_PRUEBA,
        ]);

        $request = Request::create(self::TEST_PATH, 'PUT', [
            'fecha_inicio' => self::FECHA_INICIO_ORIGINAL,
            'fecha_fin' => self::FECHA_FIN_ORIGINAL,
        ]);

        $items = [
            ['inventario_id' => $inventario->id], // Sin cantidad, debería usar 1
        ];

        $itemsActuales = [$inventario->id => 2];

        $this->service->validarStockParaActualizacion($request, $items, $itemsActuales, $calendario->id);
        $this->assertTrue(true);
    }

    public function test_validar_stock_para_actualizacion_con_cantidad_actual_cero(): void
    {
        $inventario = Inventario::create([
            'descripcion' => self::PRODUCTO_TEST,
            'stock' => 10,
        ]);

        $calendario = Calendario::create([
            'personas_id' => $this->persona->id,
            'fecha' => now(),
            'fecha_inicio' => self::FECHA_INICIO_MEDIA,
            'fecha_fin' => self::FECHA_FIN_MEDIA,
            'evento' => self::EVENTO_DE_PRUEBA,
        ]);

        $request = Request::create(self::TEST_PATH, 'PUT', [
            'fecha_inicio' => self::FECHA_INICIO_ORIGINAL,
            'fecha_fin' => self::FECHA_FIN_ORIGINAL,
        ]);

        $items = [
            ['inventario_id' => $inventario->id, 'cantidad' => 5],
        ];

        $itemsActuales = [$inventario->id => 0]; // Sin cantidad actual

        $this->service->validarStockParaActualizacion($request, $items, $itemsActuales, $calendario->id);
        $this->assertTrue(true);
    }

    public function test_validar_stock_para_actualizacion_con_items_actuales_vacio(): void
    {
        $inventario = Inventario::create([
            'descripcion' => self::PRODUCTO_TEST,
            'stock' => 10,
        ]);

        $calendario = Calendario::create([
            'personas_id' => $this->persona->id,
            'fecha' => now(),
            'fecha_inicio' => self::FECHA_INICIO_MEDIA,
            'fecha_fin' => self::FECHA_FIN_MEDIA,
            'evento' => self::EVENTO_DE_PRUEBA,
        ]);

        $request = Request::create(self::TEST_PATH, 'PUT', [
            'fecha_inicio' => self::FECHA_INICIO_ORIGINAL,
            'fecha_fin' => self::FECHA_FIN_ORIGINAL,
        ]);

        $items = [
            ['inventario_id' => $inventario->id, 'cantidad' => 5],
        ];

        $itemsActuales = []; // Array vacío

        $this->service->validarStockParaActualizacion($request, $items, $itemsActuales, $calendario->id);
        $this->assertTrue(true);
    }

    public function test_validar_stock_para_actualizacion_con_multiples_items(): void
    {
        $inventario1 = Inventario::create([
            'descripcion' => self::PRODUCTO_TEST.' 1',
            'stock' => 10,
        ]);

        $inventario2 = Inventario::create([
            'descripcion' => self::PRODUCTO_TEST.' 2',
            'stock' => 10,
        ]);

        $calendario = Calendario::create([
            'personas_id' => $this->persona->id,
            'fecha' => now(),
            'fecha_inicio' => self::FECHA_INICIO_MEDIA,
            'fecha_fin' => self::FECHA_FIN_MEDIA,
            'evento' => self::EVENTO_DE_PRUEBA,
        ]);

        $request = Request::create(self::TEST_PATH, 'PUT', [
            'fecha_inicio' => self::FECHA_INICIO_ORIGINAL,
            'fecha_fin' => self::FECHA_FIN_ORIGINAL,
        ]);

        $items = [
            ['inventario_id' => $inventario1->id, 'cantidad' => 3],
            ['inventario_id' => $inventario2->id, 'cantidad' => 2],
        ];

        $itemsActuales = [
            $inventario1->id => 2,
            $inventario2->id => 1,
        ];

        $this->service->validarStockParaActualizacion($request, $items, $itemsActuales, $calendario->id);
        $this->assertTrue(true);
    }

    // ============================================
    // TESTS ADICIONALES PARA calcularReservadas()
    // ============================================

    public function test_calcular_reservadas_sin_reservas(): void
    {
        $inventario = Inventario::create([
            'descripcion' => self::PRODUCTO_TEST,
            'stock' => 10,
        ]);

        $reservadas = $this->service->calcularReservadas($inventario->id, self::FECHA_INICIO_ORIGINAL, self::FECHA_FIN_ORIGINAL);

        $this->assertEquals(0, $reservadas);
    }

    public function test_calcular_reservadas_con_fechas_no_solapadas(): void
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
            'fecha_inicio' => '2024-02-01', // Después del rango
            'fecha_fin' => '2024-02-05',
            'evento' => self::EVENTO_DE_PRUEBA,
        ]);

        CalendarioItem::create([
            'calendario_id' => $calendario->id,
            'movimientos_inventario_id' => $movimiento->id,
            'cantidad' => 5,
        ]);

        $reservadas = $this->service->calcularReservadas($inventario->id, self::FECHA_INICIO_ORIGINAL, self::FECHA_FIN_ORIGINAL);

        $this->assertEquals(0, $reservadas);
    }

    public function test_calcular_reservadas_con_fechas_solapadas_parcialmente(): void
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
            'fecha_inicio' => '2023-12-30', // Antes del rango pero se solapa
            'fecha_fin' => '2024-01-02', // Se solapa con el rango
            'evento' => self::EVENTO_DE_PRUEBA,
        ]);

        CalendarioItem::create([
            'calendario_id' => $calendario->id,
            'movimientos_inventario_id' => $movimiento->id,
            'cantidad' => 4,
        ]);

        $reservadas = $this->service->calcularReservadas($inventario->id, self::FECHA_INICIO_ORIGINAL, self::FECHA_FIN_ORIGINAL);

        $this->assertEquals(4, $reservadas);
    }

    public function test_calcular_reservadas_con_multiples_reservas(): void
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

        $calendario1 = Calendario::create([
            'personas_id' => $this->persona->id,
            'fecha' => now(),
            'fecha_inicio' => self::FECHA_INICIO_MEDIA,
            'fecha_fin' => self::FECHA_FIN_MEDIA,
            'evento' => self::EVENTO_DE_PRUEBA,
        ]);

        $calendario2 = Calendario::create([
            'personas_id' => $this->persona->id,
            'fecha' => now(),
            'fecha_inicio' => self::FECHA_INICIO_MEDIA,
            'fecha_fin' => self::FECHA_FIN_MEDIA,
            'evento' => self::EVENTO_DE_PRUEBA,
        ]);

        CalendarioItem::create([
            'calendario_id' => $calendario1->id,
            'movimientos_inventario_id' => $movimiento->id,
            'cantidad' => 3,
        ]);

        CalendarioItem::create([
            'calendario_id' => $calendario2->id,
            'movimientos_inventario_id' => $movimiento->id,
            'cantidad' => 2,
        ]);

        $reservadas = $this->service->calcularReservadas($inventario->id, self::FECHA_INICIO_ORIGINAL, self::FECHA_FIN_ORIGINAL);

        $this->assertEquals(5, $reservadas); // 3 + 2 = 5
    }

    public function test_calcular_reservadas_con_fecha_inicio_igual_fecha_fin(): void
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
            'fecha_inicio' => self::FECHA_INICIO_ORIGINAL,
            'fecha_fin' => self::FECHA_FIN_ORIGINAL,
            'evento' => self::EVENTO_DE_PRUEBA,
        ]);

        CalendarioItem::create([
            'calendario_id' => $calendario->id,
            'movimientos_inventario_id' => $movimiento->id,
            'cantidad' => 3,
        ]);

        // Fecha inicio igual a fecha fin del rango consultado
        $reservadas = $this->service->calcularReservadas($inventario->id, self::FECHA_INICIO_ORIGINAL, self::FECHA_INICIO_ORIGINAL);

        $this->assertEquals(0, $reservadas); // No debería contar porque fecha_inicio < fechaFin es false
    }

    // ============================================
    // TESTS ADICIONALES PARA calcularReservadasExcluyendo()
    // ============================================

    public function test_calcular_reservadas_excluyendo_sin_reservas(): void
    {
        $inventario = Inventario::create([
            'descripcion' => self::PRODUCTO_TEST,
            'stock' => 10,
        ]);

        $calendario = Calendario::create([
            'personas_id' => $this->persona->id,
            'fecha' => now(),
            'fecha_inicio' => self::FECHA_INICIO_MEDIA,
            'fecha_fin' => self::FECHA_FIN_MEDIA,
            'evento' => self::EVENTO_DE_PRUEBA,
        ]);

        $reservadas = $this->service->calcularReservadasExcluyendo($inventario->id, self::FECHA_INICIO_ORIGINAL, self::FECHA_FIN_ORIGINAL, $calendario->id);

        $this->assertEquals(0, $reservadas);
    }

    public function test_calcular_reservadas_excluyendo_con_fechas_no_solapadas(): void
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
            'fecha_inicio' => '2024-02-01', // Después del rango
            'fecha_fin' => '2024-02-05',
            'evento' => self::EVENTO_DE_PRUEBA,
        ]);

        CalendarioItem::create([
            'calendario_id' => $calendario->id,
            'movimientos_inventario_id' => $movimiento->id,
            'cantidad' => 5,
        ]);

        $reservadas = $this->service->calcularReservadasExcluyendo($inventario->id, self::FECHA_INICIO_ORIGINAL, self::FECHA_FIN_ORIGINAL, 999);

        $this->assertEquals(0, $reservadas);
    }

    public function test_calcular_reservadas_excluyendo_con_multiples_reservas(): void
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

        $calendario1 = Calendario::create([
            'personas_id' => $this->persona->id,
            'fecha' => now(),
            'fecha_inicio' => self::FECHA_INICIO_MEDIA,
            'fecha_fin' => self::FECHA_FIN_MEDIA,
            'evento' => self::EVENTO_DE_PRUEBA,
        ]);

        $calendario2 = Calendario::create([
            'personas_id' => $this->persona->id,
            'fecha' => now(),
            'fecha_inicio' => self::FECHA_INICIO_MEDIA,
            'fecha_fin' => self::FECHA_FIN_MEDIA,
            'evento' => self::EVENTO_DE_PRUEBA,
        ]);

        $calendario3 = Calendario::create([
            'personas_id' => $this->persona->id,
            'fecha' => now(),
            'fecha_inicio' => self::FECHA_INICIO_MEDIA,
            'fecha_fin' => self::FECHA_FIN_MEDIA,
            'evento' => self::EVENTO_DE_PRUEBA,
        ]);

        CalendarioItem::create([
            'calendario_id' => $calendario1->id,
            'movimientos_inventario_id' => $movimiento->id,
            'cantidad' => 2,
        ]);

        CalendarioItem::create([
            'calendario_id' => $calendario2->id,
            'movimientos_inventario_id' => $movimiento->id,
            'cantidad' => 3,
        ]);

        CalendarioItem::create([
            'calendario_id' => $calendario3->id,
            'movimientos_inventario_id' => $movimiento->id,
            'cantidad' => 1,
        ]);

        // Excluir calendario2, debería contar calendario1 y calendario3
        $reservadas = $this->service->calcularReservadasExcluyendo($inventario->id, self::FECHA_INICIO_ORIGINAL, self::FECHA_FIN_ORIGINAL, $calendario2->id);

        $this->assertEquals(3, $reservadas); // 2 + 1 = 3
    }

    // ============================================
    // TESTS ADICIONALES PARA getValidationRulesForUpdateItems()
    // ============================================

    public function test_get_validation_rules_for_update_items_incluye_servicio(): void
    {
        $rules = $this->service->getValidationRulesForUpdateItems();

        $this->assertArrayHasKey('servicio', $rules);
        $this->assertStringContainsString('required', $rules['servicio']);
        $this->assertStringContainsString('max:120', $rules['servicio']);
    }

    // ============================================
    // TESTS ADICIONALES PARA getValidationMessagesForUpdateItems()
    // ============================================

    public function test_get_validation_messages_for_update_items_mergea_mensajes(): void
    {
        $messages = $this->service->getValidationMessagesForUpdateItems();

        $this->assertArrayHasKey('servicio.required', $messages);
        $this->assertArrayHasKey('items.required', $messages);
        $this->assertArrayHasKey('fecha_inicio.required', $messages);
    }

    // ============================================
    // TESTS ADICIONALES PARA getValidationMessagesForUpdateOldFormat()
    // ============================================

    public function test_get_validation_messages_for_update_old_format_mergea_mensajes(): void
    {
        $messages = $this->service->getValidationMessagesForUpdateOldFormat();

        $this->assertArrayHasKey('servicio.required', $messages);
        $this->assertArrayHasKey('cantidad.integer', $messages);
        $this->assertArrayHasKey('cantidad.min', $messages);
        $this->assertArrayHasKey('movimientos_inventario_id.required', $messages);
    }
}
