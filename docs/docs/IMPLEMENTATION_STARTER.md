# IMPLEMENTATION_STARTER.md — Plan práctico para empezar desarrollo

## Orden recomendado

No empezar por IA. Orden: Laravel, auth, roles, layouts, clientes, proyectos, timeline, documentos, diario visual, entregables, comentarios, notificaciones e IA.

## Sprint 1: base

- Instalar Laravel.
- Instalar Livewire.
- Configurar Tailwind.
- Crear auth.
- Crear enum de roles.
- Crear middleware admin/client.
- Crear layouts separados: admin, client y auth.
- Crear sidebar admin.
- Crear sidebar cliente.
- Crear dashboard admin vacío.
- Crear dashboard cliente vacío.

Criterio: admin entra en /admin/dashboard, cliente entra en /portal/dashboard, cada rol tiene layout diferente y usuario sin permisos no accede a rutas ajenas.

## Sprint 2: clientes y proyectos

- Migraciones users, clients, client_invitations y projects.
- CRUD clientes.
- Crear cliente manual.
- Invitar cliente.
- CRUD proyectos.
- Asociar proyecto a cliente.
- Mostrar proyectos en dashboard admin.
- Mostrar proyectos en dashboard cliente.

## Sprint 3: comunicación básica

- Migración project_updates.
- Crear actualización.
- Timeline admin.
- Timeline cliente.
- Visibilidad pública/interna.
- Notificación email simple.

## Sprint 4: valor diferencial

- Migración visual_entries.
- Subida de vídeo/imagen/audio.
- Diario visual admin.
- Diario visual cliente.
- Vista detalle media.

## Sprint 5: entregables

- Migración deliverables.
- Migración deliverable_files.
- Crear entregable.
- Enviar a revisión.
- Vista cliente.
- Aprobar.
- Solicitar cambios.
- Registrar actividad.

## Sprint 6: IA

- Crear AiService.
- Crear configuración webhook n8n.
- Crear centro IA.
- Generar resumen.
- Reescribir para cliente.
- Crear actualización desde resultado IA.
- Guardar historial.

## Comandos iniciales

```bash
composer create-project laravel/laravel clientflow
cd clientflow
composer require livewire/livewire
npm install
npm run dev
php artisan migrate
```

## Reglas para Codex

Darle siempre PRD_V2.md, ARCHITECTURE_V2.md, DESIGN.md, USER_FLOW_MASTER.md y el wireframe exacto de la pantalla.

No pedir “haz el panel entero”. Pedir por módulo: layout admin, CRUD clientes, dashboard cliente, diario visual, etc.
