<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Crea la tabla `notifications` nativa de Laravel.
 *
 * Esta tabla la usa el canal `database` del sistema de
 * notificaciones: cuando llega una notificacion que el destinatario
 * puede ver "in-app", Laravel inserta una fila con el payload
 * serializado en `data` y la URL de accion en `action_url`.
 *
 * En ClientFlow se usa para el badge de "mensajes no leidos en el
 * chat" en el sidebar y como base para el resto de notificaciones
 * in-app que se anadan en la seccion "Transversal: Notificaciones".
 */
return new class extends Migration
{
    /**
     * Ejecuta la migracion.
     */
    public function up(): void
    {
        Schema::create('notifications', function (Blueprint $table): void {
            $table->uuid('id')->primary();
            $table->string('type');
            $table->morphs('notifiable');
            $table->text('data');
            $table->timestamp('read_at')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Revierte la migracion.
     */
    public function down(): void
    {
        Schema::dropIfExists('notifications');
    }
};
