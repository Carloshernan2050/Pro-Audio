<?php

namespace Tests\Unit;

use App\Models\Calendario;
use App\Models\Reserva;
use App\Models\ReservaItem;
use App\Models\Usuario;
use App\Services\ReservaService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Request;
use Tests\TestCase;

class ReservaServiceTest extends TestCase
{
    use RefreshDatabase;

    private const TEST_PATH = '/test';
    private const FECHA_INICIO_ORIGINAL = '2024-01-01';
    private const FECHA_FIN_ORIGINAL = '2024-01-05';
    private const FECHA_INICIO_NUEVA = '2024-02-01';
    private const FECHA_FIN_NUEVA = '2024-02-05';
    private const NUEVO_SERVICIO = 'Nuevo Servicio';
    private const NUEVO_EVENTO = 'Nuevo Evento';
    private const EVENTO_ORIGINAL = 'Evento Original';
    private const EVENTO_DE_PRUEBA = 'Evento de prueba';

    private ReservaService $service;
    private Usuario $persona;

    protected function setUp(): void
    {
        parent::setUp();
        $this->service = new ReservaService();
        $this->persona = Usuario::create([
            'primer_nombre' => 'Test',
            'primer_apellido' => 'User',
            'correo' => 'test@example.com',
            'telefono' => '1234567890',
            'contrasena' => 'password123',
            'fecha_registro' => now(),
            'estado' => 1,
        ]);
    }

    public function test_actualizar_reserva_vinculada_existente(): void
    {
        $calendario = Calendario::create([
            'personas_id' => $this->persona->id,
            'fecha' => now(),
            'fecha_inicio' => self::FECHA_INICIO_ORIGINAL,
            'fecha_fin' => self::FECHA_FIN_ORIGINAL,
            'descripcion_evento' => self::EVENTO_ORIGINAL,
            'evento' => self::EVENTO_ORIGINAL,
        ]);
        
        $reserva = Reserva::create([
            'calendario_id' => $calendario->id,
            'servicio' => 'Servicio Original',
            'fecha_inicio' => self::FECHA_INICIO_ORIGINAL,
            'fecha_fin' => self::FECHA_FIN_ORIGINAL,
            'descripcion_evento' => self::EVENTO_ORIGINAL,
            'cantidad_total' => 10,
        ]);

        $inventario = \App\Models\Inventario::create([
            'descripcion' => 'Inventario de prueba',
            'stock' => 10,
        ]);

        ReservaItem::create([
            'reserva_id' => $reserva->id,
            'inventario_id' => $inventario->id,
            'cantidad' => 5,
        ]);

        $request = Request::create(self::TEST_PATH, 'POST', [
            'servicio' => self::NUEVO_SERVICIO,
            'fecha_inicio' => self::FECHA_INICIO_NUEVA,
            'fecha_fin' => self::FECHA_FIN_NUEVA,
            'descripcion_evento' => self::NUEVO_EVENTO,
        ]);

        $inventario2 = \App\Models\Inventario::create([
            'descripcion' => 'Inventario de prueba 2',
            'stock' => 10,
        ]);
        
        $inventario3 = \App\Models\Inventario::create([
            'descripcion' => 'Inventario de prueba 3',
            'stock' => 10,
        ]);

        $nuevosItems = [
            ['inventario_id' => $inventario2->id, 'cantidad' => 3],
            ['inventario_id' => $inventario3->id, 'cantidad' => 7],
        ];

        $this->service->actualizarReservaVinculada($request, $calendario->id, 10, $nuevosItems);

        $reserva->refresh();
        $this->assertEquals(self::NUEVO_SERVICIO, $reserva->servicio);
        $this->assertEquals(self::FECHA_INICIO_NUEVA, $reserva->fecha_inicio->format('Y-m-d'));
        $this->assertEquals(self::FECHA_FIN_NUEVA, $reserva->fecha_fin->format('Y-m-d'));
        $this->assertEquals(self::NUEVO_EVENTO, $reserva->descripcion_evento);
        $this->assertEquals(10, $reserva->cantidad_total);
        $this->assertArrayHasKey('actualizada_en', $reserva->meta);

        $this->assertEquals(2, $reserva->items()->count());
        $this->assertDatabaseMissing('reserva_items', [
            'reserva_id' => $reserva->id,
            'inventario_id' => 1,
        ]);
    }

