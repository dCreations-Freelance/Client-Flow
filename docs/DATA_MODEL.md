# DATA_MODEL.md — Modelo de datos de ClientFlow MVP

## Diagrama de relaciones

```txt
User (admin/client)
 ├─── OrganizationUser (pivot) ──── Organization
 │                                      │
 │                                      ├── Project ──────── BoardColumn ──── Task
 │                                      │                       │                  │
 │                                      │                       │            (self-ref: parent_id)
 │                                      │                       │                  │
 │                                      │                       │            ├── TaskAttachment
 │                                      │                       │            ├── TimeEntry
 │                                      │                       │            └── ActivityLog
 │                                      │                       │
 │                                      │                       ├── ProjectDocument
 │                                      │                       ├── ProjectMessage ──── MessageAttachment
 │                                      │                       │                  └── MessageRead (pivot)
 │                                      │                       ├── CalendarEvent
 │                                      │                       │       └── CalendarEventUser (pivot)
 │                                      │                       ├── ProjectAgent (pivot)
 │                                      │                       │       └── AgentTemplate
 │                                      │                       └── ActivityLog
 │                                      │
 │                                      └── OrganizationInvitation
 │
 └── ProjectTemplate
      ├── ProjectTemplateColumn
      ├── ProjectTemplateTask
      └── ProjectTemplateDocument

AiConfig (global o por proyecto)
AiChatSession ──── AiChatMessage
```

## Tablas

### users

| Columna | Tipo | Notas |
|---|---|---|
| id | bigint | PK |
| name | string | |
| email | string | unique |
| password | string | |
| role | enum(admin, client) | default: client, indexed |
| email_verified_at | timestamp | nullable |
| created_at | timestamp | |
| updated_at | timestamp | |

### organizations

| Columna | Tipo | Notas |
|---|---|---|
| id | bigint | PK |
| name | string | |
| slug | string | unique |
| description | text | nullable |
| logo_path | string | nullable |
| owner_id | bigint FK | users.id, el admin creador |
| status | enum(active, inactive) | default: active, indexed |
| created_at | timestamp | |
| updated_at | timestamp | |

### organization_user (pivot)

| Columna | Tipo | Notas |
|---|---|---|
| id | bigint | PK |
| organization_id | bigint FK | organizations.id, cascadeOnDelete |
| user_id | bigint FK | users.id, cascadeOnDelete |
| role | enum(owner, member) | default: member |
| created_at | timestamp | |
| updated_at | timestamp | |

Unique constraint: (organization_id, user_id)

### organization_invitations

| Columna | Tipo | Notas |
|---|---|---|
| id | bigint | PK |
| organization_id | bigint FK | organizations.id, cascadeOnDelete |
| email | string | |
| token | string | unique |
| role | enum(owner, member) | default: member |
| expires_at | timestamp | |
| accepted_at | timestamp | nullable |
| created_by | bigint FK | users.id, cascadeOnDelete |
| created_at | timestamp | |
| updated_at | timestamp | |

### projects

| Columna | Tipo | Notas |
|---|---|---|
| id | bigint | PK |
| organization_id | bigint FK | organizations.id, cascadeOnDelete |
| name | string | |
| slug | string | unique |
| description | text | nullable |
| status | enum(planning, in_progress, on_hold, waiting_client, completed, archived) | default: planning, indexed |
| progress | tinyint unsigned | default: 0 |
| starts_at | date | nullable |
| estimated_ends_at | date | nullable |
| cover_path | string | nullable |
| is_visible_to_client | boolean | default: true, indexed |
| archived_at | timestamp | nullable |
| created_at | timestamp | |
| updated_at | timestamp | |

### project_user (pivot)

| Columna | Tipo | Notas |
|---|---|---|
| id | bigint | PK |
| project_id | bigint FK | projects.id, cascadeOnDelete |
| user_id | bigint FK | users.id, cascadeOnDelete |
| created_at | timestamp | |
| updated_at | timestamp | |

Unique constraint: (project_id, user_id)

### board_columns

| Columna | Tipo | Notas |
|---|---|---|
| id | bigint | PK |
| project_id | bigint FK | projects.id, cascadeOnDelete |
| name | string | |
| slug | string | |
| color | string | nullable, hex color |
| position | integer | orden de las columnas |
| is_default | boolean | default: false |
| created_at | timestamp | |
| updated_at | timestamp | |

