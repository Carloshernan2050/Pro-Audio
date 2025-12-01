<?php

namespace Tests\Feature;

use App\Models\Inventario;
use App\Models\MovimientosInventario;
use App\Models\Usuario;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

/**
 * Tests Feature para MovimientosInventarioController
 *
 * Prueban los flujos completos usando rutas HTTP reales
 */
class MovimientosInventarioTest extends TestCase
{
    use RefreshDatabase;

    private const ROUTE_MOVIMIENTOS = '/movimientos';

    private const TEST_EMAIL = 'admin@example.com';

    private const TEST_PASSWORD = 'password123';

    private const TEST_NOMBRE = 'Admin';

    private const TEST_APELLIDO = 'Usuario';

    private const TEST_TELEFONO = '1234567890';

    private const TIPO_ENTRADA = 'entrada';

    private const TIPO_SALIDA = 'salida';

    private const TIPO_ALQUILADO = 'alquilado';

    private const TIPO_DEVUELTO = 'devuelto';

    private const DESCRIPCION_MOVIMIENTO = 'Movimiento de prueba';

    private const PRODUCTO_TEST = 'Producto Test';

    private const MENSAJE_STOCK_INSUFICIENTE = 'No hay suficiente stock disponible para realizar esta operación.';

    protected function setUp(): void
    {
        parent::setUp();

        // Crear rol Administrador si no existe
        if (! DB::table('roles')->where('nombre_rol', 'Administrador')->exists()) {
            DB::table('roles')->insert([
                'name' => 'Administrador',
                'nombre_rol' => 'Administrador',
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
                'roles_id' => $rolId,
            ]);
        }

        // Simular sesión iniciada
        session(['usuario_id' => $usuario->id, 'usuario_nombre' => self::TEST_NOMBRE]);
        session(['roles' => ['Administrador'], 'role' => 'Administrador']);

        return $usuario;
    }

    // ============================================
    // TESTS PARA INDEX
    // ============================================

    public function test_index_lista_movimientos(): void
    {
        $this->crearUsuarioAdmin();

        $inventario = Inventario::create([
            'descripcion' => self::PRODUCTO_TEST,
            'stock' => 10,
        ]);

        MovimientosInventario::create([
            'inventario_id' => $inventario->id,
            'tipo_movimiento' => self::TIPO_ENTRADA,
            'cantidad' => 5,
            'fecha_movimiento' => now(),
            'descripcion' => self::DESCRIPCION_MOVIMIENTO,
        ]);

        $response = $this->getJson(self::ROUTE_MOVIMIENTOS);

        $response->assertStatus(200);
        $response->assertJsonStructure([
            '*' => [
                'id',
                'inventario_id',
                'tipo_movimiento',
                'cantidad',
                'fecha_movimiento',
                'descripcion',
                'inventario',
            ],
        ]);
    }

    public function test_index_sin_movimientos(): void
    {
        $this->crearUsuarioAdmin();

        $response = $this->getJson(self::ROUTE_MOVIMIENTOS);

        $response->assertStatus(200);
        $response->assertJson([]);
    }

    // ============================================
    // TESTS PARA STORE
    // ============================================

    public function test_store_crea_movimiento_entrada(): void
    {
        $this->crearUsuarioAdmin();

        $inventario = Inventario::create([
            'descripcion' => self::PRODUCTO_TEST,
            'stock' => 10,
        ]);

        $response = $this->postJson(self::ROUTE_MOVIMIENTOS, [
            'inventario_id' => $inventario->id,
            'tipo_movimiento' => self::TIPO_ENTRADA,
            'cantidad' => 5,
            'descripcion' => self::DESCRIPCION_MOVIMIENTO,
        ]);

        $response->assertStatus(200);
        $response->assertJson([
            'success' => 'Movimiento de inventario registrado correctamente.',
        ]);
        $response->assertJsonStructure([
            'success',
            'movimiento_id',
        ]);

        $inventario->refresh();
        $this->assertEquals(15, $inventario->stock);

        $this->assertDatabaseHas('movimientos_inventario', [
            'inventario_id' => $inventario->id,
            'tipo_movimiento' => self::TIPO_ENTRADA,
            'cantidad' => 5,
        ]);
    }

    public function test_store_crea_movimiento_salida(): void
    {
        $this->crearUsuarioAdmin();

        $inventario = Inventario::create([
            'descripcion' => self::PRODUCTO_TEST,
            'stock' => 10,
        ]);

        $response = $this->postJson(self::ROUTE_MOVIMIENTOS, [
            'inventario_id' => $inventario->id,
            'tipo_movimiento' => self::TIPO_SALIDA,
            'cantidad' => 3,
        ]);

        $response->assertStatus(200);

        $inventario->refresh();
        $this->assertEquals(7, $inventario->stock);
    }

