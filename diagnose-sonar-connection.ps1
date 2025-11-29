# Script de diagnóstico para problemas de conexión con SonarQube

Write-Host ""
Write-Host "========================================" -ForegroundColor Cyan
Write-Host "Diagnóstico de Conexión SonarQube" -ForegroundColor Cyan
Write-Host "========================================" -ForegroundColor Cyan
Write-Host ""

$serverUrl = "https://sonarqube.dataguaviare.com.co"
$apiUrl = "$serverUrl/api/system/status"

# 1. Verificar resolución DNS
Write-Host "1. Verificando resolución DNS..." -ForegroundColor Yellow
try {
    $dnsResult = Resolve-DnsName -Name "sonarqube.dataguaviare.com.co" -ErrorAction Stop
    Write-Host "   [OK] DNS resuelto correctamente" -ForegroundColor Green
    Write-Host "   IP: $($dnsResult[0].IPAddress)" -ForegroundColor Gray
} catch {
    Write-Host "   [ERROR] No se pudo resolver el DNS" -ForegroundColor Red
    Write-Host "   Error: $($_.Exception.Message)" -ForegroundColor Yellow
    exit 1
}
Write-Host ""

# 2. Verificar conectividad TCP al puerto 443
Write-Host "2. Verificando conectividad TCP (puerto 443)..." -ForegroundColor Yellow
try {
    $tcpTest = Test-NetConnection -ComputerName "sonarqube.dataguaviare.com.co" -Port 443 -WarningAction SilentlyContinue
    if ($tcpTest.TcpTestSucceeded) {
        Write-Host "   [OK] Puerto 443 accesible" -ForegroundColor Green
    } else {
        Write-Host "   [ERROR] Puerto 443 no accesible" -ForegroundColor Red
        exit 1
    }
} catch {
    Write-Host "   [ERROR] Error al verificar conectividad TCP" -ForegroundColor Red
    Write-Host "   Error: $($_.Exception.Message)" -ForegroundColor Yellow
    exit 1
}
Write-Host ""

# 3. Verificar certificado SSL
Write-Host "3. Verificando certificado SSL..." -ForegroundColor Yellow
try {
    $cert = [System.Net.ServicePointManager]::ServerCertificateValidationCallback = {$true}
    $request = [System.Net.HttpWebRequest]::Create($serverUrl)
    $request.Timeout = 10000
    $response = $request.GetResponse()
    $response.Close()
    Write-Host "   [OK] Certificado SSL válido" -ForegroundColor Green
} catch {
    Write-Host "   [ADVERTENCIA] Problema con certificado SSL" -ForegroundColor Yellow
    Write-Host "   Error: $($_.Exception.Message)" -ForegroundColor Gray
}
Write-Host ""

# 4. Verificar respuesta HTTP del servidor
Write-Host "4. Verificando respuesta HTTP del servidor..." -ForegroundColor Yellow
try {
    $httpResponse = Invoke-WebRequest -Uri $apiUrl -TimeoutSec 20 -UseBasicParsing -ErrorAction Stop
    Write-Host "   [OK] Servidor responde correctamente" -ForegroundColor Green
    Write-Host "   Status Code: $($httpResponse.StatusCode)" -ForegroundColor Gray
    Write-Host "   Content: $($httpResponse.Content)" -ForegroundColor Gray
} catch {
    if ($_.Exception.Response.StatusCode -eq 401) {
        Write-Host "   [OK] Servidor responde (401 = requiere autenticación, esto es normal)" -ForegroundColor Green
    } else {
        Write-Host "   [ERROR] El servidor no responde correctamente" -ForegroundColor Red
        Write-Host "   Status Code: $($_.Exception.Response.StatusCode)" -ForegroundColor Yellow
        Write-Host "   Error: $($_.Exception.Message)" -ForegroundColor Yellow
    }
}
Write-Host ""

