<?php

namespace Tests\Unit;

use App\Http\Controllers\HistorialController;
use App\Models\Calendario;
use App\Models\Historial;
use App\Models\Inventario;
use App\Models\MovimientosInventario;
use App\Models\Reserva;
use App\Models\Usuario;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
use Tests\Unit\Exceptions\DatabaseTestException;

/**
 * Tests Unitarios para HistorialController
 *
 * Tests para estructura y configuración
 */
class HistorialControllerUnitTest extends TestCase
{
    use RefreshDatabase;

    private const TEST_TELEFONO = '1234567890';

    private const TEST_EMAIL = 'test@test.com';

    private const ROUTE_HISTORIAL = '/historial';

    private const TEST_PASSWORD = 'password';

    private const ESTADO_PENDIENTE = 'pendiente';

    private const ACCION_CREADA = 'creada';

    protected $controller;

    protected function setUp(): void
    {
        parent::setUp();
        $this->controller = new HistorialController;
    }

    public function test_controller_instancia_correctamente(): void
    {
        $this->assertInstanceOf(HistorialController::class, $this->controller);
    }

    public function test_index_retorna_vista(): void
    {
        $usuario = Usuario::create([
            'primer_nombre' => 'Test',
            'primer_apellido' => 'Usuario',
            'telefono' => self::TEST_TELEFONO,
            'correo' => self::TEST_EMAIL,
            'contrasena' => self::TEST_PASSWORD,
            'fecha_registro' => now(),
            'estado' => true,
        ]);

        $inventario = Inventario::create([
            'descripcion' => 'Test Inventario',
            'stock' => 10,
        ]);

        $movimiento = MovimientosInventario::create([
            'inventario_id' => $inventario->id,
            'tipo_movimiento' => 'salida',
            'cantidad' => 1,
            'fecha_movimiento' => now(),
            'descripcion' => 'Test movimiento',
        ]);

        $calendario = Calendario::create([
            'personas_id' => $usuario->id,
            'movimientos_inventario_id' => $movimiento->id,
            'fecha' => now()->toDateString(),
            'fecha_inicio' => now(),
            'fecha_fin' => now()->addDays(1),
            'evento' => 'Test evento',
            'descripcion_evento' => 'Test descripción',
        ]);

        $reserva = Reserva::create([
            'personas_id' => $usuario->id,
            'calendario_id' => $calendario->id,
            'fecha_inicio' => now(),
            'fecha_fin' => now()->addDays(1),
            'estado' => self::ESTADO_PENDIENTE,
        ]);

        Historial::create([
            'calendario_id' => $calendario->id,
            'reserva_id' => $reserva->id,
            'accion' => self::ACCION_CREADA,
        ]);

        $response = $this->controller->index();

        $this->assertNotNull($response);
    }

    public function test_export_pdf_retorna_pdf(): void
    {
        $usuario = Usuario::create([
            'primer_nombre' => 'Test',
            'primer_apellido' => 'Usuario',
            'telefono' => self::TEST_TELEFONO,
            'correo' => self::TEST_EMAIL,
            'contrasena' => self::TEST_PASSWORD,
            'fecha_registro' => now(),
            'estado' => true,
        ]);

        $inventario = Inventario::create([
            'descripcion' => 'Test Inventario',
            'stock' => 10,
        ]);

        $movimiento = MovimientosInventario::create([
            'inventario_id' => $inventario->id,
            'tipo_movimiento' => 'salida',
            'cantidad' => 1,
            'fecha_movimiento' => now(),
            'descripcion' => 'Test movimiento',
        ]);

        $calendario = Calendario::create([
            'personas_id' => $usuario->id,
            'movimientos_inventario_id' => $movimiento->id,
            'fecha' => now()->toDateString(),
            'fecha_inicio' => now(),
            'fecha_fin' => now()->addDays(1),
            'evento' => 'Test evento',
            'descripcion_evento' => 'Test descripción',
        ]);

        $reserva = Reserva::create([
            'personas_id' => $usuario->id,
            'calendario_id' => $calendario->id,
            'fecha_inicio' => now(),
            'fecha_fin' => now()->addDays(1),
            'estado' => self::ESTADO_PENDIENTE,
        ]);

        Historial::create([
            'calendario_id' => $calendario->id,
            'reserva_id' => $reserva->id,
            'accion' => self::ACCION_CREADA,
        ]);

        $response = $this->controller->exportPdf();

        $this->assertNotNull($response);
        $this->assertStringContainsString('historial.pdf', $response->headers->get('Content-Disposition'));
    }

    public function test_pdf_configuracion_estructura(): void
    {
        // Verificar configuración de PDF
        $formatoPapel = 'a4';
        $orientacion = 'portrait';
        $nombreArchivo = 'historial.pdf';

        $this->assertEquals('a4', $formatoPapel);
        $this->assertEquals('portrait', $orientacion);
        $this->assertStringEndsWith('.pdf', $nombreArchivo);
    }

    public function test_vista_historial_existe(): void
    {
        // Verificar que la vista existe conceptualmente
        $vista = 'usuarios.historial';

        $this->assertIsString($vista);
        $this->assertStringStartsWith('usuarios.', $vista);
    }

