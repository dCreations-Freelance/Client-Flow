<?php

use App\Http\Controllers\Admin\AiChatController as AdminAiChatController;
use App\Http\Controllers\Admin\AiConfigController as AdminAiConfigController;
use App\Http\Controllers\Admin\AgentTemplateController as AdminAgentTemplateController;
use App\Http\Controllers\Admin\BoardColumnController as AdminBoardColumnController;
use App\Http\Controllers\Admin\DashboardController as AdminDashboardController;
use App\Http\Controllers\Admin\KanbanController as AdminKanbanController;
use App\Http\Controllers\Admin\NotificationController as AdminNotificationController;
use App\Http\Controllers\Admin\NotificationPreferenceController as AdminNotificationPreferenceController;
use App\Http\Controllers\Admin\OrganizationController as AdminOrganizationController;
use App\Http\Controllers\Admin\OrganizationMemberController as AdminOrganizationMemberController;
use App\Http\Controllers\Admin\ProjectArchiveController as AdminProjectArchiveController;
use App\Http\Controllers\Admin\ProjectCalendarController as AdminProjectCalendarController;
use App\Http\Controllers\Admin\ProjectActivityController as AdminProjectActivityController;
use App\Http\Controllers\Admin\ProjectAgentController as AdminProjectAgentController;
use App\Http\Controllers\Admin\ProjectController as AdminProjectController;
use App\Http\Controllers\Admin\ProjectDocumentController as AdminProjectDocumentController;
use App\Http\Controllers\Admin\ProjectMemberController as AdminProjectMemberController;
use App\Http\Controllers\Admin\ProjectMessageController as AdminProjectMessageController;
use App\Http\Controllers\Admin\MessageAttachmentController as AdminMessageAttachmentController;
use App\Http\Controllers\Admin\ProjectTemplateController as AdminProjectTemplateController;
use App\Http\Controllers\Admin\TaskAttachmentController as AdminTaskAttachmentController;
use App\Http\Controllers\Admin\TaskController as AdminTaskController;
use App\Http\Controllers\Admin\TaskMoveController as AdminTaskMoveController;
use App\Http\Controllers\Admin\TimeEntryController as AdminTimeEntryController;
use App\Http\Controllers\Admin\ProjectTimeController as AdminProjectTimeController;
use App\Http\Controllers\Auth\AuthenticatedSessionController;
use App\Http\Controllers\Auth\InvitationAcceptanceController;
use App\Http\Controllers\Auth\NewPasswordController;
use App\Http\Controllers\Auth\PasswordResetLinkController;
use App\Http\Controllers\Auth\RegisteredUserController;
use App\Http\Controllers\Portal\AiChatController as PortalAiChatController;
use App\Http\Controllers\Portal\DashboardController as PortalDashboardController;
use App\Http\Controllers\Portal\KanbanController as PortalKanbanController;
use App\Http\Controllers\Portal\NotificationController as PortalNotificationController;
use App\Http\Controllers\Portal\ProjectActivityController as PortalProjectActivityController;
use App\Http\Controllers\Portal\NotificationPreferenceController as PortalNotificationPreferenceController;
use App\Http\Controllers\Portal\ProjectCalendarController as PortalProjectCalendarController;
use App\Http\Controllers\Portal\ProjectController as PortalProjectController;
use App\Http\Controllers\Portal\ProjectDocumentController as PortalProjectDocumentController;
use App\Http\Controllers\Portal\ProjectMessageController as PortalProjectMessageController;
use App\Http\Controllers\Portal\MessageAttachmentController as PortalMessageAttachmentController;
use App\Http\Controllers\Portal\TaskAttachmentController as PortalTaskAttachmentController;
use App\Http\Controllers\Portal\ProjectTimeController as PortalProjectTimeController;
use App\Http\Controllers\PwaController;
use App\Http\Controllers\NotificationsController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Web routes
|--------------------------------------------------------------------------
|
| Todas las rutas viven en `web.php` para conservar sesiones y CSRF. Se
| organizan en bloques: publicas, auth, panel admin, portal cliente.
| Los nombres siguen el patron `<zona>.<recurso>.<accion>`.
|
*/

// ---------------------------------------------------------------------
// Rutas publicas
// ---------------------------------------------------------------------

Route::get('/', function () {
    if (auth()->check()) {
        return auth()->user()->isAdmin()
            ? redirect()->route('admin.dashboard')
            : redirect()->route('portal.dashboard');
    }

    return view('landing');
})->name('home');

