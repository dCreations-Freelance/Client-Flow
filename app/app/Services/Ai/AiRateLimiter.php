<?php

namespace App\Services\Ai;

use App\Models\AiConfig;
use App\Models\Project;
use RuntimeException;

/**
 * Encapsula el `RateLimiter` de Laravel para aplicarlo al
 * chat IA.
 *
 * Se mantienen dos cubos por defecto:
 * - Mensajes por hora, dimensionado por
 *   `AiConfig::max_messages_per_hour`.
 * - Sesiones por dia, dimensionado por
 *   `AiConfig::max_sessions_per_day`.
 *
 * Las claves incluyen `project_id` y `user_id` para que un
 * usuario con varias sesiones simultaneas en proyectos
 * distintos no comparta cuota.
 */
class AiRateLimiter
{
    /**
     * Limite por hora para mensajes. Se interpreta como
     * "numero de envios permitidos por hora" y se reinicia
     * al cumplirse la ventana.
     */
    public const KEY_MESSAGES = 'ai-chat:messages';

    /**
     * Limite diario para sesiones nuevas. Cada vez que el
     * usuario crea una sesion se incrementa este contador.
     */
    public const KEY_SESSIONS = 'ai-chat:sessions';

    /**
     * Determina si el usuario puede enviar un mensaje en
     * este momento sin superar el limite horario.
     *
     * @return bool
     */
    public function canSendMessage(AiConfig $config, int $userId, int $projectId): bool
    {
        $key = $this->buildKey(self::KEY_MESSAGES, $userId, $projectId);
        $maxAttempts = $this->resolveMaxMessages($config);

        return $this->limiter()->tooManyAttempts($key, $maxAttempts) === false;
    }

    /**
     * Registra un envio de mensaje contra el cubo horario.
     * Llamar solo si `canSendMessage()` devolvio `true`.
     *
     * @return void
     */
    public function hitMessage(AiConfig $config, int $userId, int $projectId): void
    {
        $key = $this->buildKey(self::KEY_MESSAGES, $userId, $projectId);
        $maxAttempts = $this->resolveMaxMessages($config);

        $this->limiter()->hit($key, $this->decaySeconds());
    }

    /**
     * Segundos restantes hasta que el cubo horario se
     * resetee. Se usa para informar al usuario cuantos
     * minutos debe esperar.
     *
     * @return int
     */
    public function secondsUntilMessageSlot(AiConfig $config, int $userId, int $projectId): int
    {
        $key = $this->buildKey(self::KEY_MESSAGES, $userId, $projectId);

        return (int) $this->limiter()->availableIn($key);
    }

    /**
     * Determina si el usuario puede crear una sesion nueva
     * hoy.
     *
     * @return bool
     */
    public function canCreateSession(AiConfig $config, int $userId, int $projectId): bool
    {
        $key = $this->buildKey(self::KEY_SESSIONS, $userId, $projectId);
        $maxAttempts = $this->resolveMaxSessions($config);

        return $this->limiter()->tooManyAttempts($key, $maxAttempts) === false;
    }

    /**
     * Registra la creacion de una nueva sesion contra el
     * cubo diario.
     *
     * @return void
     */
    public function hitSession(AiConfig $config, int $userId, int $projectId): void
    {
        $key = $this->buildKey(self::KEY_SESSIONS, $userId, $projectId);
        $maxAttempts = $this->resolveMaxSessions($config);

        $this->limiter()->hit($key, $this->decaySecondsForSessions());
    }

    /**
     * Construye la clave de rate limit combinando el tipo
     * de cubo, el usuario y el proyecto. Asi cada
     * combinacion (user, project) tiene su propio contador.
     *
     * @return string
     */
    private function buildKey(string $bucket, int $userId, int $projectId): string
    {
        return sprintf('%s:%d:%d', $bucket, $userId, $projectId);
    }

    /**
     * Resuelve el limite por hora para mensajes. Prioriza
     * el valor de la `AiConfig` y cae al default del
     * archivo de configuracion.
     */
    private function resolveMaxMessages(AiConfig $config): int
    {
        $value = $config->max_messages_per_hour;

        return is_int($value) && $value > 0
            ? $value
            : (int) config('ai.default_max_messages_per_hour', 20);
    }

    /**
     * Resuelve el limite diario para sesiones.
     */
    private function resolveMaxSessions(AiConfig $config): int
    {
        $value = $config->max_sessions_per_day;

        return is_int($value) && $value > 0
            ? $value
            : (int) config('ai.default_max_sessions_per_day', 10);
    }

    /**
     * Ventana del cubo horario, en segundos. 3600 = 1h.
     */
    private function decaySeconds(): int
    {
        return 3600;
    }

    /**
     * Ventana del cubo diario, en segundos. 86400 = 24h.
     */
    private function decaySecondsForSessions(): int
    {
        return 86400;
    }

    /**
     * Resuelve el facade `RateLimiter` perezosamente para
     * que en tests se pueda sustituir con un fake si fuera
     * necesario. En la practica `RateLimiter` funciona
     * contra el driver configurado (`database` en MVP).
     */
    private function limiter(): \Illuminate\Cache\RateLimiter
    {
        return app(\Illuminate\Cache\RateLimiter::class);
    }
}
