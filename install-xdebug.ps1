# Script para instalar Xdebug en XAMPP
# Requiere: PHP 8.2.12 ZTS x64

Write-Host "========================================" -ForegroundColor Cyan
Write-Host "Instalador de Xdebug para XAMPP" -ForegroundColor Cyan
Write-Host "========================================" -ForegroundColor Cyan
Write-Host ""

$phpPath = "C:\xampp\php"
$extPath = "$phpPath\ext"
$iniPath = "$phpPath\php.ini"

# Verificar que XAMPP existe
if (-not (Test-Path $phpPath)) {
    Write-Host "ERROR: XAMPP no encontrado en $phpPath" -ForegroundColor Red
    exit 1
}

# Verificar y configurar PHP en el PATH si no está disponible
Write-Host "Verificando PHP..." -ForegroundColor Yellow
try {
    $null = & php --version 2>&1
    Write-Host "[OK] PHP encontrado en el PATH" -ForegroundColor Green
} catch {
    # Buscar PHP en ubicaciones comunes
    $phpPaths = @(
        "C:\xampp\php",
        "C:\wamp64\bin\php",
        "C:\laragon\bin\php",
        "C:\php",
        "$env:ProgramFiles\PHP",
        "$env:ProgramFiles(x86)\PHP"
    )
    
    $phpFound = $false
    foreach ($phpPathOption in $phpPaths) {
        if (Test-Path "$phpPathOption\php.exe") {
            $env:Path += ";$phpPathOption"
            Write-Host "[OK] PHP encontrado en: $phpPathOption" -ForegroundColor Green
            Write-Host "     Agregado al PATH para esta sesion." -ForegroundColor Gray
            $phpFound = $true
            break
        }
    }
    
    if (-not $phpFound) {
        Write-Host "ERROR: PHP no encontrado. Por favor:" -ForegroundColor Red
        Write-Host "  1. Instala PHP o XAMPP/WAMP/Laragon" -ForegroundColor Yellow
        Write-Host "  2. Agrega PHP al PATH del sistema" -ForegroundColor Yellow
        exit 1
    }
}
Write-Host ""

# Obtener información de PHP
Write-Host "Informacion de PHP:" -ForegroundColor Yellow
$phpVersion = & php -r "echo PHP_VERSION;"
$phpZts = & php -r "echo ZEND_THREAD_SAFE ? 'ZTS' : 'NTS';"
$phpArch = & php -r "echo (PHP_INT_SIZE == 8 ? 'x64' : 'x86');"

Write-Host "  Version: $phpVersion" -ForegroundColor Gray
Write-Host "  Thread Safety: $phpZts" -ForegroundColor Gray
Write-Host "  Arquitectura: $phpArch" -ForegroundColor Gray
Write-Host ""

# Verificar si Xdebug ya está instalado
$phpModules = & php -m 2>&1 | Out-String
if ($phpModules -match "xdebug") {
    Write-Host "[OK] Xdebug ya esta instalado!" -ForegroundColor Green
    Write-Host ""
    Write-Host "Verificando configuracion..." -ForegroundColor Yellow
    $phpIni = Get-Content $iniPath -Raw
    if ($phpIni -match "xdebug\.mode\s*=\s*coverage") {
        Write-Host "[OK] Xdebug configurado para coverage" -ForegroundColor Green
    } else {
        Write-Host "[ADVERTENCIA] Xdebug no esta configurado para coverage" -ForegroundColor Yellow
        Write-Host "Agregando configuracion de coverage..." -ForegroundColor Yellow
        Add-Content -Path $iniPath -Value "`nxdebug.mode=coverage"
        Write-Host "[OK] Configuracion agregada" -ForegroundColor Green
    }
    exit 0
}

Write-Host "Xdebug no esta instalado." -ForegroundColor Yellow
Write-Host ""
Write-Host "Para instalar Xdebug manualmente:" -ForegroundColor Cyan
Write-Host "1. Visita: https://xdebug.org/download" -ForegroundColor Gray
Write-Host "2. Descarga: php_xdebug-3.x.x-8.2-zts-vs16-x86_64.dll" -ForegroundColor Gray
Write-Host "   (Para PHP 8.2 ZTS x64 con Visual C++ 2019)" -ForegroundColor Gray
Write-Host "3. Renombra el archivo a: php_xdebug.dll" -ForegroundColor Gray
Write-Host "4. Copia a: $extPath" -ForegroundColor Gray
Write-Host "5. Edita: $iniPath" -ForegroundColor Gray
Write-Host "   Agrega estas lineas:" -ForegroundColor Gray
Write-Host "   [Xdebug]" -ForegroundColor Gray
Write-Host "   zend_extension=xdebug" -ForegroundColor Gray
Write-Host "   xdebug.mode=coverage" -ForegroundColor Gray
Write-Host ""
Write-Host "O usa el asistente de Xdebug:" -ForegroundColor Cyan
Write-Host "  https://xdebug.org/wizard" -ForegroundColor Yellow
Write-Host "  (Pega el resultado de: php -i)" -ForegroundColor Gray
Write-Host ""

