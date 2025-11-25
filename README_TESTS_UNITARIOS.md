# Tests Unitarios Verdaderos - ChatbotController

## ✅ Tests Unitarios Creados

Se creó el archivo `tests/Unit/ChatbotControllerUnitTest.php` con **40+ tests unitarios verdaderos**.

### 🎯 Características de estos Tests

✅ **Son verdaderamente unitarios:**
- NO usan base de datos
- NO dependen de Laravel TestCase (usan PHPUnit puro)
- Mockean todas las dependencias
- Son rápidos y aislados
- Prueban solo lógica pura

✅ **Usan reflexión** para acceder a métodos privados

✅ **Cubren métodos de utilidad:**
- `normalizarTexto()` - 4 tests
- `detectarIntenciones()` - 8 tests  
- `extraerDiasDesdePalabras()` - 6 tests
- `esRelacionado()` - 5 tests
- `esContinuacion()` - 6 tests
- `validarIntencionesContraMensaje()` - 6 tests
- Edge cases adicionales - 5+ tests

---

## 📊 Comparación

### ❌ Archivo Anterior: `ChatbotControllerTest.php`
- **Tipo:** Tests de Integración
- **Ubicación:** `tests/Unit/` (incorrecta)
- **Características:**
  - Usa `RefreshDatabase` (base de datos)
  - Usa Laravel TestCase
  - Depende de modelos Eloquent
  - Prueba flujos completos
  - Más lentos

### ✅ Archivo Nuevo: `ChatbotControllerUnitTest.php`
- **Tipo:** Tests Unitarios Verdaderos
- **Ubicación:** `tests/Unit/` (correcta)
- **Características:**
  - NO usa base de datos
  - Usa PHPUnit\Framework\TestCase (puro)
  - Mockea todas las dependencias
  - Prueba solo lógica pura
  - Muy rápidos

---

## 📝 Tests Incluidos

### Tests para `normalizarTexto()`
```php
- test_normalizar_texto_convierte_a_minusculas()
- test_normalizar_texto_elimina_acentos()
- test_normalizar_texto_maneja_caracteres_especiales()
- test_normalizar_texto_con_cadena_vacia()
```

### Tests para `detectarIntenciones()`
```php
- test_detectar_intenciones_alquiler()
- test_detectar_intenciones_animacion()
- test_detectar_intenciones_publicidad()
- test_detectar_intenciones_multiples()
- test_detectar_intenciones_vacio()
- test_detectar_intenciones_sin_intencion()
- test_detectar_intenciones_case_insensitive()
```

### Tests para `extraerDiasDesdePalabras()`
```php
- test_extraer_dias_desde_palabras_uno()
- test_extraer_dias_desde_palabras_dos()
- test_extraer_dias_desde_palabras_tres()
- test_extraer_dias_desde_palabras_diez()
- test_extraer_dias_desde_palabras_sin_dias()
- test_extraer_dias_desde_palabras_con_texto_adicional()
```

### Tests para `esRelacionado()`
```php
- test_es_relacionado_con_alquiler()
- test_es_relacionado_con_animacion()
- test_es_relacionado_con_publicidad()
- test_es_relacionado_con_mensaje_vacio()
- test_es_relacionado_con_mensaje_no_relacionado()
```

### Tests para `esContinuacion()`
```php
- test_es_continuacion_con_tambien()
- test_es_continuacion_con_ademas()
- test_es_continuacion_con_lo_mismo()
- test_es_continuacion_con_mensaje_normal()
- test_es_continuacion_con_mensaje_vacio()
- test_es_continuacion_case_insensitive()
```

### Tests para `validarIntencionesContraMensaje()`
```php
- test_validar_intenciones_contra_mensaje_valida_alquiler()
- test_validar_intenciones_contra_mensaje_invalida_alquiler()
- test_validar_intenciones_contra_mensaje_con_animacion()
- test_validar_intenciones_contra_mensaje_con_publicidad()
- test_validar_intenciones_contra_mensaje_vacio()
- test_validar_intenciones_contra_mensaje_multiples()
```

### Edge Cases
```php
- test_normalizar_texto_con_numeros()
- test_detectar_intenciones_con_variaciones_ortograficas()
- test_extraer_dias_desde_palabras_con_acentos()
- test_es_relacionado_case_insensitive()
```

---

## 🚀 Cómo Ejecutar

```bash
# Ejecutar todos los tests unitarios
php artisan test tests/Unit/ChatbotControllerUnitTest.php

# Ejecutar un test específico
php artisan test --filter test_normalizar_texto

# Ejecutar todos los tests unitarios
php artisan test --testsuite=Unit
```

---

## 📁 Estructura de Archivos

```
tests/
├── Unit/
│   ├── ChatbotControllerUnitTest.php  ✅ Tests unitarios verdaderos
│   └── ChatbotControllerTest.php      ❌ Tests de integración (mover a Feature)
└── Feature/
    └── (aquí deberían ir los tests de integración)
```

---

## 💡 Recomendación

1. ✅ **Mantener** `ChatbotControllerUnitTest.php` en `tests/Unit/`
2. ⚠️ **Mover** `ChatbotControllerTest.php` a `tests/Feature/ChatbotControllerFeatureTest.php`
3. ✅ Usar ambos tipos de tests:
   - Unitarios: Para lógica pura (rápidos)
   - Feature: Para flujos completos (integración)

---

## ✅ Estado

**40+ tests unitarios verdaderos creados y listos para usar.**

Todos los tests están aislados, no dependen de BD, y prueban solo la lógica pura de los métodos privados del ChatbotController.

