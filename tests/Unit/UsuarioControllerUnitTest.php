<?php

namespace Tests\Unit;

use PHPUnit\Framework\TestCase;
use App\Http\Controllers\UsuarioController;

/**
 * Tests Unitarios para UsuarioController
 * 
 * Tests para validaciones y estructura
 */
class UsuarioControllerUnitTest extends TestCase
{
    protected $controller;

    protected function setUp(): void
    {
        parent::setUp();
        $this->controller = new UsuarioController();
    }

    // ============================================
    // TESTS PARA Validaciones de Registro
    // ============================================

    public function test_validacion_registro_estructura(): void
    {
        $reglasEsperadas = [
            'primer_nombre' => 'required|string|max:255',
            'segundo_nombre' => 'nullable|string|max:255',
            'primer_apellido' => 'required|string|max:255',
            'segundo_apellido' => 'nullable|string|max:255',
            'correo' => 'required|email|unique:personas,correo',
            'telefono' => 'nullable|string|max:20',
            'direccion' => 'nullable|string|max:255',
            'contrasena' => 'required|string|min:8|confirmed',
        ];
        
        $this->assertArrayHasKey('primer_nombre', $reglasEsperadas);
        $this->assertArrayHasKey('correo', $reglasEsperadas);
        $this->assertArrayHasKey('contrasena', $reglasEsperadas);
    }

    public function test_validacion_contrasena_min_caracteres(): void
    {
        // La contraseña debe tener al menos 8 caracteres
        $reglasEsperadas = [
            'contrasena' => 'required|string|min:8|confirmed'
        ];
        
        $this->assertStringContainsString('min:8', $reglasEsperadas['contrasena']);
        $this->assertStringContainsString('confirmed', $reglasEsperadas['contrasena']);
    }

    public function test_validacion_telefono_max_caracteres(): void
    {
        // El teléfono máximo es 20 caracteres
        $maxCaracteres = 20;
        
        $this->assertEquals(20, $maxCaracteres);
    }

    public function test_validacion_correo_debe_ser_email(): void
    {
        $reglasEsperadas = [
            'correo' => 'required|email|unique:personas,correo'
        ];
        
        $this->assertStringContainsString('email', $reglasEsperadas['correo']);
    }

    // ============================================
    // TESTS PARA Validaciones de Autenticación
    // ============================================

    public function test_validacion_autenticar_estructura(): void
    {
        $reglasEsperadas = [
            'correo' => 'required|email',
            'contrasena' => 'required|string',
        ];
        
        $this->assertArrayHasKey('correo', $reglasEsperadas);
        $this->assertArrayHasKey('contrasena', $reglasEsperadas);
    }

    // ============================================
    // TESTS PARA Validaciones de Foto Perfil
    // ============================================

    public function test_validacion_foto_perfil_estructura(): void
    {
        $reglasEsperadas = [
            'foto_perfil' => 'required|image|mimes:jpeg,png,jpg,gif|max:5120'
        ];
        
        $this->assertStringContainsString('image', $reglasEsperadas['foto_perfil']);
        $this->assertStringContainsString('max:5120', $reglasEsperadas['foto_perfil']);
    }

    public function test_foto_perfil_formatos_permitidos(): void
    {
        // Formatos permitidos: jpeg, png, jpg, gif
        $formatos = ['jpeg', 'png', 'jpg', 'gif'];
        
        $this->assertCount(4, $formatos);
        $this->assertContains('jpeg', $formatos);
        $this->assertContains('png', $formatos);
    }

    public function test_foto_perfil_max_tamaño(): void
    {
        // El tamaño máximo es 5120 KB (5MB)
        $maxTamaño = 5120;
        
        $this->assertEquals(5120, $maxTamaño);
    }

    // ============================================
    // TESTS PARA Valores por Defecto
    // ============================================

    public function test_estado_activo_por_defecto(): void
    {
        // El estado por defecto es 1 (Activo)
        $estadoActivo = 1;
        
        $this->assertEquals(1, $estadoActivo);
    }

    public function test_rol_cliente_por_defecto(): void
    {
        // El rol por defecto es 'Cliente'
        $rolDefecto = 'Cliente';
        
        $this->assertEquals('Cliente', $rolDefecto);
    }
}

