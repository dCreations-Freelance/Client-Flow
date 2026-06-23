<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Anade `last_digest_sent_at` a la tabla `users`.
 *
 * Es el sello temporal que usa el comando
 * `notifications:daily-digest` para evitar mandar el resumen
 * dos veces el mismo dia. Cada usuario lleva su propio sello,
 * asi que el dato vive en la fila del usuario (no en una
 * tabla aparte) y se limpia automaticamente con la cascada al
 * borrar la cuenta.
 */
return new class extends Migration
{
    /**
     * Ejecuta la migracion.
     */
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table): void {
            $table->timestamp('last_digest_sent_at')
                ->nullable()
                ->after('remember_token');
        });
    }

    /**
     * Revierte la migracion.
     */
    public function down(): void
    {
        Schema::table('users', function (Blueprint $table): void {
            $table->dropColumn('last_digest_sent_at');
        });
    }
};
