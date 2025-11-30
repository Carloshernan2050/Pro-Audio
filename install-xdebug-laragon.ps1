# Script para instalar Xdebug en Laragon
# Requiere: PHP 8.4.15 ZTS x64

Write-Host "========================================" -ForegroundColor Cyan
Write-Host "Instalador de Xdebug para Laragon" -ForegroundColor Cyan
Write-Host "========================================" -ForegroundColor Cyan
Write-Host ""

# Rutas específicas para Laragon
$phpPath = "C:\laragon\bin\php\php-8.4.15-Win32-vs17-x64"
$extPath = "$phpPath\ext"
$iniPath = "$phpPath\php.ini"

Write-Host "Ruta de extensiones PHP: $extPath" -ForegroundColor Gray

# Verificar que Laragon existe
if (-not (Test-Path $phpPath)) {
    Write-Host "ERROR: PHP no encontrado en $phpPath" -ForegroundColor Red
    Write-Host "Verificando otras versiones de PHP en Laragon..." -ForegroundColor Yellow
    
    # Buscar otras versiones de PHP en Laragon
    $laragonPhpPath = "C:\laragon\bin\php"
    if (Test-Path $laragonPhpPath) {
        $phpVersions = Get-ChildItem -Path $laragonPhpPath -Directory | Where-Object { $_.Name -match "php-\d+\.\d+" } | Sort-Object Name -Descending
        if ($phpVersions.Count -gt 0) {
            Write-Host "Versiones de PHP encontradas:" -ForegroundColor Cyan
            foreach ($version in $phpVersions) {
                Write-Host "  - $($version.Name)" -ForegroundColor Gray
            }
            Write-Host ""
            Write-Host "Ejecuta este script desde la versión de PHP que estás usando." -ForegroundColor Yellow
            Write-Host "O actualiza las rutas en este script." -ForegroundColor Yellow
        }
    }
    exit 1
}

# Verificar que el directorio de extensiones existe
if (-not (Test-Path $extPath)) {
    Write-Host "ERROR: Directorio de extensiones no encontrado en $extPath" -ForegroundColor Red
    exit 1
} else {
    Write-Host "[OK] Directorio de extensiones encontrado" -ForegroundColor Green
}
Write-Host ""

# Verificar y configurar PHP en el PATH si no está disponible
Write-Host "Verificando PHP..." -ForegroundColor Yellow
try {
    $null = & php --version 2>&1
    Write-Host "[OK] PHP encontrado en el PATH" -ForegroundColor Green
} catch {
    $env:Path += ";$phpPath"
    Write-Host "[OK] PHP agregado al PATH para esta sesión" -ForegroundColor Green
}
Write-Host ""

# Obtener información de PHP
Write-Host "Información de PHP:" -ForegroundColor Yellow
$phpVersion = & php -r "echo PHP_VERSION;"
$phpZts = & php -r "echo ZEND_THREAD_SAFE ? 'ZTS' : 'NTS';"
$phpArch = & php -r "echo (PHP_INT_SIZE == 8 ? 'x64' : 'x86');"
$phpVcVersion = & php -r "echo defined('PHP_WINDOWS_VERSION_MAJOR') ? 'VS' : '';"
$phpVc = "vs17"  # Visual Studio 2019/2022

Write-Host "  Versión: $phpVersion" -ForegroundColor Gray
Write-Host "  Thread Safety: $phpZts" -ForegroundColor Gray
Write-Host "  Arquitectura: $phpArch" -ForegroundColor Gray
Write-Host "  Compilador: $phpVc" -ForegroundColor Gray
Write-Host ""

# Verificar si Xdebug ya está instalado (DLL en la ruta de extensiones)
$xdebugDll = Get-ChildItem -Path $extPath -Filter "php_xdebug.dll" -ErrorAction SilentlyContinue
if ($xdebugDll) {
    Write-Host "[INFO] php_xdebug.dll encontrado en: $extPath" -ForegroundColor Cyan
}

# Verificar si Xdebug está cargado en PHP
$phpModules = & php -m 2>&1 | Out-String
if ($phpModules -match "xdebug") {
    Write-Host "[OK] Xdebug ya está instalado y cargado!" -ForegroundColor Green
    Write-Host ""
    Write-Host "Verificando configuración..." -ForegroundColor Yellow
    $phpIni = Get-Content $iniPath -Raw
    if ($phpIni -match "xdebug\.mode\s*=\s*coverage") {
        Write-Host "[OK] Xdebug configurado para coverage" -ForegroundColor Green
        Write-Host ""
        Write-Host "Xdebug está listo para generar coverage.xml" -ForegroundColor Green
    } else {
        Write-Host "[ADVERTENCIA] Xdebug no está configurado para coverage" -ForegroundColor Yellow
        Write-Host "Agregando configuración de coverage..." -ForegroundColor Yellow
        Add-Content -Path $iniPath -Value "`nxdebug.mode=coverage"
        Write-Host "[OK] Configuración agregada" -ForegroundColor Green
        Write-Host ""
        Write-Host "IMPORTANTE: Reinicia Laragon para aplicar los cambios." -ForegroundColor Yellow
    }
    exit 0
} elseif ($xdebugDll) {
    Write-Host "[ADVERTENCIA] php_xdebug.dll encontrado pero no está cargado" -ForegroundColor Yellow
    Write-Host "Verifica la configuración en php.ini" -ForegroundColor Yellow
    Write-Host ""
}

