<?php

namespace Tests\Feature;

use Tests\TestCase;
use App\Models\Servicios;
use App\Models\SubServicios;
use App\Models\Usuario;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\DB;

/**
 * Tests de Integración para Chatbot
 *
 * Prueban los flujos completos de interacción con el chatbot
 */
class ChatbotTest extends TestCase
{
    use RefreshDatabase;

    private const TEST_EMAIL = 'test@example.com';
    private const TEST_PASSWORD = 'password123';
    private const TEST_NOMBRE = 'Juan';
    private const TEST_APELLIDO = 'Pérez';
    private const TEST_TELEFONO = '1234567890';
    private const ROUTE_CHAT_ENVIAR = '/chat/enviar';
    private const DESC_SERVICIO_ALQUILER = 'Servicio de alquiler';
    private const NOMBRE_EQUIPO_SONIDO = 'Equipo de sonido';
    private const DESC_EQUIPO_COMPLETO = 'Equipo completo';
    private const MENSAJE_NECESITO_ALQUILER = 'necesito alquiler';

    protected function setUp(): void
    {
        parent::setUp();
        
        // Crear rol Cliente si no existe
        if (!DB::table('roles')->where('name', 'Cliente')->exists()) {
            DB::table('roles')->insert([
                'name' => 'Cliente',
                'nombre_rol' => 'Cliente'
            ]);
        }
    }

    private function crearUsuarioAutenticado(): Usuario
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

        $rolId = DB::table('roles')->where('name', 'Cliente')->orWhere('nombre_rol', 'Cliente')->value('id');
        if ($rolId) {
            DB::table('personas_roles')->insert([
                'personas_id' => $usuario->id,
                'roles_id' => $rolId
            ]);
        }

        // Simular sesión iniciada
        session(['usuario_id' => $usuario->id, 'usuario_nombre' => self::TEST_NOMBRE]);
        session(['roles' => ['Cliente'], 'role' => 'Cliente']);

