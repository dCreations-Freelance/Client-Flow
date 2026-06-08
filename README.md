# ClientFlow

ClientFlow es un portal open source para freelancers, consultores y pequeñas agencias que quieren ofrecer a sus clientes una experiencia premium de seguimiento de proyectos.

No está pensado como otro gestor de tareas interno. Su objetivo es crear un espacio privado donde cada cliente pueda ver el estado real de su proyecto, consultar avances, descargar documentos, comentar, revisar entregables y aprobar hitos importantes.

## Estado del proyecto

El proyecto está en fase inicial de MVP. Actualmente incluye la base de la aplicación Laravel, autenticación, roles de usuario y dashboards separados para administración y portal de cliente.

## Funcionalidades previstas

- Portal privado para clientes.
- Dashboard de proyectos.
- Estado visual de progreso.
- Timeline de actividad.
- Diario visual con vídeos, capturas, audios y notas.
- Documentos centralizados.
- Entregables con aprobación.
- Comentarios por proyecto.
- Notificaciones por email.
- Centro IA para resumir y reescribir avances.
- Integración opcional con n8n/Gemini mediante webhooks.
- Panel de administración.

## Stack

- Laravel 13.
- PHP 8.3+.
- Livewire 4.
- Blade.
- Tailwind CSS 4.
- Vite.
- MySQL 8.4.
- Docker opcional para desarrollo local.

## Estructura

```txt
.
├── app/              # Aplicación Laravel
├── docs/             # Documentación de producto, arquitectura y diseño
├── TODOs.md          # Lista de tareas del proyecto
└── README.md         # Este documento
```

## Arranque con Docker

El entorno Docker está definido dentro de `app/` y no usa Laravel Sail.

```bash
cd app
cp .env.example .env
docker compose up -d --build
docker compose exec app composer install
docker compose exec app php artisan key:generate
docker compose exec app php artisan migrate
```

Servicios locales:

- Aplicación: `http://localhost:8080`
- Vite: `http://localhost:5173`
- MySQL local: `127.0.0.1:3307`

Credenciales de base de datos del entorno Docker:

```txt
DB_CONNECTION=mysql
DB_HOST=mysql
DB_PORT=3306
DB_DATABASE=clientflow
DB_USERNAME=clientflow
DB_PASSWORD=clientflow
```

## Arranque sin Docker

Requisitos:

- PHP 8.3 o superior.
- Composer.
- Node.js.
- MySQL.

Instalación:

```bash
cd app
composer install
npm install
cp .env.example .env
php artisan key:generate
php artisan migrate
```

Desarrollo:

```bash
composer run dev
```

También puedes levantar Laravel y Vite por separado:

```bash
php artisan serve
npm run dev
```

## Comandos útiles

Desde `app/`:

```bash
composer run test
npm run build
php artisan migrate:fresh
php artisan route:list
```

Con Docker:

```bash
docker compose exec app php artisan test
docker compose exec app php artisan migrate:fresh
docker compose logs -f app
```

## Rutas actuales

- `/`: landing inicial o redirección por rol si el usuario ya inició sesión.
- `/login`: inicio de sesión.
- `/register`: registro.
- `/admin/dashboard`: dashboard de administración.
- `/portal/dashboard`: dashboard de cliente.

## Roles

La aplicación usa dos roles principales:

- `admin`: gestiona clientes, proyectos, contenidos y entregables.
- `client`: accede al portal privado de cliente.

## Documentación

La documentación de producto está en `docs/`:

```txt
docs/docs/PRD_V2.md
docs/docs/USER_FLOW_MASTER.md
docs/docs/DESIGN.md
docs/docs/WIREFRAMES_DESKTOP.md
docs/docs/ARCHITECTURE_V2.md
docs/docs/IMPLEMENTATION_STARTER.md
```

También hay una guía específica del entorno Docker en `app/docker/README.md`.

## Filosofía

ClientFlow existe para reducir ansiedad, mejorar comunicación y aumentar percepción de profesionalidad. Una buena plataforma de cliente no debe obligar al cliente a gestionar el proyecto. Debe darle tranquilidad.

## Licencia

MIT.
