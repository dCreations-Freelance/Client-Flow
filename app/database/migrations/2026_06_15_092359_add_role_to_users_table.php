<?php

use App\Enums\UserRole;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Anade la columna `role` a la tabla `users` para distinguir entre administradores
     * y clientes. Sigue `docs/DATA_MODEL.md` (enum, default client, indexada).
     */
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table): void {
            $table->string('role')
                ->default(UserRole::Client->value)
                ->index()
                ->after('password');
        });
    }

    /**
     * Revierte la columna `role`. Se hace antes de eliminar la tabla para no dejar
     * columnas huerfanas si existe una migracion posterior que la modifique.
     */
    public function down(): void
    {
        Schema::table('users', function (Blueprint $table): void {
            $table->dropIndex(['role']);
            $table->dropColumn('role');
        });
    }
};
