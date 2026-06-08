# ARCHITECTURE_V2.md — Arquitectura técnica inicial

## Decisión principal

ClientFlow se construirá como monolito Laravel optimizado para hosting compartido. No se asume VPS, Redis, workers permanentes ni Docker en producción.

Stack: PHP 8.3/8.4, Laravel 11/12, Blade, Livewire, Alpine.js, Tailwind CSS, MySQL, Storage local, SMTP, cron para scheduler, n8n + Gemini mediante webhooks.

## Principios técnicos

Monolito primero, sin microservicios MVP, sin colas obligatorias, sin procesos residentes, compatible con Hostinger compartido, separación por módulos, open source fácil de instalar e IA desacoplada.

## Estructura Laravel

```txt
app/
├── Actions/
├── DTOs/
├── Enums/
├── Http/
│   ├── Controllers/
│   │   ├── Admin/
│   │   ├── Client/
│   │   └── Auth/
│   ├── Middleware/
│   └── Requests/
├── Livewire/
│   ├── Admin/
│   ├── Client/
│   └── Shared/
├── Models/
├── Policies/
├── Services/
│   ├── Ai/
│   ├── Notifications/
│   ├── Projects/
│   └── Files/
└── ViewModels/
```

## Rutas

```txt
/
├── login
├── register
├── invitation/{token}
├── password/forgot
├── password/reset/{token}

admin/
├── dashboard
├── clients
├── clients/create
├── clients/invite
├── clients/{client}
├── projects
├── projects/create
├── projects/{project}
├── projects/{project}/timeline
├── projects/{project}/updates/create
├── visual-diary
├── documents
├── deliverables
├── comments
├── ai
├── activity
└── settings

portal/
├── dashboard
├── projects
├── projects/{project}
├── projects/{project}/timeline
├── projects/{project}/visual-diary
├── projects/{project}/documents
├── projects/{project}/deliverables
├── deliverables/{deliverable}
├── comments
├── notifications
└── profile
```

## Tablas principales

### users

id, name, email, password, role, status, last_login_at, email_verified_at, timestamps.

### clients

id, user_id nullable, name, company, email, phone, notes, status, invitation_status, timestamps.

### client_invitations

id, client_id, email, token, expires_at, accepted_at, created_by, timestamps.

### projects

id, client_id, name, slug, description, goal, status, progress, current_phase, next_milestone, starts_at, estimated_ends_at, cover_path, is_visible_to_client, archived_at, timestamps.

### project_updates

id, project_id, author_id, title, content, type, visibility, notify_client, published_at, timestamps.

### visual_entries

id, project_id, author_id, title, description, type, media_path, thumbnail_path, duration, visibility, published_at, timestamps.

### documents

id, project_id, uploaded_by, title, description, category, file_path, file_name, file_size, mime_type, visibility, timestamps.

### deliverables

id, project_id, created_by, title, description, status, requires_approval, review_due_at, sent_at, approved_at, closed_at, timestamps.

### deliverable_files

id, deliverable_id, document_id nullable, file_path nullable, file_name, mime_type, timestamps.

### comments

id, project_id, commentable_type nullable, commentable_id nullable, user_id, parent_id nullable, content, status, timestamps.

### notifications

id, user_id, type, title, body, data json, read_at nullable, timestamps.

### ai_generations

id, user_id, project_id nullable, type, input, output, provider, status, timestamps.

### activity_logs

id, user_id nullable, project_id nullable, client_id nullable, event, description, data json, timestamps.

## Permisos

Admin ve todo. Cliente solo ve proyectos asociados a su client_id, actualizaciones públicas, entradas visuales públicas, documentos públicos, entregables de sus proyectos y comentarios de sus proyectos.

Policies: ProjectPolicy, DocumentPolicy, DeliverablePolicy, CommentPolicy, VisualEntryPolicy.

## Servicios

AiService, FileService, ActivityService y NotificationService.

## IA con n8n

```txt
Laravel → webhook n8n → Gemini → n8n limpia respuesta → Laravel guarda generación
```

La IA devuelve borradores, nunca publica sola en MVP.

## Hosting compartido

Apuntar dominio a /public, configurar .env, ejecutar migraciones por SSH y cron:

```bash
* * * * * php /home/user/project/artisan schedule:run >> /dev/null 2>&1
```

Sin queue:work permanente. MVP usa queue sync o envío directo.

## Storage

```txt
storage/app/clientflow/
├── projects/{project_id}/documents/
├── projects/{project_id}/visual/
├── projects/{project_id}/deliverables/
└── avatars/
```

Los archivos privados se sirven mediante controlador con autorización.
