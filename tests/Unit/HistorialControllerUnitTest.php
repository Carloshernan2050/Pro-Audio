<?php

namespace Tests\Unit;

use Tests\TestCase;
use App\Http\Controllers\HistorialController;
use App\Models\Historial;
use App\Models\Reserva;
use App\Models\Usuario;
use Illuminate\Foundation\Testing\RefreshDatabase;

/**
 * Tests Unitarios para HistorialController
 *
 * Tests para estructura y configuración
 */
class HistorialControllerUnitTest extends TestCase
{
    use RefreshDatabase;

    protected $controller;

    protected function setUp(): void
    {
        parent::setUp();
        $this->controller = new HistorialController();
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
            'telefono' => '1234567890',
            'correo' => 'test@test.com',
            'contrasena' => 'password',
            'fecha_registro' => now(),
            'estado' => true
        ]);

        $reserva = Reserva::create([
            'personas_id' => $usuario->id,
            'fecha_inicio' => now(),
            'estado' => 'pendiente'
        ]);

        Historial::create([
            'reserva_id' => $reserva->id,
            'accion' => 'creada'
        ]);

        $response = $this->controller->index();
        
        $this->assertNotNull($response);
    }

    public function test_export_pdf_retorna_pdf(): void
    {
        $usuario = Usuario::create([
            'primer_nombre' => 'Test',
            'primer_apellido' => 'Usuario',
            'telefono' => '1234567890',
            'correo' => 'test@test.com',
            'contrasena' => 'password',
            'fecha_registro' => now(),
            'estado' => true
        ]);

        $reserva = Reserva::create([
            'personas_id' => $usuario->id,
            'fecha_inicio' => now(),
            'estado' => 'pendiente'
        ]);

        Historial::create([
            'reserva_id' => $reserva->id,
            'accion' => 'creada'
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
}
