<?php

namespace Tests\Feature;

use App\Http\Controllers\ChatbotController;
use App\Models\Servicios;
use App\Models\SubServicios;
use App\Models\Usuario;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use ReflectionClass;
use Tests\TestCase;

/**
 * Tests Feature finales para llegar al 100% en ChatbotController
 */
class ChatbotControllerFinalCoverageTest extends TestCase
{
    use RefreshDatabase;

    private const ROUTE_CHAT_ENVIAR = '/chat/enviar';
    private const TEST_EMAIL = 'test@example.com';
    private const TEST_PASSWORD = 'password123';

    protected function setUp(): void
    {
        parent::setUp();

        // Asegurar que la tabla roles existe y tiene las columnas necesarias
        if (! DB::getSchemaBuilder()->hasTable('roles')) {
            DB::statement('CREATE TABLE roles (id INTEGER PRIMARY KEY AUTOINCREMENT, nombre_rol VARCHAR(255), name VARCHAR(255), guard_name VARCHAR(255) DEFAULT "web", created_at DATETIME, updated_at DATETIME)');
        }

        // Agregar columna name si no existe
        if (DB::getSchemaBuilder()->hasTable('roles') && ! DB::getSchemaBuilder()->hasColumn('roles', 'name')) {
            DB::statement('ALTER TABLE roles ADD COLUMN name VARCHAR(255)');
        }

        // Agregar columna guard_name si no existe
        if (DB::getSchemaBuilder()->hasTable('roles') && ! DB::getSchemaBuilder()->hasColumn('roles', 'guard_name')) {
            DB::statement('ALTER TABLE roles ADD COLUMN guard_name VARCHAR(255) DEFAULT "web"');
        }

        // Crear rol Cliente si no existe
        $exists = DB::table('roles')
            ->where(function($query) {
                $query->where('name', 'Cliente')
                      ->orWhere('nombre_rol', 'Cliente');
            })
            ->exists();

        if (! $exists) {
            $roleData = ['nombre_rol' => 'Cliente'];
            if (DB::getSchemaBuilder()->hasColumn('roles', 'name')) {
                $roleData['name'] = 'Cliente';
            }
            if (DB::getSchemaBuilder()->hasColumn('roles', 'guard_name')) {
                $roleData['guard_name'] = 'web';
            }
            DB::table('roles')->insert($roleData);
        }
    }

    private function crearUsuarioAutenticado(): Usuario
    {
        $usuario = Usuario::create([
            'primer_nombre' => 'Juan',
            'primer_apellido' => 'Pérez',
            'correo' => self::TEST_EMAIL,
            'telefono' => '1234567890',
            'contrasena' => Hash::make(self::TEST_PASSWORD),
            'fecha_registro' => now(),
            'estado' => true,
        ]);

        $rolId = DB::table('roles')->where('name', 'Cliente')->orWhere('nombre_rol', 'Cliente')->value('id');
        if ($rolId) {
            DB::table('personas_roles')->insert([
                'personas_id' => $usuario->id,
                'roles_id' => $rolId,
            ]);
        }

        session(['usuario_id' => $usuario->id, 'usuario_nombre' => 'Juan']);
        session(['roles' => ['Cliente'], 'role' => 'Cliente']);

        return $usuario;
    }

    /**
     * Test para cubrir línea 155 - return respuestaRecuperacion cuando intentarRecuperacion retorna algo
     */
    public function test_manejar_error_con_recuperacion_exitosa_cubre_linea_155(): void
    {
        $this->crearUsuarioAutenticado();

        $servicio = Servicios::create([
            'nombre_servicio' => 'Alquiler',
            'descripcion' => 'Servicio de alquiler',
        ]);

        SubServicios::create([
            'servicios_id' => $servicio->id,
            'nombre' => 'Equipo de Sonido',
            'descripcion' => 'Equipo completo de sonido profesional',
            'precio' => 100,
        ]);

        // Forzar un error en procesarMensajePrincipal pero con intenciones detectables
        // Esto hará que intentarRecuperacion retorne una respuesta (no null)
        // y por lo tanto se ejecute línea 155
        
        // Usar un mensaje que cause error pero tenga intenciones
        session(['chat.days' => 0]);

        // Mockear messageProcessor para que lance excepción
        $this->mock(\App\Services\ChatbotMessageProcessor::class, function ($mock) {
            $mock->shouldReceive('procesarMensajeTexto')
                ->andThrow(new \Exception('Test error'));
        });

        $response = $this->postJson(self::ROUTE_CHAT_ENVIAR, [
            'mensaje' => 'alquiler',
        ]);

        // Debe retornar respuesta de recuperación (línea 155)
        $response->assertStatus(200);
        $data = json_decode($response->getContent(), true);
        $this->assertArrayHasKey('respuesta', $data);
    }