    public function test_store_crea_movimiento_alquilado(): void
    {
        $this->crearUsuarioAdmin();

        $inventario = Inventario::create([
            'descripcion' => self::PRODUCTO_TEST,
            'stock' => 10,
        ]);

        $response = $this->postJson(self::ROUTE_MOVIMIENTOS, [
            'inventario_id' => $inventario->id,
            'tipo_movimiento' => self::TIPO_ALQUILADO,
            'cantidad' => 4,
        ]);

        $response->assertStatus(200);

        $inventario->refresh();
        $this->assertEquals(6, $inventario->stock);
    }

    public function test_store_crea_movimiento_devuelto(): void
    {
        $this->crearUsuarioAdmin();

        $inventario = Inventario::create([
            'descripcion' => self::PRODUCTO_TEST,
            'stock' => 10,
        ]);

        $response = $this->postJson(self::ROUTE_MOVIMIENTOS, [
            'inventario_id' => $inventario->id,
            'tipo_movimiento' => self::TIPO_DEVUELTO,
            'cantidad' => 5,
        ]);

        $response->assertStatus(200);

        $inventario->refresh();
        $this->assertEquals(15, $inventario->stock);
    }

    public function test_store_valida_inventario_id_requerido(): void
    {
        $this->crearUsuarioAdmin();

        $response = $this->postJson(self::ROUTE_MOVIMIENTOS, [
            'tipo_movimiento' => self::TIPO_ENTRADA,
            'cantidad' => 5,
        ]);

        $response->assertStatus(422);
        $response->assertJsonValidationErrors('inventario_id');
    }

    public function test_store_valida_inventario_id_existe(): void
    {
        $this->crearUsuarioAdmin();

        $response = $this->postJson(self::ROUTE_MOVIMIENTOS, [
            'inventario_id' => 999,
            'tipo_movimiento' => self::TIPO_ENTRADA,
            'cantidad' => 5,
        ]);

        $response->assertStatus(422);
        $response->assertJsonValidationErrors('inventario_id');
    }

    public function test_store_valida_tipo_movimiento_requerido(): void
    {
        $this->crearUsuarioAdmin();

        $inventario = Inventario::create([
            'descripcion' => self::PRODUCTO_TEST,
            'stock' => 10,
        ]);

        $response = $this->postJson(self::ROUTE_MOVIMIENTOS, [
            'inventario_id' => $inventario->id,
            'cantidad' => 5,
        ]);

        $response->assertStatus(422);
        $response->assertJsonValidationErrors('tipo_movimiento');
    }

    public function test_store_valida_tipo_movimiento_valido(): void
    {
        $this->crearUsuarioAdmin();

        $inventario = Inventario::create([
            'descripcion' => self::PRODUCTO_TEST,
            'stock' => 10,
        ]);

        $response = $this->postJson(self::ROUTE_MOVIMIENTOS, [
            'inventario_id' => $inventario->id,
            'tipo_movimiento' => 'tipo_invalido',
            'cantidad' => 5,
        ]);

        $response->assertStatus(422);
        $response->assertJsonValidationErrors('tipo_movimiento');
    }

    public function test_store_valida_cantidad_requerida(): void
    {
        $this->crearUsuarioAdmin();

        $inventario = Inventario::create([
            'descripcion' => self::PRODUCTO_TEST,
            'stock' => 10,
        ]);

        $response = $this->postJson(self::ROUTE_MOVIMIENTOS, [
            'inventario_id' => $inventario->id,
            'tipo_movimiento' => self::TIPO_ENTRADA,
        ]);

        $response->assertStatus(422);
        $response->assertJsonValidationErrors('cantidad');
    }

    public function test_store_valida_cantidad_minimo(): void
    {
        $this->crearUsuarioAdmin();

        $inventario = Inventario::create([
            'descripcion' => self::PRODUCTO_TEST,
            'stock' => 10,
        ]);

        $response = $this->postJson(self::ROUTE_MOVIMIENTOS, [
            'inventario_id' => $inventario->id,
            'tipo_movimiento' => self::TIPO_ENTRADA,
            'cantidad' => 0,
        ]);

        $response->assertStatus(422);
        $response->assertJsonValidationErrors('cantidad');
    }

    public function test_store_valida_cantidad_debe_ser_integer(): void
    {
        $this->crearUsuarioAdmin();

        $inventario = Inventario::create([
            'descripcion' => self::PRODUCTO_TEST,
            'stock' => 10,
        ]);

        $response = $this->postJson(self::ROUTE_MOVIMIENTOS, [
            'inventario_id' => $inventario->id,
            'tipo_movimiento' => self::TIPO_ENTRADA,
            'cantidad' => 'no_es_numero',
        ]);

        $response->assertStatus(422);
        $response->assertJsonValidationErrors('cantidad');
    }

