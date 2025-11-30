# Script para instalar PCOV en Laragon
# PCOV es más rápido que Xdebug para cobertura de código

Write-Host "========================================" -ForegroundColor Cyan
Write-Host "Instalador de PCOV para Laragon" -ForegroundColor Cyan
Write-Host "========================================" -ForegroundColor Cyan
Write-Host ""

# Obtener información de PHP
$phpVersion = & php -r "echo PHP_VERSION;"
$phpZts = & php -r "echo ZEND_THREAD_SAFE ? 'ZTS' : 'NTS';"
$phpArch = & php -r "echo (PHP_INT_SIZE == 8 ? 'x64' : 'x86');"
$phpExtDir = & php -r "echo ini_get('extension_dir');"
$phpIniPath = & php --ini | Select-String "Loaded Configuration File" | ForEach-Object { 
    if ($_ -match ":\s*(.+)") {
        $matches[1].Trim()
    }
}

Write-Host "Información de PHP:" -ForegroundColor Yellow
Write-Host "  Versión: $phpVersion" -ForegroundColor Gray
Write-Host "  Thread Safety: $phpZts" -ForegroundColor Gray
Write-Host "  Arquitectura: $phpArch" -ForegroundColor Gray
Write-Host "  Directorio de extensiones: $phpExtDir" -ForegroundColor Gray
Write-Host "  php.ini: $phpIniPath" -ForegroundColor Gray
Write-Host ""

# Verificar si PCOV ya está instalado
$phpModules = & php -m 2>&1 | Out-String
if ($phpModules -match "pcov") {
    Write-Host "[OK] PCOV ya está instalado y cargado!" -ForegroundColor Green
    exit 0
}

# Verificar si el DLL existe
$pcovDll = Join-Path $phpExtDir "php_pcov.dll"
if (Test-Path $pcovDll) {
    Write-Host "[INFO] php_pcov.dll encontrado pero no cargado" -ForegroundColor Yellow
    Write-Host "Configurando php.ini..." -ForegroundColor Yellow
} else {
    Write-Host "PCOV no está instalado." -ForegroundColor Yellow
    Write-Host ""
    Write-Host "Para instalar PCOV:" -ForegroundColor Cyan
    Write-Host "1. Visita: https://pecl.php.net/package/pcov" -ForegroundColor Gray
    Write-Host "2. O descarga desde: https://windows.php.net/downloads/pecl/releases/pcov/" -ForegroundColor Gray
    Write-Host "3. Descarga la versión compatible con PHP $phpVersion $phpZts $phpArch" -ForegroundColor Gray
    Write-Host "4. Extrae php_pcov.dll y cópialo a:" -ForegroundColor Gray
    Write-Host "   $phpExtDir" -ForegroundColor Yellow
    Write-Host ""
    Write-Host "O usa PECL (si está disponible):" -ForegroundColor Cyan
    Write-Host "  pecl install pcov" -ForegroundColor Yellow
    Write-Host ""
    
    if (-not (Test-Path $phpExtDir)) {
        Write-Host "[ERROR] Directorio de extensiones no encontrado: $phpExtDir" -ForegroundColor Red
        exit 1
    }
    
    Write-Host "¿Deseas continuar con la configuración de php.ini? (S/N)" -ForegroundColor Yellow
    $response = Read-Host
    if ($response -ne "S" -and $response -ne "s") {
        exit 0
    }
}

# Configurar php.ini
if (-not (Test-Path $phpIniPath)) {
    Write-Host "[ERROR] php.ini no encontrado en: $phpIniPath" -ForegroundColor Red
    exit 1
}

$iniContent = Get-Content $phpIniPath -Raw

# Verificar si PCOV ya está configurado
if ($iniContent -match "extension\s*=\s*pcov" -or $iniContent -match "extension\s*=\s*php_pcov") {
    Write-Host "[INFO] PCOV ya está configurado en php.ini" -ForegroundColor Yellow
} else {
    Write-Host "Agregando configuración de PCOV a php.ini..." -ForegroundColor Yellow
    
    # Buscar la sección de extensiones
    if ($iniContent -match "; Windows Extensions" -or $iniContent -match "extension=") {
        # Agregar después de la última línea de extensión
        $pcovConfig = "`n; PCOV for code coverage`nextension=pcov`n"
        Add-Content -Path $phpIniPath -Value $pcovConfig
        Write-Host "[OK] Configuración agregada" -ForegroundColor Green
    } else {
        # Agregar al final del archivo
        $pcovConfig = @"

; PCOV for code coverage
extension=pcov
"@
        Add-Content -Path $phpIniPath -Value $pcovConfig
        Write-Host "[OK] Configuración agregada al final del archivo" -ForegroundColor Green
    }
}

Write-Host ""
Write-Host "========================================" -ForegroundColor Green
Write-Host "PCOV configurado!" -ForegroundColor Green
Write-Host "========================================" -ForegroundColor Green
Write-Host ""
Write-Host "IMPORTANTE: Reinicia Laragon o el servidor web para aplicar los cambios." -ForegroundColor Yellow
Write-Host ""
Write-Host "Para verificar la instalación:" -ForegroundColor Cyan
Write-Host "  php -m | Select-String pcov" -ForegroundColor Gray
Write-Host ""
Write-Host "Después de reiniciar, puedes generar coverage.xml ejecutando:" -ForegroundColor Cyan
Write-Host "  php artisan test --coverage" -ForegroundColor Yellow
Write-Host ""