    /**
     * Test para cubrir líneas 173-192 - intentarRecuperacion completo
     * Específicamente líneas 185-190 cuando sessionDaysValue > 0
     */
    public function test_intentar_recuperacion_con_session_days_mayor_cero_cubre_lineas_185_190(): void
    {
        $this->crearUsuarioAutenticado();

        $servicio = Servicios::create([
            'nombre_servicio' => 'Alquiler',
            'descripcion' => 'Servicio de alquiler',
        ]);

        SubServicios::create([
            'servicios_id' => $servicio->id,
            'nombre' => 'Equipo de Sonido',
            'descripcion' => 'Equipo completo de sonido profesional',
            'precio' => 100,
        ]);

        $controller = app(ChatbotController::class);
        $reflection = new ReflectionClass($controller);
        $method = $reflection->getMethod('intentarRecuperacion');
        $method->setAccessible(true);

        // Crear request con mensaje que tenga intenciones
        $request = Request::create('/chat/enviar', 'POST', [
            'mensaje' => 'alquiler',
        ]);

        // Establecer sessionDaysValue > 0 para cubrir líneas 185-190
        session(['chat.days' => 5]);

        $response = $method->invoke($controller, $request);

        // Debe retornar JSON con actions y days cuando sessionDaysValue > 0
        $this->assertNotNull($response);
        $data = json_decode($response->getContent(), true);
        $this->assertArrayHasKey('actions', $data);
        $this->assertArrayHasKey('days', $data);
        $this->assertEquals(5, $data['days']);
        $this->assertNotNull($data['actions'][0]['meta']['dias']);
    }

    /**
     * Test para cubrir líneas 173-180 - intentarRecuperacion con hint y token
     */
    public function test_intentar_recuperacion_con_hint_y_token_cubre_lineas_173_180(): void
    {
        $this->crearUsuarioAutenticado();

        $servicio = Servicios::create([
            'nombre_servicio' => 'Alquiler',
            'descripcion' => 'Servicio de alquiler',
        ]);

        // Crear subservicio con nombre que genere sugerencias
        SubServicios::create([
            'servicios_id' => $servicio->id,
            'nombre' => 'Equipo de Sonido',
            'descripcion' => 'Equipo completo de sonido profesional para eventos',
            'precio' => 100,
        ]);

        $controller = app(ChatbotController::class);
        $reflection = new ReflectionClass($controller);
        $method = $reflection->getMethod('intentarRecuperacion');
        $method->setAccessible(true);

        $request = Request::create('/chat/enviar', 'POST', [
            'mensaje' => 'alquiler',
        ]);

        session(['chat.days' => 0]);

        $response = $method->invoke($controller, $request);

        // Debe retornar respuesta con o sin token
        $this->assertNotNull($response);
        $data = json_decode($response->getContent(), true);
        $this->assertArrayHasKey('respuesta', $data);
    }

    /**
     * Test para cubrir línea 177 - catch cuando generarSugerenciasPorToken falla
     */
    public function test_intentar_recuperacion_catch_generar_sugerencias_cubre_linea_177(): void
    {
        $this->crearUsuarioAutenticado();

        $servicio = Servicios::create([
            'nombre_servicio' => 'Alquiler',
            'descripcion' => 'Servicio de alquiler',
        ]);

        $controller = app(ChatbotController::class);
        $reflection = new ReflectionClass($controller);
        $method = $reflection->getMethod('intentarRecuperacion');
        $method->setAccessible(true);

        // Mockear suggestionGenerator para que lance excepción
        $suggestionGenerator = $this->mock(\App\Services\ChatbotSuggestionGenerator::class);
        $suggestionGenerator->shouldReceive('generarSugerenciasPorToken')
            ->andThrow(new \Exception('Error generating suggestions'));

        // Inyectar el mock en el controller usando Reflection
        $property = $reflection->getProperty('suggestionGenerator');
        $property->setAccessible(true);
        $property->setValue($controller, $suggestionGenerator);

        $request = Request::create('/chat/enviar', 'POST', [
            'mensaje' => 'alquiler',
        ]);

        session(['chat.days' => 0]);

        $response = $method->invoke($controller, $request);

        // Debe continuar sin error (línea 177 catch)
        $this->assertNotNull($response);
        $data = json_decode($response->getContent(), true);
        $this->assertArrayHasKey('respuesta', $data);
    }

    /**
     * Test para cubrir línea 192 - catch final en intentarRecuperacion
     */
    public function test_intentar_recuperacion_catch_final_cubre_linea_192(): void
    {
        $this->crearUsuarioAutenticado();

        $controller = app(ChatbotController::class);
        $reflection = new ReflectionClass($controller);
        $method = $reflection->getMethod('intentarRecuperacion');
        $method->setAccessible(true);

        // Mockear intentionDetector para que lance excepción
        $intentionDetector = $this->mock(\App\Services\ChatbotIntentionDetector::class);
        $intentionDetector->shouldReceive('detectarIntenciones')
            ->andThrow(new \Exception('Error detecting intentions'));

        // Inyectar el mock en el controller
        $property = $reflection->getProperty('intentionDetector');
        $property->setAccessible(true);
        $property->setValue($controller, $intentionDetector);

        $request = Request::create('/chat/enviar', 'POST', [
            'mensaje' => 'alquiler',
        ]);

        $response = $method->invoke($controller, $request);

        // Debe retornar null (línea 192 catch)
        $this->assertNull($response);
    }

    /**
     * Test para cubrir líneas 265-272 - obtenerDiasParaRespuesta
     */
    public function test_obtener_dias_para_respuesta_cubre_lineas_265_272(): void
    {
        $this->crearUsuarioAutenticado();

        $controller = app(ChatbotController::class);
        $reflection = new ReflectionClass($controller);
        $method = $reflection->getMethod('obtenerDiasParaRespuesta');
        $method->setAccessible(true);

        // Test línea 267: dias > 0
        session(['chat.days' => 0]);
        $result = $method->invoke($controller, 5);
        $this->assertEquals(5, $result);

        // Test línea 269: sessionDaysValue > 0
        session(['chat.days' => 3]);
        $result = $method->invoke($controller, 0);
        $this->assertEquals(3, $result);

        // Test línea 272: return null
        session(['chat.days' => 0]);
        $result = $method->invoke($controller, 0);
        $this->assertNull($result);
    }
}