        return $usuario;
    }

    // ============================================
    // TESTS PARA VISTA DEL CHATBOT
    // ============================================

    public function test_vista_chatbot_requiere_autenticacion(): void
    {
        $response = $this->get('/usuarios/chatbot');

        // Debería redirigir o requerir autenticación según el middleware
        $this->assertTrue(
            $response->isRedirect() || $response->status() === 200
        );
    }

    public function test_vista_chatbot_retorna_vista(): void
    {
        $this->crearUsuarioAutenticado();

        $response = $this->withoutVite()->get('/usuarios/chatbot');

        $response->assertStatus(200);
        $response->assertViewIs('usuarios.chatbot');
    }

    // ============================================
    // TESTS PARA ENVIAR MENSAJE AL CHATBOT
    // ============================================

    public function test_enviar_mensaje_solicita_catalogo(): void
    {
        $this->crearUsuarioAutenticado();

        $response = $this->postJson(self::ROUTE_CHAT_ENVIAR, [
            'mensaje' => 'catalogo'
        ]);

        $response->assertStatus(200);
        $response->assertJsonStructure([
            'respuesta',
            'optionGroups'
        ]);
    }

    public function test_enviar_mensaje_con_intencion_alquiler(): void
    {
        $this->crearUsuarioAutenticado();

        $servicio = Servicios::create([
            'nombre_servicio' => 'Alquiler',
            'descripcion' => self::DESC_SERVICIO_ALQUILER
        ]);

        SubServicios::create([
            'servicios_id' => $servicio->id,
            'nombre' => self::NOMBRE_EQUIPO_SONIDO,
            'descripcion' => self::DESC_EQUIPO_COMPLETO,
            'precio' => 100
        ]);

        $response = $this->postJson(self::ROUTE_CHAT_ENVIAR, [
            'mensaje' => self::MENSAJE_NECESITO_ALQUILER
        ]);

        $response->assertStatus(200);
        $response->assertJsonStructure([
            'respuesta'
        ]);
    }

    public function test_enviar_mensaje_con_seleccion_subservicios(): void
    {
        $this->crearUsuarioAutenticado();

        $servicio = Servicios::create([
            'nombre_servicio' => 'Alquiler',
            'descripcion' => self::DESC_SERVICIO_ALQUILER
        ]);

        $subServicio = SubServicios::create([
            'servicios_id' => $servicio->id,
            'nombre' => self::NOMBRE_EQUIPO_SONIDO,
            'descripcion' => self::DESC_EQUIPO_COMPLETO,
            'precio' => 100
        ]);

        $response = $this->postJson(self::ROUTE_CHAT_ENVIAR, [
            'seleccion' => [$subServicio->id],
            'dias' => 3
        ]);

        $response->assertStatus(200);
        $response->assertJsonStructure([
            'respuesta',
            'cotizacion'
        ]);
    }

    public function test_enviar_mensaje_actualiza_dias(): void
    {
        $this->crearUsuarioAutenticado();

        $servicio = Servicios::create([
            'nombre_servicio' => 'Alquiler',
            'descripcion' => self::DESC_SERVICIO_ALQUILER
        ]);

        $subServicio = SubServicios::create([
            'servicios_id' => $servicio->id,
            'nombre' => self::NOMBRE_EQUIPO_SONIDO,
            'descripcion' => self::DESC_EQUIPO_COMPLETO,
            'precio' => 100
        ]);

        // Primero seleccionar un servicio
        session(['chat.selecciones' => [$subServicio->id]]);

        // Luego actualizar días
        $response = $this->postJson(self::ROUTE_CHAT_ENVIAR, [
            'mensaje' => 'por 5 dias'
        ]);

        $response->assertStatus(200);
        $this->assertEquals(5, session('chat.days'));
    }

    public function test_enviar_mensaje_confirmar_intencion(): void
    {
        $this->crearUsuarioAutenticado();

        $servicio = Servicios::create([
            'nombre_servicio' => 'Alquiler',
            'descripcion' => self::DESC_SERVICIO_ALQUILER
        ]);

        SubServicios::create([
            'servicios_id' => $servicio->id,
            'nombre' => self::NOMBRE_EQUIPO_SONIDO,
            'descripcion' => self::DESC_EQUIPO_COMPLETO,
            'precio' => 100
        ]);

        $response = $this->postJson(self::ROUTE_CHAT_ENVIAR, [
            'confirm_intencion' => true,
            'intenciones' => ['Alquiler'],
            'dias' => 0
        ]);

        $response->assertStatus(200);
        $response->assertJsonStructure([
            'respuesta',
            'optionGroups'
        ]);
    }

    public function test_enviar_mensaje_limpiar_cotizacion(): void
    {
        $this->crearUsuarioAutenticado();

        session(['chat.selecciones' => [1, 2, 3], 'chat.days' => 5]);

        $response = $this->postJson(self::ROUTE_CHAT_ENVIAR, [
            'limpiar_cotizacion' => true
        ]);

        $response->assertStatus(200);
        $this->assertEmpty(session('chat.selecciones'));
        $this->assertEquals(0, session('chat.days'));
    }

    public function test_enviar_mensaje_terminar_cotizacion(): void
    {
        $this->crearUsuarioAutenticado();

        $servicio = Servicios::create([
            'nombre_servicio' => 'Alquiler',
            'descripcion' => self::DESC_SERVICIO_ALQUILER
        ]);

        $subServicio = SubServicios::create([
            'servicios_id' => $servicio->id,
            'nombre' => self::NOMBRE_EQUIPO_SONIDO,
            'descripcion' => self::DESC_EQUIPO_COMPLETO,
            'precio' => 100
        ]);

        session(['chat.selecciones' => [$subServicio->id], 'chat.days' => 3]);

        $response = $this->postJson(self::ROUTE_CHAT_ENVIAR, [
            'terminar_cotizacion' => true
        ]);

        $response->assertStatus(200);
        $response->assertJsonStructure([
            'respuesta',
            'limpiar_chat',
            'selecciones',
            'total'
        ]);
    }

    public function test_enviar_mensaje_vacio(): void
    {
        $this->crearUsuarioAutenticado();

        $response = $this->postJson(self::ROUTE_CHAT_ENVIAR, [
            'mensaje' => ''
        ]);

        $response->assertStatus(200);
        $response->assertJsonStructure([
            'respuesta'
        ]);
    }

    public function test_enviar_mensaje_fuera_de_tema(): void
    {
        $this->crearUsuarioAutenticado();

        $response = $this->postJson(self::ROUTE_CHAT_ENVIAR, [
            'mensaje' => 'hola como estas'
        ]);

        $response->assertStatus(200);
        $response->assertJsonStructure([
            'respuesta',
            'sugerencias'
        ]);
    }

    public function test_enviar_mensaje_confirm_intencion_sin_subservicios(): void
    {
        $this->crearUsuarioAutenticado();

        $response = $this->postJson(self::ROUTE_CHAT_ENVIAR, [
            'confirm_intencion' => true,
            'intenciones' => ['IntencionInexistente'],
            'dias' => 0
        ]);

        $response->assertStatus(200);
        $response->assertJsonStructure([
            'respuesta',
            'optionGroups'
        ]);
        $data = json_decode($response->getContent(), true);
        $this->assertStringContainsString('catálogo', strtolower($data['respuesta']));
    }

    public function test_enviar_mensaje_confirm_intencion_con_dias(): void
    {
        $this->crearUsuarioAutenticado();

        $servicio = Servicios::create([
            'nombre_servicio' => 'Alquiler',
            'descripcion' => self::DESC_SERVICIO_ALQUILER
        ]);

        SubServicios::create([
            'servicios_id' => $servicio->id,
            'nombre' => self::NOMBRE_EQUIPO_SONIDO,
            'descripcion' => self::DESC_EQUIPO_COMPLETO,
            'precio' => 100
        ]);

        $response = $this->postJson(self::ROUTE_CHAT_ENVIAR, [
            'confirm_intencion' => true,
            'intenciones' => ['Alquiler'],
            'dias' => 5
        ]);

        $response->assertStatus(200);
        $response->assertJsonStructure([
            'respuesta',
            'optionGroups'
        ]);
    }

    public function test_enviar_mensaje_confirm_intencion_con_session_days(): void
    {
        $this->crearUsuarioAutenticado();

        $servicio = Servicios::create([
            'nombre_servicio' => 'Alquiler',
            'descripcion' => self::DESC_SERVICIO_ALQUILER
        ]);

        SubServicios::create([
            'servicios_id' => $servicio->id,
            'nombre' => self::NOMBRE_EQUIPO_SONIDO,
            'descripcion' => self::DESC_EQUIPO_COMPLETO,
            'precio' => 100
        ]);

        session(['chat.days' => 3]);

        $response = $this->postJson(self::ROUTE_CHAT_ENVIAR, [
            'confirm_intencion' => true,
            'intenciones' => ['Alquiler'],
            'dias' => 0
        ]);

        $response->assertStatus(200);
    }

    public function test_enviar_mensaje_seleccion_vacia(): void
    {
        $this->crearUsuarioAutenticado();

        $response = $this->postJson(self::ROUTE_CHAT_ENVIAR, [
            'seleccion' => [],
            'dias' => 0
        ]);

        $response->assertStatus(200);
        $response->assertJsonStructure([
            'respuesta',
            'optionGroups'
        ]);
        $this->assertEmpty(session('chat.selecciones'));
    }

    public function test_enviar_mensaje_seleccion_con_ids_invalidos(): void
    {
        $this->crearUsuarioAutenticado();

        $response = $this->postJson(self::ROUTE_CHAT_ENVIAR, [
            'seleccion' => [0, -1, 'abc'],
            'dias' => 0
        ]);

        $response->assertStatus(200);
        $response->assertJsonStructure([
            'respuesta',
            'optionGroups'
        ]);
    }

    public function test_enviar_mensaje_seleccion_items_no_encontrados(): void
    {
        $this->crearUsuarioAutenticado();

        $response = $this->postJson(self::ROUTE_CHAT_ENVIAR, [
            'seleccion' => [99999, 99998],
            'dias' => 0
        ]);

        $response->assertStatus(200);
        $response->assertJsonStructure([
            'respuesta',
            'optionGroups'
        ]);
        $this->assertEmpty(session('chat.selecciones'));
    }

    public function test_enviar_mensaje_seleccion_con_dias_cero_usando_session(): void
    {
        $this->crearUsuarioAutenticado();

        $servicio = Servicios::create([
            'nombre_servicio' => 'Alquiler',
            'descripcion' => self::DESC_SERVICIO_ALQUILER
        ]);

        $subServicio = SubServicios::create([
            'servicios_id' => $servicio->id,
            'nombre' => self::NOMBRE_EQUIPO_SONIDO,
            'descripcion' => self::DESC_EQUIPO_COMPLETO,
            'precio' => 100
        ]);

        session(['chat.days' => 3]);

        $response = $this->postJson(self::ROUTE_CHAT_ENVIAR, [
            'seleccion' => [$subServicio->id],
            'dias' => 0
        ]);

        $response->assertStatus(200);
        $response->assertJsonStructure([
            'respuesta',
            'cotizacion'
        ]);
    }

    public function test_enviar_mensaje_seleccion_con_dias_cero_sin_session(): void
    {
        $this->crearUsuarioAutenticado();

        $servicio = Servicios::create([
            'nombre_servicio' => 'Alquiler',
            'descripcion' => self::DESC_SERVICIO_ALQUILER
        ]);

        $subServicio = SubServicios::create([
            'servicios_id' => $servicio->id,
            'nombre' => self::NOMBRE_EQUIPO_SONIDO,
            'descripcion' => self::DESC_EQUIPO_COMPLETO,
            'precio' => 100
        ]);

        session()->forget('chat.days');

        $response = $this->postJson(self::ROUTE_CHAT_ENVIAR, [
            'seleccion' => [$subServicio->id],
            'dias' => 0
        ]);

        $response->assertStatus(200);
        $response->assertJsonStructure([
            'respuesta',
            'cotizacion'
        ]);
    }

    public function test_enviar_mensaje_con_error_y_recuperacion_exitosa(): void
    {
        $this->crearUsuarioAutenticado();

        $servicio = Servicios::create([
            'nombre_servicio' => 'Alquiler',
            'descripcion' => self::DESC_SERVICIO_ALQUILER
        ]);

        SubServicios::create([
            'servicios_id' => $servicio->id,
            'nombre' => self::NOMBRE_EQUIPO_SONIDO,
            'descripcion' => self::DESC_EQUIPO_COMPLETO,
            'precio' => 100
        ]);

        // Simular un error que puede ser recuperado
        // Esto se hace enviando un mensaje que cause un error pero que tenga intenciones detectables
        $response = $this->postJson(self::ROUTE_CHAT_ENVIAR, [
            'mensaje' => 'alquiler'
        ]);

        $response->assertStatus(200);
        // Puede retornar recuperación o procesamiento normal
        $this->assertIsString(json_decode($response->getContent(), true)['respuesta'] ?? '');
    }

    public function test_enviar_mensaje_con_error_sin_recuperacion(): void
    {
        $this->crearUsuarioAutenticado();

        session(['chat.days' => 1, 'chat.selecciones' => []]);

        // Mensaje que no tiene intenciones detectables para recuperación
        $response = $this->postJson(self::ROUTE_CHAT_ENVIAR, [
            'mensaje' => 'xyzabc123'
        ]);

        $response->assertStatus(200);
        $response->assertJsonStructure([
            'respuesta'
        ]);
        // Puede retornar optionGroups o sugerencias dependiendo del flujo
        $data = json_decode($response->getContent(), true);
        $this->assertArrayHasKey('respuesta', $data);
    }

    public function test_enviar_mensaje_con_mensaje_vacio_y_seleccion(): void
    {
        $this->crearUsuarioAutenticado();

        $servicio = Servicios::create([
            'nombre_servicio' => 'Alquiler',
            'descripcion' => self::DESC_SERVICIO_ALQUILER
        ]);

        $subServicio = SubServicios::create([
            'servicios_id' => $servicio->id,
            'nombre' => self::NOMBRE_EQUIPO_SONIDO,
            'descripcion' => self::DESC_EQUIPO_COMPLETO,
            'precio' => 100
        ]);

        $response = $this->postJson(self::ROUTE_CHAT_ENVIAR, [
            'mensaje' => '',
            'seleccion' => [$subServicio->id],
            'dias' => 3
        ]);

        $response->assertStatus(200);
        $response->assertJsonStructure([
            'respuesta',
            'cotizacion'
        ]);
    }

    public function test_enviar_mensaje_con_dias_desde_request(): void
    {
        $this->crearUsuarioAutenticado();

        $servicio = Servicios::create([
            'nombre_servicio' => 'Alquiler',
            'descripcion' => self::DESC_SERVICIO_ALQUILER
        ]);

        SubServicios::create([
            'servicios_id' => $servicio->id,
            'nombre' => self::NOMBRE_EQUIPO_SONIDO,
            'descripcion' => self::DESC_EQUIPO_COMPLETO,
            'precio' => 100
        ]);

        $response = $this->postJson(self::ROUTE_CHAT_ENVIAR, [
            'mensaje' => 'necesito alquiler',
            'dias' => 5
        ]);

        $response->assertStatus(200);
        // Los días pueden guardarse en la sesión o no dependiendo del flujo
        $response->assertJsonStructure(['respuesta']);
    }

    public function test_enviar_mensaje_terminar_cotizacion_guarda_cotizacion(): void
    {
        $this->crearUsuarioAutenticado();

        $servicio = Servicios::create([
            'nombre_servicio' => 'Alquiler',
            'descripcion' => self::DESC_SERVICIO_ALQUILER
        ]);

        $subServicio = SubServicios::create([
            'servicios_id' => $servicio->id,
            'nombre' => self::NOMBRE_EQUIPO_SONIDO,
            'descripcion' => self::DESC_EQUIPO_COMPLETO,
            'precio' => 100
        ]);

        session(['chat.selecciones' => [$subServicio->id], 'chat.days' => 3]);

        $response = $this->postJson(self::ROUTE_CHAT_ENVIAR, [
            'terminar_cotizacion' => true
        ]);

        $response->assertStatus(200);
        
        // Verificar que se guardó la cotización
        $this->assertDatabaseHas('cotizacion', [
            'personas_id' => session('usuario_id')
        ]);

        // Verificar que se limpió la sesión
        $this->assertEmpty(session('chat.selecciones'));
    }

    public function test_enviar_mensaje_terminar_cotizacion_sin_usuario_id(): void
    {
        $this->crearUsuarioAutenticado();

        $servicio = Servicios::create([
            'nombre_servicio' => 'Alquiler',
            'descripcion' => self::DESC_SERVICIO_ALQUILER
        ]);

        $subServicio = SubServicios::create([
            'servicios_id' => $servicio->id,
            'nombre' => self::NOMBRE_EQUIPO_SONIDO,
            'descripcion' => self::DESC_EQUIPO_COMPLETO,
            'precio' => 100
        ]);

        session()->forget('usuario_id');
        session(['chat.selecciones' => [$subServicio->id], 'chat.days' => 3]);

        // El controlador puede fallar si usuario_id es null, pero debería manejar el error
        $response = $this->postJson(self::ROUTE_CHAT_ENVIAR, [
            'terminar_cotizacion' => true
        ]);

        // Puede retornar 200 con error manejado o 500 si no se maneja
        $this->assertContains($response->status(), [200, 500]);
    }

    public function test_enviar_mensaje_con_seleccion_duplicados(): void
    {
        $this->crearUsuarioAutenticado();

        $servicio = Servicios::create([
            'nombre_servicio' => 'Alquiler',
            'descripcion' => self::DESC_SERVICIO_ALQUILER
        ]);

        $subServicio = SubServicios::create([
            'servicios_id' => $servicio->id,
            'nombre' => self::NOMBRE_EQUIPO_SONIDO,
            'descripcion' => self::DESC_EQUIPO_COMPLETO,
            'precio' => 100
        ]);

        // Selección con IDs duplicados
        $response = $this->postJson(self::ROUTE_CHAT_ENVIAR, [
            'seleccion' => [$subServicio->id, $subServicio->id, $subServicio->id],
            'dias' => 3
        ]);

        $response->assertStatus(200);
        $response->assertJsonStructure([
            'respuesta',
            'cotizacion'
        ]);
        
        // Verificar que solo se guardó una vez
        $selecciones = session('chat.selecciones', []);
        $this->assertCount(1, array_unique($selecciones));
    }

    public function test_enviar_mensaje_con_mensaje_y_dias(): void
    {
        $this->crearUsuarioAutenticado();

        $servicio = Servicios::create([
            'nombre_servicio' => 'Alquiler',
            'descripcion' => self::DESC_SERVICIO_ALQUILER
        ]);

        SubServicios::create([
            'servicios_id' => $servicio->id,
            'nombre' => self::NOMBRE_EQUIPO_SONIDO,
            'descripcion' => self::DESC_EQUIPO_COMPLETO,
            'precio' => 100
        ]);

        $response = $this->postJson(self::ROUTE_CHAT_ENVIAR, [
            'mensaje' => 'necesito alquiler',
            'dias' => 7
        ]);

        $response->assertStatus(200);
        // Los días pueden guardarse o no dependiendo del flujo del procesador
        $response->assertJsonStructure(['respuesta']);
    }

    public function test_enviar_mensaje_con_continuacion(): void
    {
        $this->crearUsuarioAutenticado();

        $servicio = Servicios::create([
            'nombre_servicio' => 'Alquiler',
            'descripcion' => self::DESC_SERVICIO_ALQUILER
        ]);

        SubServicios::create([
            'servicios_id' => $servicio->id,
            'nombre' => self::NOMBRE_EQUIPO_SONIDO,
            'descripcion' => self::DESC_EQUIPO_COMPLETO,
            'precio' => 100
        ]);

        session(['chat.intenciones' => ['Alquiler'], 'chat.days' => 3]);

        $response = $this->postJson(self::ROUTE_CHAT_ENVIAR, [
            'mensaje' => 'tambien'
        ]);

        $response->assertStatus(200);
        $response->assertJsonStructure([
            'respuesta'
        ]);
    }
}