Route::get('invitacion/{token}', [InvitationAcceptanceController::class, 'show'])->name('invitation.accept');
Route::post('invitacion/{token}', [InvitationAcceptanceController::class, 'store']);

// ---------------------------------------------------------------------
// PWA: manifest y service worker
// ---------------------------------------------------------------------
// Las dos rutas son publicas (sin middleware auth) porque el
// navegador necesita poder pedir el manifest y registrar el SW
// incluso antes de que el usuario haya iniciado sesion. Asi se
// puede "anadir a pantalla de inicio" desde la landing.

Route::get('/manifest.webmanifest', [PwaController::class, 'manifest'])
    ->name('pwa.manifest');
Route::get('/sw.js', [PwaController::class, 'serviceWorker'])
    ->name('pwa.sw');

// ---------------------------------------------------------------------
// API interna: contadores para el polling de la PWA
// ---------------------------------------------------------------------
// Vive en `web.php` (no en `api.php`) para poder usar el
// middleware `auth` de sesion, que es el que aplica al resto de
// la app. El cliente lo invoca con `credentials: 'same-origin'`
// para que la cookie de sesion viaje automaticamente.

Route::middleware('auth')
    ->get('/api/notifications/unread-count', [NotificationsController::class, 'unreadCount'])
    ->name('api.notifications.unread-count');

// ---------------------------------------------------------------------
// Autenticacion: solo accesibles por visitantes (`guest`)
// ---------------------------------------------------------------------

Route::middleware('guest')->group(function (): void {
    Route::get('iniciar-sesion', [AuthenticatedSessionController::class, 'create'])->name('login');
    // 5 intentos por minuto por IP en login (auditoria H-02). Suficiente
    // para typos legitimos, frena brute-force basico.
    Route::post('iniciar-sesion', [AuthenticatedSessionController::class, 'store'])
        ->middleware('throttle:5,1');

    Route::get('registro', [RegisteredUserController::class, 'create'])->name('register');
    // 3 registros por hora por IP. Crea cuentas nuevas, no queremos
    // abuso para acumulacion de cuentas de spam.
    Route::post('registro', [RegisteredUserController::class, 'store'])
        ->middleware('throttle:3,60');

    Route::get('recuperar-contrasena', [PasswordResetLinkController::class, 'create'])->name('password.request');
    Route::post('recuperar-contrasena', [PasswordResetLinkController::class, 'store'])->name('password.email');

    Route::get('recuperar-contrasena/{token}', [NewPasswordController::class, 'create'])->name('password.reset');
    Route::post('restablecer-contrasena', [NewPasswordController::class, 'store'])->name('password.update');
});

Route::post('cerrar-sesion', [AuthenticatedSessionController::class, 'destroy'])
    ->middleware('auth')
    ->name('logout');

// ---------------------------------------------------------------------
// Panel de administracion (`/admin/*`)
// ---------------------------------------------------------------------

