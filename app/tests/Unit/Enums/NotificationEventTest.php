<?php

namespace Tests\Unit\Enums;

use App\Enums\NotificationEvent;
use Tests\TestCase;

/**
 * Tests unitarios del enum `NotificationEvent`.
 *
 * Cubren: etiquetas en castellano, descripciones legibles,
 * defaults por canal y valores persistentes.
 */
class NotificationEventTest extends TestCase
{
    public function test_label_devuelve_etiqueta_en_castellano(): void
    {
        $this->assertSame('Mensajes nuevos del chat', NotificationEvent::NewMessage->label());
        $this->assertSame('Tareas asignadas', NotificationEvent::TaskAssigned->label());
        $this->assertSame('Tareas con deadline cercano', NotificationEvent::TaskDueSoon->label());
        $this->assertSame('Invitaciones a eventos', NotificationEvent::EventInvitation->label());
        $this->assertSame('Invitaciones a organizaciones', NotificationEvent::OrganizationInvitation->label());
        $this->assertSame('Resumen diario por email', NotificationEvent::DailyDigest->label());
    }

    public function test_description_devuelve_texto_explicativo(): void
    {
        foreach (NotificationEvent::cases() as $event) {
            $this->assertNotEmpty(
                $event->description(),
                "La descripcion de {$event->value} no puede estar vacia.",
            );
        }
    }

    public function test_default_in_app_activo_para_los_eventos_mas_comunes(): void
    {
        // Mensajes, tareas, deadlines y eventos: la campana es util.
        $this->assertTrue(NotificationEvent::NewMessage->defaultInApp());
        $this->assertTrue(NotificationEvent::TaskAssigned->defaultInApp());
        $this->assertTrue(NotificationEvent::TaskDueSoon->defaultInApp());
        $this->assertTrue(NotificationEvent::EventInvitation->defaultInApp());
    }

    public function test_default_in_app_inactivo_para_organizacion_y_digest(): void
    {
        // La invitacion a organizacion no tiene un in-app natural
        // (el destinatario todavia no es usuario). El digest es
        // email por definicion.
        $this->assertFalse(NotificationEvent::OrganizationInvitation->defaultInApp());
        $this->assertFalse(NotificationEvent::DailyDigest->defaultInApp());
    }

    public function test_default_email_activo_para_todos_los_eventos(): void
    {
        // El MVP asume que el email es el canal "por defecto"
        // para cualquier aviso. El opt-out lo gestiona el usuario.
        foreach (NotificationEvent::cases() as $event) {
            $this->assertTrue(
                $event->defaultEmail(),
                "El email deberia estar activo por defecto para {$event->value}.",
            );
        }
    }

    public function test_value_devuelve_el_string_persistente(): void
    {
        $this->assertSame('new_message', NotificationEvent::NewMessage->value);
        $this->assertSame('task_assigned', NotificationEvent::TaskAssigned->value);
        $this->assertSame('task_due_soon', NotificationEvent::TaskDueSoon->value);
        $this->assertSame('event_invitation', NotificationEvent::EventInvitation->value);
        $this->assertSame('organization_invitation', NotificationEvent::OrganizationInvitation->value);
        $this->assertSame('daily_digest', NotificationEvent::DailyDigest->value);
    }

    public function test_catalogo_de_eventos_tiene_seis_casos(): void
    {
        // Si esto cambia, hay que revisar la politica de defaults
        // y la pagina de preferencias.
        $this->assertCount(6, NotificationEvent::cases());
    }
}
