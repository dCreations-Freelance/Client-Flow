<?php

namespace App\Services\Ai\Contracts;

use RuntimeException;

/**
 * Excepcion estandar que lanzan los providers cuando algo
 * falla (HTTP no-2xx, respuesta malformada, etc.). La UI
 * captura esta excepcion para mostrar un mensaje amable
 * al usuario.
 *
 * Vive en su propio archivo para que el autoloader PSR-4
 * de Composer la encuentre sin tener que cargar
 * `AiResponse.php`.
 */
class AiProviderException extends RuntimeException {}
