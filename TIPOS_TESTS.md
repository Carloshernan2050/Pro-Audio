# Tipos de Tests - ChatbotController

## 📊 Estado Actual

Actualmente tienes **2 archivos de tests**:

### 1. ✅ `ChatbotControllerUnitTest.php` (Tests Unitarios Verdaderos)
- **38 tests** unitarios puros
- NO usan base de datos
- NO dependen de Laravel
- Prueban solo lógica pura
- **Ubicación correcta:** `tests/Unit/`

### 2. ⚠️ `ChatbotControllerTest.php` (Tests de Integración)
- **34 tests** de integración
- SÍ usan base de datos (RefreshDatabase)
- Dependen de Laravel
- Prueban flujos completos
- **Ubicación incorrecta:** `tests/Unit/` (debería estar en `tests/Feature/`)

---

## 🎯 Tipos de Tests Recomendados

### 1. **Tests Unitarios** ✅ (Ya tienes)
- **Qué prueban:** Lógica pura, métodos individuales
- **Características:**
  - Sin base de datos
  - Sin dependencias externas
  - Muy rápidos
  - Aislados

**Ejemplo:** `normalizarTexto()`, `detectarIntenciones()`

---

### 2. **Tests de Integración** ⚠️ (Tienes pero mal ubicados)
- **Qué prueban:** Interacción entre componentes (Controller + BD + Sesiones)
- **Características:**
  - Usan base de datos
  - Prueban flujos completos
  - Más lentos
  - Necesitan setup de BD

**Ejemplo:** Probar el método `enviar()` completo con selección de servicios

---

### 3. **Tests Feature/E2E** ❌ (No tienes aún)
- **Qué prueban:** Funcionalidad completa desde la petición HTTP hasta la respuesta
- **Características:**
  - Hacen requests HTTP reales
  - Prueban toda la aplicación
  - Muy lentos
  - Necesitan toda la app funcionando

**Ejemplo:** `POST /chat/enviar` con mensaje completo

---

## ✅ Recomendación

### Opción 1: Solo Tests Unitarios (Actual)
- ✅ Ya tienes tests unitarios
- ✅ Funcionan perfectamente
- ❌ No cubren flujos completos
- ❌ No prueban integración con BD

### Opción 2: Tests Unitarios + Tests de Integración (Recomendado)
- ✅ Tests unitarios para lógica pura (rápidos)
- ✅ Tests de integración para flujos completos
- ✅ Cobertura completa
- ⚠️ Requiere mover el archivo actual

### Opción 3: Todos los tipos (Ideal para proyectos grandes)
- ✅ Tests unitarios
- ✅ Tests de integración  
- ✅ Tests feature/E2E
- ✅ Máxima cobertura

---

## 📁 Estructura Recomendada

```
tests/
├── Unit/
│   └── ChatbotControllerUnitTest.php      ✅ Tests unitarios (lógica pura)
│
└── Feature/
    ├── ChatbotControllerFeatureTest.php   ✅ Tests de integración (BD)
    └── ChatbotEnviarTest.php              ✅ Tests E2E (HTTP completo)
```

---

## 💡 ¿Qué quieres hacer?

1. **Solo tests unitarios** (actual) - Ya está ✅
2. **Tests unitarios + integración** - Mover archivo actual a Feature/
3. **Todos los tipos** - Crear también tests feature/E2E

¿Qué prefieres?