Route::middleware(['auth', 'admin'])
    ->prefix('admin')
    ->name('admin.')
    ->group(function (): void {
        Route::get('/', fn () => redirect()->route('admin.dashboard'));
        Route::get('tablero', [AdminDashboardController::class, 'index'])->name('dashboard');

        // Organizaciones
        Route::resource('organizaciones', AdminOrganizationController::class)
            ->parameters(['organizaciones' => 'organization'])
            ->names('organizations');
        Route::get('organizaciones/{organization}/miembros', [AdminOrganizationMemberController::class, 'index'])
            ->name('organizations.members');
        Route::post('organizaciones/{organization}/miembros', [AdminOrganizationMemberController::class, 'store'])
            ->name('organizations.members.store');
        Route::delete('organizaciones/{organization}/miembros/{user}', [AdminOrganizationMemberController::class, 'destroy'])
            ->name('organizations.members.destroy');

        // Proyectos
        Route::resource('proyectos', AdminProjectController::class)
            ->parameters(['proyectos' => 'project'])
            ->names('projects');
        Route::post('proyectos/{project}/miembros', [AdminProjectMemberController::class, 'store'])
            ->name('projects.members.store');
        Route::delete('proyectos/{project}/miembros/{user}', [AdminProjectMemberController::class, 'destroy'])
            ->name('projects.members.destroy');
        Route::post('proyectos/{project}/archivar', [AdminProjectArchiveController::class, 'archive'])
            ->name('projects.archive');
        Route::post('proyectos/{project}/restaurar', [AdminProjectArchiveController::class, 'unarchive'])
            ->name('projects.unarchive');

        // Kanban: tablero, columnas y tareas.
        Route::get('proyectos/{project}/tablero', [AdminKanbanController::class, 'index'])
            ->name('projects.board');

        Route::post('proyectos/{project}/columnas', [AdminBoardColumnController::class, 'store'])
            ->name('projects.columns.store');
        Route::put('proyectos/{project}/columnas/{column}', [AdminBoardColumnController::class, 'update'])
            ->name('projects.columns.update');
        Route::delete('proyectos/{project}/columnas/{column}', [AdminBoardColumnController::class, 'destroy'])
            ->name('projects.columns.destroy');
        Route::post('proyectos/{project}/columnas/reordenar', [AdminBoardColumnController::class, 'reorder'])
            ->name('projects.columns.reorder');

        Route::post('proyectos/{project}/tareas', [AdminTaskController::class, 'store'])
            ->name('projects.tasks.store');
        Route::put('proyectos/{project}/tareas/{task}', [AdminTaskController::class, 'update'])
            ->name('projects.tasks.update');
        Route::delete('proyectos/{project}/tareas/{task}', [AdminTaskController::class, 'destroy'])
            ->name('projects.tasks.destroy');
        Route::post('proyectos/{project}/tareas/{task}/completar', [AdminTaskController::class, 'complete'])
            ->name('projects.tasks.complete');
        Route::post('proyectos/{project}/tareas/{task}/reabrir', [AdminTaskController::class, 'reopen'])
            ->name('projects.tasks.reopen');

        // Vista de detalle de tarea (usada para gestionar adjuntos).
        Route::get('proyectos/{project}/tareas/{task}', [AdminTaskController::class, 'show'])
            ->name('projects.tasks.show');

        // Mover tarea entre columnas (drag & drop del kanban).
        Route::patch('proyectos/{project}/tareas/{task}/mover', [AdminTaskMoveController::class, 'update'])
            ->name('projects.tasks.move');

        // Adjuntos de tareas: subir, descargar y eliminar.
        // Solo admin puede crear/eliminar; descargar sigue la
        // policy `download` que delega en la policy de la tarea.
        Route::post('proyectos/{project}/tareas/{task}/adjuntos', [AdminTaskAttachmentController::class, 'store'])
            ->name('projects.tasks.attachments.store');
        Route::get('proyectos/{project}/tareas/{task}/adjuntos/{attachment}', [AdminTaskAttachmentController::class, 'download'])
            ->name('projects.tasks.attachments.download');
        Route::delete('proyectos/{project}/tareas/{task}/adjuntos/{attachment}', [AdminTaskAttachmentController::class, 'destroy'])
            ->name('projects.tasks.attachments.destroy');

        // Adjuntos de mensajes del chat: descargar y eliminar.
        // La subida se hace desde el componente Livewire del
        // chat, pero mantenemos una ruta HTTP equivalente en
        // tests y como fallback.
        Route::post('proyectos/{project}/mensajes/{message}/adjuntos', [AdminMessageAttachmentController::class, 'store'])
            ->name('projects.messages.attachments.store');
        Route::get('proyectos/{project}/mensajes/{message}/adjuntos/{attachment}', [AdminMessageAttachmentController::class, 'download'])
            ->name('projects.messages.attachments.download');
        Route::delete('proyectos/{project}/mensajes/{message}/adjuntos/{attachment}', [AdminMessageAttachmentController::class, 'destroy'])
            ->name('projects.messages.attachments.destroy');

        // Documentacion: CRUD de documentos del proyecto.
        // Las acciones del editor se hacen via componente Livewire
        // (POST PUT a los mismos endpoints).
        Route::get('proyectos/{project}/documentos', [AdminProjectDocumentController::class, 'index'])
            ->name('projects.documents.index');
        Route::get('proyectos/{project}/documentos/crear', [AdminProjectDocumentController::class, 'create'])
            ->name('projects.documents.create');
        Route::post('proyectos/{project}/documentos', [AdminProjectDocumentController::class, 'store'])
            ->name('projects.documents.store');
        Route::get('proyectos/{project}/documentos/{document}', [AdminProjectDocumentController::class, 'show'])
            ->name('projects.documents.show');
        Route::get('proyectos/{project}/documentos/{document}/editar', [AdminProjectDocumentController::class, 'edit'])
            ->name('projects.documents.edit');
        Route::put('proyectos/{project}/documentos/{document}', [AdminProjectDocumentController::class, 'update'])
            ->name('projects.documents.update');
        Route::delete('proyectos/{project}/documentos/{document}', [AdminProjectDocumentController::class, 'destroy'])
            ->name('projects.documents.destroy');

        // Chat del proyecto. El grueso de la interaccion se hace
        // via componente Livewire; el POST es para tests y como
        // fallback si Livewire falla.
        Route::get('proyectos/{project}/chat', [AdminProjectMessageController::class, 'index'])
            ->name('projects.chat');
        Route::post('proyectos/{project}/mensajes', [AdminProjectMessageController::class, 'store'])
            ->name('projects.chat.store');

        // Feed de actividad del proyecto. Read-only: el admin
        // ve TODOS los eventos (incluidos los privados). El
        // componente Livewire concentra la logica de paginacion
        // y filtros; este endpoint solo renderiza la vista.
        Route::get('proyectos/{project}/actividad', [AdminProjectActivityController::class, 'index'])
            ->name('projects.activity');

        // Asistente IA por proyecto (admin usa su propio chat
        // de prueba en cada proyecto).
        Route::get('proyectos/{project}/ia', [AdminAiChatController::class, 'index'])
            ->name('projects.ai');
        Route::get('proyectos/{project}/ia/sesiones/{session}', [AdminAiChatController::class, 'show'])
            ->name('projects.ai.show');
        Route::delete('proyectos/{project}/ia/sesiones/{session}', [AdminAiChatController::class, 'destroy'])
            ->name('projects.ai.destroy');

        // Configuracion global de IA.
        Route::get('configuracion/ia', [AdminAiConfigController::class, 'edit'])
            ->name('ai.config.edit');
        Route::put('configuracion/ia', [AdminAiConfigController::class, 'update'])
            ->name('ai.config.update');
        Route::post('configuracion/ia/probar', [AdminAiConfigController::class, 'test'])
            ->name('ai.config.test');

        // Templates de agentes IA. La biblioteca de prompts y
        // herramientas que el admin exporta a sus IDEs y asigna
        // a proyectos. `resource` registra las 7 acciones
        // canonicas (incluido `create` sin param y `edit` con
        // el template) y `export` se anade a mano.
        Route::resource('plantillas-agente', AdminAgentTemplateController::class)
            ->parameters(['plantillas-agente' => 'agent_template'])
            ->names('agent-templates');
        Route::get('plantillas-agente/{agent_template}/exportar', [AdminAgentTemplateController::class, 'export'])
            ->name('agent-templates.export');

        // Plantillas de proyecto: biblioteca de esqueletos
        // reutilizables. `resource` registra las 7 acciones
        // canonicas; las acciones anidadas (columnas, tareas,
        // documentos) se anaden a mano para evitar proliferacion
        // de rutas.
        Route::resource('plantillas-proyecto', AdminProjectTemplateController::class)
            ->parameters(['plantillas-proyecto' => 'project_template'])
            ->names('project-templates');
        Route::post('plantillas-proyecto/{project_template}/columnas', [AdminProjectTemplateController::class, 'storeColumn'])
            ->name('project-templates.columns.store');
        Route::put('plantillas-proyecto/{project_template}/columnas/{column}', [AdminProjectTemplateController::class, 'updateColumn'])
            ->name('project-templates.columns.update');
        Route::delete('plantillas-proyecto/{project_template}/columnas/{column}', [AdminProjectTemplateController::class, 'destroyColumn'])
            ->name('project-templates.columns.destroy');
        Route::post('plantillas-proyecto/{project_template}/tareas', [AdminProjectTemplateController::class, 'storeTask'])
            ->name('project-templates.tasks.store');
        Route::put('plantillas-proyecto/{project_template}/tareas/{task}', [AdminProjectTemplateController::class, 'updateTask'])
            ->name('project-templates.tasks.update');
        Route::delete('plantillas-proyecto/{project_template}/tareas/{task}', [AdminProjectTemplateController::class, 'destroyTask'])
            ->name('project-templates.tasks.destroy');
        Route::post('plantillas-proyecto/{project_template}/documentos', [AdminProjectTemplateController::class, 'storeDocument'])
            ->name('project-templates.documents.store');
        Route::put('plantillas-proyecto/{project_template}/documentos/{document}', [AdminProjectTemplateController::class, 'updateDocument'])
            ->name('project-templates.documents.update');
        Route::delete('plantillas-proyecto/{project_template}/documentos/{document}', [AdminProjectTemplateController::class, 'destroyDocument'])
            ->name('project-templates.documents.destroy');

        // Creacion de proyectos desde plantilla: formulario
        // pre-rellenado y endpoint de aplicacion. Vive en el
        // ProjectController (no en ProjectTemplateController)
        // porque produce un Project, no un ProjectTemplate.
        Route::get('proyectos/crear-desde-plantilla/{project_template}', [AdminProjectController::class, 'createFromTemplate'])
            ->name('projects.create-from-template');
        Route::post('proyectos/desde-plantilla/{project_template}', [AdminProjectController::class, 'storeFromTemplate'])
            ->name('projects.store-from-template');

        // Asignacion de agentes a proyectos. Cinco rutas planas
        // en vez de un resource porque el conjunto de acciones
        // no es el canonico de un CRUD (no hay show/edit propios,
        // solo edicion inline del override via `?edit=ID`).
        Route::get('proyectos/{project}/agentes', [AdminProjectAgentController::class, 'index'])
            ->name('projects.agents.index');
        Route::post('proyectos/{project}/agentes', [AdminProjectAgentController::class, 'store'])
            ->name('projects.agents.store');
        Route::put('proyectos/{project}/agentes/{agent}', [AdminProjectAgentController::class, 'update'])
            ->name('projects.agents.update');
        Route::delete('proyectos/{project}/agentes/{agent}', [AdminProjectAgentController::class, 'destroy'])
            ->name('projects.agents.destroy');
        Route::get('proyectos/{project}/agentes/{agent}/exportar', [AdminProjectAgentController::class, 'export'])
            ->name('projects.agents.export');

        // Calendario del proyecto. Toda la interaccion (navegacion,
        // modal, crear/editar/eliminar) se hace via componente
        // Livewire; este endpoint solo renderiza la vista.
        Route::get('proyectos/{project}/calendario', [AdminProjectCalendarController::class, 'index'])
            ->name('projects.calendar');

        // Registro de tiempo: dashboard por proyecto y CRUD HTTP
        // de entradas (la mayor parte de la interaccion se hace
        // via componentes Livewire `TimeTracker` y
        // `ProjectTimeDashboard`; las rutas HTTP son fallback
        // para tests e integraciones externas).
        Route::get('proyectos/{project}/tiempo', [AdminProjectTimeController::class, 'index'])
            ->name('projects.time.index');
        Route::get('proyectos/{project}/tiempo/exportar', [AdminProjectTimeController::class, 'export'])
            ->name('projects.time.export');
        Route::post('proyectos/{project}/tareas/{task}/entradas-tiempo', [AdminTimeEntryController::class, 'store'])
            ->name('projects.tasks.time-entries.store');
        Route::put('proyectos/{project}/tareas/{task}/entradas-tiempo/{entry}', [AdminTimeEntryController::class, 'update'])
            ->name('projects.tasks.time-entries.update');
        Route::delete('proyectos/{project}/tareas/{task}/entradas-tiempo/{entry}', [AdminTimeEntryController::class, 'destroy'])
            ->name('projects.tasks.time-entries.destroy');

        // Preferencias de notificacion del admin (solo las suyas)
        // y endpoints JSON para la campana in-app.
        Route::get('notificaciones/preferencias', [AdminNotificationPreferenceController::class, 'index'])
            ->name('notifications.preferences');
        Route::put('notificaciones/preferencias', [AdminNotificationPreferenceController::class, 'update'])
            ->name('notifications.preferences.update');
        Route::get('notificaciones/bandeja', [AdminNotificationController::class, 'index'])
            ->name('notifications.inbox');
        Route::post('notificaciones/{notification}/leer', [AdminNotificationController::class, 'markAsRead'])
            ->name('notifications.read');
        Route::post('notificaciones/leer-todas', [AdminNotificationController::class, 'markAllAsRead'])
            ->name('notifications.read-all');
    });

