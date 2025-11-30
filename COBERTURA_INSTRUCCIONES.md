# Instrucciones para Configurar Cobertura de Código

## Estado Actual

✅ **Reporte JUnit generado correctamente**: `tests/_coverage/test-reporter.xml`
❌ **Reporte de cobertura NO generado**: Se requiere instalar un driver de cobertura (Xdebug o PCOV)

## Problema

PHPUnit necesita un driver de cobertura para generar el archivo `coverage.xml` que SonarQube requiere.

**Mensaje de error**: `Code coverage driver not available. Did you install Xdebug or PCOV?`

## Solución: Instalar PCOV (Recomendado)

PCOV es más rápido que Xdebug para cobertura de código.

### Opción 1: Usar el script automático

```powershell
.\install-pcov-laragon.ps1
```

Este script:
1. Detecta tu versión de PHP
2. Te indica dónde descargar PCOV
3. Configura php.ini automáticamente

### Opción 2: Instalación manual

1. **Obtener información de PHP**:
   ```powershell
   php -r "echo PHP_VERSION;"
   php -r "echo ZEND_THREAD_SAFE ? 'ZTS' : 'NTS';"
   php -r "echo (PHP_INT_SIZE == 8 ? 'x64' : 'x86');"
   php -r "echo ini_get('extension_dir');"
   ```

2. **Descargar PCOV**:
   - Visita: https://pecl.php.net/package/pcov
   - O: https://windows.php.net/downloads/pecl/releases/pcov/
   - Descarga la versión compatible con tu PHP (ej: `pcov-1.0.11-8.4-ts-vs17-x64.zip`)

3. **Instalar**:
   - Extrae `php_pcov.dll`
   - Cópialo a: `C:\laragon\bin\php\php-8.4.15-Win32-vs17-x64\ext\`
   - Renómbralo a: `php_pcov.dll`

4. **Configurar php.ini**:
   - Abre: `C:\laragon\bin\php\php-8.4.15-Win32-vs17-x64\php.ini`
   - Agrega al final:
     ```ini
     ; PCOV for code coverage
     extension=pcov
     ```

5. **Reiniciar Laragon**:
   - Detén y reinicia Laragon para cargar la extensión

6. **Verificar**:
   ```powershell
   php -m | Select-String pcov
   ```

## Alternativa: Instalar Xdebug

Si prefieres Xdebug:

1. **Usar el script existente**:
   ```powershell
   .\install-xdebug.ps1
   ```

2. **O manualmente**:
   - Visita: https://xdebug.org/wizard
   - Pega el resultado de: `php -i`
   - Sigue las instrucciones para descargar e instalar

## Generar Reportes de Cobertura

Una vez instalado el driver de cobertura:

```powershell
# Generar reportes para SonarQube
.\generate-sonar-reports.ps1

# O directamente con PHPUnit
php artisan test --coverage

# O con PHPUnit directamente
vendor\bin\phpunit.bat --coverage-clover tests\_coverage\coverage.xml --log-junit tests\_coverage\test-reporter.xml
```

## Verificar que Funciona

Después de instalar el driver:

```powershell
# Verificar que está cargado
php -m | Select-String -Pattern "pcov|xdebug"

# Generar cobertura
php artisan test --coverage --min=0

# Verificar que se generó el archivo
Test-Path tests\_coverage\coverage.xml
```

## Configuración Actual

- ✅ `phpunit.xml` configurado correctamente
- ✅ `sonar-project.properties` apunta a los reportes correctos
- ✅ Reporte JUnit generándose correctamente
- ❌ Falta instalar driver de cobertura (PCOV o Xdebug)

## Nota Importante

SonarQube puede funcionar sin el reporte de cobertura, pero no mostrará el porcentaje de cobertura de código. El reporte JUnit (`test-reporter.xml`) es suficiente para que SonarQube detecte y analice los tests.

