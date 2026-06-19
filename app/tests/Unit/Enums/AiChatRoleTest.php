<?php

namespace Tests\Unit\Enums;

use App\Enums\AiChatRole;
use PHPUnit\Framework\TestCase;

class AiChatRoleTest extends TestCase
{
    public function test_enum_has_three_expected_cases(): void
    {
        $values = array_map(
            static fn (AiChatRole $role): string => $role->value,
            AiChatRole::cases(),
        );

        $this->assertSame(['user', 'assistant', 'system'], $values);
    }

    public function test_helper_flags_return_the_right_values(): void
    {
        $this->assertTrue(AiChatRole::User->isUser());
        $this->assertFalse(AiChatRole::User->isAssistant());
        $this->assertFalse(AiChatRole::User->isSystem());

        $this->assertFalse(AiChatRole::Assistant->isUser());
        $this->assertTrue(AiChatRole::Assistant->isAssistant());
        $this->assertFalse(AiChatRole::Assistant->isSystem());

        $this->assertFalse(AiChatRole::System->isUser());
        $this->assertFalse(AiChatRole::System->isAssistant());
        $this->assertTrue(AiChatRole::System->isSystem());
    }
}
