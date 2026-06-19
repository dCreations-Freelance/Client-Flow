<?php

/*
|--------------------------------------------------------------------------
| AI Configuration
|--------------------------------------------------------------------------
|
| Configuracion del modulo de IA para el cliente (fase 7). Centraliza
| los defaults por provider, los parametros de rate limit y la URL del
| provider Opencode (OpenAI-compatible) para que el admin pueda
| apuntarlo a un proxy local.
|
| Las API keys NO se guardan en este archivo: se almacenan cifradas
| en la tabla `ai_configs` (columna `api_key`, cast `encrypted`).
|
*/

return [

    /*
    |--------------------------------------------------------------------------
    | Defaults globales
    |--------------------------------------------------------------------------
    |
    | Aplican cuando la AiConfig activa no define un valor propio. Se
    | exponen aqui para que el equipo de operaciones pueda ajustarlos
    | sin tocar codigo.
    |
    */

    'default_max_messages_per_hour' => (int) env('AI_MAX_MESSAGES_PER_HOUR', 20),

    'default_max_sessions_per_day' => (int) env('AI_MAX_SESSIONS_PER_DAY', 10),

    /*
    |--------------------------------------------------------------------------
    | Provider Opencode
    |--------------------------------------------------------------------------
    |
    | Opencode reusa el formato JSON de OpenAI Chat Completions pero
    | permite apuntar a un endpoint arbitrario: LM Studio, Ollama,
    | vLLM, o un proxy corporativo. Si la URL no se define, se usa
    | la API publica de OpenAI como fallback.
    |
    */

    'opencode' => [
        // Base URL de la API Opencode Zen. La API es
        // OpenAI-compatible en el endpoint
        // `/v1/chat/completions`; lo unico que cambia
        // respecto a OpenAI es la URL y el namespace de
        // los modelos (prefijo `opencode-go/` o similar).
        'base_url' => env('OPENCODE_BASE_URL', 'https://opencode.ai/zen/go'),
    ],

    /*
    |--------------------------------------------------------------------------
    | Modelos por defecto por provider
    |--------------------------------------------------------------------------
    |
    | Coinciden con `AiProvider::defaultModel()`. Se duplican aqui para
    | que se puedan sobreescribir desde `.env` sin tocar PHP. En la
    | practica la fuente de verdad es el enum; este mapa es solo un
    | override opcional.
    |
    */

    'models' => [
        'openai' => env('OPENAI_DEFAULT_MODEL', 'gpt-4o-mini'),
        'anthropic' => env('ANTHROPIC_DEFAULT_MODEL', 'claude-3-5-haiku-latest'),
        // Opencode Zen namespacia los modelos con un
        // prefijo que indica el tier (e.g. `opencode-go/`,
        // `opencode-big-pickle/`). El admin puede cambiar
        // el modelo en cada AiConfig desde la UI.
        'opencode' => env('OPENCODE_DEFAULT_MODEL', 'opencode-go/kimi-k2.6'),
    ],

    /*
    |--------------------------------------------------------------------------
    | Timeouts HTTP
    |--------------------------------------------------------------------------
    |
    | Las llamadas a los providers son bloqueantes (sincronas) y se
    | ejecutan dentro de un request HTTP de Laravel. Limitar el tiempo
    | evita que un provider lento bloquee la peticion del usuario mas
    | alla de lo razonable.
    |
    */

    'http_timeout' => (int) env('AI_HTTP_TIMEOUT', 30),

    'http_connect_timeout' => (int) env('AI_HTTP_CONNECT_TIMEOUT', 10),

];
