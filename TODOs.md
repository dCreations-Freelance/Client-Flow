# TODOs ClientFlow

## Estado actual

- Proyecto Laravel creado directamente en `app`.
- Livewire instalado.
- Tailwind/Vite presente desde el esqueleto Laravel.
- Docker local propio creado sin Sail.
- Documentacion de agentes IA creada en `.agents`.

## Entorno local

- [ ] Ejecutar `cp .env.example .env` dentro de `app` si se clona desde cero.
- [ ] Ejecutar `docker compose up -d --build` dentro de `app`.
- [ ] Ejecutar `docker compose exec app composer install`.
- [ ] Ejecutar `docker compose exec app php artisan key:generate`.
- [ ] Ejecutar `docker compose exec app php artisan migrate`.
- [ ] Ejecutar `docker compose exec app php artisan test`.
- [ ] Ejecutar `docker compose exec node npm run build` o `npm run build` localmente.

## Sprint 1: base

- [ ] Instalar autenticacion Laravel adecuada para Blade/Livewire.
- [ ] Crear enum `UserRole` con `admin` y `client`.
- [ ] Anadir campo `role` a `users`.
- [ ] Crear middleware para rutas admin.
- [ ] Crear middleware para rutas cliente.
- [ ] Configurar redireccion post-login por rol.
- [ ] Crear layout admin.
- [ ] Crear layout cliente.
- [ ] Crear layout auth.
- [ ] Crear sidebar admin.
- [ ] Crear sidebar cliente.
- [ ] Crear `/admin/dashboard`.
- [ ] Crear `/portal/dashboard`.
- [ ] Proteger rutas para que cada rol solo acceda a su zona.

## Sprint 2: clientes y proyectos

- [ ] Crear migracion `clients`.
- [ ] Crear migracion `client_invitations`.
- [ ] Crear migracion `projects`.
- [ ] Crear modelos y relaciones principales.
- [ ] Crear CRUD clientes.
- [ ] Crear flujo de invitacion cliente.
- [ ] Crear CRUD proyectos.
- [ ] Asociar proyectos a clientes.
- [ ] Mostrar proyectos en dashboard admin.
- [ ] Mostrar proyectos visibles en dashboard cliente.

## Sprint 3: comunicacion basica

- [ ] Crear migracion `project_updates`.
- [ ] Crear timeline admin.
- [ ] Crear timeline cliente.
- [ ] Implementar visibilidad publica/interna.
- [ ] Preparar notificacion email simple.

## Sprint 4: diario visual

- [ ] Crear migracion `visual_entries`.
- [ ] Implementar subida de imagen/video/audio.
- [ ] Crear diario visual admin.
- [ ] Crear diario visual cliente.
- [ ] Crear vista detalle de media.

## Sprint 5: entregables

- [ ] Crear migracion `deliverables`.
- [ ] Crear migracion `deliverable_files`.
- [ ] Crear flujo enviar a revision.
- [ ] Crear vista cliente de entregable.
- [ ] Implementar aprobar entregable.
- [ ] Implementar solicitar cambios.
- [ ] Registrar actividad relevante.

## Sprint 6: IA asistida

- [ ] Crear `AiService`.
- [ ] Configurar webhook n8n opcional.
- [ ] Crear centro IA.
- [ ] Generar resumen para cliente.
- [ ] Reescribir actualizacion en lenguaje no tecnico.
- [ ] Guardar historial en `ai_generations`.

## Criterios generales

- [ ] Mantener compatibilidad con hosting compartido.
- [ ] No introducir Redis obligatorio.
- [ ] No introducir workers permanentes.
- [ ] No publicar contenido IA automaticamente.
- [ ] Proteger todos los archivos privados mediante autorizacion.
