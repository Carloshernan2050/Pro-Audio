# PRO AUDIO - Sistema de Gestión de Eventos

Sistema web desarrollado en Laravel para la gestión integral de servicios de eventos, incluyendo alquiler de equipos de audio, iluminación y video, gestión de inventario, calendario de eventos, reservas y chatbot inteligente.

## Descripción

PRO AUDIO es una aplicación web completa diseñada para gestionar todos los aspectos operativos de una empresa de servicios de eventos. El sistema proporciona herramientas administrativas para la gestión de servicios, inventario, reservas, calendario de eventos y cuenta con un sistema de chatbot inteligente para asistencia automatizada a clientes.

## Características Principales

### Gestión de Usuarios y Roles
- Sistema de autenticación y registro de usuarios
- Gestión de roles mediante Spatie Laravel Permission (Superadmin, Admin, Usuario, Cliente, Invitado)
- Perfiles de usuario con gestión de fotografías de perfil
- Control de acceso basado en roles (Role-Based Access Control - RBAC)

### Gestión de Servicios
- Operaciones CRUD completas para servicios y subservicios
- Sistema de categorización de servicios (Animación, Publicidad, Alquiler)
- Gestión de imágenes e iconos para servicios y subservicios
- Motor de búsqueda avanzada de servicios
- Visualización dinámica de servicios mediante sistema de slugs

### Gestión de Inventario
- Control integral de inventario de equipos
- Registro de movimientos de inventario (entradas y salidas)
- Control de stock en tiempo real
- Validación de disponibilidad de stock para reservas

### Calendario de Eventos
- Interfaz de calendario interactiva
- Gestión completa de eventos y registros
- Sistema de items de calendario asociados a eventos
- Validación de disponibilidad y conflictos

### Sistema de Reservas
- Creación y gestión de reservas
- Proceso de confirmación de reservas
- Asociación de reservas con inventario
- Control de stock durante el proceso de reserva

### Chatbot Inteligente
- Sistema de chatbot semántico con procesamiento de lenguaje natural
- Detección automática de intenciones del usuario
- Generación automática de sugerencias de servicios
- Gestión de sesiones de chat
- Integración con módulos de servicios y subservicios

### Reportes y Exportación
- Historial completo de actividades del sistema
- Exportación a PDF de historial y cotizaciones
- Generación de reportes de movimientos de inventario

### Interfaz de Usuario
- Diseño responsive adaptativo
- Dashboard administrativo interactivo
- Sistema de búsqueda en tiempo real
- Interfaz de usuario intuitiva

## Stack Tecnológico

### Backend
- **Laravel 12.0** - Framework PHP
- **PHP 8.2+** - Lenguaje de programación
- **MySQL/MariaDB** - Sistema de gestión de base de datos relacional
- **Spatie Laravel Permission** - Sistema de gestión de roles y permisos
- **Guzzle HTTP** - Cliente HTTP para integración con APIs externas
- **DomPDF** - Biblioteca para generación de documentos PDF

### Frontend
- **Blade Templates** - Motor de plantillas de Laravel
- **Vite** - Herramienta de construcción y bundler de assets
- **Tailwind CSS 4.0** - Framework CSS utility-first
- **JavaScript (Vanilla)** - Lenguaje para interactividad del cliente
- **Font Awesome** - Biblioteca de iconos

### Testing y Calidad de Código
- **PHPUnit 11.5** - Framework de testing unitario
- **SonarQube** - Plataforma de análisis estático de calidad de código
- **Laravel Pint** - Herramienta de formateo de código

## Requisitos del Sistema

### Requisitos Mínimos
- PHP >= 8.2
- Composer (gestor de dependencias de PHP)
- Node.js >= 18.x y npm
- MySQL >= 8.0 o MariaDB >= 10.3

### Extensiones PHP Requeridas
- BCMath
- Ctype
- Fileinfo
- JSON
- Mbstring
- OpenSSL
- PDO
- Tokenizer
- XML

## Instalación

### Paso 1: Clonar el Repositorio

```bash
git clone <url-del-repositorio>
cd Pro_Audio_Y
```

### Paso 2: Instalar Dependencias de PHP

```bash
composer install
```

### Paso 3: Instalar Dependencias de Node.js

```bash
npm install
```

### Paso 4: Configurar Variables de Entorno

```bash
cp .env.example .env
php artisan key:generate
```

Editar el archivo `.env` con las configuraciones correspondientes a la base de datos:

```env
DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3005
DB_DATABASE=pro_audio
DB_USERNAME=tu_usuario
DB_PASSWORD=tu_contraseña
```

### Paso 5: Ejecutar Migraciones y Seeders

```bash
php artisan migrate
php artisan db:seed
```

### Paso 6: Compilar Assets

Para entorno de desarrollo:

```bash
npm run dev
```

Para entorno de producción:

```bash
npm run build
```

### Paso 7: Iniciar Servidor de Desarrollo

```bash
php artisan serve
```

Alternativamente, utilizar el script de desarrollo completo:

```bash
composer dev
```

La aplicación estará disponible en `http://localhost:8000`

## Configuración Adicional

### Configurar Enlaces Simbólicos de Almacenamiento

