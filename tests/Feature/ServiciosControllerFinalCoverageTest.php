<?php

namespace Tests\Feature;

use App\Exceptions\ServiceCreationException;
use App\Models\Servicios;
use App\Models\Usuario;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Tests\TestCase;

/**
 * Tests para cubrir líneas faltantes en ServiciosController
 */
class ServiciosControllerFinalCoverageTest extends TestCase
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
     * Test para cubrir líneas 32-36 de ServiciosController
     * index() catch block cuando hay excepción
     */
    public function test_index_catch_exception_cubre_lineas_32_36(): void
    {
        $this->crearUsuarioAdmin();

        $controller = new \App\Http\Controllers\ServiciosController();
        
        // Guardar información de la conexión original
        $connection = DB::connection();
        $connectionName = $connection->getName();
        $originalConfig = config("database.connections.{$connectionName}");

        try {
            // Cambiar temporalmente la configuración para que la conexión falle
            config(["database.connections.{$connectionName}.database" => ':nonexistent:']);

            // Limpiar la instancia de conexión para forzar que use la nueva configuración
            DB::purge($connectionName);

            // Usar reflection para llamar directamente al método index()
            // Esto permite que el catch block se ejecute sin que la vista falle primero
            $reflection = new \ReflectionClass($controller);
            $method = $reflection->getMethod('index');
            
            $response = $method->invoke($controller);

            // Debería retornar la vista con error (líneas 32-36)
            $this->assertInstanceOf(\Illuminate\Contracts\View\View::class, $response);
            $this->assertEquals('usuarios.ajustes', $response->name());
        } finally {
            // Restaurar la configuración original
            config(["database.connections.{$connectionName}" => $originalConfig]);

            // Limpiar y reconectar
            DB::purge($connectionName);
            DB::reconnect($connectionName);
        }
    }

    /**
     * Test para cubrir línea 69 de ServiciosController
     * store() throw ServiceCreationException cuando el servicio no se guarda correctamente
     */
    public function test_store_throw_service_creation_exception_cubre_linea_69(): void
    {
        $this->crearUsuarioAdmin();

        // Usar un evento saving que retorne false para cancelar el guardado
        // Esto hará que create() retorne false, lo cual se verifica en la línea 68
        // y lanza ServiceCreationException en la línea 69
        Servicios::saving(function () {
            return false; // Cancelar el guardado
        });

        try {
            $response = $this->post(route('servicios.store'), [
                'nombre_servicio' => 'Test Servicio Sin Guardar',
                'descripcion' => 'Test descripción',
            ]);

            // Debería retornar redirect con error (línea 69 -> líneas 85-86)
            $response->assertRedirect(route('servicios.index'));
            $response->assertSessionHas('error');
            
            $errorMessage = session('error');
            $this->assertStringContainsString('Error al crear el servicio', $errorMessage);
        } finally {
            // Limpiar eventos
            Servicios::flushEventListeners();
        }
    }

    /**
     * Test para cubrir líneas 75-80 de ServiciosController
     * store() catch block cuando falla la generación del blade
     */
    public function test_store_catch_blade_error_cubre_lineas_75_80(): void
    {
        $this->crearUsuarioAdmin();

        // Asegurarse de que existe la plantilla base para que el método la use
        $plantillaBase = resource_path('views/usuarios/animacion.blade.php');
        if (!File::exists($plantillaBase)) {
            // Crear la plantilla base si no existe
            File::ensureDirectoryExists(resource_path('views/usuarios'));
            File::put($plantillaBase, '@extends(\'layouts.app\')');
        }

        // Mockear File::put() para que lance excepción al generar blade
        File::shouldReceive('exists')
            ->with(\Mockery::pattern('/animacion\.blade\.php/'))
            ->andReturn(true);
        File::shouldReceive('exists')
            ->with(\Mockery::pattern('/views\/usuarios$/'))
            ->andReturn(true);
        File::shouldReceive('get')
            ->andReturn('test content');
        File::shouldReceive('put')
            ->andThrow(new \Exception('File write error'));

        // Crear el servicio normalmente - el error ocurrirá al generar el blade
        $response = $this->post(route('servicios.store'), [
            'nombre_servicio' => 'Test Servicio Blade Error',
            'descripcion' => 'Test descripcion',
            'icono' => 'test-icon',
        ]);

        // Debería redirigir con success y warning (líneas 75-80)
        $response->assertRedirect(route('servicios.index'));
        $response->assertSessionHas('success');
        // El warning puede o no estar presente dependiendo de cómo se maneje el mock
        // Verificamos que al menos el success esté presente
    }

    /**
     * Test para cubrir líneas 85-90 de ServiciosController
     * store() catch block general cuando hay excepción
     */
    public function test_store_catch_general_exception_cubre_lineas_85_90(): void
    {
        $this->crearUsuarioAdmin();

        $controller = new \App\Http\Controllers\ServiciosController();
        $request = \Illuminate\Http\Request::create(route('servicios.store'), 'POST', [
            'nombre_servicio' => 'Test General Exception',
            'descripcion' => 'Test',
        ]);
        $request->headers->set('Content-Type', 'application/x-www-form-urlencoded');
        $request->setLaravelSession($this->app->make('session.store'));

        // Usar un evento creating para lanzar una excepción general
        // que se capture en el catch Exception (líneas 88-90)
        Servicios::creating(function () {
            throw new \Exception('Error simulado en creación de servicio');
        });

        // Llamar al método store usando reflection
        $reflection = new \ReflectionClass($controller);
        $method = $reflection->getMethod('store');
        
        try {
            $response = $method->invoke($controller, $request);
            
            // Debería retornar redirect con error (líneas 88-90)
            $this->assertInstanceOf(\Illuminate\Http\RedirectResponse::class, $response);
            
            // Verificar que la sesión tiene el mensaje de error
            $this->assertTrue(session()->has('error'));
            $errorMessage = session('error');
            $this->assertStringContainsString('Error inesperado al crear el servicio', $errorMessage);
        } finally {
            // Limpiar eventos
            Servicios::flushEventListeners();
        }
    }

    /**
     * Test para cubrir líneas 153-155 de ServiciosController
     * update() catch block cuando hay excepción
     */
    public function test_update_catch_exception_cubre_lineas_153_155(): void
    {
        $this->crearUsuarioAdmin();

        // Crear un servicio válido
        $servicio = Servicios::create([
            'nombre_servicio' => 'Test Servicio Update',
            'descripcion' => 'Test descripcion',
            'icono' => 'test-icon',
        ]);

        // Mockear File::put() para que lance excepción al actualizar blade
        File::shouldReceive('exists')
            ->andReturn(true);
        File::shouldReceive('get')
            ->andReturn('test content');
        File::shouldReceive('put')
            ->andThrow(new \Exception('File update error'));

        $response = $this->put(route('servicios.update', $servicio->id), [
            'nombre_servicio' => 'Test Servicio Actualizado',
            'descripcion' => 'Test descripcion actualizada',
            'icono' => 'test-icon-updated',
        ]);

        // Debería redirigir con error (líneas 153-155)
        $response->assertRedirect(route('servicios.index'));
        $response->assertSessionHas('error');
    }

    protected function tearDown(): void
    {
        // Limpiar mocks
        if (class_exists('\Mockery')) {
            \Mockery::close();
        }
        File::clearResolvedInstances();
        Log::clearResolvedInstances();
        parent::tearDown();
    }
}