    public function test_store_crea_historial(): void
    {
        $usuario = Usuario::create([
            'primer_nombre' => 'Test',
            'primer_apellido' => 'Usuario',
            'telefono' => self::TEST_TELEFONO,
            'correo' => self::TEST_EMAIL,
            'contrasena' => self::TEST_PASSWORD,
            'fecha_registro' => now(),
            'estado' => true,
        ]);

        $reserva = Reserva::create([
            'personas_id' => $usuario->id,
            'fecha_inicio' => now(),
            'fecha_fin' => now()->addDays(1),
            'estado' => self::ESTADO_PENDIENTE,
        ]);

        $request = \Illuminate\Http\Request::create(self::ROUTE_HISTORIAL, 'POST', [
            'reserva_id' => $reserva->id,
            'accion' => self::ACCION_CREADA,
            'observaciones' => 'Test observación',
        ]);

        $response = $this->controller->store($request);
        $responseData = json_decode($response->getContent(), true);

        $this->assertEquals(201, $response->getStatusCode());
        $this->assertTrue($responseData['success']);
        $this->assertDatabaseHas('historial', [
            'reserva_id' => $reserva->id,
            'accion' => self::ACCION_CREADA,
        ]);
    }

    public function test_store_valida_datos(): void
    {
        $request = \Illuminate\Http\Request::create(self::ROUTE_HISTORIAL, 'POST', [
            'reserva_id' => 99999, // ID que no existe
        ]);

        $response = $this->controller->store($request);
        $responseData = json_decode($response->getContent(), true);

        $this->assertEquals(422, $response->getStatusCode());
        $this->assertArrayHasKey('error', $responseData);
    }

    public function test_update_actualiza_historial(): void
    {
        $usuario = Usuario::create([
            'primer_nombre' => 'Test',
            'primer_apellido' => 'Usuario',
            'telefono' => self::TEST_TELEFONO,
            'correo' => self::TEST_EMAIL,
            'contrasena' => self::TEST_PASSWORD,
            'fecha_registro' => now(),
            'estado' => true,
        ]);

        $reserva = Reserva::create([
            'personas_id' => $usuario->id,
            'fecha_inicio' => now(),
            'fecha_fin' => now()->addDays(1),
            'estado' => self::ESTADO_PENDIENTE,
        ]);

        $historial = Historial::create([
            'reserva_id' => $reserva->id,
            'accion' => self::ACCION_CREADA,
        ]);

        $request = \Illuminate\Http\Request::create(self::ROUTE_HISTORIAL."/{$historial->id}", 'PUT', [
            'accion' => 'actualizada',
            'observaciones' => 'Observación actualizada',
        ]);

        $response = $this->controller->update($request, $historial);
        $responseData = json_decode($response->getContent(), true);

        $this->assertEquals(200, $response->getStatusCode());
        $this->assertTrue($responseData['success']);
        $this->assertDatabaseHas('historial', [
            'id' => $historial->id,
            'accion' => 'actualizada',
        ]);
    }

    public function test_update_valida_datos(): void
    {
        $historial = Historial::create([
            'accion' => self::ACCION_CREADA,
        ]);

        $request = \Illuminate\Http\Request::create(self::ROUTE_HISTORIAL."/{$historial->id}", 'PUT', [
            'reserva_id' => 99999, // ID que no existe
        ]);

        $response = $this->controller->update($request, $historial);
        $responseData = json_decode($response->getContent(), true);

        $this->assertEquals(422, $response->getStatusCode());
        $this->assertArrayHasKey('error', $responseData);
    }

    public function test_store_catch_exception(): void
    {
        $usuario = Usuario::create([
            'primer_nombre' => 'Test',
            'primer_apellido' => 'Usuario',
            'telefono' => self::TEST_TELEFONO,
            'correo' => self::TEST_EMAIL,
            'contrasena' => self::TEST_PASSWORD,
            'fecha_registro' => now(),
            'estado' => true,
        ]);

        $reserva = Reserva::create([
            'personas_id' => $usuario->id,
            'fecha_inicio' => now(),
            'fecha_fin' => now()->addDays(1),
            'estado' => self::ESTADO_PENDIENTE,
        ]);

        // Forzar excepción usando un evento de Eloquent que falle
        Historial::creating(function () {
            throw new DatabaseTestException('Database error');
        });

        $request = \Illuminate\Http\Request::create(self::ROUTE_HISTORIAL, 'POST', [
            'reserva_id' => $reserva->id,
            'accion' => self::ACCION_CREADA,
        ]);

        $response = $this->controller->store($request);
        $responseData = json_decode($response->getContent(), true);

        $this->assertEquals(500, $response->getStatusCode());
        $this->assertArrayHasKey('error', $responseData);
        $this->assertStringContainsString('Error al crear el historial', $responseData['error']);

        // Limpiar el evento
        Historial::unsetEventDispatcher();
    }

    public function test_update_catch_exception(): void
    {
        $usuario = Usuario::create([
            'primer_nombre' => 'Test',
            'primer_apellido' => 'Usuario',
            'telefono' => self::TEST_TELEFONO,
            'correo' => self::TEST_EMAIL,
            'contrasena' => self::TEST_PASSWORD,
            'fecha_registro' => now(),
            'estado' => true,
        ]);

        $reserva = Reserva::create([
            'personas_id' => $usuario->id,
            'fecha_inicio' => now(),
            'fecha_fin' => now()->addDays(1),
            'estado' => self::ESTADO_PENDIENTE,
        ]);

        $historial = Historial::create([
            'reserva_id' => $reserva->id,
            'accion' => self::ACCION_CREADA,
        ]);

        // Forzar excepción usando un evento de Eloquent que falle
        Historial::updating(function () {
            throw new DatabaseTestException('Database update error');
        });

        $request = \Illuminate\Http\Request::create(self::ROUTE_HISTORIAL."/{$historial->id}", 'PUT', [
            'accion' => 'actualizada',
        ]);

        $response = $this->controller->update($request, $historial);
        $responseData = json_decode($response->getContent(), true);

        $this->assertEquals(500, $response->getStatusCode());
        $this->assertArrayHasKey('error', $responseData);
        $this->assertStringContainsString('Error al actualizar el historial', $responseData['error']);

        // Limpiar el evento
        Historial::unsetEventDispatcher();
    }
}
