# Dockerización del Proyecto ProAudio

Este proyecto está dockerizado en 3 contenedores:

## Contenedores

1. **Backend** - Laravel/PHP 8.4 con Nginx (`pro-audio-backend`)
2. **Frontend** - Nginx sirviendo assets compilados (`pro-audio-frontend`)
3. **Database** - MySQL 8.0 (`pro-audio-db`)

## Requisitos Previos

- Docker
- Docker Compose

## Configuración

1. **Crear archivo `.env`** basado en las siguientes variables:

```env
APP_NAME=ProAudio
APP_ENV=production
APP_KEY=
APP_DEBUG=false
APP_URL=http://localhost:8001

DB_CONNECTION=mysql
DB_HOST=database
DB_PORT=3306
DB_DATABASE=proaudio
DB_USERNAME=proaudio
DB_PASSWORD=root

APP_PORT=8001
FRONTEND_PORT=3001
DB_PORT=3005
```

2. **Generar la clave de aplicación** (si no existe):
```bash
php artisan key:generate
```

## Uso

### Construir y levantar los contenedores

```bash
docker-compose up -d --build
```

### Ver logs

```bash
# Todos los contenedores
docker-compose logs -f

# Contenedor específico
docker-compose logs -f backend
docker-compose logs -f frontend
```

### Ejecutar comandos en el contenedor backend

```bash
# Ejecutar migraciones
docker-compose exec backend php artisan migrate

# Ejecutar seeders
docker-compose exec backend php artisan db:seed

# Acceder a la shell del contenedor
docker-compose exec backend bash
```

### Detener los contenedores

```bash
docker-compose down
```

### Detener y eliminar volúmenes (incluyendo la base de datos)

```bash
docker-compose down -v
```

## Puertos

- **Backend**: http://localhost:8001
- **Frontend**: http://localhost:3001
- **Database**: http://localhost:3005 (mapeado como 3005:3306)

## Estructura de Archivos Docker

```
.
├── Dockerfile.backend          # Dockerfile para el backend Laravel
├── Dockerfile.frontend          # Dockerfile para el frontend
├── docker-compose.yml           # Orquestación de contenedores
├── .dockerignore               # Archivos a ignorar en el build
└── docker/
    ├── backend/
    │   └── start.sh            # Script de inicio del backend
    ├── nginx/
    │   ├── nginx.conf          # Configuración Nginx para backend
    │   └── frontend.conf       # Configuración Nginx para frontend
    └── mysql/
        └── init.sql            # Script de inicialización de MySQL
```

## Notas Importantes

1. **Base de datos propia**: Este proyecto tiene su propio contenedor de MySQL (`pro-audio-db`) con puerto 3005
2. El backend espera a que MySQL (`database`) esté listo antes de iniciar
3. Las migraciones se ejecutan automáticamente al iniciar el backend
4. Los assets del frontend se compilan durante el build del contenedor
5. Los nombres de los contenedores coinciden con la project-key de SonarQube: `pro-audio`
6. Los puertos están configurados para evitar conflictos con otros servicios (8001 y 3001)

