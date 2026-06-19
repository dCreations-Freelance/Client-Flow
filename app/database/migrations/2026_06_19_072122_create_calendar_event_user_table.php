<?php

use App\Models\CalendarEvent;
use App\Models\User;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Crea la tabla pivot `calendar_event_user` que modela los
 * asistentes invitados a cada evento de calendario.
 *
 * - La PK compuesta `(calendar_event_id, user_id)` evita duplicados
 *   de manera natural y simplifica las queries "esta este usuario
 *   en este evento?".
 * - `cascadeOnDelete` en ambos lados: si se borra un evento se
 *   borran sus asistentes, y si se borra un usuario se quitan sus
 *   invitaciones (no tendria sentido mantener invitaciones a un
 *   usuario borrado).
 * - Anadimos un id surrogate para que Eloquent pueda distinguir
 *   filas en el pivot si en una fase futura se introducen
 *   metadatos (estado de aceptacion, fecha de envio de email, etc).
 *
 * Indices:
 * - PK compuesta ya cubre las busquedas por (evento, usuario).
 * - `user_id` indexado para resolver "en que eventos esta
 *   invitado este usuario?".
 */
return new class extends Migration
{
    /**
     * Ejecuta la migracion.
     */
    public function up(): void
    {
        Schema::create('calendar_event_user', function (Blueprint $table): void {
            $table->id();

            $table->foreignIdFor(CalendarEvent::class, 'calendar_event_id')
                ->constrained('calendar_events')
                ->cascadeOnDelete();

            $table->foreignIdFor(User::class, 'user_id')
                ->constrained('users')
                ->cascadeOnDelete();

            $table->timestamps();

            // PK compuesta: un usuario no puede estar invitado dos
            // veces al mismo evento.
            $table->unique(['calendar_event_id', 'user_id'], 'calendar_event_user_unique');

            // Indice en user_id para buscar "eventos en los que
            // participa este usuario".
            $table->index('user_id');
        });
    }

    /**
     * Revierte la migracion.
     */
    public function down(): void
    {
        Schema::dropIfExists('calendar_event_user');
    }
};