if (-not $xdebugDll) {
    Write-Host "Xdebug no está instalado." -ForegroundColor Yellow
    Write-Host ""
    Write-Host "Para instalar Xdebug manualmente:" -ForegroundColor Cyan
    Write-Host "1. Visita: https://xdebug.org/download" -ForegroundColor Gray
    Write-Host "2. Descarga la versión compatible con:" -ForegroundColor Gray
    Write-Host "   PHP $phpVersion $phpZts $phpArch" -ForegroundColor Yellow
    Write-Host "   Busca: php_xdebug-3.x.x-8.4-$phpZts-$phpVc-$phpArch.dll" -ForegroundColor Yellow
    Write-Host "3. Renombra el archivo a: php_xdebug.dll" -ForegroundColor Gray
    Write-Host "4. Copia el DLL a la ruta de extensiones:" -ForegroundColor Gray
    Write-Host "   $extPath" -ForegroundColor Yellow
    Write-Host "5. Ejecuta: .\install-xdebug-laragon.ps1 -configure" -ForegroundColor Gray
    Write-Host ""
}

Write-Host "O usa el asistente de Xdebug:" -ForegroundColor Cyan
Write-Host "  https://xdebug.org/wizard" -ForegroundColor Yellow
Write-Host "  (Pega el resultado de: php -i)" -ForegroundColor Gray
Write-Host ""

# Crear archivo phpinfo.txt para el wizard
Write-Host "Creando archivo phpinfo.txt..." -ForegroundColor Yellow
& php -i > phpinfo.txt 2>&1
Write-Host "[OK] Archivo phpinfo.txt creado" -ForegroundColor Green
Write-Host ""

Write-Host "========================================" -ForegroundColor Cyan
Write-Host "Instalación de Xdebug" -ForegroundColor Cyan
Write-Host "========================================" -ForegroundColor Cyan
Write-Host ""
Write-Host "PASO 1: Obtener el DLL de Xdebug" -ForegroundColor Yellow
Write-Host "  1. Abre tu navegador y visita:" -ForegroundColor Cyan
Write-Host "     https://xdebug.org/wizard" -ForegroundColor Yellow
Write-Host ""
Write-Host "  2. Abre el archivo phpinfo.txt (ya está creado)" -ForegroundColor Cyan
Write-Host "     y copia TODO su contenido (Ctrl+A, Ctrl+C)" -ForegroundColor Gray
Write-Host ""
Write-Host "  3. Pega el contenido en el wizard de Xdebug" -ForegroundColor Cyan
Write-Host "     y sigue las instrucciones para descargar" -ForegroundColor Gray
Write-Host ""
Write-Host "PASO 2: Instalar el DLL" -ForegroundColor Yellow
Write-Host "  Una vez descargado el DLL:" -ForegroundColor Cyan
Write-Host "  1. Copia el DLL a la ruta de extensiones:" -ForegroundColor Gray
Write-Host "     $extPath" -ForegroundColor Yellow
Write-Host "  2. Renómbralo a: php_xdebug.dll" -ForegroundColor Gray
Write-Host ""
Write-Host "PASO 3: Configurar php.ini" -ForegroundColor Yellow
Write-Host "  Después de copiar el DLL, ejecuta:" -ForegroundColor Cyan
Write-Host "  .\install-xdebug-laragon.ps1 -configure" -ForegroundColor Yellow
Write-Host ""

# Si se pasa el parámetro -configure, configurar php.ini
if ($args -contains "-configure") {
    Write-Host "Configurando php.ini..." -ForegroundColor Yellow
    
    if (-not (Test-Path "$extPath\php_xdebug.dll")) {
        Write-Host "[ERROR] php_xdebug.dll no encontrado en $extPath" -ForegroundColor Red
        Write-Host "Por favor, copia el DLL primero." -ForegroundColor Yellow
        exit 1
    }
    
    if (-not (Test-Path $iniPath)) {
        Write-Host "[ERROR] php.ini no encontrado en $iniPath" -ForegroundColor Red
        exit 1
    }
    
    $iniContent = Get-Content $iniPath -Raw
    
    # Verificar si ya existe configuración de Xdebug
    if ($iniContent -notmatch "\[Xdebug\]") {
        # Agregar configuración al final del archivo
        $xdebugConfig = @"

[Xdebug]
zend_extension=xdebug
xdebug.mode=coverage
"@
        Add-Content -Path $iniPath -Value $xdebugConfig
        Write-Host "[OK] Configuración agregada a php.ini" -ForegroundColor Green
    } else {
        Write-Host "[INFO] Xdebug ya está configurado en php.ini" -ForegroundColor Yellow
        # Verificar si tiene xdebug.mode=coverage
        if ($iniContent -notmatch "xdebug\.mode\s*=\s*coverage") {
            Add-Content -Path $iniPath -Value "xdebug.mode=coverage"
            Write-Host "[OK] xdebug.mode=coverage agregado" -ForegroundColor Green
        } else {
            Write-Host "[OK] xdebug.mode=coverage ya está configurado" -ForegroundColor Green
        }
    }
    
    Write-Host ""
    Write-Host "========================================" -ForegroundColor Green
    Write-Host "Xdebug configurado correctamente!" -ForegroundColor Green
    Write-Host "========================================" -ForegroundColor Green
    Write-Host ""
    Write-Host "Ubicación del DLL: $extPath\php_xdebug.dll" -ForegroundColor Cyan
    Write-Host ""
    Write-Host "IMPORTANTE: Reinicia Laragon para aplicar los cambios." -ForegroundColor Yellow
    Write-Host ""
    Write-Host "Para verificar la instalación:" -ForegroundColor Cyan
    Write-Host "  php -m | Select-String xdebug" -ForegroundColor Gray
    Write-Host ""
    Write-Host "Después de reiniciar, puedes generar coverage.xml ejecutando:" -ForegroundColor Cyan
    Write-Host "  .\generate-sonar-reports.ps1" -ForegroundColor Yellow
    Write-Host ""
}

