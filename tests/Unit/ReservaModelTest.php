<?php

namespace Tests\Unit;

use App\Models\Reserva;
use App\Models\ReservaItem;
use App\Models\Usuario;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Tests Unitarios para el Modelo Reserva
 *
 * Tests que ejecutan el código del modelo con base de datos
 */
class ReservaModelTest extends TestCase
{
    use RefreshDatabase;

    // ============================================
    // TESTS PARA Relación persona()
    // ============================================

    public function test_reserva_tiene_relacion_persona(): void
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

        // Ejecutar la relación persona()
        $persona = $reserva->persona;

        $this->assertNotNull($persona);
        $this->assertEquals($usuario->id, $persona->id);
        $this->assertInstanceOf(Usuario::class, $persona);
    }

    public function test_reserva_relacion_persona_carga(): void
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

        // Verificar que la relación funciona
        $this->assertEquals($usuario->id, $reserva->persona->id);
    }

    // ============================================
    // TESTS PARA Relación items()
    // ============================================

    public function test_reserva_tiene_relacion_items(): void
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
            'servicio' => 'Alquiler',
            'fecha_inicio' => now(),
            'fecha_fin' => now()->addDay(),
            'descripcion_evento' => 'Evento de prueba',
            'cantidad_total' => 1,
            'estado' => 'pendiente',
        ]);

        // Verificar que items() existe y funciona
        $items = $reserva->items;

        $this->assertCount(0, $items);
    }
}

