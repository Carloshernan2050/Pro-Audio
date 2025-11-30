<?php

namespace Tests\Feature;

use Tests\TestCase;
use App\Models\Historial;
use App\Models\Reserva;
use App\Models\Usuario;
use App\Models\Calendario;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Vite;

/**
 * Tests de Integración para HistorialController
 *
 * Prueban los flujos completos de visualización y exportación de historial
 */
class HistorialTest extends TestCase
{
    use RefreshDatabase;

    private const TEST_EMAIL = 'test@example.com';
    private const TEST_PASSWORD = 'password123';
    private const TEST_NOMBRE = 'Juan';
    private const TEST_APELLIDO = 'Pérez';
    private const TEST_TELEFONO = '1234567890';
    private const TEST_EVENTO = 'Test evento';
    private const ROUTE_HISTORIAL = '/historial';
    private const ROUTE_HISTORIAL_PDF = '/historial/pdf';

    protected function setUp(): void
    {
        parent::setUp();

        // Mock Vite para evitar errores de manifest
        Vite::shouldReceive('__invoke')
            ->zeroOrMoreTimes()
            ->andReturn('<link rel="stylesheet" href="/build/assets/app.css">');
        Vite::shouldReceive('asset')
            ->zeroOrMoreTimes()
            ->andReturn('/build/assets/app.css');

        // Crear rol Cliente si no existe (usando valor válido del ENUM)
        if (!DB::table('roles')->where('nombre_rol', 'Cliente')->exists()) {
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

        $rolId = DB::table('roles')->where('nombre_rol', 'Cliente')->value('id');
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
    // TESTS PARA LISTAR HISTORIAL
    // ============================================

    public function test_index_lista_historial(): void
    {
        $usuario = $this->crearUsuarioAutenticado();

        $calendario = Calendario::create([
            'personas_id' => $usuario->id,
            'fecha' => now(),
            'descripcion_evento' => self::TEST_EVENTO,
            'fecha_inicio' => now(),
            'fecha_fin' => now()->addDays(1),
            'evento' => 'test',
            'cantidad' => 1
        ]);

        $reserva = Reserva::create([
            'personas_id' => $usuario->id,
            'fecha_reserva' => now(),
            'fecha_inicio' => now(),
            'fecha_fin' => now()->addDays(1),
            'estado' => 'pendiente'
        ]);

        Historial::create([
            'calendario_id' => $calendario->id,
            'reserva_id' => $reserva->id,
            'accion' => 'creada',
            'fecha' => now()
        ]);

        $response = $this->get(self::ROUTE_HISTORIAL);

        $response->assertStatus(200);
        $response->assertViewIs('usuarios.historial');
        $response->assertViewHas('items');
    }

    public function test_index_sin_historial(): void
    {
        $this->crearUsuarioAutenticado();

        $response = $this->get(self::ROUTE_HISTORIAL);

        $response->assertStatus(200);
        $response->assertViewIs('usuarios.historial');
    }

    public function test_index_carga_relacion_reserva(): void
    {
        $usuario = $this->crearUsuarioAutenticado();

        $calendario = Calendario::create([
            'personas_id' => $usuario->id,
            'fecha' => now(),
            'descripcion_evento' => self::TEST_EVENTO,
            'fecha_inicio' => now(),
            'fecha_fin' => now()->addDays(1),
            'evento' => 'test',
            'cantidad' => 1
        ]);

        $reserva = Reserva::create([
            'personas_id' => $usuario->id,
            'fecha_reserva' => now(),
            'fecha_inicio' => now(),
            'fecha_fin' => now()->addDays(1),
            'estado' => 'pendiente'
        ]);

        Historial::create([
            'calendario_id' => $calendario->id,
            'reserva_id' => $reserva->id,
            'accion' => 'creada',
            'fecha' => now()
        ]);

        $response = $this->get(self::ROUTE_HISTORIAL);

        $response->assertStatus(200);
        $items = $response->viewData('items');
        $this->assertNotNull($items);
        if ($items->count() > 0) {
            $this->assertNotNull($items->first()->reserva);
        }
    }

    // ============================================
    // TESTS PARA EXPORTAR PDF
    // ============================================

    public function test_export_pdf_genera_descarga(): void
    {
        $usuario = $this->crearUsuarioAutenticado();

        $calendario = Calendario::create([
            'personas_id' => $usuario->id,
            'fecha' => now(),
            'descripcion_evento' => self::TEST_EVENTO,
            'fecha_inicio' => now(),
            'fecha_fin' => now()->addDays(1),
            'evento' => 'test',
            'cantidad' => 1
        ]);

        $reserva = Reserva::create([
            'personas_id' => $usuario->id,
            'fecha_reserva' => now(),
            'fecha_inicio' => now(),
            'fecha_fin' => now()->addDays(1),
            'estado' => 'pendiente'
        ]);

        Historial::create([
            'calendario_id' => $calendario->id,
            'reserva_id' => $reserva->id,
            'accion' => 'creada',
            'fecha' => now()
        ]);

        $response = $this->get(self::ROUTE_HISTORIAL_PDF);

        $response->assertStatus(200);
        $response->assertHeader('Content-Type', 'application/pdf');
        $response->assertHeader('Content-Disposition', 'attachment; filename=historial.pdf');
    }

    public function test_export_pdf_sin_historial(): void
    {
        $this->crearUsuarioAutenticado();

        $response = $this->get(self::ROUTE_HISTORIAL_PDF);

        $response->assertStatus(200);
        $response->assertHeader('Content-Type', 'application/pdf');
    }

    // ============================================
    // TESTS PARA PERMISOS
    // ============================================

    public function test_index_requiere_autenticacion(): void
    {
        $response = $this->get('/historial');

        // Debería redirigir o denegar acceso
        $this->assertTrue($response->isRedirect() || $response->status() === 403);
    }

    public function test_export_pdf_requiere_autenticacion(): void
    {
        $response = $this->get(self::ROUTE_HISTORIAL_PDF);

        // Debería redirigir o denegar acceso
        $this->assertTrue($response->isRedirect() || $response->status() === 403);
    }
}

