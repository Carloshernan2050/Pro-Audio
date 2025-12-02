<?php

namespace Tests\Feature;

use App\Models\Usuario;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

/**
 * Tests Feature finales para llegar al 100% en UsuarioController
 */
class UsuarioControllerFinalCoverageTest extends TestCase
{
    use RefreshDatabase;

    private const TEST_EMAIL = 'test@example.com';
    private const TEST_PASSWORD = 'password123';

    protected function setUp(): void
    {
        parent::setUp();

        if (! DB::table('roles')->where('name', 'Cliente')->exists()) {
            DB::table('roles')->insert([
                'name' => 'Cliente',
                'nombre_rol' => 'Cliente',
            ]);
        }
    }

    protected function tearDown(): void
    {
        // Limpiar todos los mocks para evitar interferencias con otros tests
        // Esto es crítico para evitar problemas con transacciones de BD
        if (class_exists('\Mockery')) {
            \Mockery::close();
        }
        // También limpiar cualquier mock de facades
        DB::clearResolvedInstances();
        parent::tearDown();
    }

    /**
     * Test para cubrir línea 52 - catch de excepción al asignar rol
     * Fuerza una excepción durante la asignación del rol en el método store()
     * para que se ejecute el bloque catch (línea 52)
     * 
     * Usamos reflection para llamar directamente al método store() y mockear
     * DB::table('personas_roles')->insert() para que lance una excepción
     */
    public function test_store_catch_exception_al_asignar_rol_cubre_linea_52(): void
    {
        // Asegurar que existe el rol Cliente
        if (! DB::table('roles')->where('name', 'Cliente')->exists()) {
            DB::table('roles')->insert([
                'name' => 'Cliente',
                'nombre_rol' => 'Cliente',
                'guard_name' => 'web',
            ]);
        }

        // Crear un request válido
        $request = \Illuminate\Http\Request::create('/usuarios/registro', 'POST', [
            'primer_nombre' => 'Test',
            'primer_apellido' => 'Exception',
            'correo' => 'test-exception-'.time().'@example.com',
            'telefono' => '1234567890',
            'contrasena' => self::TEST_PASSWORD,
            'contrasena_confirmation' => self::TEST_PASSWORD,
        ]);
        $request->setLaravelSession($this->app->make('session.store'));

        // Obtener el controlador
        $controller = app(\App\Http\Controllers\UsuarioController::class);

        // Mockear DB::table('personas_roles')->insert() para que lance una excepción
        $rolId = DB::table('roles')->where('name', 'Cliente')->orWhere('nombre_rol', 'Cliente')->value('id');
        
        // Interceptar solo la inserción en personas_roles usando un evento
        $originalInsert = null;
        
        // Usar un closure para interceptar la inserción
        DB::beforeExecuting(function ($query, $bindings) use (&$originalInsert) {
            if (str_contains($query, 'personas_roles') && str_contains($query, 'insert')) {
                throw new \Exception('Error simulado al insertar en personas_roles');
            }
        });

        try {
            // Llamar al método store() usando reflection para que el coverage lo cuente
            $reflection = new \ReflectionClass($controller);
            $method = $reflection->getMethod('store');
            $response = $method->invoke($controller, $request);

            // Verificar que el usuario se creó a pesar de la excepción
            $usuario = Usuario::where('correo', 'like', 'test-exception-%@example.com')->first();
            $this->assertNotNull($usuario, 'El usuario debería haberse creado a pesar de la excepción');

            // Verificar que se redirige correctamente (el catch permite que el flujo continúe)
            $this->assertInstanceOf(\Illuminate\Http\RedirectResponse::class, $response);
            
        } finally {
            // Limpiar el listener
            DB::flushQueryLog();
        }
    }
}