// ---------------------------------------------------------------------
// Portal de cliente (`/portal/*`)
// ---------------------------------------------------------------------

Route::middleware(['auth', 'client'])
    ->prefix('portal')
    ->name('portal.')
    ->group(function (): void {
        Route::get('/', fn () => redirect()->route('portal.dashboard'));
        Route::get('tablero', [PortalDashboardController::class, 'index'])->name('dashboard');

        Route::get('organizaciones/{organization}', [PortalProjectController::class, 'showOrganization'])
            ->name('organizations.show');

        Route::get('proyectos', [PortalProjectController::class, 'index'])->name('projects.index');
        Route::get('proyectos/{project}', [PortalProjectController::class, 'show'])->name('projects.show');
        Route::get('proyectos/{project}/tablero', [PortalKanbanController::class, 'index'])
            ->name('projects.board');
        Route::get('proyectos/{project}/tareas/{task}', [PortalKanbanController::class, 'showTask'])
            ->name('projects.tasks.show');

        // Adjuntos de tareas: el portal solo descarga.
        Route::get('proyectos/{project}/tareas/{task}/adjuntos/{attachment}', [PortalTaskAttachmentController::class, 'download'])
            ->name('projects.tasks.attachments.download');

        // Adjuntos de mensajes del chat: el cliente puede subir
        // (consistente con su permiso para enviar mensajes) y
        // descargar. Eliminar es exclusivo del admin.
        Route::post('proyectos/{project}/mensajes/{message}/adjuntos', [PortalMessageAttachmentController::class, 'store'])
            ->name('projects.messages.attachments.store');
        Route::get('proyectos/{project}/mensajes/{message}/adjuntos/{attachment}', [PortalMessageAttachmentController::class, 'download'])
            ->name('projects.messages.attachments.download');

        // Documentacion publica del proyecto: solo documentos con
        // `visibility = public` y proyectos visibles al cliente.
        Route::get('proyectos/{project}/documentos', [PortalProjectDocumentController::class, 'index'])
            ->name('projects.documents.index');
        Route::get('proyectos/{project}/documentos/{document}', [PortalProjectDocumentController::class, 'show'])
            ->name('projects.documents.show');

        // Chat del proyecto (cliente).
        Route::get('proyectos/{project}/chat', [PortalProjectMessageController::class, 'index'])
            ->name('projects.chat');
        Route::post('proyectos/{project}/mensajes', [PortalProjectMessageController::class, 'store'])
            ->name('projects.chat.store');

        // Feed de actividad del proyecto (cliente). Mismo
        // componente compartido que el admin, pero montado
        // con `portalMode = true` para que aplique el scope
        // `public` y el cliente solo vea los eventos que
        // `ActivityType::isPublic()` admite.
        Route::get('proyectos/{project}/actividad', [PortalProjectActivityController::class, 'index'])
            ->name('projects.activity');

        // Asistente IA por proyecto (portal cliente).
        Route::get('proyectos/{project}/ia', [PortalAiChatController::class, 'index'])
            ->name('projects.ai');
        Route::post('proyectos/{project}/ia/sesiones', [PortalAiChatController::class, 'store'])
            ->name('projects.ai.sessions.store');
        Route::get('proyectos/{project}/ia/sesiones/{session}', [PortalAiChatController::class, 'show'])
            ->name('projects.ai.show');
        Route::delete('proyectos/{project}/ia/sesiones/{session}', [PortalAiChatController::class, 'destroy'])
            ->name('projects.ai.destroy');

        // Calendario del proyecto (cliente, solo lectura).
        // El mismo componente Livewire se monta con readOnly=true.
        Route::get('proyectos/{project}/calendario', [PortalProjectCalendarController::class, 'index'])
            ->name('projects.calendar');

        // Resumen de tiempo del proyecto (solo lectura).
        // Muestra totales agregados, sin descripciones
        // individuales ni desglose por tarea.
        Route::get('proyectos/{project}/tiempo', [PortalProjectTimeController::class, 'index'])
            ->name('projects.time.index');

        // Preferencias de notificacion del cliente y endpoints
        // JSON para la campana in-app del portal. Mismas rutas
        // que el admin, con prefijo `portal.`.
        Route::get('notificaciones/preferencias', [PortalNotificationPreferenceController::class, 'index'])
            ->name('notifications.preferences');
        Route::put('notificaciones/preferencias', [PortalNotificationPreferenceController::class, 'update'])
            ->name('notifications.preferences.update');
        Route::get('notificaciones/bandeja', [PortalNotificationController::class, 'index'])
            ->name('notifications.inbox');
        Route::post('notificaciones/{notification}/leer', [PortalNotificationController::class, 'markAsRead'])
            ->name('notifications.read');
        Route::post('notificaciones/leer-todas', [PortalNotificationController::class, 'markAllAsRead'])
            ->name('notifications.read-all');
    });
