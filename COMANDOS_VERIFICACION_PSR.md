# Comandos para Verificar Estándares PSR

## 📋 Verificación de Estilo de Código (PSR-1 y PSR-2)

### Verificar sin corregir (solo reporte)
```powershell
./vendor/bin/pint --test
```

Este comando:
- ✅ Verifica todos los archivos PHP
- ✅ Muestra qué problemas encontró
- ✅ **NO modifica** los archivos
- ✅ Retorna código de salida 0 si todo está bien, 1 si hay problemas

### Corregir automáticamente
```powershell
./vendor/bin/pint
```

Este comando:
- ✅ Verifica todos los archivos PHP
- ✅ **Corrige automáticamente** los problemas encontrados
- ✅ Muestra qué archivos fueron modificados

### Verificar archivos específicos
```powershell
# Verificar solo un archivo
./vendor/bin/pint --test app/Http/Controllers/UsuarioController.php

# Verificar solo un directorio
./vendor/bin/pint --test app/Models/

# Verificar múltiples archivos
./vendor/bin/pint --test app/Http/Controllers/*.php
```

---

## 🔍 Verificación de PSR-4 (Autoloading)

### Verificar configuración de Composer
```powershell
composer validate
```

### Verificar que el autoloader esté actualizado
```powershell
composer dump-autoload
```

### Verificar namespaces manualmente
```powershell
# Verificar que los namespaces coincidan con la estructura
# App\Models\User debe estar en app/Models/User.php
# App\Http\Controllers\UsuarioController debe estar en app/Http/Controllers/UsuarioController.php
```

---

## 🧪 Verificación Completa (Recomendado)

### Script para verificar todo
```powershell
# 1. Verificar PSR-4 (autoloading)
composer validate
composer dump-autoload

# 2. Verificar PSR-1/PSR-2 (estilo de código)
./vendor/bin/pint --test

# 3. Si hay problemas, corregirlos
./vendor/bin/pint
```

---

## 📝 Agregar Scripts a composer.json (Opcional)

Puedes agregar estos scripts a tu `composer.json` para facilitar el uso:

```json
{
    "scripts": {
        "psr:check": "./vendor/bin/pint --test",
        "psr:fix": "./vendor/bin/pint",
        "validate:all": [
            "composer validate",
            "@psr:check"
        ]
    }
}
```

Luego usar:
```powershell
# Verificar estilo
composer psr:check

# Corregir estilo
composer psr:fix

# Verificar todo
composer validate:all
```

---

## 🔄 Integración con Git (Pre-commit Hook)

Para verificar automáticamente antes de cada commit, puedes crear un hook:

**`.git/hooks/pre-commit`** (en Windows, crear como `.git/hooks/pre-commit.bat`):
```powershell
#!/bin/sh
./vendor/bin/pint --test
if [ $? -ne 0 ]; then
    echo "❌ Errores de estilo PSR encontrados. Ejecuta: ./vendor/bin/pint"
    exit 1
fi
```

---

## 📊 Resumen de Comandos

| Acción | Comando |
|--------|---------|
| **Verificar estilo** (sin cambios) | `./vendor/bin/pint --test` |
| **Corregir estilo** | `./vendor/bin/pint` |
| **Verificar PSR-4** | `composer validate` |
| **Actualizar autoloader** | `composer dump-autoload` |
| **Verificar todo** | `composer validate && ./vendor/bin/pint --test` |

---

## ⚠️ Notas Importantes

1. **Laravel Pint** es la herramienta oficial de Laravel para verificar PSR-1 y PSR-2
2. **PSR-4** se verifica automáticamente con `composer dump-autoload`
3. Los problemas de estilo **NO afectan** la funcionalidad del código, pero son importantes para mantener consistencia
4. Es recomendable ejecutar `./vendor/bin/pint --test` antes de hacer commit

