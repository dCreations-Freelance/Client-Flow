<?php

namespace Tests\Unit\Models;

use App\Enums\AiChatRole;
use App\Models\AiChatMessage;
use App\Models\AiChatSession;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AiChatMessageTest extends TestCase
{
    use RefreshDatabase;

    public function test_role_is_cast_to_ai_chat_role_enum(): void
    {
        $message = AiChatMessage::factory()->assistant()->create();
        $this->assertSame(AiChatRole::Assistant, $message->role);
    }

    public function test_helper_flags_return_the_right_values(): void
    {
        $user = AiChatMessage::factory()->create();
        $assistant = AiChatMessage::factory()->assistant()->create();
        $system = AiChatMessage::factory()->system()->create();

        $this->assertTrue($user->isUser());
        $this->assertFalse($user->isAssistant());
        $this->assertFalse($user->isSystem());

        $this->assertTrue($assistant->isAssistant());
        $this->assertTrue($system->isSystem());
    }

    public function test_belongs_to_a_session(): void
    {
        $session = AiChatSession::factory()->create();
        $message = AiChatMessage::factory()->for($session, 'session')->create();

        $this->assertSame($session->id, $message->session->id);
    }
}
