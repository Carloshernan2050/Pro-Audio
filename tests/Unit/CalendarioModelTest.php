<?php

namespace Tests\Unit;

use App\Models\Calendario;
use App\Models\Usuario;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Tests Unitarios para el Modelo Calendario
 *
 * Tests que ejecutan el código del modelo con base de datos
 */
class CalendarioModelTest extends TestCase
{
    use RefreshDatabase;

    // ============================================
    // TESTS PARA Relación usuario()
    // ============================================

    public function test_calendario_tiene_relacion_usuario(): void
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

        $calendario = Calendario::create([
            'personas_id' => $usuario->id,
            'fecha' => now(),
            'fecha_inicio' => now(),
            'fecha_fin' => now()->addDay(),
            'descripcion_evento' => 'Evento de prueba',
            'evento' => 'Evento Test',
            'cantidad' => 1,
        ]);

        // Ejecutar la relación usuario()
        $usuarioRelacionado = $calendario->usuario;

        $this->assertNotNull($usuarioRelacionado);
        $this->assertEquals($usuario->id, $usuarioRelacionado->id);
        $this->assertInstanceOf(Usuario::class, $usuarioRelacionado);
    }

    public function test_calendario_relacion_usuario_carga(): void
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

        $calendario = Calendario::create([
            'personas_id' => $usuario->id,
            'fecha' => now(),
            'fecha_inicio' => now(),
            'fecha_fin' => now()->addDay(),
            'descripcion_evento' => 'Evento de prueba',
            'evento' => 'Evento Test',
            'cantidad' => 1,
        ]);

        // Verificar que la relación funciona
        $this->assertEquals($usuario->id, $calendario->usuario->id);
    }
}

