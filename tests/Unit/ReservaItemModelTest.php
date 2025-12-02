<?php

namespace Tests\Unit;

use App\Models\Inventario;
use App\Models\Reserva;
use App\Models\ReservaItem;
use App\Models\Usuario;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Tests Unitarios para el Modelo ReservaItem
 *
 * Tests que ejecutan el código del modelo con base de datos
 */
class ReservaItemModelTest extends TestCase
{
    use RefreshDatabase;

    // ============================================
    // TESTS PARA Relación reserva()
    // ============================================

    public function test_reserva_item_tiene_relacion_reserva(): void
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

        $reserva = Reserva::create([
            'personas_id' => $usuario->id,
            'servicio' => 'Alquiler',
            'fecha_inicio' => now(),
            'fecha_fin' => now()->addDay(),
            'descripcion_evento' => 'Evento de prueba',
            'cantidad_total' => 1,
            'estado' => 'pendiente',
        ]);

        $inventario = Inventario::create([
            'descripcion' => 'Producto de prueba',
            'stock' => 10,
        ]);

        $reservaItem = ReservaItem::create([
            'reserva_id' => $reserva->id,
            'inventario_id' => $inventario->id,
            'cantidad' => 2,
        ]);

        // Ejecutar la relación reserva()
        $reservaRelacionada = $reservaItem->reserva;

        $this->assertNotNull($reservaRelacionada);
        $this->assertEquals($reserva->id, $reservaRelacionada->id);
        $this->assertInstanceOf(Reserva::class, $reservaRelacionada);
    }

    public function test_reserva_item_relacion_reserva_carga(): void
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

        $reserva = Reserva::create([
            'personas_id' => $usuario->id,
            'servicio' => 'Alquiler',
            'fecha_inicio' => now(),
            'fecha_fin' => now()->addDay(),
            'descripcion_evento' => 'Evento de prueba',
            'cantidad_total' => 1,
            'estado' => 'pendiente',
        ]);

        $inventario = Inventario::create([
            'descripcion' => 'Producto de prueba',
            'stock' => 10,
        ]);

        $reservaItem = ReservaItem::create([
            'reserva_id' => $reserva->id,
            'inventario_id' => $inventario->id,
            'cantidad' => 3,
        ]);

        // Verificar que la relación funciona
        $this->assertEquals($reserva->id, $reservaItem->reserva->id);
    }
}

