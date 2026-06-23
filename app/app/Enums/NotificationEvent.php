<?php

namespace App\Enums;

/**
 * Catalogo de eventos que disparan una notificacion en ClientFlow.
 *
 * Es el "vocabulario" que comparten el servicio
 * `NotificationDispatcher`, la tabla `notification_preferences` y los
 * comandos Artisan. Anadir un evento nuevo es tan facil como:
 * 1. Anadir el caso aqui con sus defaults.
 * 2. Crear la clase `Notification` correspondiente.
 * 3. Llamar a `NotificationDispatcher::dispatch(...)` en el trigger
 *    adecuado.
 *
 * Los defaults son la politica del MVP ("recibir todo por todos los
 * canales"); el usuario puede desactivarlos desde la pagina de
 * preferencias y la BD guarda su decision en `notification_preferences`.
 *
 * Cada caso expone:
 * - `label()`: nombre legible en castellano para la UI.
 * - `description()`: frase corta que explica al usuario para que sirve.
 * - `defaultInApp()` / `defaultEmail()`: canales activos por defecto.
 */
enum NotificationEvent: string
{
    /**
     * Nuevo mensaje en el chat de un proyecto del que el usuario es
     * miembro. Es la notificacion de mayor volumen y la unica que
     * se sigue enviando siempre que el usuario este en la sala
     * (por defecto, ambos canales activos).
     */
    case NewMessage = 'new_message';

    /**
     * Se asigna una tarea a este usuario (o se reasigna). Por
     * defecto llega por in-app y email para que la persona no se
     * pierda el encargo.
     */
    case TaskAssigned = 'task_assigned';

    /**
     * Una tarea asignada a este usuario tiene su fecha limite en
     * los proximos 3 dias. El usuario puede silenciar el email si
     * ya consulta el portal a diario, pero el in-app queda
     * activado por defecto.
     */
    case TaskDueSoon = 'task_due_soon';

    /**
     * Invitacion a un evento del calendario (meeting, milestone,
     * deadline). Es puntual y relevante, asi que ambos canales
     * quedan activos por defecto.
     */
    case EventInvitation = 'event_invitation';

    /**
     * Invitacion a unirse a una organizacion. Como no se puede
     * responder desde el portal, sigue siendo solo email (el in-app
     * queda false por defecto). Se mantiene en el catalogo para
     * que un futuro flujo de aceptacion in-app pueda activarlo.
     */
    case OrganizationInvitation = 'organization_invitation';

    /**
     * Resumen diario que envia el comando `notifications:daily-digest`.
     * Es un email largo que recapitula la actividad del dia; el
     * in-app no tiene sentido (no es accion puntual), asi que el
     * default es solo email.
     */
    case DailyDigest = 'daily_digest';

    /**
     * Etiqueta legible en castellano para mostrar en la UI.
     *
     * @return string
     */
    public function label(): string
    {
        return match ($this) {
            self::NewMessage => 'Mensajes nuevos del chat',
            self::TaskAssigned => 'Tareas asignadas',
            self::TaskDueSoon => 'Tareas con deadline cercano',
            self::EventInvitation => 'Invitaciones a eventos',
            self::OrganizationInvitation => 'Invitaciones a organizaciones',
            self::DailyDigest => 'Resumen diario por email',
        };
    }

    /**
     * Descripcion corta que se muestra junto al toggle en la pagina
     * de preferencias, para que el usuario entienda que esta
     * activando o desactivando.
     *
     * @return string
     */
    public function description(): string
    {
        return match ($this) {
            self::NewMessage => 'Cuando alguien envia un mensaje en un proyecto donde participas.',
            self::TaskAssigned => 'Cuando un administrador te asigna una tarea nueva.',
            self::TaskDueSoon => 'Cuando una tarea tuya tiene su fecha limite en menos de 3 dias.',
            self::EventInvitation => 'Cuando un administrador te invita a un evento del calendario.',
            self::OrganizationInvitation => 'Cuando un administrador te invita a una organizacion.',
            self::DailyDigest => 'Un email cada mañana con el resumen de lo que ha pasado en tus proyectos.',
        };
    }

    /**
     * Si el canal in-app (la campana del header) esta activo por
     * defecto para este evento. Consultar la fila del usuario
     * sigue siendo la fuente de verdad; esto solo es el valor
     * inicial cuando se siembra la preferencia.
     *
     * @return bool
     */
    public function defaultInApp(): bool
    {
        return match ($this) {
            self::NewMessage,
            self::TaskAssigned,
            self::TaskDueSoon,
            self::EventInvitation => true,
            self::OrganizationInvitation,
            self::DailyDigest => false,
        };
    }

    /**
     * Si el canal email esta activo por defecto.
     *
     * @return bool
     */
    public function defaultEmail(): bool
    {
        return match ($this) {
            self::NewMessage,
            self::TaskAssigned,
            self::TaskDueSoon,
            self::EventInvitation,
            self::DailyDigest,
            self::OrganizationInvitation => true,
        };
    }
}