### tasks

| Columna | Tipo | Notas |
|---|---|---|
| id | bigint | PK |
| project_id | bigint FK | projects.id, cascadeOnDelete |
| column_id | bigint FK | board_columns.id, cascadeOnDelete |
| parent_id | bigint FK | tasks.id, nullable (subtareas) |
| title | string | |
| description | text | nullable |
| priority | enum(critical, high, medium, low) | default: medium, indexed |
| type | enum(feature, bug, improvement, task) | default: task, indexed |
| estimated_hours | decimal(6,2) | nullable |
| actual_hours | decimal(6,2) | nullable |
| due_date | date | nullable |
| position | integer | orden dentro de la columna |
| assignee_id | bigint FK | users.id, nullable |
| completed_at | timestamp | nullable |
| created_by | bigint FK | users.id |
| created_at | timestamp | |
| updated_at | timestamp | |

### project_documents

| Columna | Tipo | Notas |
|---|---|---|
| id | bigint | PK |
| project_id | bigint FK | projects.id, cascadeOnDelete |
| title | string | |
| content | longtext | markdown |
| visibility | enum(private, public) | default: private, indexed |
| created_by | bigint FK | users.id |
| created_at | timestamp | |
| updated_at | timestamp | |

Los documentos `private` solo son visibles por admin (y accesibles via MCP). Los `public` son visibles por clientes del proyecto.

### project_messages

| Columna | Tipo | Notas |
|---|---|---|
| id | bigint | PK |
| project_id | bigint FK | projects.id, cascadeOnDelete |
| user_id | bigint FK | users.id |
| content | text | |
| type | enum(text, system, file) | default: text |
| created_at | timestamp | |
| updated_at | timestamp | |

Los mensajes de tipo `system` se generan automaticamente (tarea creada, estado cambiado, etc.).

### calendar_events

| Columna | Tipo | Notas |
|---|---|---|
| id | bigint | PK |
| project_id | bigint FK | projects.id, cascadeOnDelete, nullable |
| title | string | |
| description | text | nullable |
| type | enum(meeting, deadline, milestone) | default: meeting |
| starts_at | datetime | |
| ends_at | datetime | nullable |
| created_by | bigint FK | users.id |
| created_at | timestamp | |
| updated_at | timestamp | |

### calendar_event_user (pivot: asistentes)

| Columna | Tipo | Notas |
|---|---|---|
| id | bigint | PK |
| calendar_event_id | bigint FK | calendar_events.id, cascadeOnDelete |
| user_id | bigint FK | users.id, cascadeOnDelete |
| created_at | timestamp | |

### task_attachments

| Columna | Tipo | Notas |
|---|---|---|
| id | bigint | PK |
| task_id | bigint FK | tasks.id, cascadeOnDelete |
| user_id | bigint FK | users.id |
| filename | string | nombre interno en disco |
| original_name | string | nombre original del archivo |
| mime_type | string | image/png, application/pdf, etc. |
| size | integer | en bytes |
| created_at | timestamp | |

### message_attachments

| Columna | Tipo | Notas |
|---|---|---|
| id | bigint | PK |
| message_id | bigint FK | project_messages.id, cascadeOnDelete |
| user_id | bigint FK | users.id |
| filename | string | nombre interno en disco |
| original_name | string | nombre original del archivo |
| mime_type | string | |
| size | integer | en bytes |
| created_at | timestamp | |

### time_entries

| Columna | Tipo | Notas |
|---|---|---|
| id | bigint | PK |
| task_id | bigint FK | tasks.id, cascadeOnDelete |
| user_id | bigint FK | users.id |
| project_id | bigint FK | projects.id, cascadeOnDelete |
| description | text | nullable |
| type | enum(manual, timer) | default: manual |
| minutes | integer | duracion en minutos |
| started_at | timestamp | nullable, solo para tipo timer |
| billed | boolean | default: false, facturable |
| created_at | timestamp | |
| updated_at | timestamp | |

### message_reads (pivot)

| Columna | Tipo | Notas |
|---|---|---|
| message_id | bigint FK | project_messages.id, cascadeOnDelete |
| user_id | bigint FK | users.id, cascadeOnDelete |
| read_at | timestamp | |

Primary key compuesto: (message_id, user_id)

### project_templates