    public function test_store_valida_stock_insuficiente_salida(): void
    {
        $this->crearUsuarioAdmin();

        $inventario = Inventario::create([
            'descripcion' => self::PRODUCTO_TEST,
            'stock' => 5,
        ]);

        $response = $this->postJson(self::ROUTE_MOVIMIENTOS, [
            'inventario_id' => $inventario->id,
            'tipo_movimiento' => self::TIPO_SALIDA,
            'cantidad' => 10,
        ]);

        $response->assertStatus(400);
        $response->assertJson([
            'error' => self::MENSAJE_STOCK_INSUFICIENTE,
        ]);
    }

    public function test_store_valida_stock_insuficiente_alquilado(): void
    {
        $this->crearUsuarioAdmin();

        $inventario = Inventario::create([
            'descripcion' => self::PRODUCTO_TEST,
            'stock' => 5,
        ]);

        $response = $this->postJson(self::ROUTE_MOVIMIENTOS, [
            'inventario_id' => $inventario->id,
            'tipo_movimiento' => self::TIPO_ALQUILADO,
            'cantidad' => 10,
        ]);

        $response->assertStatus(400);
        $response->assertJson([
            'error' => self::MENSAJE_STOCK_INSUFICIENTE,
        ]);
    }

    public function test_store_usa_descripcion_vacia_si_no_se_proporciona(): void
    {
        $this->crearUsuarioAdmin();

        $inventario = Inventario::create([
            'descripcion' => self::PRODUCTO_TEST,
            'stock' => 10,
        ]);

        $response = $this->postJson(self::ROUTE_MOVIMIENTOS, [
            'inventario_id' => $inventario->id,
            'tipo_movimiento' => self::TIPO_ENTRADA,
            'cantidad' => 5,
        ]);

        $response->assertStatus(200);

        $movimiento = MovimientosInventario::latest()->first();
        $this->assertEquals('', $movimiento->descripcion);
    }

    // ============================================
    // TESTS PARA UPDATE
    // ============================================

    public function test_update_actualiza_movimiento_mismo_inventario(): void
    {
        $this->crearUsuarioAdmin();

        $inventario = Inventario::create([
            'descripcion' => self::PRODUCTO_TEST,
            'stock' => 10,
        ]);

        $movimiento = MovimientosInventario::create([
            'inventario_id' => $inventario->id,
            'tipo_movimiento' => self::TIPO_ENTRADA,
            'cantidad' => 5,
            'fecha_movimiento' => now(),
            'descripcion' => self::DESCRIPCION_MOVIMIENTO,
        ]);

        $inventario->refresh();

        $response = $this->putJson(self::ROUTE_MOVIMIENTOS.'/'.$movimiento->id, [
            'inventario_id' => $inventario->id,
            'tipo_movimiento' => self::TIPO_ENTRADA,
            'cantidad' => 8,
        ]);

        $response->assertStatus(200);
        $response->assertJson([
            'success' => 'Movimiento de inventario actualizado correctamente.',
        ]);

        $inventario->refresh();
        // Stock inicial era 15 (10 + 5), se revierte -5, se aplica +8 = 18
        $this->assertEquals(18, $inventario->stock);
    }

    public function test_update_cambia_tipo_movimiento(): void
    {
        $this->crearUsuarioAdmin();

        $inventario = Inventario::create([
            'descripcion' => self::PRODUCTO_TEST,
            'stock' => 10,
        ]);

        $movimiento = MovimientosInventario::create([
            'inventario_id' => $inventario->id,
            'tipo_movimiento' => self::TIPO_ENTRADA,
            'cantidad' => 5,
            'fecha_movimiento' => now(),
            'descripcion' => self::DESCRIPCION_MOVIMIENTO,
        ]);

        $inventario->refresh();

        $response = $this->putJson(self::ROUTE_MOVIMIENTOS.'/'.$movimiento->id, [
            'inventario_id' => $inventario->id,
            'tipo_movimiento' => self::TIPO_SALIDA,
            'cantidad' => 3,
        ]);

        $response->assertStatus(200);

        $inventario->refresh();
        // Stock inicial era 15 (10 + 5), se revierte -5, se aplica -3 = 7
        $this->assertEquals(7, $inventario->stock);
    }

