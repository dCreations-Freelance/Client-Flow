<?php

namespace App\Models;

use App\Enums\NotificationEvent;
use App\Enums\UserRole;
use Database\Factories\UserFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;

/**
 * Modelo de usuario base de ClientFlow.
 *
 * Contiene la columna `role` que diferencia administradores de clientes y
 * las relaciones con organizaciones y proyectos que se iran completando en
 * fases posteriores. Mantiene la convencion de casts para enums de PHP 8.
 */
class User extends Authenticatable
{
    /** @use HasFactory<UserFactory> */
    use HasApiTokens, HasFactory, Notifiable;

    /**
     * Atributos asignables en masa. Se incluyen `role` para permitir el
     * registro de clientes con su rol por defecto sin necesidad de un fill
     * explicito posterior.
     *
     * `password` se omite a proposito (auditoria L-04): no queremos
     * que un `User::create($request->all())` descuidado pueda fijar
     * contrasena en claro. Los call sites internos asignan `password`
     * individualmente y el cast `'hashed'` se encarga de hashearlo
     * al persistir.
     *
     * @var list<string>
     */
    protected $fillable = [
        'name',
        'email',
        'role',
    ];

    /**
     * Atributos ocultos en serializaciones. `remember_token` se mantiene
     * oculto por seguridad junto con el password.
     *
     * @var list<string>
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * Casts de atributos. `role` se castea al enum para que las comparaciones
     * en policies y middleware sean type-safe. `last_digest_sent_at` se
     * mantiene como datetime para que el comando `notifications:daily-digest`
     * pueda comparar contra `now()` directamente.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
            'role' => UserRole::class,
            'last_digest_sent_at' => 'datetime',
        ];
    }

    /**
     * Comprobacion rapida del rol administrador.
     *
     * @return bool
     */
    public function isAdmin(): bool
    {
        return $this->role?->isAdmin() ?? false;
    }

    /**
     * Comprobacion rapida del rol cliente.
     *
     * @return bool
     */
    public function isClient(): bool
    {
        return $this->role?->isClient() ?? false;
    }

    /**
     * Organizaciones en las que el usuario es miembro. Se declara de forma
     * adelantada porque se usara en policies y vistas desde la fase 1B.
     *
     * @return BelongsToMany<Organization>
     */
    public function organizations(): BelongsToMany
    {
        return $this->belongsToMany(Organization::class, 'organization_user')
            ->withPivot('role')
            ->withTimestamps();
    }

    /**
     * Organizaciones donde el usuario figura como `owner` en el pivot. Es
     * el conjunto de organizaciones que el cliente ve destacado en su portal.
     *
     * @return BelongsToMany<Organization>
     */
    public function ownedOrganizations(): BelongsToMany
    {
        return $this->organizations()->wherePivot('role', 'owner');
    }

    /**
     * Proyectos en los que el usuario participa. Pendiente de uso hasta
     * la fase 2, pero se incluye la firma para mantener el codigo compilable
     * y alineado con `docs/DATA_MODEL.md`.
     *
     * @return BelongsToMany<Project>
     */
    public function projects(): BelongsToMany
    {
        return $this->belongsToMany(Project::class, 'project_user')
            ->withTimestamps();
    }

    /**
     * Invitaciones a organizaciones creadas por este usuario (admin). Solo
     * tendra datos para administradores, pero se declara para mantener la
     * simetria con `docs/DATA_MODEL.md`.
     *
     * @return HasMany<OrganizationInvitation>
     */
    public function sentInvitations(): HasMany
    {
        return $this->hasMany(OrganizationInvitation::class, 'created_by');
    }

    /**
     * Sesiones de chat con el asistente IA iniciadas por este
     * usuario. Se materializa en fase 7 para resolver el
     * sidebar de sesiones y limpiar el historial al borrar
     * un usuario.
     *
     * @return HasMany<AiChatSession>
     */
    public function aiChatSessions(): HasMany
    {
        return $this->hasMany(AiChatSession::class);
    }

    /**
     * Eventos de calendario en los que el usuario esta invitado como
     * asistente. La relacion se mantiene con timestamps para saber
     * cuando se le anadio a cada evento.
     *
     * @return BelongsToMany<CalendarEvent>
     */
    public function attendedEvents(): BelongsToMany
    {
        return $this->belongsToMany(CalendarEvent::class, 'calendar_event_user')
            ->withTimestamps();
    }

    /**
     * Eventos de calendario creados por este usuario. Pensado para
     * los administradores, que son los unicos que pueden crear
     * eventos en MVP, pero la declaracion se mantiene generica.
     *
     * @return HasMany<CalendarEvent>
     */
    public function createdCalendarEvents(): HasMany
    {
        return $this->hasMany(CalendarEvent::class, 'created_by');
    }

    /**
     * Templates de agentes IA creados por este usuario. En MVP
     * solo el admin los da de alta, pero la relacion se mantiene
     * generica para que un cliente nunca pueda crear templates
     * por error (la policy ya lo bloquea).
     *
     * @return HasMany<AgentTemplate>
     */
    public function agentTemplates(): HasMany
    {
        return $this->hasMany(AgentTemplate::class, 'created_by');
    }

    /**
     * Preferencias de notificacion del usuario. Una fila por evento.
     * El listener `CreateDefaultNotificationPreferences` siembra las
     * seis filas por defecto en el alta del usuario, asi que esta
     * relacion siempre devuelve resultados para usuarios que ya
     * existian antes de la fase transversal.
     *
     * @return HasMany<NotificationPreference>
     */
    public function notificationPreferences(): HasMany
    {
        return $this->hasMany(NotificationPreference::class);
    }

    /**
     * Entradas de tiempo registradas por el usuario.
     * Se usa para el dashboard "tiempo por miembro" y
     * para localizar timers activos.
     *
     * @return HasMany<TimeEntry>
     */
    public function timeEntries(): HasMany
    {
        return $this->hasMany(TimeEntry::class)->recent();
    }

    /**
     * Devuelve la preferencia del usuario para un evento concreto.
     *
     * Si el usuario aun no tiene una fila persistida (por ejemplo
     * si se ha creado antes de la fase y no se ha ejecutado el
     * seeder de "rellenar defaults"), construye una instancia
     * transitoria con los defaults del enum. Asi el codigo que
     * llama no tiene que tratar con nulos.
     *
     * La instancia devuelta NO se persiste; es un "valor por
     * defecto" que se evalua en memoria. Esto es importante: si el
     * dispatcher persiste esta instancia transitoria estariamos
     * creando filas vacias en BD.
     *
     * @param  NotificationEvent  $event
     * @return NotificationPreference
     */
    public function preferenceFor(NotificationEvent $event): NotificationPreference
    {
        $existing = $this->notificationPreferences
            ->firstWhere('event', $event->value);

        if ($existing !== null) {
            return $existing;
        }

        // Construimos una preferencia virtual (no persistida) con
        // los defaults del enum. Asignamos el user_id para que las
        // policies que reciben la instancia la reconozcan como
        // "del usuario actual".
        $virtual = new NotificationPreference([
            'user_id' => $this->id,
            'event' => $event,
            'in_app' => $event->defaultInApp(),
            'email' => $event->defaultEmail(),
        ]);
        $virtual->exists = false;

        return $virtual;
    }
}
