<?php

namespace Tests\Unit\Services\Ai;

use App\Models\AiConfig;
use App\Services\Ai\AiRateLimiter;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AiRateLimiterTest extends TestCase
{
    use RefreshDatabase;

    public function test_allows_messages_up_to_the_configured_limit(): void
    {
        $config = AiConfig::factory()->create(['max_messages_per_hour' => 2]);
        $limiter = app(AiRateLimiter::class);

        $this->assertTrue($limiter->canSendMessage($config, 1, 10));
        $limiter->hitMessage($config, 1, 10);
        $this->assertTrue($limiter->canSendMessage($config, 1, 10));
        $limiter->hitMessage($config, 1, 10);
        $this->assertFalse($limiter->canSendMessage($config, 1, 10));
    }

    public function test_counts_per_user_and_project_independently(): void
    {
        $config = AiConfig::factory()->create(['max_messages_per_hour' => 1]);
        $limiter = app(AiRateLimiter::class);

        $limiter->hitMessage($config, 1, 10);
        $this->assertFalse($limiter->canSendMessage($config, 1, 10));
        $this->assertTrue($limiter->canSendMessage($config, 1, 11));
        $this->assertTrue($limiter->canSendMessage($config, 2, 10));
    }

    public function test_sessions_have_a_separate_daily_limit(): void
    {
        $config = AiConfig::factory()->create(['max_sessions_per_day' => 1]);
        $limiter = app(AiRateLimiter::class);

        $this->assertTrue($limiter->canCreateSession($config, 1, 10));
        $limiter->hitSession($config, 1, 10);
        $this->assertFalse($limiter->canCreateSession($config, 1, 10));
    }

    public function test_falls_back_to_default_limits_when_config_is_zero(): void
    {
        config()->set('ai.default_max_messages_per_hour', 5);
        $config = AiConfig::factory()->create(['max_messages_per_hour' => 0]);
        $limiter = app(AiRateLimiter::class);

        for ($i = 0; $i < 5; $i++) {
            $this->assertTrue($limiter->canSendMessage($config, 1, 10));
            $limiter->hitMessage($config, 1, 10);
        }
        $this->assertFalse($limiter->canSendMessage($config, 1, 10));
    }
}
