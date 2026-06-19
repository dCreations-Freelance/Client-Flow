<?php

namespace App\Services\Ai;

use App\Enums\DocumentVisibility;
use App\Models\Project;

/**
 * Construye el system prompt que `AiService` envia al
 * provider antes que el historial de la conversacion.
 *
 * La estrategia es **minima y determinista**:
 * - Nombre del proyecto, organizacion y estado actual.
 * - Progreso calculado a partir de las tareas raiz.
 * - Las 10 ultimas tareas (titulo, estado, prioridad,
 *   asignado) ordenadas por fecha de creacion.
 * - Titulos de los documentos publicos del proyecto.
 *
 * Se mantiene en castellano y con tono neutro, sin
 * "como un asistente" ni instrucciones redundantes: el
 * provider recibe contexto factual y se le pide brevedad
 * solo en el prompt final.
 */
class ProjectContextBuilder
{
    /**
     * Numero maximo de tareas que se incluyen en el
     * system prompt. Suficiente para que el modelo tenga
     * visibilidad de la actividad reciente sin inflar
     * tokens.
     */
    private const RECENT_TASKS_LIMIT = 10;

    /**
     * Genera el system prompt en texto plano.
     *
     * @return string
     */
    public function build(Project $project): string
    {
        $lines = [];

        $lines[] = sprintf(
            'Eres el asistente IA del proyecto "%s" de la organizacion "%s".',
            $project->name,
            $project->organization?->name ?? 'sin organizacion',
        );

        $lines[] = sprintf(
            'Estado del proyecto: %s. Progreso: %d%% (%d de %d tareas raiz completadas).',
            $project->status?->label() ?? 'Sin estado',
            $project->tasks_progress_percent,
            $project->completed_tasks_count,
            $project->total_tasks_count,
        );

        $lines[] = '';
        $lines[] = 'Tareas recientes:';

        $recentTasks = $project->tasks()
            ->with('assignee:id,name')
            ->orderByDesc('created_at')
            ->limit(self::RECENT_TASKS_LIMIT)
            ->get();

        if ($recentTasks->isEmpty()) {
            $lines[] = '- (El proyecto aun no tiene tareas.)';
        } else {
            foreach ($recentTasks as $task) {
                $lines[] = sprintf(
                    '- [%s] %s (Prioridad: %s, Asignado a: %s)',
                    $task->completed_at !== null ? 'Completada' : 'Pendiente',
                    $task->title,
                    $task->priority?->label() ?? 'Media',
                    $task->assignee?->name ?? 'sin asignar',
                );
            }
        }

        $lines[] = '';
        $lines[] = 'Documentos publicos disponibles:';

        $publicDocuments = $project->documents()
            ->where('visibility', DocumentVisibility::Public->value)
            ->orderByDesc('updated_at')
            ->limit(10)
            ->get();

        if ($publicDocuments->isEmpty()) {
            $lines[] = '- (El proyecto no tiene documentos publicos.)';
        } else {
            foreach ($publicDocuments as $document) {
                $lines[] = '- '.$document->title;
            }
        }

        $lines[] = '';
        $lines[] = 'Responde siempre en castellano, de forma concisa. Si no conoces la respuesta o la pregunta queda fuera del alcance del proyecto, indicalo explicitamente.';

        return implode("\n", $lines);
    }
}
