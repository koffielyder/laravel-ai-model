<?php

declare(strict_types=1);

namespace Koffielyder\LaravelAiModel;

use Illuminate\Support\ServiceProvider;
use Koffielyder\LaravelAiModel\MigrationExtensions\BlueprintMacroServiceProvider;
use Koffielyder\LaravelAiModel\Services\AiGeneratorService;
use Koffielyder\LaravelAiModel\AI\AiClient;

class AiModelServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->mergeConfigFrom(__DIR__.'/../config/ai-model.php', 'ai-model');
        $this->app->register(BlueprintMacroServiceProvider::class);

        $this->app->singleton(AiGeneratorService::class, function ($app) {
            return new AiGeneratorService($app->make(AiClient::class));
        });
    }

    public function boot(): void
    {
        $this->publishes([
            __DIR__.'/../config/ai-model.php' => config_path('ai-model.php'),
        ], 'config');
    }
}
