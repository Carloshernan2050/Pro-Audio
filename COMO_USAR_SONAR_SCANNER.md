# Cómo Usar SonarQube Scanner

## ✅ Sonar-Scanner Instalado

SonarQube Scanner está instalado y disponible en tu sistema:
- **Versión**: 7.3.0.5189
- **Ubicación**: `C:\laragon\www\sonar-scanner-cli-7.3.0.5189-windows-x64\`
- **Archivo de configuración del proyecto**: `sonar-project.properties` (en la raíz del proyecto)

## Opciones para Ejecutar SonarQube Scanner

### Opción 1: Usar el Script Helper (Recomendado) ⭐

El script `run-sonar-scanner.ps1` facilita el uso del token:

```powershell
# Si tienes el token en variable de entorno
.\run-sonar-scanner.ps1

# O pasar el token como parámetro
.\run-sonar-scanner.ps1 tu-token-aqui
```

**Ventajas**:
- ✅ Maneja automáticamente el token
- ✅ Verifica reportes antes de ejecutar
- ✅ Verifica conectividad con el servidor
- ✅ Muestra mensajes informativos

### Opción 2: Comando Directo (Con Token)

Ejecutar `sonar-scanner` directamente con el token:

```powershell
sonar-scanner -Dsonar.token=tu-token-aqui
```

### Opción 3: Configurar Token en Variable de Entorno

Configurar el token una vez y usar `sonar-scanner` directamente:

```powershell
# Configurar token permanentemente (User)
[System.Environment]::SetEnvironmentVariable('SONAR_TOKEN', 'tu-token-aqui', 'User')

# O temporalmente para esta sesión
$env:SONAR_TOKEN = 'tu-token-aqui'

# Luego ejecutar
sonar-scanner
```

### Opción 4: Token en sonar-project.properties (No recomendado por seguridad)

No recomendado, pero posible:

```properties
# En sonar-project.properties (NO RECOMENDADO)
sonar.token=tu-token-aqui
```

Luego:
```powershell
sonar-scanner
```

⚠️ **Advertencia**: No hacer commit del token en el repositorio.

## Flujo de Trabajo Completo

### Paso 1: Generar Reportes

```powershell
.\generate-sonar-reports.ps1
```

Esto genera:
- `tests/_coverage/coverage.xml` (cobertura de código)
- `tests/_coverage/test-reporter.xml` (resultados de tests)

### Paso 2: Ejecutar SonarQube Scanner

**Opción A**: Con el script helper
```powershell
.\run-sonar-scanner.ps1
```

**Opción B**: Comando directo
```powershell
sonar-scanner -Dsonar.token=tu-token-aqui
```

**Opción C**: Si ya configuraste el token
```powershell
sonar-scanner
```

### Paso 3: Ver Resultados

Ir a: `https://sonarqube.dataguaviare.com.co/dashboard?id=pro-audio`

## Obtener el Token de SonarQube

Si necesitas obtener o regenerar tu token:

1. Ir a: `https://sonarqube.dataguaviare.com.co`
2. Iniciar sesión
3. Click en tu avatar (arriba derecha)
4. **My Account** → **Security**
5. Generar un nuevo token o copiar uno existente

## Configuración Actual

Tu proyecto ya tiene configurado `sonar-project.properties` con:

```properties
sonar.projectKey=pro-audio
sonar.projectName=pro-audio
sonar.host.url=https://sonarqube.dataguaviare.com.co
sonar.sources=app
sonar.tests=tests
sonar.php.coverage.reportPaths=tests/_coverage/coverage.xml
sonar.php.tests.reportPath=tests/_coverage/test-reporter.xml
```

## Comandos Útiles

### Ver versión de sonar-scanner
```powershell
sonar-scanner --version
```

### Ver ayuda
```powershell
sonar-scanner --help
```

### Ejecutar con opciones adicionales
```powershell
# Con timeout aumentado
sonar-scanner -Dsonar.token=tu-token -Dsonar.ws.timeout=120

# Con debug (más información)
sonar-scanner -Dsonar.token=tu-token -X

# Solo validar configuración sin ejecutar
sonar-scanner -Dsonar.token=tu-token -Dsonar.scanner.dumpToFile=sonar-scanner.properties
```

## Troubleshooting

### Error: Token no encontrado

**Solución**: Configurar el token primero:

```powershell
# Opción 1: Variable de entorno temporal
$env:SONAR_TOKEN = 'tu-token-aqui'

# Opción 2: Variable permanente
[System.Environment]::SetEnvironmentVariable('SONAR_TOKEN', 'tu-token-aqui', 'User')

# Opción 3: Pasar como parámetro
sonar-scanner -Dsonar.token=tu-token-aqui
```

### Error: Reportes no encontrados

**Solución**: Generar reportes primero:

```powershell
.\generate-sonar-reports.ps1
```

### Error: No se puede conectar al servidor

**Solución**: Verificar conectividad:

```powershell
# Probar conectividad
Test-NetConnection -ComputerName sonarqube.dataguaviare.com.co -Port 443
```

## Recomendación

**Usar el script helper** (`.\run-sonar-scanner.ps1`) porque:
- ✅ Es más fácil y seguro
- ✅ Verifica todo antes de ejecutar
- ✅ Maneja errores de manera amigable
- ✅ Muestra mensajes informativos

Pero si prefieres usar `sonar-scanner` directamente, también funciona perfectamente. Solo asegúrate de tener el token configurado.

