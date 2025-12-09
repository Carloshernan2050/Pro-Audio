<?php

namespace Tests\Unit;

use App\Models\Calendario;
use App\Models\CalendarioItem;
use App\Models\Inventario;
use App\Models\MovimientosInventario;
use App\Models\Usuario;
use App\Repositories\CalendarioItemRepository;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CalendarioItemRepositoryTest extends TestCase
{
    use RefreshDatabase;

    private CalendarioItemRepository $repository;

    protected function setUp(): void
    {
        parent::setUp();
        $this->repository = app(CalendarioItemRepository::class);
    }

    public function test_create_crea_calendario_item(): void
    {
        $usuario = Usuario::create([
            'primer_nombre' => 'Test',
            'primer_apellido' => 'Usuario',
            'correo' => 'test@example.com',
            'telefono' => '1234567890',
            'contrasena' => bcrypt('password'),
            'fecha_registro' => now(),
            'estado' => true,
        ]);

        $inventario = Inventario::create([
            'descripcion' => 'Producto de prueba',
            'stock' => 10,
        ]);

        $movimiento = MovimientosInventario::create([
            'inventario_id' => $inventario->id,
            'tipo_movimiento' => 'entrada',
            'cantidad' => 10,
            'fecha_movimiento' => now(),
            'descripcion' => 'Movimiento de prueba',
        ]);

        $calendario = Calendario::create([
            'personas_id' => $usuario->id,
            'fecha' => now(),
            'fecha_inicio' => now(),
            'fecha_fin' => now()->addDay(),
            'descripcion_evento' => 'Evento de prueba',
            'evento' => 'Evento Test',
            'cantidad' => 1,
        ]);

        $data = [
            'calendario_id' => $calendario->id,
            'movimientos_inventario_id' => $movimiento->id,
            'cantidad' => 2,
        ];

        $calendarioItem = $this->repository->create($data);

        $this->assertInstanceOf(CalendarioItem::class, $calendarioItem);
        $this->assertEquals($calendario->id, $calendarioItem->calendario_id);
        $this->assertEquals($movimiento->id, $calendarioItem->movimientos_inventario_id);
        $this->assertEquals(2, $calendarioItem->cantidad);
    }

    public function test_delete_by_calendario_id_elimina_items(): void
    {
        $usuario = Usuario::create([
            'primer_nombre' => 'Test',
            'primer_apellido' => 'Usuario',
            'correo' => 'test2@example.com',
            'telefono' => '1234567891',
            'contrasena' => bcrypt('password'),
            'fecha_registro' => now(),
            'estado' => true,
        ]);

        $inventario = Inventario::create([
            'descripcion' => 'Producto de prueba',
            'stock' => 10,
        ]);

        $movimiento = MovimientosInventario::create([
            'inventario_id' => $inventario->id,
            'tipo_movimiento' => 'entrada',
            'cantidad' => 10,
            'fecha_movimiento' => now(),
            'descripcion' => 'Movimiento de prueba',
        ]);

        $calendario = Calendario::create([
            'personas_id' => $usuario->id,
            'fecha' => now(),
            'fecha_inicio' => now(),
            'fecha_fin' => now()->addDay(),
            'descripcion_evento' => 'Evento de prueba',
            'evento' => 'Evento Test',
            'cantidad' => 1,
        ]);

        // Crear varios items
        CalendarioItem::create([
            'calendario_id' => $calendario->id,
            'movimientos_inventario_id' => $movimiento->id,
            'cantidad' => 1,
        ]);

        CalendarioItem::create([
            'calendario_id' => $calendario->id,
            'movimientos_inventario_id' => $movimiento->id,
            'cantidad' => 2,
        ]);

        $result = $this->repository->deleteByCalendarioId($calendario->id);

        $this->assertTrue($result);
        $this->assertEquals(0, CalendarioItem::where('calendario_id', $calendario->id)->count());
    }

    public function test_delete_by_calendario_id_retorna_false_si_no_hay_items(): void
    {
        $usuario = Usuario::create([
            'primer_nombre' => 'Test',
            'primer_apellido' => 'Usuario',
            'correo' => 'test3@example.com',
            'telefono' => '1234567892',
            'contrasena' => bcrypt('password'),
            'fecha_registro' => now(),
            'estado' => true,
        ]);

        $calendario = Calendario::create([
            'personas_id' => $usuario->id,
            'fecha' => now(),
            'fecha_inicio' => now(),
            'fecha_fin' => now()->addDay(),
            'descripcion_evento' => 'Evento de prueba',
            'evento' => 'Evento Test',
            'cantidad' => 1,
        ]);

        // Intentar eliminar items de un calendario sin items
        $result = $this->repository->deleteByCalendarioId($calendario->id);

        $this->assertFalse($result);
    }

    public function test_get_by_calendario_id_retorna_items(): void
    {
        $usuario = Usuario::create([
            'primer_nombre' => 'Test',
            'primer_apellido' => 'Usuario',
            'correo' => 'test4@example.com',
            'telefono' => '1234567893',
            'contrasena' => bcrypt('password'),
            'fecha_registro' => now(),
            'estado' => true,
        ]);

        $inventario = Inventario::create([
            'descripcion' => 'Producto de prueba',
            'stock' => 10,
        ]);

        $movimiento = MovimientosInventario::create([
            'inventario_id' => $inventario->id,
            'tipo_movimiento' => 'entrada',
            'cantidad' => 10,
            'fecha_movimiento' => now(),
            'descripcion' => 'Movimiento de prueba',
        ]);

        $calendario = Calendario::create([
            'personas_id' => $usuario->id,
            'fecha' => now(),
            'fecha_inicio' => now(),
            'fecha_fin' => now()->addDay(),
            'descripcion_evento' => 'Evento de prueba',
            'evento' => 'Evento Test',
            'cantidad' => 1,
        ]);

        // Crear varios items
        $item1 = CalendarioItem::create([
            'calendario_id' => $calendario->id,
            'movimientos_inventario_id' => $movimiento->id,
            'cantidad' => 1,
        ]);

        $item2 = CalendarioItem::create([
            'calendario_id' => $calendario->id,
            'movimientos_inventario_id' => $movimiento->id,
            'cantidad' => 2,
        ]);

        $items = $this->repository->getByCalendarioId($calendario->id);

        $this->assertCount(2, $items);
        $this->assertTrue($items->contains('id', $item1->id));
        $this->assertTrue($items->contains('id', $item2->id));
    }

    public function test_get_by_calendario_id_retorna_vacio_si_no_hay_items(): void
    {
        $usuario = Usuario::create([
            'primer_nombre' => 'Test',
            'primer_apellido' => 'Usuario',
            'correo' => 'test5@example.com',
            'telefono' => '1234567894',
            'contrasena' => bcrypt('password'),
            'fecha_registro' => now(),
            'estado' => true,
        ]);

        $calendario = Calendario::create([
            'personas_id' => $usuario->id,
            'fecha' => now(),
            'fecha_inicio' => now(),
            'fecha_fin' => now()->addDay(),
            'descripcion_evento' => 'Evento de prueba',
            'evento' => 'Evento Test',
            'cantidad' => 1,
        ]);

        $items = $this->repository->getByCalendarioId($calendario->id);

        $this->assertCount(0, $items);
    }
}