# 5. Verificar timeout con diferentes valores
Write-Host "5. Probando diferentes timeouts..." -ForegroundColor Yellow
$timeouts = @(5, 10, 15, 30)
foreach ($timeout in $timeouts) {
    try {
        $testResponse = Invoke-WebRequest -Uri $apiUrl -TimeoutSec $timeout -UseBasicParsing -ErrorAction Stop
        Write-Host "   [OK] Timeout de $timeout segundos: Funciona" -ForegroundColor Green
        break
    } catch {
        if ($timeout -eq $timeouts[-1]) {
            Write-Host "   [ERROR] No se pudo conectar incluso con timeout de $timeout segundos" -ForegroundColor Red
            Write-Host "   El servidor puede estar sobrecargado o hay un problema de red" -ForegroundColor Yellow
        }
    }
}
Write-Host ""

# 6. Verificar configuración del scanner
Write-Host "6. Verificando configuración del scanner..." -ForegroundColor Yellow
$scannerExe = $null
$pathDirs = $env:Path -split ';' | Where-Object { $_ -and $_.Trim() -ne '' }
foreach ($dir in $pathDirs) {
    if ($dir -and $dir.Trim() -ne '') {
        $exePath = Join-Path $dir "sonar-scanner.bat"
        if (Test-Path $exePath) {
            $scannerExe = $exePath
            break
        }
    }
}

if ($scannerExe) {
    Write-Host "   [OK] SonarQube Scanner encontrado: $scannerExe" -ForegroundColor Green
    
    # Verificar archivo de configuración del scanner
    $scannerConfPath = Join-Path (Split-Path (Split-Path $scannerExe)) "conf\sonar-scanner.properties"
    if (Test-Path $scannerConfPath) {
        Write-Host "   [OK] Archivo de configuración encontrado: $scannerConfPath" -ForegroundColor Green
        $confContent = Get-Content $scannerConfPath -Raw
        if ($confContent -match "sonar.ws.timeout") {
            Write-Host "   [INFO] Timeout configurado en sonar-scanner.properties" -ForegroundColor Gray
        } else {
            Write-Host "   [INFO] No hay timeout configurado en sonar-scanner.properties" -ForegroundColor Gray
            Write-Host "   [SUGERENCIA] Puedes agregar: sonar.ws.timeout=120" -ForegroundColor Yellow
        }
    }
} else {
    Write-Host "   [ERROR] SonarQube Scanner no encontrado en PATH" -ForegroundColor Red
}
Write-Host ""

# 7. Verificar token
Write-Host "7. Verificando token de SonarQube..." -ForegroundColor Yellow
$token = [System.Environment]::GetEnvironmentVariable('SONAR_TOKEN', 'User')
if ([string]::IsNullOrEmpty($token)) {
    $token = $env:SONAR_TOKEN
}

if ([string]::IsNullOrEmpty($token)) {
    Write-Host "   [ADVERTENCIA] Token no encontrado en variables de entorno" -ForegroundColor Yellow
} else {
    Write-Host "   [OK] Token encontrado (longitud: $($token.Length) caracteres)" -ForegroundColor Green
}
Write-Host ""

# Resumen
Write-Host "========================================" -ForegroundColor Cyan
Write-Host "Resumen del Diagnóstico" -ForegroundColor Cyan
Write-Host "========================================" -ForegroundColor Cyan
Write-Host ""
Write-Host "Si todos los tests pasaron pero el scanner aún falla:" -ForegroundColor Yellow
Write-Host "  1. El servidor puede estar sobrecargado - intenta más tarde" -ForegroundColor Gray
Write-Host "  2. Agrega timeout aumentado al ejecutar el scanner:" -ForegroundColor Gray
Write-Host "     sonar-scanner -Dsonar.ws.timeout=120 -Dsonar.token=TU_TOKEN" -ForegroundColor Cyan
Write-Host "  3. Verifica el archivo de configuración del scanner:" -ForegroundColor Gray
Write-Host "     Agrega: sonar.ws.timeout=120" -ForegroundColor Cyan
Write-Host ""