| Columna | Tipo | Notas |
|---|---|---|
| id | bigint | PK |
| name | string | |
| slug | string | unique |
| description | text | nullable |
| category | string | nullable, e.g. "web", "mobile", "design" |
| default_status | enum(planning, in_progress) | default: planning |
| created_by | bigint FK | users.id |
| created_at | timestamp | |
| updated_at | timestamp | |

### project_template_columns

| Columna | Tipo | Notas |
|---|---|---|
| id | bigint | PK |
| template_id | bigint FK | project_templates.id, cascadeOnDelete |
| name | string | |
| color | string | nullable, hex color |
| position | integer | |

### project_template_tasks

| Columna | Tipo | Notas |
|---|---|---|
| id | bigint | PK |
| template_id | bigint FK | project_templates.id, cascadeOnDelete |
| column_slug | string | referencia a columna por slug |
| title | string | |
| description | text | nullable |
| type | enum(feature, bug, improvement, task) | default: task |
| priority | enum(critical, high, medium, low) | default: medium |
| estimated_hours | decimal(6,2) | nullable |
| position | integer | |

### project_template_documents

| Columna | Tipo | Notas |
|---|---|---|
| id | bigint | PK |
| template_id | bigint FK | project_templates.id, cascadeOnDelete |
| title | string | |
| content | longtext | markdown |
| visibility | enum(private, public) | default: private |
| position | integer | |

### activity_log

| Columna | Tipo | Notas |
|---|---|---|
| id | bigint | PK |
| project_id | bigint FK | projects.id, cascadeOnDelete, nullable |
| organization_id | bigint FK | organizations.id, nullable |
| user_id | bigint FK | users.id, nullable (eventos de sistema) |
| description | string | texto legible: "Se creo la tarea X" |
| type | string | task_created, task_completed, document_created, status_changed, etc. |
| properties | json | nullable, datos adicionales del evento |
| created_at | timestamp | |

### agent_templates

| Columna | Tipo | Notas |
|---|---|---|
| id | bigint | PK |
| name | string | e.g. "Arquitecto Backend" |
| description | text | nullable |
| system_prompt | longtext | |
| tools | json | nullable, herramientas configuradas |
| model | string | nullable, modelo preferido |
| category | string | nullable, e.g. "development", "architecture" |
| created_by | bigint FK | users.id |
| created_at | timestamp | |
| updated_at | timestamp | |

### project_agents (pivot)

| Columna | Tipo | Notas |
|---|---|---|
| id | bigint | PK |
| project_id | bigint FK | projects.id, cascadeOnDelete |
| agent_template_id | bigint FK | agent_templates.id, cascadeOnDelete |
| system_prompt_override | longtext | nullable, personalizaciones del proyecto |
| created_at | timestamp | |
| updated_at | timestamp | |

### ai_configs

| Columna | Tipo | Notas |
|---|---|---|
| id | bigint | PK |
| project_id | bigint FK | projects.id, nullable (null = global) |
| provider | enum(openai, anthropic) | |
| api_key | string | encrypted |
| model | string | nullable, modelo por defecto |
| is_active | boolean | default: true |
| created_at | timestamp | |
| updated_at | timestamp | |

### ai_chat_sessions

| Columna | Tipo | Notas |
|---|---|---|
| id | bigint | PK |
| project_id | bigint FK | projects.id, cascadeOnDelete |
| user_id | bigint FK | users.id |
| title | string | nullable |
| created_at | timestamp | |
| updated_at | timestamp | |

### ai_chat_messages

| Columna | Tipo | Notas |
|---|---|---|
| id | bigint | PK |
| ai_chat_session_id | bigint FK | ai_chat_sessions.id, cascadeOnDelete |
| role | enum(user, assistant, system) | |
| content | text | |
| created_at | timestamp | |

## Relaciones Eloquent

