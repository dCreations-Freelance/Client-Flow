<?php

namespace App\Models;

use App\Enums\NotificationEvent;
use Database\Factories\NotificationPreferenceFactory;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Preferencia de un usuario para un evento de notificacion.
 *
 * Una fila indica si el usuario quiere recibir un evento concreto
 * por el canal in-app (la campana del header) o por email. La
 * combinacion (user_id, event) es unica.
 *
 * El modelo NO contiene la logica de "que pasa si no existe la fila":
 * esa responsabilidad es del helper `User::preferenceFor()`, que
 * devuelve una instancia transitoria con los defaults del enum
 * cuando el usuario aun no ha tocado sus preferencias. Asi nunca se
 * llega a `NotificationDispatcher` con una preferencia nula.
 */
class NotificationPreference extends Model
{
    /** @use HasFactory<NotificationPreferenceFactory> */
    use HasFactory;

    /**
     * @var list<string>
     */
    protected $fillable = [
        'user_id',
        'event',
        'in_app',
        'email',
    ];

    /**
     * Casts: el evento se castea al enum para que las comparaciones
     * en policies y servicios sean type-safe. Los booleanos quedan
     * explicitos para que las columnas lleguen como `bool` a las
     * vistas y los serializadores JSON.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'event' => NotificationEvent::class,
            'in_app' => 'boolean',
            'email' => 'boolean',
        ];
    }

    /**
     * Usuario al que pertenece la preferencia.
     *
     * @return BelongsTo<User, NotificationPreference>
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Determina si la campana in-app esta activa para esta
     * preferencia.
     *
     * @return bool
     */
    public function isInAppEnabled(): bool
    {
        return (bool) $this->in_app;
    }

    /**
     * Determina si el email esta activo para esta preferencia.
     *
     * @return bool
     */
    public function isEmailEnabled(): bool
    {
        return (bool) $this->email;
    }

    /**
     * Determina si el usuario ha deshabilitado ambos canales. El
     * `NotificationDispatcher` consulta esto para cortar antes de
     * despachar la notificacion y no ensuciar la tabla `notifications`
     * con filas que nadie va a leer.
     *
     * @return bool
     */
    public function isFullyDisabled(): bool
    {
        return ! $this->isInAppEnabled() && ! $this->isEmailEnabled();
    }

    // -----------------------------------------------------------------
    // Scopes
    // -----------------------------------------------------------------

    /**
     * Filtra las preferencias de un usuario concreto.
     *
     * @param  Builder<NotificationPreference>  $query
     * @param  int  $userId
     * @return Builder<NotificationPreference>
     */
    public function scopeForUser(Builder $query, int $userId): Builder
    {
        return $query->where('user_id', $userId);
    }

    /**
     * Solo preferencias con in-app activo. Util para el
     * panel de administracion si en algun momento queremos
     * "limpiar" filas obsoletas.
     *
     * @param  Builder<NotificationPreference>  $query
     * @return Builder<NotificationPreference>
     */
    public function scopeInAppEnabled(Builder $query): Builder
    {
        return $query->where('in_app', true);
    }

    /**
     * Solo preferencias con email activo.
     *
     * @param  Builder<NotificationPreference>  $query
     * @return Builder<NotificationPreference>
     */
    public function scopeEmailEnabled(Builder $query): Builder
    {
        return $query->where('email', true);
    }
}
