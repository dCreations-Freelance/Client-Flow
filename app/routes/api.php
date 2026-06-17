<?php

use App\Http\Controllers\Api\McpController;
use App\Http\Middleware\EnsureMcpAccess;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Rutas destinadas a integraciones externas. En el MVP solo expone el
| MCP server bajo /api/mcp/*, protegido por token de Sanctum y
| restringido a usuarios con rol admin.
|
*/

Route::middleware(['auth:sanctum', EnsureMcpAccess::class])
    ->prefix('mcp')
    ->group(function (): void {
        Route::get('/sse', [McpController::class, 'sse'])->name('api.mcp.sse');
        Route::post('/messages', [McpController::class, 'messages'])->name('api.mcp.messages');
    });