# Intentar descargar automáticamente
Write-Host "Intentando descargar Xdebug automaticamente..." -ForegroundColor Yellow
Write-Host ""

# Crear archivo phpinfo.txt para el wizard
Write-Host "Creando archivo phpinfo.txt..." -ForegroundColor Yellow
& php -i > phpinfo.txt 2>&1
Write-Host "[OK] Archivo phpinfo.txt creado" -ForegroundColor Green
Write-Host ""

Write-Host "========================================" -ForegroundColor Cyan
Write-Host "Instalacion de Xdebug" -ForegroundColor Cyan
Write-Host "========================================" -ForegroundColor Cyan
Write-Host ""
Write-Host "PASO 1: Obtener el DLL de Xdebug" -ForegroundColor Yellow
Write-Host "  1. Abre tu navegador y visita:" -ForegroundColor Cyan
Write-Host "     https://xdebug.org/wizard" -ForegroundColor Yellow
Write-Host ""
Write-Host "  2. Abre el archivo phpinfo.txt (ya esta creado)" -ForegroundColor Cyan
Write-Host "     y copia TODO su contenido (Ctrl+A, Ctrl+C)" -ForegroundColor Gray
Write-Host ""
Write-Host "  3. Pega el contenido en el wizard de Xdebug" -ForegroundColor Cyan
Write-Host "     y sigue las instrucciones para descargar" -ForegroundColor Gray
Write-Host ""
Write-Host "PASO 2: Instalar el DLL" -ForegroundColor Yellow
Write-Host "  Una vez descargado el DLL:" -ForegroundColor Cyan
Write-Host "  1. Copia el DLL a: $extPath" -ForegroundColor Gray
Write-Host "  2. Renombralo a: php_xdebug.dll" -ForegroundColor Gray
Write-Host ""
Write-Host "PASO 3: Configurar php.ini" -ForegroundColor Yellow
Write-Host "  Despues de copiar el DLL, ejecuta:" -ForegroundColor Cyan
Write-Host "  .\install-xdebug.ps1 -configure" -ForegroundColor Yellow
Write-Host ""
Write-Host "O manualmente edita: $iniPath" -ForegroundColor Gray
Write-Host "Y agrega estas lineas al final:" -ForegroundColor Gray
Write-Host "  [Xdebug]" -ForegroundColor Gray
Write-Host "  zend_extension=xdebug" -ForegroundColor Gray
Write-Host "  xdebug.mode=coverage" -ForegroundColor Gray
Write-Host ""

# Si se pasa el parámetro -configure, configurar php.ini
if ($args -contains "-configure") {
    Write-Host "Configurando php.ini..." -ForegroundColor Yellow
    
    if (-not (Test-Path "$extPath\php_xdebug.dll")) {
        Write-Host "[ERROR] php_xdebug.dll no encontrado en $extPath" -ForegroundColor Red
        Write-Host "Por favor, copia el DLL primero." -ForegroundColor Yellow
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
        Write-Host "[OK] Configuracion agregada a php.ini" -ForegroundColor Green
    } else {
        Write-Host "[INFO] Xdebug ya esta configurado en php.ini" -ForegroundColor Yellow
        # Verificar si tiene xdebug.mode=coverage
        if ($iniContent -notmatch "xdebug\.mode\s*=\s*coverage") {
            Add-Content -Path $iniPath -Value "xdebug.mode=coverage"
            Write-Host "[OK] xdebug.mode=coverage agregado" -ForegroundColor Green
        }
    }
    
    Write-Host ""
    Write-Host "========================================" -ForegroundColor Green
    Write-Host "Xdebug configurado correctamente!" -ForegroundColor Green
    Write-Host "========================================" -ForegroundColor Green
    Write-Host ""
    Write-Host "IMPORTANTE: Reinicia Apache en XAMPP para aplicar los cambios." -ForegroundColor Yellow
    Write-Host ""
    Write-Host "Para verificar la instalacion:" -ForegroundColor Cyan
    Write-Host "  php -m | Select-String xdebug" -ForegroundColor Gray
    Write-Host ""
}

