<?php

namespace Tests\Unit\Config;

use Tests\TestCase;

/**
 * Verifica la configuracion de seguridad de Sanctum.
 *
 * La expiracion de tokens (auditoria H-03) evita que un token
 * filtrado siga siendo valido indefinidamente.
 */
class SanctumExpirationTest extends TestCase
{
    public function test_expiracion_de_tokens_es_30_dias(): void
    {
        $expected = 60 * 24 * 30;

        $this->assertSame($expected, config('sanctum.expiration'));
        $this->assertSame(43200, config('sanctum.expiration'));
    }
}
