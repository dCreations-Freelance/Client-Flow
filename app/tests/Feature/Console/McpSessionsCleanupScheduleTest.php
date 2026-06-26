<?php

namespace Tests\Feature\Console;

use Tests\TestCase;

/**
 * Verifica que la limpieza de sesiones MCP esta registrada en el
 * scheduler (auditoria M-07). La validacion se hace a traves del
 * comando `schedule:list`, que es la fuente de verdad del scheduler.
 */
class McpSessionsCleanupScheduleTest extends TestCase
{
    public function test_cleanup_aparece_en_el_listado_del_scheduler(): void
    {
        \Illuminate\Support\Facades\Artisan::call('schedule:list');

        $output = \Illuminate\Support\Facades\Artisan::output();

        $this->assertStringContainsString('mcp-sessions-cleanup', $output);
        $this->assertStringContainsString('*/15 * * * *', $output);
    }
}