    public function test_update_cambia_inventario(): void
    {
        $this->crearUsuarioAdmin();

        $inventario1 = Inventario::create([
            'descripcion' => 'Producto 1',
            'stock' => 10,
        ]);

        $inventario2 = Inventario::create([
            'descripcion' => 'Producto 2',
            'stock' => 20,
        ]);

        // Crear movimiento sin actualizar stock manualmente (el controlador lo hace)
        $response = $this->postJson(self::ROUTE_MOVIMIENTOS, [
            'inventario_id' => $inventario1->id,
            'tipo_movimiento' => self::TIPO_ENTRADA,
            'cantidad' => 5,
        ]);

        $movimientoId = $response->json('movimiento_id');
        $inventario1->refresh();
        $inventario2->refresh();

        $response = $this->putJson(self::ROUTE_MOVIMIENTOS.'/'.$movimientoId, [
            'inventario_id' => $inventario2->id,
            'tipo_movimiento' => self::TIPO_ENTRADA,
            'cantidad' => 5,
        ]);

        $response->assertStatus(200);

        $inventario1->refresh();
        $inventario2->refresh();
        // Inventario1: 15 - 5 = 10 (revierte)
        $this->assertEquals(10, $inventario1->stock);
        // Inventario2: 20 + 5 = 25 (aplica)
        $this->assertEquals(25, $inventario2->stock);
    }

    public function test_update_valida_stock_insuficiente(): void
    {
        $this->crearUsuarioAdmin();

        $inventario = Inventario::create([
            'descripcion' => self::PRODUCTO_TEST,
            'stock' => 10,
        ]);

        $movimiento = MovimientosInventario::create([
            'inventario_id' => $inventario->id,
            'tipo_movimiento' => self::TIPO_ENTRADA,
            'cantidad' => 5,
            'fecha_movimiento' => now(),
            'descripcion' => self::DESCRIPCION_MOVIMIENTO,
        ]);

        $inventario->refresh();

        $response = $this->putJson(self::ROUTE_MOVIMIENTOS.'/'.$movimiento->id, [
            'inventario_id' => $inventario->id,
            'tipo_movimiento' => self::TIPO_SALIDA,
            'cantidad' => 20, // Más que el stock disponible (15)
        ]);

        $response->assertStatus(400);
        $response->assertJson([
            'error' => self::MENSAJE_STOCK_INSUFICIENTE,
        ]);
    }

    public function test_update_movimiento_no_existe(): void
    {
        $this->crearUsuarioAdmin();

        $inventario = Inventario::create([
            'descripcion' => self::PRODUCTO_TEST,
            'stock' => 10,
        ]);

        $response = $this->putJson(self::ROUTE_MOVIMIENTOS.'/999', [
            'inventario_id' => $inventario->id,
            'tipo_movimiento' => self::TIPO_ENTRADA,
            'cantidad' => 5,
        ]);

        $response->assertStatus(404);
    }

    // ============================================
    // TESTS PARA DESTROY
    // ============================================

    public function test_destroy_elimina_movimiento_entrada(): void
    {
        $this->crearUsuarioAdmin();

        $inventario = Inventario::create([
            'descripcion' => self::PRODUCTO_TEST,
            'stock' => 10,
        ]);

        // Crear movimiento usando el controlador para que actualice el stock
        $response = $this->postJson(self::ROUTE_MOVIMIENTOS, [
            'inventario_id' => $inventario->id,
            'tipo_movimiento' => self::TIPO_ENTRADA,
            'cantidad' => 5,
        ]);

        $movimientoId = $response->json('movimiento_id');
        $inventario->refresh();

        $response = $this->deleteJson(self::ROUTE_MOVIMIENTOS.'/'.$movimientoId);

        $response->assertStatus(200);
        $response->assertJson([
            'success' => 'Movimiento de inventario eliminado correctamente.',
        ]);

        $inventario->refresh();
        // Se revierte: 15 - 5 = 10
        $this->assertEquals(10, $inventario->stock);

        $this->assertDatabaseMissing('movimientos_inventario', [
            'id' => $movimientoId,
        ]);
    }

    public function test_destroy_elimina_movimiento_salida(): void
    {
        $this->crearUsuarioAdmin();

        $inventario = Inventario::create([
            'descripcion' => self::PRODUCTO_TEST,
            'stock' => 10,
        ]);

        // Crear movimiento usando el controlador para que actualice el stock
        $response = $this->postJson(self::ROUTE_MOVIMIENTOS, [
            'inventario_id' => $inventario->id,
            'tipo_movimiento' => self::TIPO_SALIDA,
            'cantidad' => 3,
        ]);

        $movimientoId = $response->json('movimiento_id');
        $inventario->refresh();

        $response = $this->deleteJson(self::ROUTE_MOVIMIENTOS.'/'.$movimientoId);

        $response->assertStatus(200);

        $inventario->refresh();
        // Se revierte: 7 + 3 = 10
        $this->assertEquals(10, $inventario->stock);
    }

    public function test_destroy_movimiento_no_existe(): void
    {
        $this->crearUsuarioAdmin();

        $response = $this->deleteJson(self::ROUTE_MOVIMIENTOS.'/999');

        $response->assertStatus(404);
    }
}
