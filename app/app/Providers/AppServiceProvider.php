<?php

namespace App\Providers;

use App\Services\Ai\AiRateLimiter;
use App\Services\Ai\AiService;
use App\Services\Ai\ProjectContextBuilder;
use App\Services\Ai\Providers\AnthropicProvider;
use App\Services\Ai\Providers\OpenAiProvider;
use App\Services\Ai\Providers\OpencodeProvider;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        // Singletons para los servicios de IA. No tienen
        // estado, asi que una sola instancia basta para todo
        // el ciclo de vida del request.
        $this->app->singleton(AiRateLimiter::class);
        $this->app->singleton(ProjectContextBuilder::class);

        $this->app->singleton(OpenAiProvider::class);
        $this->app->singleton(AnthropicProvider::class);
        $this->app->singleton(OpencodeProvider::class);

        $this->app->singleton(AiService::class, function ($app): AiService {
            return new AiService(
                rateLimiter: $app->make(AiRateLimiter::class),
                contextBuilder: $app->make(ProjectContextBuilder::class),
                providers: AiService::defaultProviders(
                    openai: $app->make(OpenAiProvider::class),
                    anthropic: $app->make(AnthropicProvider::class),
                    opencode: $app->make(OpencodeProvider::class),
                ),
            );
        });
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        //
    }
}