    public function test_actualizar_reserva_vinculada_no_existe(): void
    {
        $calendario = Calendario::create([
            'personas_id' => $this->persona->id,
            'fecha' => now(),
            'fecha_inicio' => self::FECHA_INICIO_ORIGINAL,
            'fecha_fin' => self::FECHA_FIN_ORIGINAL,
            'evento' => self::EVENTO_DE_PRUEBA,
        ]);

        $request = Request::create(self::TEST_PATH, 'POST', [
            'servicio' => self::NUEVO_SERVICIO,
        ]);

        $this->service->actualizarReservaVinculada($request, $calendario->id, 10, []);

        $this->assertDatabaseMissing('reservas', [
            'calendario_id' => $calendario->id,
        ]);
    }

    public function test_actualizar_reserva_formato_antiguo_existente(): void
    {
        $calendario = Calendario::create([
            'personas_id' => $this->persona->id,
            'fecha' => now(),
            'fecha_inicio' => self::FECHA_INICIO_ORIGINAL,
            'fecha_fin' => self::FECHA_FIN_ORIGINAL,
            'cantidad' => 5,
            'evento' => self::EVENTO_DE_PRUEBA,
        ]);
        
        $reserva = Reserva::create([
            'calendario_id' => $calendario->id,
            'servicio' => 'Servicio Original',
            'fecha_inicio' => self::FECHA_INICIO_ORIGINAL,
            'fecha_fin' => self::FECHA_FIN_ORIGINAL,
            'cantidad_total' => 5,
        ]);

        $request = Request::create(self::TEST_PATH, 'POST', [
            'servicio' => self::NUEVO_SERVICIO,
            'fecha_inicio' => self::FECHA_INICIO_NUEVA,
            'fecha_fin' => self::FECHA_FIN_NUEVA,
            'descripcion_evento' => self::NUEVO_EVENTO,
            'cantidad' => 8,
        ]);

        $this->service->actualizarReservaFormatoAntiguo($request, $calendario->id, $calendario);

        $reserva->refresh();
        $this->assertEquals(self::NUEVO_SERVICIO, $reserva->servicio);
        $this->assertEquals(self::FECHA_INICIO_NUEVA, $reserva->fecha_inicio->format('Y-m-d'));
        $this->assertEquals(self::FECHA_FIN_NUEVA, $reserva->fecha_fin->format('Y-m-d'));
        $this->assertEquals(self::NUEVO_EVENTO, $reserva->descripcion_evento);
        $this->assertEquals(8, $reserva->cantidad_total);
        $this->assertArrayHasKey('actualizada_en', $reserva->meta);
    }

    public function test_actualizar_reserva_formato_antiguo_sin_cantidad_usar_calendario(): void
    {
        $calendario = Calendario::create([
            'personas_id' => $this->persona->id,
            'fecha' => now(),
            'fecha_inicio' => self::FECHA_INICIO_ORIGINAL,
            'fecha_fin' => self::FECHA_FIN_ORIGINAL,
            'cantidad' => 12,
            'evento' => self::EVENTO_DE_PRUEBA,
        ]);
        
        $reserva = Reserva::create([
            'calendario_id' => $calendario->id,
            'fecha_inicio' => self::FECHA_INICIO_ORIGINAL,
            'fecha_fin' => self::FECHA_FIN_ORIGINAL,
            'cantidad_total' => 5,
        ]);

        $request = Request::create(self::TEST_PATH, 'POST', [
            'servicio' => self::NUEVO_SERVICIO,
            'fecha_inicio' => self::FECHA_INICIO_NUEVA,
            'fecha_fin' => self::FECHA_FIN_NUEVA,
            'descripcion_evento' => self::NUEVO_EVENTO,
        ]);

        $this->service->actualizarReservaFormatoAntiguo($request, $calendario->id, $calendario);

        $reserva->refresh();
        $this->assertEquals(12, $reserva->cantidad_total);
    }

    public function test_actualizar_reserva_formato_antiguo_no_existe(): void
    {
        $calendario = Calendario::create([
            'personas_id' => $this->persona->id,
            'fecha' => now(),
            'fecha_inicio' => self::FECHA_INICIO_ORIGINAL,
            'fecha_fin' => self::FECHA_FIN_ORIGINAL,
            'evento' => self::EVENTO_DE_PRUEBA,
        ]);

        $request = Request::create(self::TEST_PATH, 'POST', [
            'servicio' => self::NUEVO_SERVICIO,
        ]);

        $this->service->actualizarReservaFormatoAntiguo($request, $calendario->id, $calendario);

        $this->assertDatabaseMissing('reservas', [
            'calendario_id' => $calendario->id,
        ]);
    }
}

