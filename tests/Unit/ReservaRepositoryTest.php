<?php

namespace Tests\Unit;

use App\Models\Reserva;
use App\Models\Usuario;
use App\Repositories\ReservaRepository;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ReservaRepositoryTest extends TestCase
{
    use RefreshDatabase;

    private ReservaRepository $repository;

    protected function setUp(): void
    {
        parent::setUp();
        $this->repository = app(ReservaRepository::class);
    }

    public function test_find_encuentra_reserva(): void
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
            'fecha_inicio' => now(),
            'fecha_fin' => now()->addDay(),
            'evento' => 'Evento Test',
            'cantidad' => 1,
        ]);

        $encontrado = $this->repository->find($reserva->id);

        $this->assertInstanceOf(Reserva::class, $encontrado);
        $this->assertEquals($reserva->id, $encontrado->id);
    }
}

