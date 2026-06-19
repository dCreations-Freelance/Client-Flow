<?php

namespace Tests\Unit\Enums;

use App\Enums\AiProvider;
use PHPUnit\Framework\TestCase;

class AiProviderTest extends TestCase
{
    public function test_enum_has_three_expected_cases(): void
    {
        $values = array_map(
            static fn (AiProvider $provider): string => $provider->value,
            AiProvider::cases(),
        );

        $this->assertSame(['openai', 'anthropic', 'opencode'], $values);
    }

    public function test_each_case_exposes_a_non_empty_default_model_and_label(): void
    {
        foreach (AiProvider::cases() as $provider) {
            $this->assertNotEmpty($provider->defaultModel());
            $this->assertNotEmpty($provider->label());
            $this->assertNotEmpty($provider->baseUrl());
        }
    }

    public function test_openai_and_opencode_are_marked_as_openai_compatible(): void
    {
        $this->assertTrue(AiProvider::Openai->isOpenaiCompatible());
        $this->assertTrue(AiProvider::Opencode->isOpenaiCompatible());
        $this->assertFalse(AiProvider::Anthropic->isOpenaiCompatible());
    }

    public function test_opencode_default_model_and_url_match_zen_api(): void
    {
        // El default debe apuntar a un modelo de Opencode
        // Zen namespaced y a la URL real del servicio.
        $this->assertStringStartsWith('opencode-go/', AiProvider::Opencode->defaultModel());
        $this->assertSame('https://opencode.ai/zen/go', AiProvider::Opencode->baseUrl());
    }
}
