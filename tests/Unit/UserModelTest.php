<?php

namespace Tests\Unit;

use App\Models\User;
use PHPUnit\Framework\TestCase;

/**
 * Tests Unitarios para el Modelo User
 *
 * Tests para verificar estructura y configuración del modelo
 */
class UserModelTest extends TestCase
{
    // ============================================
    // TESTS PARA Instanciación y Configuración
    // ============================================

    public function test_user_instancia_correctamente(): void
    {
        $user = new User;

        $this->assertInstanceOf(User::class, $user);
    }

    public function test_user_tiene_guard_name(): void
    {
        $user = new User;

        $this->assertEquals('web', $user->guardName());
    }

    public function test_user_fillable_attributes(): void
    {
        $user = new User;
        $fillable = $user->getFillable();

        $this->assertContains('name', $fillable);
        $this->assertContains('email', $fillable);
        $this->assertContains('password', $fillable);
        $this->assertCount(3, $fillable);
    }

    public function test_user_hidden_attributes(): void
    {
        $user = new User;
        $hidden = $user->getHidden();

        $this->assertContains('password', $hidden);
        $this->assertContains('remember_token', $hidden);
    }

    public function test_user_usa_has_roles_trait(): void
    {
        $user = new User;

        $traits = class_uses_recursive(get_class($user));
        $this->assertContains(\Spatie\Permission\Traits\HasRoles::class, $traits);
    }

    public function test_user_usa_has_factory_trait(): void
    {
        $user = new User;

        $traits = class_uses_recursive(get_class($user));
        $this->assertContains(\Illuminate\Database\Eloquent\Factories\HasFactory::class, $traits);
    }

    public function test_user_usa_notifiable_trait(): void
    {
        $user = new User;

        $traits = class_uses_recursive(get_class($user));
        $this->assertContains(\Illuminate\Notifications\Notifiable::class, $traits);
    }

    public function test_user_extends_authenticatable(): void
    {
        $user = new User;

        $this->assertInstanceOf(\Illuminate\Foundation\Auth\User::class, $user);
    }

    public function test_user_has_casts_method(): void
    {
        $user = new User;

        // Verificar que el método casts existe usando reflexión
        $reflection = new \ReflectionClass($user);
        $method = $reflection->getMethod('casts');
        $method->setAccessible(true);

        $casts = $method->invoke($user);

        $this->assertIsArray($casts);
        $this->assertArrayHasKey('email_verified_at', $casts);
        $this->assertArrayHasKey('password', $casts);
    }
}

