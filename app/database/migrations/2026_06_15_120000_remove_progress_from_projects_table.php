<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Elimina la columna `progress` de `projects`. El progreso se
     * calcula ahora a partir de las tareas (completadas vs raiz)
     * en `Project::tasksProgressPercent`, por lo que el campo
     * manual deja de tener sentido y puede desincronizarse.
     */
    public function up(): void
    {
        Schema::table('projects', function (Blueprint $table): void {
            $table->dropColumn('progress');
        });
    }

    public function down(): void
    {
        Schema::table('projects', function (Blueprint $table): void {
            $table->unsignedTinyInteger('progress')->default(0);
        });
    }
};
