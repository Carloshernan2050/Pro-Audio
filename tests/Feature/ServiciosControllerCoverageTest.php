<?php

namespace Tests\Feature;

use App\Exceptions\ServiceCreationException;
use App\Http\Controllers\ServiciosController;
use App\Models\Servicios;
use App\Models\Usuario;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

/**
 * Tests para cubrir las líneas faltantes en ServiciosController (32-36, 69, 85-90)
 * 
 * Estado de cobertura:
 * - Líneas 32-36 (index catch): Cubierto parcialmente (el catch se ejecuta, pero la vista también falla)
 * - Línea 69 (ServiceCreationException): Requiere mock avanzado de Servicios::create() para retornar null/objeto sin id
 * - Líneas 85-90 (catch blocks en store): Cubierto parcialmente con eventos de Eloquent
 * 
 * Nota: Algunos tests requieren técnicas avanzadas de mocking que pueden no funcionar
 * correctamente cuando las clases ya están cargadas. Se recomienda usar herramientas
 * de cobertura de código para verificar si estas líneas se ejecutan en escenarios reales.
 */
class ServiciosControllerCoverageTest extends TestCase
{
    use RefreshDatabase;

    private const ROUTE_SERVICIOS = '/servicios';
    private const TEST_EMAIL = 'admin@example.com';
    private const TEST_PASSWORD = 'password123';
    private const TEST_NOMBRE = 'Admin';
    private const TEST_APELLIDO = 'Usuario';
    private const TEST_TELEFONO = '1234567890';

    protected function setUp(): void
    {
        parent::setUp();

        // Crear rol Administrador si no existe
        if (! DB::table('roles')->where('nombre_rol', 'Administrador')->exists()) {
            DB::table('roles')->insert([
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

        session(['usuario_id' => $usuario->id, 'usuario_nombre' => self::TEST_NOMBRE]);
        session(['roles' => ['Administrador'], 'role' => 'Administrador']);

        return $usuario;
    }

    protected function tearDown(): void
    {
        if (class_exists('\Mockery')) {
            \Mockery::close();
        }
        DB::clearResolvedInstances();
        parent::tearDown();
    }

    /**
     * Test para cubrir líneas 32-36: catch block en index() cuando hay excepción
     * 
     * Usamos reflection para llamar directamente al método index() y evitar
     * que la excepción ocurra en la vista (topbar), permitiendo que el catch
     * del controlador se ejecute correctamente.
     */
    public function test_index_catch_exception_cubre_lineas_32_36(): void
    {
        $this->crearUsuarioAdmin();

        $controller = app(ServiciosController::class);
        
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
     * Test para cubrir línea 69 y líneas 85-86: ServiceCreationException
     * 
     * Usa un evento saving que retorna false para cancelar el guardado.
     * Esto hace que create() retorne false, lo cual se verifica en la línea 68
     * y lanza ServiceCreationException en la línea 69, cubriendo también las líneas 85-86.
     */
    public function test_store_service_creation_exception_cubre_linea_69_y_85_86(): void
    {
        $this->crearUsuarioAdmin();

        $controller = app(ServiciosController::class);
        $request = Request::create(self::ROUTE_SERVICIOS, 'POST', [
            'nombre_servicio' => 'Test Servicio Fallido',
            'descripcion' => 'Test descripción',
        ]);
        $request->headers->set('Content-Type', 'application/x-www-form-urlencoded');
        $request->setLaravelSession($this->app->make('session.store'));

        // Usar un evento saving que retorne false para cancelar el guardado
        // Esto hará que create() retorne false (que se evalúa como !$servicio en línea 68)
        Servicios::saving(function () {
            return false; // Cancelar el guardado
        });

        // Llamar al método store usando reflection
        $reflection = new \ReflectionClass($controller);
        $method = $reflection->getMethod('store');
        
        try {
            $response = $method->invoke($controller, $request);
            
            // Debería retornar redirect con error (líneas 85-86)
            $this->assertInstanceOf(\Illuminate\Http\RedirectResponse::class, $response);
            
            // Verificar que la sesión tiene el mensaje de error de ServiceCreationException
            $this->assertTrue(session()->has('error'));
            $errorMessage = session('error');
            $this->assertStringContainsString('Error al crear el servicio', $errorMessage);
        } finally {
            // Limpiar eventos
            Servicios::flushEventListeners();
        }
    }

    /**
     * Test para cubrir líneas 88-90: catch Exception general
     * 
     * Forzamos una excepción general en el método store después de pasar la validación
     */
    public function test_store_catch_general_exception_cubre_lineas_88_90(): void
    {
        $this->crearUsuarioAdmin();

        $controller = app(ServiciosController::class);
        $request = Request::create(self::ROUTE_SERVICIOS, 'POST', [
            'nombre_servicio' => 'Test General Exception',
            'descripcion' => 'Test',
        ]);
        $request->headers->set('Content-Type', 'application/x-www-form-urlencoded');
        $request->setLaravelSession($this->app->make('session.store'));

        // Usar un evento creating para lanzar una excepción que simule
        // un error general después de pasar la validación
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
     * Test adicional para cubrir línea 69: ServiceCreationException
     * 
     * Este test complementa el anterior usando un enfoque diferente.
     * Verifica que el comportamiento es correcto cuando create() falla.
     */
    public function test_store_service_creation_exception_directo(): void
    {
        $this->crearUsuarioAdmin();

        // Este test verifica el comportamiento cuando create() retorna false
        // usando el evento saving que retorna false
        Servicios::saving(function () {
            return false; // Cancelar el guardado
        });

        try {
            $response = $this->post(self::ROUTE_SERVICIOS, [
                'nombre_servicio' => 'Test Servicio Sin Guardar',
                'descripcion' => 'Test descripción',
            ]);

            // Debería retornar redirect con error
            $response->assertRedirect(route('servicios.index'));
            $response->assertSessionHas('error');
            
            $errorMessage = session('error');
            $this->assertStringContainsString('Error al crear el servicio', $errorMessage);
        } finally {
            // Limpiar eventos
            Servicios::flushEventListeners();
        }
    }
}
