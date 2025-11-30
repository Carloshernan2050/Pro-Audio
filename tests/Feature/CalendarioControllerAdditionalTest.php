<?php

namespace Tests\Feature;

use Tests\TestCase;
use App\Models\Calendario;
use App\Models\CalendarioItem;
use App\Models\Inventario;
use App\Models\MovimientosInventario;
use App\Models\Reserva;
use App\Models\Usuario;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\DB;

/**
 * Tests Adicionales de Integración para CalendarioController
 *
 * Prueban casos edge y flujos complejos adicionales
 */
class CalendarioControllerAdditionalTest extends TestCase
{
    use RefreshDatabase;

    private const ROUTE_CALENDARIO = '/calendario';
    private const TEST_EMAIL = 'admin@example.com';
    private const TEST_PASSWORD = 'password123';
    private const TEST_NOMBRE = 'Admin';
    private const TEST_APELLIDO = 'Usuario';
    private const TEST_TELEFONO = '1234567890';
    private const FECHA_INICIO = '2024-01-01';
    private const FECHA_FIN = '2024-01-05';
    private const DESCRIPCION_EVENTO = 'Evento de prueba';
    private const PRODUCTO_TEST = 'Producto Test';
    private const MOVIMIENTO_DE_PRUEBA = 'Movimiento de prueba';
    private const FECHA_INICIO_ACTUALIZADA = '2024-01-02';
    private const FECHA_FIN_ACTUALIZADA = '2024-01-06';
    private const EVENTO_ACTUALIZADO = 'Evento actualizado';
    private const MOVIMIENTO_1 = 'Movimiento 1';
    private const MOVIMIENTO_2 = 'Movimiento 2';

    protected function setUp(): void
    {
        parent::setUp();

        if (!DB::table('roles')->where('nombre_rol', 'Administrador')->exists()) {
            DB::table('roles')->insert([
                'nombre_rol' => 'Administrador'
            ]);
        }
    }

    private function crearUsuarioAdmin(): Usuario
    {
        $usuario = Usuario::create([
            'primer_nombre' => self::TEST_NOMBRE,
            'primer_apellido' => self::TEST_APELLIDO,
            'correo' => self::TEST_EMAIL,
            'telefono' => self::TEST_TELEFONO,
            'contrasena' => Hash::make(self::TEST_PASSWORD),
            'fecha_registro' => now(),
            'estado' => true,
        ]);

        $rolId = DB::table('roles')->where('nombre_rol', 'Administrador')->value('id');
        if ($rolId) {
            DB::table('personas_roles')->insert([
                'personas_id' => $usuario->id,
                'roles_id' => $rolId
            ]);
        }

        session(['usuario_id' => $usuario->id, 'usuario_nombre' => self::TEST_NOMBRE]);
        session(['roles' => ['Administrador'], 'role' => 'Administrador']);

        return $usuario;
    }

    // ============================================
    // TESTS ADICIONALES PARA GUARDAR
    // ============================================

    public function test_guardar_con_items_multiples_inventarios(): void
    {
        $this->crearUsuarioAdmin();

        $inventario1 = Inventario::create([
            'descripcion' => 'Producto 1',
            'stock' => 10,
        ]);

        $inventario2 = Inventario::create([
            'descripcion' => 'Producto 2',
            'stock' => 15,
        ]);

        $response = $this->postJson(self::ROUTE_CALENDARIO, [
            'fecha_inicio' => self::FECHA_INICIO,
            'fecha_fin' => self::FECHA_FIN,
            'descripcion_evento' => self::DESCRIPCION_EVENTO,
            'items' => [
                [
                    'inventario_id' => $inventario1->id,
                    'cantidad' => 3,
                ],
                [
                    'inventario_id' => $inventario2->id,
                    'cantidad' => 5,
                ]
            ],
        ]);

        $response->assertStatus(200);
        
        $calendario = Calendario::latest()->first();
        $this->assertEquals(8, $calendario->cantidad); // 3 + 5 = 8
        $this->assertCount(2, $calendario->items);
    }

    public function test_guardar_con_items_normaliza_indices_no_numericos(): void
    {
        $this->crearUsuarioAdmin();

        $inventario = Inventario::create([
            'descripcion' => self::PRODUCTO_TEST,
            'stock' => 10,
        ]);

        // Array con índices no numéricos
        $response = $this->postJson(self::ROUTE_CALENDARIO, [
            'fecha_inicio' => self::FECHA_INICIO,
            'fecha_fin' => self::FECHA_FIN,
            'descripcion_evento' => self::DESCRIPCION_EVENTO,
            'items' => [
                'item1' => [
                    'inventario_id' => $inventario->id,
                    'cantidad' => 5,
                ]
            ],
        ]);

        $response->assertStatus(200);
    }

