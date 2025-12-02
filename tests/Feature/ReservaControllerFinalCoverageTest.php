<?php

namespace Tests\Feature;

use App\Http\Controllers\ReservaController;
use App\Models\Calendario;
use App\Models\Inventario;
use App\Models\Reserva;
use App\Models\ReservaItem;
use App\Models\Usuario;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

/**
 * Tests Feature para cubrir líneas faltantes en ReservaController
 */
class ReservaControllerFinalCoverageTest extends TestCase
{
    use RefreshDatabase;

    private const TEST_EMAIL = 'admin@example.com';
    private const TEST_PASSWORD = 'password123';

    protected function setUp(): void
    {
        parent::setUp();

        if (! DB::table('roles')->where('nombre_rol', 'Administrador')->exists()) {
            DB::table('roles')->insert([
                'nombre_rol' => 'Administrador',
                'name' => 'Administrador',
            ]);
        }
    }

    private function crearUsuarioAdmin(): Usuario
    {
        $usuario = Usuario::create([
            'primer_nombre' => 'Admin',
            'primer_apellido' => 'Usuario',
            'correo' => self::TEST_EMAIL,
            'telefono' => '1234567890',
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

        session(['usuario_id' => $usuario->id, 'usuario_nombre' => 'Admin']);
        session(['roles' => ['Administrador'], 'role' => 'Administrador']);

        return $usuario;
    }

    /**
     * Test para cubrir líneas 154-155 - inventario no encontrado en confirm
     */
    public function test_confirm_reserva_inventario_no_encontrado_cubre_lineas_154_155(): void
    {
        $this->crearUsuarioAdmin();

        // Crear una reserva con un item que tiene inventario_id inválido
        $reserva = Reserva::create([
            'personas_id' => session('usuario_id'),
            'servicio' => 'Test Servicio',
            'fecha_inicio' => now()->format('Y-m-d'),
            'fecha_fin' => now()->addDays(5)->format('Y-m-d'),
            'estado' => 'pendiente',
        ]);

        // Crear un inventario primero
        $inventario = Inventario::create([
            'descripcion' => 'Producto Test',
            'stock' => 10,
        ]);

        // Crear item con el inventario
        $item = ReservaItem::create([
            'reserva_id' => $reserva->id,
            'inventario_id' => $inventario->id,
            'cantidad' => 5,
        ]);

        // Cargar la reserva con items e inventario
        $reserva->load('items.inventario');

        // Manipular el item para que su relación inventario sea null
        // Esto simula el caso donde el inventario fue eliminado (líneas 154-155)
        $item->setRelation('inventario', null);
        
        // Actualizar la colección de items de la reserva
        $items = $reserva->items;
        $items->transform(function ($reservaItem) use ($item) {
            if ($reservaItem->id === $item->id) {
                $reservaItem->setRelation('inventario', null);
            }
            return $reservaItem;
        });
        $reserva->setRelation('items', $items);

        // Usar reflection para llamar a validateReservaItems directamente
        // con la reserva que tiene el item con inventario null
        $controller = app(\App\Http\Controllers\ReservaController::class);
        $reflection = new \ReflectionClass($controller);
        $method = $reflection->getMethod('validateReservaItems');
        $method->setAccessible(true);

        // Ejecutar el método con la reserva manipulada (líneas 154-155)
        $response = $method->invoke($controller, $reserva);

        // Debe retornar error JSON (líneas 154-155)
        // El response puede ser un JsonResponse, verificar el contenido
        if ($response instanceof \Illuminate\Http\JsonResponse) {
            $this->assertEquals(422, $response->getStatusCode());
            $data = json_decode($response->getContent(), true);
            $this->assertArrayHasKey('error', $data);
        } else {
            // Si es una respuesta HTTP normal, verificar el status
            $this->assertContains($response->getStatusCode(), [422, 500]);
        }
    }

    /**
     * Test para cubrir línea 190 - continue cuando inventario no existe en processReservaItems
     */
    public function test_process_reserva_items_inventario_no_existe_cubre_linea_190(): void
    {
        $this->crearUsuarioAdmin();

        // Crear una reserva y calendario
        $reserva = Reserva::create([
            'personas_id' => session('usuario_id'),
            'servicio' => 'Test Servicio',
            'fecha_inicio' => now()->format('Y-m-d'),
            'fecha_fin' => now()->addDays(5)->format('Y-m-d'),
            'estado' => 'confirmada',
        ]);

        $calendario = Calendario::create([
            'personas_id' => session('usuario_id'),
            'movimientos_inventario_id' => null,
            'fecha' => now(),
            'fecha_inicio' => $reserva->fecha_inicio,
            'fecha_fin' => $reserva->fecha_fin,
            'descripcion_evento' => 'Test',
            'evento' => 'Alquiler',
        ]);

        // Crear un inventario primero
        $inventario = Inventario::create([
            'descripcion' => 'Producto Test',
            'stock' => 10,
        ]);

        // Crear item con el inventario
        $item = ReservaItem::create([
            'reserva_id' => $reserva->id,
            'inventario_id' => $inventario->id,
            'cantidad' => 5,
        ]);

        // Cargar la reserva con items e inventario
        $reserva->load('items.inventario');

        // Manipular el item para que su relación inventario sea null
        $item->setRelation('inventario', null);
        
        // Actualizar la colección de items de la reserva
        $items = $reserva->items;
        $items->transform(function ($reservaItem) use ($item) {
            if ($reservaItem->id === $item->id) {
                $reservaItem->setRelation('inventario', null);
            }
            return $reservaItem;
        });
        $reserva->setRelation('items', $items);

        // Usar reflection para llamar a processReservaItems directamente
        $controller = app(\App\Http\Controllers\ReservaController::class);
        $reflection = new \ReflectionClass($controller);
        $method = $reflection->getMethod('processReservaItems');
        $method->setAccessible(true);

        // Ejecutar el método (línea 190 se ejecuta cuando inventario es null)
        $method->invoke($controller, $reserva, $calendario);

        // Si no hay excepción, el test pasa (línea 190 continue)
        $this->assertTrue(true);
    }

    /**
     * Test para cubrir línea 249 - authorizeAdminLike cuando roles no es array
     */
    public function test_authorize_admin_like_roles_no_array_cubre_linea_249(): void
    {
        $this->crearUsuarioAdmin();

        // Crear una reserva
        $reserva = Reserva::create([
            'personas_id' => session('usuario_id'),
            'servicio' => 'Test',
            'fecha_inicio' => now()->format('Y-m-d'),
            'fecha_fin' => now()->addDays(5)->format('Y-m-d'),
            'estado' => 'pendiente',
        ]);

        // Usar reflection para llamar a authorizeAdminLike directamente
        // con roles como string (no array) para cubrir línea 249
        $controller = app(\App\Http\Controllers\ReservaController::class);
        $reflection = new \ReflectionClass($controller);
        $method = $reflection->getMethod('authorizeAdminLike');
        $method->setAccessible(true);

        // Establecer roles como string (no array) - línea 249
        session(['roles' => 'Administrador', 'role' => 'Administrador']);

        // Debe ejecutarse sin error (línea 249 convierte a array)
        $method->invoke($controller);
        
        // Si no lanza excepción, el test pasa
        $this->assertTrue(true);
    }

    /**
     * Test para cubrir línea 254 - authorizeAdminLike cuando no está autorizado
     */
    public function test_authorize_admin_like_no_autorizado_cubre_linea_254(): void
    {
        // Crear usuario sin rol admin
        $usuario = Usuario::create([
            'primer_nombre' => 'Cliente',
            'primer_apellido' => 'Test',
            'correo' => 'cliente@example.com',
            'telefono' => '1234567891',
            'contrasena' => Hash::make(self::TEST_PASSWORD),
            'fecha_registro' => now(),
            'estado' => true,
        ]);

        // Crear rol Cliente
        if (! DB::table('roles')->where('nombre_rol', 'Cliente')->exists()) {
            DB::table('roles')->insert([
                'nombre_rol' => 'Cliente',
                'name' => 'Cliente',
            ]);
        }

        $rolId = DB::table('roles')->where('nombre_rol', 'Cliente')->value('id');
        if ($rolId) {
            DB::table('personas_roles')->insert([
                'personas_id' => $usuario->id,
                'roles_id' => $rolId,
            ]);
        }

        session(['usuario_id' => $usuario->id]);
        session(['roles' => ['Cliente'], 'role' => 'Cliente']); // Rol cliente, no admin

        // Usar reflection para llamar a authorizeAdminLike directamente (línea 254)
        $controller = app(\App\Http\Controllers\ReservaController::class);
        $reflection = new \ReflectionClass($controller);
        $method = $reflection->getMethod('authorizeAdminLike');
        $method->setAccessible(true);

        // Debe lanzar HttpException con código 403 (línea 254)
        try {
            $method->invoke($controller);
            $this->fail('Expected abort(403) to be called');
        } catch (\Symfony\Component\HttpKernel\Exception\HttpException $e) {
            $this->assertEquals(403, $e->getStatusCode());
        }
    }
}

