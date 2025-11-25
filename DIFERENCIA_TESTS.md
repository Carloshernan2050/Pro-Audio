# Diferencia: Tests Unitarios vs Tests de Integración

## ❌ Lo que creé (Tests de Integración)

Los tests actuales en `tests/Unit/ChatbotControllerTest.php` son **tests de integración** porque:

1. ✅ Usan `RefreshDatabase` - interactúan con la base de datos real
2. ✅ Instancian el controlador completo con todas sus dependencias
3. ✅ Usan sesiones reales de Laravel
4. ✅ Dependen de modelos Eloquent (SubServicios, Cotizacion, etc.)
5. ✅ Prueban el flujo completo desde el request hasta la respuesta
6. ✅ Son más lentos (necesitan setup de BD)

**Ejemplo actual:**
```php
use RefreshDatabase; // ❌ Esto lo hace test de integración

public function test_enviar_con_seleccion() {
    // Crea datos reales en BD
    $servicio = Servicios::create([...]);
    $subServicio = SubServicios::create([...]);
    
    // Usa controlador real con dependencias reales
    $response = $this->controller->enviar($request);
}
```

---

## ✅ Tests Unitarios Verdaderos

Los tests unitarios deberían:

1. ❌ **NO usar base de datos** - todo se mockea
2. ❌ **NO usar Laravel TestCase** - usar `PHPUnit\Framework\TestCase`
3. ✅ **Aislar completamente** la unidad de código
4. ✅ **Mockear todas las dependencias** externas
5. ✅ **Ser muy rápidos** (sin setup de BD)
6. ✅ **Probar lógica pura** sin framework

**Ejemplo de test unitario verdadero:**
```php
use PHPUnit\Framework\TestCase; // ✅ Sin Laravel

class ChatbotControllerUnitTest extends TestCase
{
    public function test_normalizar_texto() {
        $controller = new ChatbotController();
        $method = $this->getPrivateMethod('normalizarTexto');
        
        // Solo prueba la lógica, sin BD, sin sesiones
        $result = $method->invoke($controller, 'HOLA');
        $this->assertEquals('hola', $result);
    }
}
```

---

## 📊 Comparación

| Característica | Tests Actuales (Integración) | Tests Unitarios Verdaderos |
|----------------|------------------------------|----------------------------|
| Base de datos | ✅ Usa RefreshDatabase | ❌ Todo mockeado |
| Framework | ✅ Usa Laravel TestCase | ❌ PHPUnit puro |
| Velocidad | ⚠️ Más lentos | ✅ Muy rápidos |
| Aislamiento | ⚠️ Dependen de BD/Modelos | ✅ Totalmente aislados |
| Propósito | ✅ Prueban flujo completo | ✅ Prueban lógica individual |

---

## 🎯 ¿Qué quieres?

### Opción 1: Tests Unitarios Verdaderos
- Probar métodos privados aislados (normalizarTexto, detectarIntenciones, etc.)
- Sin base de datos, todo mockeado
- Muy rápidos
- Usar `PHPUnit\Framework\TestCase`

### Opción 2: Mantener como Tests de Integración
- Ya funcionan bien
- Prueban el sistema completo
- Solo mover a `tests/Feature/` donde corresponde

### Opción 3: Ambos
- Tests unitarios para lógica pura
- Tests de integración para flujos completos

---

## 💡 Recomendación

Los tests actuales están bien, pero deberían estar en:
- ❌ `tests/Unit/` (ubicación actual) 
- ✅ `tests/Feature/` (ubicación correcta para tests de integración)

O crear verdaderos tests unitarios para métodos que no dependen de BD.

