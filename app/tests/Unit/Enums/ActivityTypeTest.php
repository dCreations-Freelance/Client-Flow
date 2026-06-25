<?php

namespace Tests\Unit\Enums;

use App\Enums\ActivityType;
use PHPUnit\Framework\TestCase;

/**
 * Tests del enum `ActivityType`.
 *
 * Cubre los helpers de cada caso (`label`, `icon`, `tone`,
 * `category`, `isPublic`) y el mapa estatico `categoryLabels`.
 *
 * El enum es la "fuente de verdad" sobre que eventos existen
 * y como se muestran, asi que cualquier cambio aqui debe
 * acompanarse de tests que verifiquen las invariantes:
 *
 *  - `label()` nunca vacio.
 *  - `icon()` siempre en un set cerrado.
 *  - `tone()` siempre en un set cerrado.
 *  - `category()` siempre una clave de `categoryLabels()`.
 *  - `isPublic()` coherente con el set conservador.
 */
class ActivityTypeTest extends TestCase
{
    public function test_label_no_vacio_para_todos_los_casos(): void
    {
        foreach (ActivityType::cases() as $type) {
            $this->assertNotEmpty(
                $type->label(),
                "El caso {$type->value} no tiene label legible.",
            );
        }
    }

    public function test_icon_en_set_cerrado(): void
    {
        $allowed = ['task', 'document', 'event', 'message', 'project', 'member', 'attachment'];

        foreach (ActivityType::cases() as $type) {
            $this->assertContains(
                $type->icon(),
                $allowed,
                "El caso {$type->value} devuelve un icono no soportado: {$type->icon()}",
            );
        }
    }

    public function test_tone_en_set_cerrado(): void
    {
        $allowed = ['blue', 'green', 'amber', 'red', 'purple', 'gray'];

        foreach (ActivityType::cases() as $type) {
            $this->assertContains(
                $type->tone(),
                $allowed,
                "El caso {$type->value} devuelve un tono no soportado: {$type->tone()}",
            );
        }
    }

    public function test_category_coincide_con_clave_de_category_labels(): void
    {
        $labels = ActivityType::categoryLabels();

        foreach (ActivityType::cases() as $type) {
            $this->assertArrayHasKey(
                $type->category(),
                $labels,
                "La categoria {$type->category()} del caso {$type->value} no esta en categoryLabels().",
            );
        }
    }

    public function test_is_public_es_false_para_eventos_internos(): void
    {
        // El set conservador marca como privados los eventos
        // de auditoria interna: tareas eliminadas/actualizadas,
        // miembros y operaciones de admin (crear proyecto,
        // aplicar plantilla).
        $privateCases = [
            ActivityType::TaskUpdated,
            ActivityType::TaskDeleted,
            ActivityType::MemberAdded,
            ActivityType::MemberRemoved,
            ActivityType::ProjectCreated,
            ActivityType::TemplateApplied,
        ];

        foreach ($privateCases as $type) {
            $this->assertFalse(
                $type->isPublic(),
                "{$type->value} deberia ser privado (admin-only) pero isPublic() devuelve true.",
            );
        }
    }

    public function test_is_public_es_true_para_eventos_visibles_al_cliente(): void
    {
        // Lo que el cliente quiere ver: cambios de estado
        // visibles, mensajes del chat, eventos de calendario,
        // archivado y tareas en su ciclo de vida principal.
        $publicCases = [
            ActivityType::TaskCreated,
            ActivityType::TaskCompleted,
            ActivityType::TaskReopened,
            ActivityType::TaskMoved,
            ActivityType::MessageSent,
            ActivityType::StatusChanged,
            ActivityType::ProjectArchived,
            ActivityType::ProjectUnarchived,
            ActivityType::EventCreated,
            ActivityType::EventUpdated,
            ActivityType::EventDeleted,
        ];

        foreach ($publicCases as $type) {
            $this->assertTrue(
                $type->isPublic(),
                "{$type->value} deberia ser publico pero isPublic() devuelve false.",
            );
        }
    }

    public function test_category_labels_incluye_all_y_seis_categorias_concretas(): void
    {
        $labels = ActivityType::categoryLabels();

        $this->assertArrayHasKey('all', $labels);
        $this->assertArrayHasKey('tasks', $labels);
        $this->assertArrayHasKey('documents', $labels);
        $this->assertArrayHasKey('events', $labels);
        $this->assertArrayHasKey('messages', $labels);
        $this->assertArrayHasKey('project', $labels);
        $this->assertArrayHasKey('members', $labels);
        $this->assertCount(7, $labels);
    }

    public function test_value_es_el_identificador_canonico_para_bd(): void
    {
        // El value es lo que se persiste en `activity_log.type`.
        // Este test documenta que el contrato esta roto si se
        // cambia el `value` sin migracion.
        $this->assertSame('task_created', ActivityType::TaskCreated->value);
        $this->assertSame('message_sent', ActivityType::MessageSent->value);
        $this->assertSame('attachment_uploaded_to_task', ActivityType::AttachmentUploadedToTask->value);
    }
}
