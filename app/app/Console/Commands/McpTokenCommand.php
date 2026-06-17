<?php

namespace App\Console\Commands;

use App\Models\User;
use Illuminate\Console\Command;

/**
 * Genera un token de API personal para usar con el MCP server.
 *
 * El token se muestra una sola vez en pantalla; no se puede
 * recuperar posteriormente porque Sanctum lo almacena hasheado.
 */
class McpTokenCommand extends Command
{
    /**
     * @var string
     */
    protected $signature = 'mcp:token
                            {user? : ID o email del usuario admin}
                            {--name= : Nombre descriptivo del token (default: mcp-client)}';

    /**
     * @var string
     */
    protected $description = 'Genera un API token para conectar un cliente MCP.';

    /**
     * Ejecuta el comando.
     */
    public function handle(): int
    {
        $user = $this->resolveUser();

        if ($user === null) {
            $this->error('Usuario no encontrado o no es administrador.');

            return self::FAILURE;
        }

        $tokenName = $this->option('name') ?: 'mcp-client';
        $token = $user->createToken($tokenName, ['mcp:read'])->plainTextToken;

        $this->info('Token generado correctamente.');
        $this->newLine();
        $this->line($token);
        $this->newLine();
        $this->info('Guardalo en un lugar seguro; no se volvera a mostrar.');

        return self::SUCCESS;
    }

    /**
     * Resuelve el usuario admin objetivo.
     *
     * Si se proporciona un argumento, busca por ID o email. Si no,
     * usa el primer administrador de la base de datos.
     *
     * @return User|null
     */
    private function resolveUser(): ?User
    {
        $identifier = $this->argument('user');

        if ($identifier === null) {
            return User::query()->where('role', 'admin')->first();
        }

        $query = User::query();

        if (is_numeric($identifier)) {
            $query->where('id', (int) $identifier);
        } else {
            $query->where('email', $identifier);
        }

        $user = $query->first();

        if ($user !== null && ! $user->isAdmin()) {
            return null;
        }

        return $user;
    }
}
