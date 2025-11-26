# Guía para Verificar con SonarQube Manualmente

## ⚠️ IMPORTANTE: Generar Reportes de Cobertura Primero

**ANTES de ejecutar SonarQube, DEBES generar los reportes de cobertura y tests:**

```powershell
# Opción 1: Usar el script automatizado (recomendado)
.\generate-sonar-reports.ps1

# Opción 2: Generar manualmente
vendor\bin\phpunit.bat --coverage-clover tests\_coverage\coverage.xml --log-junit tests\_coverage\test-reporter.xml
```

Esto generará:
- `tests/_coverage/coverage.xml` - Reporte de cobertura en formato Clover XML
- `tests/_coverage/test-reporter.xml` - Reporte de tests en formato JUnit XML

**Sin estos archivos, SonarQube NO encontrará los tests ni mostrará la cobertura.**

---

## Opción 1: Análisis con SonarQube Scanner (Servidor SonarQube)

### Requisitos Previos:
- Tener SonarQube Server ejecutándose (local o remoto)
- Tener SonarQube Scanner instalado
- **Generar los reportes de cobertura primero** (ver arriba)

### Pasos:

#### 1. Instalar SonarQube Scanner

**Windows:**
```powershell
# Descargar desde: https://docs.sonarqube.org/latest/analyzing-source-code/scanners/sonarscanner/
# O usar Chocolatey:
choco install sonarscanner-msbuild-net46
```

**O usar Docker:**
```bash
docker pull sonarsource/sonar-scanner-cli
```

#### 2. Configurar el Token de SonarQube

1. Ve a tu servidor SonarQube (ej: http://localhost:9000)
2. Inicia sesión
3. Ve a `My Account` → `Security` → `Generate Token`
4. Copia el token generado

#### 2. Generar Reportes de Cobertura

**⚠️ ESTO ES OBLIGATORIO antes de ejecutar SonarQube:**

```powershell
# Ejecutar el script para generar reportes
.\generate-sonar-reports.ps1
```

O manualmente:
```powershell
# Crear directorio si no existe
New-Item -ItemType Directory -Path tests\_coverage -Force

# Ejecutar tests con cobertura
vendor\bin\phpunit.bat --coverage-clover tests\_coverage\coverage.xml --log-junit tests\_coverage\test-reporter.xml
```

#### 3. Configurar el Token de SonarQube

1. Ve a tu servidor SonarQube (ej: http://localhost:9000)
2. Inicia sesión
3. Ve a `My Account` → `Security` → `Generate Token`
4. Copia el token generado

#### 4. Ejecutar el Análisis

**Con SonarQube Scanner instalado:**
```bash
# En la raíz del proyecto
sonar-scanner \
  -Dsonar.projectKey=laravel-chatbot \
  -Dsonar.sources=app \
  -Dsonar.host.url=http://localhost:9000 \
  -Dsonar.login=TU_TOKEN_AQUI
```

**Con Docker:**
```bash
docker run --rm \
  -v "%cd%:/usr/src" \
  -w /usr/src \
  sonarsource/sonar-scanner-cli \
  -Dsonar.projectKey=laravel-chatbot \
  -Dsonar.sources=app \
  -Dsonar.host.url=http://host.docker.internal:9000 \
  -Dsonar.login=TU_TOKEN_AQUI
```

**O usar el archivo de configuración:**
```bash
# Si tienes sonar-project.properties en la raíz
sonar-scanner -Dsonar.login=TU_TOKEN_AQUI
```

#### 5. Ver los Resultados

1. Ve a tu servidor SonarQube
2. Busca el proyecto "laravel-chatbot"
3. Verás el análisis completo con:
   - Issues (problemas encontrados)
   - Code Smells
   - Bugs
   - Vulnerabilidades
   - Cobertura de código

---

## Opción 2: Usar SonarQube Community Edition (Local)

### Instalar SonarQube Localmente:

**Con Docker (Recomendado):**
```bash
# Ejecutar SonarQube
docker run -d --name sonarqube \
  -p 9000:9000 \
  -e SONAR_ES_BOOTSTRAP_CHECKS_DISABLE=true \
  sonarqube:community

# Acceder a: http://localhost:9000
# Usuario por defecto: admin / admin
```

**Luego sigue los pasos de la Opción 1**

---

## Opción 3: Análisis Rápido con PHPStan o Psalm (Alternativa)

Si no tienes acceso a SonarQube, puedes usar herramientas similares:

### PHPStan:
```bash
composer require --dev phpstan/phpstan
vendor/bin/phpstan analyse app --level=5
```

### Psalm:
```bash
composer require --dev vimeo/psalm
vendor/bin/psalm
```

---

## Comandos Útiles

### Ver problemas específicos:
- `php:S1448` - Demasiados métodos en una clase
- `php:S1172` - Parámetros no utilizados
- `php:S3776` - Complejidad cognitiva alta

---

## Notas Importantes

- **⚠️ CRÍTICO**: Siempre genera los reportes de cobertura ANTES de ejecutar SonarQube
- **SonarQube Server** requiere instalación pero ofrece análisis completo
- El archivo `sonar-project.properties` ya está configurado en la raíz del proyecto
- Los reportes se generan en `tests/_coverage/` y deben existir antes del análisis
- Los análisis se pueden automatizar en CI/CD (GitHub Actions, GitLab CI, etc.)

## Solución de Problemas

### SonarQube no encuentra los tests

1. **Verifica que los reportes existan:**
   ```powershell
   Test-Path tests\_coverage\coverage.xml
   Test-Path tests\_coverage\test-reporter.xml
   ```

2. **Vuelve a generar los reportes:**
   ```powershell
   .\generate-sonar-reports.ps1
   ```

3. **Verifica las rutas en `sonar-project.properties`:**
   - `sonar.php.coverage.reportPaths=tests/_coverage/coverage.xml`
   - `sonar.php.tests.reportPath=tests/_coverage/test-reporter.xml`

### Cobertura muestra 0%

- Asegúrate de haber generado los reportes ANTES de ejecutar SonarQube
- Verifica que PHPUnit tenga una extensión de cobertura habilitada (Xdebug o PCOV)
- Revisa que los tests realmente ejecuten código de la carpeta `app/`

