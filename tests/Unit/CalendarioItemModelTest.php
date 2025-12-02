<?php

namespace Tests\Unit;

use App\Models\Calendario;
use App\Models\CalendarioItem;
use App\Models\Inventario;
use App\Models\MovimientosInventario;
use App\Models\Usuario;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Tests Unitarios para el Modelo CalendarioItem
 *
 * Tests que ejecutan el código del modelo con base de datos
 */
class CalendarioItemModelTest extends TestCase
{
    use RefreshDatabase;

    // ============================================
    // TESTS PARA Relación calendario()
    // ============================================

    public function test_calendario_item_tiene_relacion_calendario(): void
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

        $calendarioItem = CalendarioItem::create([
            'calendario_id' => $calendario->id,
            'movimientos_inventario_id' => $movimiento->id,
            'cantidad' => 2,
        ]);

        // Ejecutar la relación calendario()
        $calendarioRelacionado = $calendarioItem->calendario;

        $this->assertNotNull($calendarioRelacionado);
        $this->assertEquals($calendario->id, $calendarioRelacionado->id);
        $this->assertInstanceOf(Calendario::class, $calendarioRelacionado);
    }

    public function test_calendario_item_relacion_calendario_carga(): void
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
            'descripcion' => 'Producto de prueba 2',
            'stock' => 10,
        ]);

        $movimiento = MovimientosInventario::create([
            'inventario_id' => $inventario->id,
            'tipo_movimiento' => 'entrada',
            'cantidad' => 10,
            'fecha_movimiento' => now(),
            'descripcion' => 'Movimiento de prueba 2',
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

        $calendarioItem = CalendarioItem::create([
            'calendario_id' => $calendario->id,
            'movimientos_inventario_id' => $movimiento->id,
            'cantidad' => 3,
        ]);

        // Verificar que la relación funciona
        $this->assertEquals($calendario->id, $calendarioItem->calendario->id);
    }
}

