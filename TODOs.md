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

- [x] Instalar autenticacion Laravel adecuada para Blade/Livewire.
- [x] Crear enum `UserRole` con `admin` y `client`.
- [x] Anadir campo `role` a `users`.
- [x] Crear middleware para rutas admin.
- [x] Crear middleware para rutas cliente.
- [x] Configurar redireccion post-login por rol.
- [x] Crear layout admin.
- [x] Crear layout cliente.
- [x] Crear layout auth.
- [x] Crear sidebar admin.
- [x] Crear sidebar cliente.
- [x] Crear `/admin/dashboard`.
- [x] Crear `/portal/dashboard`.
- [x] Proteger rutas para que cada rol solo acceda a su zona.

## Sprint 2: clientes y proyectos

- [x] Crear migracion `clients`.
- [x] Crear migracion `client_invitations`.
- [x] Crear migracion `projects`.
- [x] Crear modelos y relaciones principales.
- [x] Crear CRUD clientes.
- [x] Crear flujo de invitacion cliente.
- [x] Crear CRUD proyectos.
- [x] Asociar proyectos a clientes.
- [x] Mostrar proyectos en dashboard admin.
- [x] Mostrar proyectos visibles en dashboard cliente.

## Sprint 3: comunicacion basica

- [x] Crear migracion `project_updates`.
- [x] Crear timeline admin.
- [x] Crear timeline cliente.
- [x] Implementar visibilidad publica/interna.
- [x] Preparar notificacion email simple.

## Sprint 4: diario visual

- [x] Crear migracion `visual_entries`.
- [x] Implementar subida de imagen/video/audio.
- [x] Crear diario visual admin.
- [x] Crear diario visual cliente.
- [x] Crear vista detalle de media.

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