```bash
php artisan storage:link
```

### Limpieza de Caché

En caso de requerir limpieza de caché:

```bash
php artisan config:clear
php artisan cache:clear
php artisan view:clear
php artisan route:clear
```

### Generación de Reportes para SonarQube

```powershell
.\generate-sonar-reports.ps1
```

## Usuarios por Defecto

Tras la ejecución de los seeders, se crean los siguientes tipos de usuario:

- **Superadmin**: Usuario con permisos completos sobre el sistema
- **Admin**: Usuario con permisos administrativos limitados
- **Cliente**: Usuario con permisos de acceso restringidos
- **Invitado**: Usuario sin autenticación

Las credenciales de acceso se encuentran definidas en los archivos seeders correspondientes.

## Testing

### Ejecución de Tests Unitarios

```bash
php artisan test
```

Alternativamente, utilizando PHPUnit directamente:

```bash
vendor/bin/phpunit
```

### Ejecución de Tests con Cobertura de Código

```bash
php artisan test --coverage
```

### Generación de Reportes de Cobertura para SonarQube

```powershell
.\generate-sonar-reports.ps1
```

Los reportes se generan en el directorio `tests/_coverage/`

## Estructura del Proyecto

```
Pro_Audio_Y/
├── app/
│   ├── Exceptions/          # Excepciones personalizadas del sistema
│   ├── Http/
│   │   ├── Controllers/     # Controladores de la aplicación
│   │   └── Middleware/      # Middleware de autenticación y autorización
│   ├── Models/              # Modelos Eloquent ORM
│   ├── Providers/           # Service Providers de Laravel
│   └── Services/            # Servicios de lógica de negocio
├── database/
│   ├── migrations/          # Migraciones de esquema de base de datos
│   ├── seeders/             # Seeders para datos iniciales
│   └── factories/           # Factories para generación de datos de prueba
├── resources/
│   ├── views/               # Plantillas Blade
│   ├── css/                 # Archivos de estilos CSS
│   └── js/                  # Archivos JavaScript
├── routes/
│   └── web.php              # Definición de rutas web
├── tests/
│   ├── Unit/                # Tests unitarios
│   └── Feature/             # Tests de funcionalidad
└── public/                  # Archivos públicos accesibles vía web
```

## Sistema de Roles y Permisos

El sistema implementa gestión de roles mediante Spatie Laravel Permission con los siguientes niveles de acceso:

- **Superadmin**: Acceso completo y sin restricciones al sistema
- **Admin**: Permisos para gestión de servicios, inventario, calendario y reservas
- **Usuario**: Permisos de visualización de servicios y acceso al módulo de chatbot
- **Cliente**: Acceso limitado a servicios y funcionalidad de búsqueda
- **Invitado**: Acceso únicamente a visualización básica de contenido público

## Funcionalidades por Módulo

### Módulo de Servicios
- Creación, edición y eliminación de servicios
- Asignación de iconos e imágenes a servicios
- Gestión de subservicios asociados
- Visualización pública de catálogo de servicios

### Módulo de Inventario
- Gestión de equipos y control de stock
- Registro de movimientos de inventario (entradas y salidas)
- Validación de disponibilidad de equipos
- Control de niveles mínimos de stock

### Módulo de Calendario
- Vista mensual de eventos programados
- Creación y edición de eventos
- Asociación de items a eventos del calendario
- Validación de fechas y disponibilidad de recursos

### Módulo de Reservas
- Creación de reservas en estado pendiente
- Proceso de confirmación de reservas
- Asociación de reservas con elementos de inventario
- Control de stock durante el proceso de reserva

### Módulo de Chatbot
- Procesamiento de lenguaje natural
- Detección automática de intenciones del usuario
- Generación de sugerencias de servicios
- Gestión de contexto de conversación

## Solución de Problemas

### Error de Permisos en Directorio Storage

```bash
chmod -R 775 storage bootstrap/cache
```

### Error de Conexión a Base de Datos

Verificar que las credenciales configuradas en el archivo `.env` sean correctas y que la base de datos especificada exista en el servidor.

### Assets No Se Cargan Correctamente

Ejecutar los siguientes comandos:

```bash
npm run build
php artisan view:clear
```

## Licencia

Este proyecto está bajo la Licencia MIT. Para más detalles, consultar el archivo `LICENSE`.

## Contribución

Las contribuciones al proyecto son bienvenidas. Para contribuir, seguir el siguiente proceso:

1. Realizar un fork del proyecto
2. Crear una rama para la nueva funcionalidad (`git checkout -b feature/NuevaFuncionalidad`)
3. Realizar commit de los cambios (`git commit -m 'Agregar nueva funcionalidad'`)
4. Subir los cambios a la rama (`git push origin feature/NuevaFuncionalidad`)
5. Abrir un Pull Request

## Soporte

Para solicitar soporte técnico, abrir un issue en el repositorio del proyecto o contactar al equipo de desarrollo.

## Agradecimientos

- Laravel Framework por proporcionar la base del sistema
- Spatie por el paquete de gestión de permisos
- Comunidad de desarrolladores de código abierto

---

**Desarrollado para PRO AUDIO**
