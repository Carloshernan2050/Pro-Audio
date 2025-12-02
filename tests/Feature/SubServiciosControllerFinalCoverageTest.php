<?php

namespace Tests\Feature;

use App\Models\Servicios;
use App\Models\SubServicios;
use App\Models\Usuario;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

/**
 * Tests para cubrir líneas faltantes en SubServiciosController
 */
class SubServiciosControllerFinalCoverageTest extends TestCase
{
    use RefreshDatabase;

    private function crearUsuarioAdmin(): Usuario
    {
        $usuario = Usuario::create([
            'primer_nombre' => 'Admin',
            'primer_apellido' => 'Test',
            'correo' => 'admin@test.com',
            'telefono' => '1234567890',
            'contrasena' => Hash::make('password123'),
            'fecha_registro' => now(),
            'estado' => true,
        ]);

        // Crear rol Admin si no existe
        if (! DB::table('roles')->where('name', 'Admin')->exists()) {
            DB::table('roles')->insert([
                'name' => 'Admin',
                'nombre_rol' => 'Administrador',
                'guard_name' => 'web',
            ]);
        }

        $rolId = DB::table('roles')->where('name', 'Admin')->value('id');
        if ($rolId) {
            DB::table('personas_roles')->insert([
                'personas_id' => $usuario->id,
                'roles_id' => $rolId,
            ]);
        }

        session(['usuario_id' => $usuario->id]);
        session(['roles' => ['Admin'], 'role' => 'Admin']);

        return $usuario;
    }

    /**
     * Test para cubrir línea 46 de SubServiciosController
     * handleExceptionError() retorna JSON cuando la request es AJAX
     */
    public function test_handle_exception_error_ajax_cubre_linea_46(): void
    {
        $this->crearUsuarioAdmin();

        $servicio = Servicios::create([
            'nombre_servicio' => 'Test Servicio',
            'descripcion' => 'Test descripcion',
            'icono' => 'test-icon',
        ]);

        // Mockear Storage para que lance excepción al guardar imagen
        Storage::shouldReceive('disk')
            ->with('public')
            ->andReturnSelf();
        Storage::shouldReceive('putFileAs')
            ->andThrow(new \Exception('Storage error'));

        // Crear request con imagen para forzar el error
        $file = UploadedFile::fake()->image('test.jpg');
        
        // Hacer request AJAX
        $response = $this->withHeaders([
            'X-Requested-With' => 'XMLHttpRequest',
        ])->post(route('subservicios.store'), [
            'servicios_id' => $servicio->id,
            'nombre' => 'Test SubServicio',
            'descripcion' => 'Test descripcion',
            'precio' => 100000,
            'imagen' => $file,
        ]);

        // Debería retornar JSON con error (línea 46)
        $response->assertStatus(422);
        $response->assertJsonStructure(['error']);
    }

    /**
     * Test para cubrir líneas 67-73 de SubServiciosController
     * index() catch block cuando hay excepción
     * Usa reflection para invocar el método directamente y evitar problemas con la vista.
     */
    public function test_index_catch_exception_cubre_lineas_67_73(): void
    {
        $this->crearUsuarioAdmin();

        // Usar reflection para invocar el método index() directamente
        $controller = app(\App\Http\Controllers\SubServiciosController::class);
        $reflection = new \ReflectionClass($controller);
        $method = $reflection->getMethod('index');
        $method->setAccessible(true);

        // Guardar información de la conexión original
        $connection = DB::connection();
        $connectionName = $connection->getName();
        $originalConfig = config("database.connections.{$connectionName}");
        
        try {
            // Cambiar temporalmente la configuración para que la conexión falle
            config(["database.connections.{$connectionName}.database" => ':nonexistent:']);
            
            // Limpiar la instancia de conexión para forzar que use la nueva configuración
            DB::purge($connectionName);
            
            // Mockear Log para verificar que se llama
            Log::shouldReceive('error')
                ->once()
                ->with(\Mockery::pattern('/SubServiciosController@index Error/'));
            
            // Ahora el método debería lanzar una excepción al intentar consultar
            // y el catch block (líneas 67-73) debería manejarla
            $response = $method->invoke($controller);
            
            // Debería retornar la vista con error (líneas 67-73)
            $this->assertInstanceOf(\Illuminate\View\View::class, $response);
            $this->assertEquals('usuarios.subservicios', $response->name());
        } finally {
            // Restaurar la configuración original
            config(["database.connections.{$connectionName}" => $originalConfig]);
            
            // Limpiar y reconectar
            DB::purge($connectionName);
            DB::reconnect($connectionName);
            
            // Limpiar mocks
            Log::clearResolvedInstances();
        }
    }

    /**
     * Test para cubrir líneas 120-121 de SubServiciosController
     * store() catch block cuando hay excepción general
     */
    public function test_store_catch_general_exception_cubre_lineas_120_121(): void
    {
        $this->crearUsuarioAdmin();

        $servicio = Servicios::create([
            'nombre_servicio' => 'Test Servicio',
            'descripcion' => 'Test descripcion',
            'icono' => 'test-icon',
        ]);

        // Mockear Storage para que lance excepción al guardar imagen
        Storage::shouldReceive('disk')
            ->with('public')
            ->andReturnSelf();
        Storage::shouldReceive('putFileAs')
            ->andThrow(new \Exception('Storage error'));

        // Crear request con imagen para forzar el error
        $file = UploadedFile::fake()->image('test.jpg');
        
        $response = $this->post(route('subservicios.store'), [
            'servicios_id' => $servicio->id,
            'nombre' => 'Test SubServicio',
            'descripcion' => 'Test descripcion',
            'precio' => 100000,
            'imagen' => $file,
        ]);

        // Debería redirigir con error (líneas 120-121)
        $response->assertRedirect(route('subservicios.index'));
        $response->assertSessionHas('error');
    }

    /**
     * Test para cubrir líneas 190-191 de SubServiciosController
     * update() catch block cuando hay excepción general
     */
    public function test_update_catch_general_exception_cubre_lineas_190_191(): void
    {
        $this->crearUsuarioAdmin();

        $servicio = Servicios::create([
            'nombre_servicio' => 'Test Servicio',
            'descripcion' => 'Test descripcion',
            'icono' => 'test-icon',
        ]);

        $subServicio = SubServicios::create([
            'servicios_id' => $servicio->id,
            'nombre' => 'Test SubServicio',
            'descripcion' => 'Test descripcion',
            'precio' => 100000,
        ]);

        // Mockear Storage para que lance excepción al actualizar imagen
        Storage::shouldReceive('disk')
            ->with('public')
            ->andReturnSelf();
        Storage::shouldReceive('exists')
            ->andReturn(false);
        Storage::shouldReceive('putFileAs')
            ->andThrow(new \Exception('Storage update error'));

        // Crear request con imagen para forzar el error
        $file = UploadedFile::fake()->image('test.jpg');
        
        $response = $this->put(route('subservicios.update', $subServicio->id), [
            'servicios_id' => $servicio->id,
            'nombre' => 'Test SubServicio Actualizado',
            'descripcion' => 'Test descripcion actualizada',
            'precio' => 150000,
            'imagen' => $file,
        ]);

        // Debería redirigir con error (líneas 190-191)
        $response->assertRedirect(route('subservicios.index'));
        $response->assertSessionHas('error');
    }

    protected function tearDown(): void
    {
        // Limpiar mocks
        if (class_exists('\Mockery')) {
            \Mockery::close();
        }
        Storage::clearResolvedInstances();
        Log::clearResolvedInstances();
        parent::tearDown();
    }
}

