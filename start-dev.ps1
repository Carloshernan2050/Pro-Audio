# Script para iniciar el servidor de desarrollo
# Ejecuta Vite y PHP Artisan Serve simultáneamente

Write-Host ""
Write-Host "========================================" -ForegroundColor Cyan
Write-Host "Iniciando servidor de desarrollo..." -ForegroundColor Cyan
Write-Host "========================================" -ForegroundColor Cyan
Write-Host ""
Write-Host "Esto iniciará:" -ForegroundColor Yellow
Write-Host "  - Vite (servidor de desarrollo frontend)" -ForegroundColor Gray
Write-Host "  - PHP Artisan Serve (servidor Laravel)" -ForegroundColor Gray
Write-Host ""
Write-Host "Presiona Ctrl+C para detener ambos servidores" -ForegroundColor Yellow
Write-Host ""

# Verificar que npm esté instalado
try {
    $null = & npm --version 2>&1
} catch {
    Write-Host "ERROR: npm no está instalado o no está en el PATH" -ForegroundColor Red
    Write-Host "Por favor instala Node.js desde: https://nodejs.org/" -ForegroundColor Yellow
    exit 1
}

# Verificar que PHP esté disponible
try {
    $null = & php --version 2>&1
} catch {
    Write-Host "ERROR: PHP no está instalado o no está en el PATH" -ForegroundColor Red
    Write-Host "Por favor instala PHP o agrega PHP al PATH" -ForegroundColor Yellow
    exit 1
}

# Ejecutar el comando npm que usa concurrently
npm run dev:all