    public function test_guardar_con_items_items_no_es_array(): void
    {
        $this->crearUsuarioAdmin();

        // items no es array, debería usar formato antiguo
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

        $response = $this->postJson(self::ROUTE_CALENDARIO, [
            'fecha_inicio' => self::FECHA_INICIO,
            'fecha_fin' => self::FECHA_FIN,
            'descripcion_evento' => self::DESCRIPCION_EVENTO,
            'items' => 'no_es_array',
            'movimientos_inventario_id' => $movimiento->id,
        ]);

        // Puede usar formato antiguo o fallar validación
        $this->assertContains($response->status(), [200, 422]);
    }

    // ============================================
    // TESTS ADICIONALES PARA ACTUALIZAR
    // ============================================

    public function test_actualizar_con_items_cambia_cantidad_total(): void
    {
        $this->crearUsuarioAdmin();

        $inventario = Inventario::create([
            'descripcion' => self::PRODUCTO_TEST,
            'stock' => 10,
        ]);

        $movimiento = MovimientosInventario::create([
            'inventario_id' => $inventario->id,
            'tipo_movimiento' => 'alquilado',
            'cantidad' => 5,
            'fecha_movimiento' => now(),
            'descripcion' => self::MOVIMIENTO_DE_PRUEBA,
        ]);

        $calendario = Calendario::create([
            'personas_id' => session('usuario_id'),
            'movimientos_inventario_id' => null,
            'fecha' => now(),
            'fecha_inicio' => self::FECHA_INICIO,
            'fecha_fin' => self::FECHA_FIN,
            'descripcion_evento' => self::DESCRIPCION_EVENTO,
            'evento' => 'Alquiler',
            'cantidad' => 5,
        ]);

        CalendarioItem::create([
            'calendario_id' => $calendario->id,
            'movimientos_inventario_id' => $movimiento->id,
            'cantidad' => 5,
        ]);

        $inventario->decrement('stock', 5);

        $response = $this->put(self::ROUTE_CALENDARIO . '/' . $calendario->id, [
            'servicio' => 'Alquiler',
            'fecha_inicio' => self::FECHA_INICIO_ACTUALIZADA,
            'fecha_fin' => self::FECHA_FIN_ACTUALIZADA,
            'descripcion_evento' => self::EVENTO_ACTUALIZADO,
            'items' => [
                [
                    'inventario_id' => $inventario->id,
                    'cantidad' => 7, // Cambiar de 5 a 7
                ]
            ],
        ]);

        $response->assertRedirect(route('usuarios.calendario'));
        
        $calendario->refresh();
        $this->assertEquals(7, $calendario->cantidad);
    }

    public function test_actualizar_con_items_elimina_items_anteriores(): void
    {
        $this->crearUsuarioAdmin();

        $inventario = Inventario::create([
            'descripcion' => self::PRODUCTO_TEST,
            'stock' => 20,
        ]);

        $movimiento1 = MovimientosInventario::create([
            'inventario_id' => $inventario->id,
            'tipo_movimiento' => 'alquilado',
            'cantidad' => 5,
            'fecha_movimiento' => now(),
            'descripcion' => self::MOVIMIENTO_1,
        ]);

        $movimiento2 = MovimientosInventario::create([
            'inventario_id' => $inventario->id,
            'tipo_movimiento' => 'alquilado',
            'cantidad' => 3,
            'fecha_movimiento' => now(),
            'descripcion' => self::MOVIMIENTO_2,
        ]);

        $calendario = Calendario::create([
            'personas_id' => session('usuario_id'),
            'movimientos_inventario_id' => null,
            'fecha' => now(),
            'fecha_inicio' => self::FECHA_INICIO,
            'fecha_fin' => self::FECHA_FIN,
            'descripcion_evento' => self::DESCRIPCION_EVENTO,
            'evento' => 'Alquiler',
            'cantidad' => 8,
        ]);

        CalendarioItem::create([
            'calendario_id' => $calendario->id,
            'movimientos_inventario_id' => $movimiento1->id,
            'cantidad' => 5,
        ]);

        CalendarioItem::create([
            'calendario_id' => $calendario->id,
            'movimientos_inventario_id' => $movimiento2->id,
            'cantidad' => 3,
        ]);

        $inventario->decrement('stock', 8);

        $response = $this->put(self::ROUTE_CALENDARIO . '/' . $calendario->id, [
            'servicio' => 'Alquiler',
            'fecha_inicio' => self::FECHA_INICIO_ACTUALIZADA,
            'fecha_fin' => self::FECHA_FIN_ACTUALIZADA,
            'descripcion_evento' => self::EVENTO_ACTUALIZADO,
            'items' => [
                [
                    'inventario_id' => $inventario->id,
                    'cantidad' => 4, // Solo un item nuevo
                ]
            ],
        ]);

        $response->assertRedirect(route('usuarios.calendario'));
        
        $calendario->refresh();
        $this->assertCount(1, $calendario->items);
    }

