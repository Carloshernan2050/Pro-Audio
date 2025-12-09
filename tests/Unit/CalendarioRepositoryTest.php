<?php

namespace Tests\Unit;

use App\Models\Calendario;
use App\Models\Usuario;
use App\Repositories\CalendarioRepository;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CalendarioRepositoryTest extends TestCase
{
    use RefreshDatabase;

    private CalendarioRepository $repository;

    protected function setUp(): void
    {
        parent::setUp();
        $this->repository = app(CalendarioRepository::class);
    }

    public function test_find_encuentra_calendario(): void
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

        $encontrado = $this->repository->find($calendario->id);

        $this->assertInstanceOf(Calendario::class, $encontrado);
        $this->assertEquals($calendario->id, $encontrado->id);
    }
}