```php
// User
User::organizations()        → belongsToMany(Organization::class, 'organization_user')
User::projects()             → belongsToMany(Project::class, 'project_user')
User::assignedTasks()        → hasMany(Task::class, 'assignee_id')
User::aiChatSessions()       → hasMany(AiChatSession::class)
User::timeEntries()          → hasMany(TimeEntry::class)
User::taskAttachments()      → hasMany(TaskAttachment::class)
User::messageAttachments()   → hasMany(MessageAttachment::class)
User::messageReads()         → hasMany(MessageRead::class)
User::activityLogs()         → hasMany(ActivityLog::class)

// Organization
Organization::members()      → belongsToMany(User::class, 'organization_user')
Organization::projects()     → hasMany(Project::class)
Organization::owner()        → belongsTo(User::class, 'owner_id')
Organization::activityLogs() → hasMany(ActivityLog::class)

// Project
Project::organization()          → belongsTo(Organization::class)
Project::members()               → belongsToMany(User::class, 'project_user')
Project::columns()               → hasMany(BoardColumn::class)
Project::tasks()                 → hasMany(Task::class)
Project::documents()             → hasMany(ProjectDocument::class)
Project::messages()              → hasMany(ProjectMessage::class)
Project::calendarEvents()        → hasMany(CalendarEvent::class)
Project::agents()                → belongsToMany(AgentTemplate::class, 'project_agents')
Project::aiConfig()              → hasOne(AiConfig::class)
Project::aiChatSessions()        → hasMany(AiChatSession::class)
Project::timeEntries()           → hasMany(TimeEntry::class)
Project::activityLogs()          → hasMany(ActivityLog::class)

// BoardColumn
BoardColumn::project()    → belongsTo(Project::class)
BoardColumn::tasks()      → hasMany(Task::class)

// Task
Task::project()           → belongsTo(Project::class)
Task::column()            → belongsTo(BoardColumn::class)
Task::parent()            → belongsTo(Task::class, 'parent_id')
Task::subtasks()          → hasMany(Task::class, 'parent_id')
Task::assignee()          → belongsTo(User::class, 'assignee_id')
Task::creator()           → belongsTo(User::class, 'created_by')
Task::attachments()       → hasMany(TaskAttachment::class)
Task::timeEntries()       → hasMany(TimeEntry::class)

// ProjectDocument
ProjectDocument::project()   → belongsTo(Project::class)
ProjectDocument::creator()   → belongsTo(User::class, 'created_by')

// ProjectMessage
ProjectMessage::project()    → belongsTo(Project::class)
ProjectMessage::user()       → belongsTo(User::class)
ProjectMessage::attachments() → hasMany(MessageAttachment::class)
ProjectMessage::reads()      → hasMany(MessageRead::class)

// TaskAttachment
TaskAttachment::task()       → belongsTo(Task::class)
TaskAttachment::user()       → belongsTo(User::class)

// MessageAttachment
MessageAttachment::message() → belongsTo(ProjectMessage::class)
MessageAttachment::user()    → belongsTo(User::class)

// MessageRead
MessageRead::message()       → belongsTo(ProjectMessage::class)
MessageRead::user()          → belongsTo(User::class)

// TimeEntry
TimeEntry::task()            → belongsTo(Task::class)
TimeEntry::user()            → belongsTo(User::class)
TimeEntry::project()         → belongsTo(Project::class)

// ActivityLog
ActivityLog::project()       → belongsTo(Project::class)
ActivityLog::organization()  → belongsTo(Organization::class)
ActivityLog::user()          → belongsTo(User::class)

// ProjectTemplate
ProjectTemplate::creator()   → belongsTo(User::class, 'created_by')
ProjectTemplate::columns()   → hasMany(ProjectTemplateColumn::class)
ProjectTemplate::tasks()     → hasMany(ProjectTemplateTask::class)
ProjectTemplate::documents() → hasMany(ProjectTemplateDocument::class)

// AgentTemplate
AgentTemplate::projects()    → belongsToMany(Project::class, 'project_agents')
AgentTemplate::creator()     → belongsTo(User::class, 'created_by')

// AiConfig
AiConfig::project()          → belongsTo(Project::class)

// AiChatSession
AiChatSession::project()     → belongsTo(Project::class)
AiChatSession::user()        → belongsTo(User::class)
AiChatSession::messages()    → hasMany(AiChatMessage::class)
```

## Enums

```php
UserRole: admin, client
OrganizationStatus: active, inactive
OrganizationUserRole: owner, member
ProjectStatus: planning, in_progress, on_hold, waiting_client, completed, archived
TaskPriority: critical, high, medium, low
TaskType: feature, bug, improvement, task
DocumentVisibility: private, public
MessageType: text, system, file
CalendarEventType: meeting, deadline, milestone
AiProvider: openai, anthropic
AiChatRole: user, assistant, system
TimeEntryType: manual, timer
```