<?php

namespace Tests\Unit;

use App\Models\Inventario;
use App\Models\Reserva;
use App\Models\ReservaItem;
use App\Models\Usuario;
use App\Repositories\ReservaItemRepository;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ReservaItemRepositoryTest extends TestCase
{
    use RefreshDatabase;

    private ReservaItemRepository $repository;

    protected function setUp(): void
    {
        parent::setUp();
        $this->repository = app(ReservaItemRepository::class);
    }

    public function test_create_crea_reserva_item(): void
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

        $reserva = Reserva::create([
            'personas_id' => $usuario->id,
            'fecha_inicio' => now(),
            'fecha_fin' => now()->addDay(),
            'evento' => 'Evento Test',
            'cantidad' => 1,
        ]);

        $data = [
            'reserva_id' => $reserva->id,
            'inventario_id' => $inventario->id,
            'cantidad' => 2,
        ];

        $reservaItem = $this->repository->create($data);

        $this->assertInstanceOf(ReservaItem::class, $reservaItem);
        $this->assertEquals($reserva->id, $reservaItem->reserva_id);
        $this->assertEquals($inventario->id, $reservaItem->inventario_id);
        $this->assertEquals(2, $reservaItem->cantidad);
    }

    public function test_delete_by_reserva_id_elimina_items(): void
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

        $reserva = Reserva::create([
            'personas_id' => $usuario->id,
            'fecha_inicio' => now(),
            'fecha_fin' => now()->addDay(),
            'evento' => 'Evento Test',
            'cantidad' => 1,
        ]);

        // Crear varios items
        ReservaItem::create([
            'reserva_id' => $reserva->id,
            'inventario_id' => $inventario->id,
            'cantidad' => 1,
        ]);

        ReservaItem::create([
            'reserva_id' => $reserva->id,
            'inventario_id' => $inventario->id,
            'cantidad' => 2,
        ]);

        $result = $this->repository->deleteByReservaId($reserva->id);

        $this->assertTrue($result);
        $this->assertEquals(0, ReservaItem::where('reserva_id', $reserva->id)->count());
    }

    public function test_delete_by_reserva_id_retorna_false_si_no_hay_items(): void
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

        $reserva = Reserva::create([
            'personas_id' => $usuario->id,
            'fecha_inicio' => now(),
            'fecha_fin' => now()->addDay(),
            'evento' => 'Evento Test',
            'cantidad' => 1,
        ]);

        // Intentar eliminar items de una reserva sin items
        $result = $this->repository->deleteByReservaId($reserva->id);

        $this->assertFalse($result);
    }

    public function test_get_by_reserva_id_retorna_items(): void
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

        $reserva = Reserva::create([
            'personas_id' => $usuario->id,
            'fecha_inicio' => now(),
            'fecha_fin' => now()->addDay(),
            'evento' => 'Evento Test',
            'cantidad' => 1,
        ]);

        // Crear varios items
        $item1 = ReservaItem::create([
            'reserva_id' => $reserva->id,
            'inventario_id' => $inventario->id,
            'cantidad' => 1,
        ]);

        $item2 = ReservaItem::create([
            'reserva_id' => $reserva->id,
            'inventario_id' => $inventario->id,
            'cantidad' => 2,
        ]);

        $items = $this->repository->getByReservaId($reserva->id);

        $this->assertCount(2, $items);
        $this->assertTrue($items->contains('id', $item1->id));
        $this->assertTrue($items->contains('id', $item2->id));
    }

    public function test_get_by_reserva_id_retorna_vacio_si_no_hay_items(): void
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

        $reserva = Reserva::create([
            'personas_id' => $usuario->id,
            'fecha_inicio' => now(),
            'fecha_fin' => now()->addDay(),
            'evento' => 'Evento Test',
            'cantidad' => 1,
        ]);

        $items = $this->repository->getByReservaId($reserva->id);

        $this->assertCount(0, $items);
    }
}

