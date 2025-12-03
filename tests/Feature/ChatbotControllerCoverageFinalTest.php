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
 * Cubre líneas: 189, 234-236
 */
class ChatbotControllerCoverageFinalTest extends TestCase
{
    use RefreshDatabase;

    private const ROUTE_CHAT_ENVIAR = '/chat/enviar';
    private const TEST_EMAIL = 'test@example.com';
    private const TEST_PASSWORD = 'password123';

    protected function setUp(): void
    {
        parent::setUp();

        // Asegurar que la tabla roles existe
        if (! DB::getSchemaBuilder()->hasTable('roles')) {
            DB::statement('CREATE TABLE roles (id INTEGER PRIMARY KEY AUTOINCREMENT, nombre_rol VARCHAR(255), name VARCHAR(255), guard_name VARCHAR(255) DEFAULT "web", created_at DATETIME, updated_at DATETIME)');
        }

        if (DB::getSchemaBuilder()->hasTable('roles') && ! DB::getSchemaBuilder()->hasColumn('roles', 'name')) {
            DB::statement('ALTER TABLE roles ADD COLUMN name VARCHAR(255)');
        }

        if (DB::getSchemaBuilder()->hasTable('roles') && ! DB::getSchemaBuilder()->hasColumn('roles', 'guard_name')) {
            DB::statement('ALTER TABLE roles ADD COLUMN guard_name VARCHAR(255) DEFAULT "web"');
        }

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
     * Test para cubrir línea 189: procesarSugerenciaDeBaseDatos cuando mejorSugerencia está vacío
     * 
     * Para cubrir la línea 189, necesitamos que:
     * - $hint tenga 'token' y 'sugerencias' (no vacío) para pasar la línea 183
     * - Pero $hint['sugerencias'][0] sea null o una cadena vacía para ejecutar línea 189
     */
    public function test_procesar_sugerencia_de_base_datos_mejor_sugerencia_vacio_cubre_linea_189(): void
    {
        $this->crearUsuarioAutenticado();

        $controller = app(ChatbotController::class);
        $reflection = new ReflectionClass($controller);
        $method = $reflection->getMethod('procesarSugerenciaDeBaseDatos');
        $method->setAccessible(true);

        // Mockear suggestionGenerator para que retorne hint con sugerencias que tengan el primer elemento vacío
        // El array de sugerencias debe tener al menos un elemento (no estar vacío) para pasar la línea 183
        // Pero el primer elemento debe ser una cadena vacía para ejecutar línea 189
        // Nota: empty('') retorna true, pero empty(['']) retorna false (el array no está vacío)
        $suggestionGenerator = $this->mock(\App\Services\ChatbotSuggestionGenerator::class);
        $suggestionGenerator->shouldReceive('generarSugerenciasPorToken')
            ->andReturn([['token' => 'test', 'sugerencias' => ['']]]); // Array con cadena vacía como primer elemento

        // Inyectar el mock
        $property = $reflection->getProperty('suggestionGenerator');
        $property->setAccessible(true);
        $property->setValue($controller, $suggestionGenerator);

        $resultado = $method->invoke($controller, 'test');

        // Debe retornar null cuando mejorSugerencia está vacío (línea 189)
        $this->assertNull($resultado);
    }

    /**
     * Test para cubrir líneas 234-236: construirAccionesRecuperacion con ints vacío
     */
    public function test_construir_acciones_recuperacion_ints_vacio_cubre_lineas_234_236(): void
    {
        $this->crearUsuarioAutenticado();

        $controller = app(ChatbotController::class);
        $reflection = new ReflectionClass($controller);
        $method = $reflection->getMethod('construirAccionesRecuperacion');
        $method->setAccessible(true);

        // Llamar con array vacío de intenciones para cubrir líneas 234-236
        $resultado = $method->invoke($controller, [], 0);

        // Debe retornar solo el botón de "Mostrar catálogo" (líneas 234-236)
        $this->assertIsArray($resultado);
        $this->assertCount(1, $resultado);
        $this->assertEquals('reject_intent', $resultado[0]['id']);
        $this->assertEquals('Mostrar catálogo', $resultado[0]['label']);
    }
}

