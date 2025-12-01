<?php

namespace Tests\Feature;

use App\Models\Inventario;
use App\Models\Usuario;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

/**
 * Tests de Integración para InventarioController
 *
 * Prueban los flujos completos de CRUD de inventario
 */
class InventarioTest extends TestCase
{
    use RefreshDatabase;

    private const TEST_EMAIL = 'admin@example.com';

    private const TEST_PASSWORD = 'password123';

    private const TEST_NOMBRE = 'Admin';

    private const TEST_APELLIDO = 'Usuario';

    private const TEST_TELEFONO = '1234567890';

    private const ROUTE_INVENTARIO = '/inventario';

    private const DESC_PRODUCTO = 'Producto de prueba';

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

        // Simular sesión iniciada como Admin
        session(['usuario_id' => $usuario->id, 'usuario_nombre' => self::TEST_NOMBRE]);
        session(['roles' => ['Administrador'], 'role' => 'Administrador']);

        return $usuario;
    }

    // ============================================
    // TESTS PARA LISTAR INVENTARIO
    // ============================================

    public function test_index_lista_inventario(): void
    {
        $this->crearUsuarioAdmin();

        Inventario::create([
            'descripcion' => self::DESC_PRODUCTO,
            'stock' => 10,
        ]);

        $response = $this->get(self::ROUTE_INVENTARIO);

        $response->assertStatus(200);
        $response->assertJsonStructure([
            '*' => ['id', 'descripcion', 'stock'],
        ]);
    }

    public function test_index_sin_inventario(): void
    {
        $this->crearUsuarioAdmin();

        $response = $this->get(self::ROUTE_INVENTARIO);

        $response->assertStatus(200);
        $response->assertJson([]);
    }

    // ============================================
    // TESTS PARA CREAR INVENTARIO
    // ============================================

    public function test_store_crea_inventario_exitoso(): void
    {
        $this->crearUsuarioAdmin();

        $response = $this->postJson(self::ROUTE_INVENTARIO, [
            'descripcion' => self::DESC_PRODUCTO,
            'stock' => 20,
        ]);

        $response->assertStatus(200);
        $response->assertJson(['success' => 'Artículo del inventario creado correctamente.']);

        $this->assertDatabaseHas('inventario', [
            'descripcion' => self::DESC_PRODUCTO,
            'stock' => 20,
        ]);
    }

    public function test_store_validacion_descripcion_requerida(): void
    {
        $this->crearUsuarioAdmin();

        $response = $this->postJson(self::ROUTE_INVENTARIO, [
            'stock' => 10,
        ]);

        $response->assertStatus(422);
        $response->assertJsonValidationErrors('descripcion');
    }

    public function test_store_validacion_stock_requerido(): void
    {
        $this->crearUsuarioAdmin();

        $response = $this->postJson(self::ROUTE_INVENTARIO, [
            'descripcion' => self::DESC_PRODUCTO,
        ]);

        $response->assertStatus(422);
        $response->assertJsonValidationErrors('stock');
    }

    public function test_store_validacion_stock_minimo(): void
    {
        $this->crearUsuarioAdmin();

        $response = $this->postJson(self::ROUTE_INVENTARIO, [
            'descripcion' => self::DESC_PRODUCTO,
            'stock' => -5,
        ]);

        $response->assertStatus(422);
        $response->assertJsonValidationErrors('stock');
    }

    public function test_store_validacion_descripcion_max_caracteres(): void
    {
        $this->crearUsuarioAdmin();

        $response = $this->postJson(self::ROUTE_INVENTARIO, [
            'descripcion' => str_repeat('a', 256),
            'stock' => 10,
        ]);

        $response->assertStatus(422);
        $response->assertJsonValidationErrors('descripcion');
    }

    // ============================================
    // TESTS PARA ACTUALIZAR INVENTARIO
    // ============================================

    public function test_update_actualiza_inventario(): void
    {
        $this->crearUsuarioAdmin();

        $inventario = Inventario::create([
            'descripcion' => 'Producto Original',
            'stock' => 10,
        ]);

        $response = $this->putJson(self::ROUTE_INVENTARIO."/{$inventario->id}", [
            'descripcion' => 'Producto Actualizado',
            'stock' => 25,
        ]);

        $response->assertStatus(200);
        $response->assertJson(['success' => 'Artículo del inventario actualizado correctamente.']);

        $this->assertDatabaseHas('inventario', [
            'id' => $inventario->id,
            'descripcion' => 'Producto Actualizado',
            'stock' => 25,
        ]);
    }

    public function test_update_inventario_no_existe(): void
    {
        $this->crearUsuarioAdmin();

        $response = $this->putJson(self::ROUTE_INVENTARIO.'/99999', [
            'descripcion' => self::DESC_PRODUCTO,
            'stock' => 10,
        ]);

        $response->assertStatus(404);
    }

    public function test_update_validacion_descripcion_requerida(): void
    {
        $this->crearUsuarioAdmin();

        $inventario = Inventario::create([
            'descripcion' => self::DESC_PRODUCTO,
            'stock' => 10,
        ]);

        $response = $this->putJson(self::ROUTE_INVENTARIO."/{$inventario->id}", [
            'stock' => 20,
        ]);

        $response->assertStatus(422);
        $response->assertJsonValidationErrors('descripcion');
    }

    // ============================================
    // TESTS PARA ELIMINAR INVENTARIO
    // ============================================

    public function test_destroy_elimina_inventario(): void
    {
        $this->crearUsuarioAdmin();

        $inventario = Inventario::create([
            'descripcion' => self::DESC_PRODUCTO,
            'stock' => 10,
        ]);

        $response = $this->deleteJson(self::ROUTE_INVENTARIO."/{$inventario->id}");

        $response->assertStatus(200);
        $response->assertJson(['success' => 'Artículo del inventario eliminado correctamente.']);

        $this->assertDatabaseMissing('inventario', [
            'id' => $inventario->id,
        ]);
    }

    public function test_destroy_inventario_no_existe(): void
    {
        $this->crearUsuarioAdmin();

        $response = $this->deleteJson(self::ROUTE_INVENTARIO.'/99999');

        $response->assertStatus(404);
    }

    // ============================================
    // TESTS PARA PERMISOS
    // ============================================

    public function test_index_requiere_rol_admin(): void
    {
        $response = $this->get(self::ROUTE_INVENTARIO);

        // Debería redirigir o denegar acceso
        $this->assertTrue($response->isRedirect() || $response->status() === 403);
    }
}
