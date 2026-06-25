# ClientFlow

ClientFlow es una web app open source para freelancers y agencias pequenas que quieran gestionar clientes, proyectos y comunicacion desde un solo lugar.

## Propuesta de valor

- El cliente puede estar informado del estado de su proyecto en todo momento.
- Se elimina la dependencia de emails, WhatsApps y tools externas para mensajeria.
- Conexion con entornos de desarrollo via MCP para acceder a documentacion del proyecto.
- Base de datos de agentes IA reutilizables.

## Funcionalidades principales

- Organizations con miembros y proyectos
- Kanban vitaminado (prioridad, estimacion, subtareas, deadlines)
- Documentacion privada/publica en markdown
- Chat por proyecto (admin ↔ clientes)
- MCP server (solo lectura, para conectar desde IDEs)
- Asistente IA para clientes (configurable: OpenAI, Anthropic)
- Calendario de reuniones y deadlines
- Templates de agentes IA
- PWA

## Stack

- Laravel 13
- PHP 8.3+
- Livewire 4
- Blade
- Tailwind CSS 4
- Vite
- MySQL 8.4
- Docker para desarrollo local

## Estructura

```txt
.
├── app/              # Aplicacion Laravel
├── docs/             # Documentacion de producto y arquitectura
├── .agents/          # Agentes IA de apoyo al desarrollo
├── TODOs.md          # Lista de tareas del proyecto
└── README.md         # Este documento
```

## Documentacion

```txt
docs/
├── PRD.md              # Product Requirements Document
├── ARCHITECTURE.md     # Arquitectura tecnica
├── DATA_MODEL.md       # Modelo de datos
├── DESIGN.md           # Design system y guias visuales
├── USER_FLOWS.md       # Flujos de usuario y pantallas
├── IMPLEMENTATION.md   # Convenciones y patrones de codigo
└── CLEANUP.md           # Instrucciones de reset del proyecto
```

## Arranque con Docker

```bash
cd app
cp .env.example .env
docker compose up -d --build
docker compose exec app composer install
docker compose exec app php artisan key:generate
docker compose exec app php artisan migrate
```

Servicios locales:

- Aplicacion: `http://localhost:8080`
- Vite: `http://localhost:5173`
- MySQL local: `127.0.0.1:3307`

## Arranque sin Docker

```bash
cd app
composer install
npm install
cp .env.example .env
php artisan key:generate
php artisan migrate
composer run dev
```

## Licencia

MIT.

## PROMPT comienzo siempre
Tienes estos agents disponibles @.opencode/agents/ y esta es la información del proyecto @docs necesito que hagas la primera fase de @TODOs.md 

Una vez terminada la implementación de la funcionalidad debe redactarse en @docs/tasks/ , el código debe estar perfectamente comentado y entendible para cualquier persona sin conocimientos tecnicos así como usar phpdoc