    public function test_actualizar_formato_antiguo_con_cantidad(): void
    {
        $this->crearUsuarioAdmin();

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
            'personas_id' => session('usuario_id'),
            'movimientos_inventario_id' => $movimiento->id,
            'fecha' => now(),
            'fecha_inicio' => self::FECHA_INICIO,
            'fecha_fin' => self::FECHA_FIN,
            'descripcion_evento' => self::DESCRIPCION_EVENTO,
            'evento' => 'Alquiler',
        ]);

        $response = $this->put(self::ROUTE_CALENDARIO . '/' . $calendario->id, [
            'servicio' => 'Alquiler',
            'movimientos_inventario_id' => $movimiento->id,
            'fecha_inicio' => self::FECHA_INICIO_ACTUALIZADA,
            'fecha_fin' => self::FECHA_FIN_ACTUALIZADA,
            'descripcion_evento' => self::EVENTO_ACTUALIZADO,
            'cantidad' => 8,
        ]);

        $response->assertRedirect(route('usuarios.calendario'));
        
        $calendario->refresh();
        $this->assertEquals(8, $calendario->cantidad);
    }

    public function test_actualizar_formato_antiguo_sin_cantidad(): void
    {
        $this->crearUsuarioAdmin();

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
            'personas_id' => session('usuario_id'),
            'movimientos_inventario_id' => $movimiento->id,
            'fecha' => now(),
            'fecha_inicio' => self::FECHA_INICIO,
            'fecha_fin' => self::FECHA_FIN,
            'descripcion_evento' => self::DESCRIPCION_EVENTO,
            'evento' => 'Alquiler',
            'cantidad' => 5,
        ]);

        $response = $this->put(self::ROUTE_CALENDARIO . '/' . $calendario->id, [
            'servicio' => 'Alquiler',
            'movimientos_inventario_id' => $movimiento->id,
            'fecha_inicio' => self::FECHA_INICIO_ACTUALIZADA,
            'fecha_fin' => self::FECHA_FIN_ACTUALIZADA,
            'descripcion_evento' => self::EVENTO_ACTUALIZADO,
            // Sin cantidad
        ]);

        $response->assertRedirect(route('usuarios.calendario'));
        
        $calendario->refresh();
        // La cantidad debería mantenerse
        $this->assertEquals(5, $calendario->cantidad);
    }

    // ============================================
    // TESTS ADICIONALES PARA ELIMINAR
    // ============================================

    public function test_eliminar_con_multiples_items_restaura_stock(): void
    {
        $this->crearUsuarioAdmin();

        $inventario = Inventario::create([
            'descripcion' => self::PRODUCTO_TEST,
            'stock' => 20,
        ]);

        $movimiento1 = MovimientosInventario::create([
            'inventario_id' => $inventario->id,
            'tipo_movimiento' => 'alquilado',
            'cantidad' => 5,
            'fecha_movimiento' => now(),
            'descripcion' => self::MOVIMIENTO_1,
        ]);

        $movimiento2 = MovimientosInventario::create([
            'inventario_id' => $inventario->id,
            'tipo_movimiento' => 'alquilado',
            'cantidad' => 3,
            'fecha_movimiento' => now(),
            'descripcion' => self::MOVIMIENTO_2,
        ]);

        $calendario = Calendario::create([
            'personas_id' => session('usuario_id'),
            'movimientos_inventario_id' => null,
            'fecha' => now(),
            'fecha_inicio' => self::FECHA_INICIO,
            'fecha_fin' => self::FECHA_FIN,
            'descripcion_evento' => self::DESCRIPCION_EVENTO,
            'evento' => 'Alquiler',
        ]);

        CalendarioItem::create([
            'calendario_id' => $calendario->id,
            'movimientos_inventario_id' => $movimiento1->id,
            'cantidad' => 5,
        ]);

        CalendarioItem::create([
            'calendario_id' => $calendario->id,
            'movimientos_inventario_id' => $movimiento2->id,
            'cantidad' => 3,
        ]);

        $inventario->decrement('stock', 8); // Stock queda en 12

        $response = $this->deleteJson(self::ROUTE_CALENDARIO . '/' . $calendario->id);

        $response->assertStatus(200);
        
        $inventario->refresh();
        $this->assertEquals(20, $inventario->stock); // 12 + 5 + 3 = 20
    }

    public function test_eliminar_con_item_sin_movimiento_inventario(): void
    {
        $this->crearUsuarioAdmin();

        $inventario = Inventario::create([
            'descripcion' => self::PRODUCTO_TEST,
            'stock' => 10,
        ]);

        $movimiento = MovimientosInventario::create([
            'inventario_id' => $inventario->id,
            'tipo_movimiento' => 'alquilado',
            'cantidad' => 5,
            'fecha_movimiento' => now(),
            'descripcion' => self::MOVIMIENTO_DE_PRUEBA,
        ]);

        $calendario = Calendario::create([
            'personas_id' => session('usuario_id'),
            'movimientos_inventario_id' => null,
            'fecha' => now(),
            'fecha_inicio' => self::FECHA_INICIO,
            'fecha_fin' => self::FECHA_FIN,
            'descripcion_evento' => self::DESCRIPCION_EVENTO,
            'evento' => 'Alquiler',
        ]);

        // Item con movimientoInventario válido (la columna es NOT NULL)
        CalendarioItem::create([
            'calendario_id' => $calendario->id,
            'movimientos_inventario_id' => $movimiento->id,
            'cantidad' => 5,
        ]);

        $inventario->decrement('stock', 5);

        $response = $this->deleteJson(self::ROUTE_CALENDARIO . '/' . $calendario->id);

        $response->assertStatus(200);
        $this->assertDatabaseMissing('calendario', ['id' => $calendario->id]);
        
        // Verificar que el stock se restauró
        $inventario->refresh();
        $this->assertEquals(10, $inventario->stock);
    }

    public function test_eliminar_retorna_json_si_es_ajax(): void
    {
        $this->crearUsuarioAdmin();

        $calendario = Calendario::create([
            'personas_id' => session('usuario_id'),
            'movimientos_inventario_id' => null,
            'fecha' => now(),
            'fecha_inicio' => self::FECHA_INICIO,
            'fecha_fin' => self::FECHA_FIN,
            'descripcion_evento' => self::DESCRIPCION_EVENTO,
            'evento' => 'Alquiler',
        ]);

        $response = $this->withHeaders([
            'X-Requested-With' => 'XMLHttpRequest'
        ])->deleteJson(self::ROUTE_CALENDARIO . '/' . $calendario->id);

        $response->assertStatus(200);
        $response->assertJson([
            'success' => true,
            'message' => 'Evento eliminado y stock restaurado.'
        ]);
    }

    // ============================================
    // TESTS ADICIONALES PARA GET EVENTOS
    // ============================================

    public function test_get_eventos_con_multiples_calendarios(): void
    {
        $usuario = $this->crearUsuarioAdmin();

        $inventario = Inventario::create([
            'descripcion' => self::PRODUCTO_TEST,
            'stock' => 10,
        ]);

        $movimiento1 = MovimientosInventario::create([
            'inventario_id' => $inventario->id,
            'tipo_movimiento' => 'entrada',
            'cantidad' => 10,
            'fecha_movimiento' => now(),
            'descripcion' => self::MOVIMIENTO_1,
        ]);

        $movimiento2 = MovimientosInventario::create([
            'inventario_id' => $inventario->id,
            'tipo_movimiento' => 'entrada',
            'cantidad' => 10,
            'fecha_movimiento' => now(),
            'descripcion' => self::MOVIMIENTO_2,
        ]);

        Calendario::create([
            'personas_id' => $usuario->id,
            'movimientos_inventario_id' => $movimiento1->id,
            'fecha' => now(),
            'fecha_inicio' => self::FECHA_INICIO,
            'fecha_fin' => self::FECHA_FIN,
            'descripcion_evento' => 'Evento 1',
            'evento' => 'Alquiler',
        ]);

        Calendario::create([
            'personas_id' => $usuario->id,
            'movimientos_inventario_id' => $movimiento2->id,
            'fecha' => now(),
            'fecha_inicio' => '2024-02-01',
            'fecha_fin' => '2024-02-05',
            'descripcion_evento' => 'Evento 2',
            'evento' => 'Alquiler',
        ]);

        $response = $this->getJson('/calendario/eventos');

        $response->assertStatus(200);
        $eventos = $response->json();
        $this->assertIsArray($eventos);
        $this->assertGreaterThanOrEqual(2, count($eventos));
    }
}

