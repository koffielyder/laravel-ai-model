<?php

namespace Koffielyder\LaravelAiModel;

use Illuminate\Support\ServiceProvider;

class AiModelServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->mergeConfigFrom(__DIR__.'/../config/ai-model.php', 'ai-model');
    }

    public function boot(): void
    {
        if ($this->app->runningInConsole()) {
            $this->publishes([
                __DIR__.'/../config/ai-model.php' => config_path('ai-model.php'),
            ], 'config');
    
            $this->commands([
                \Koffielyder\LaravelAiModel\Console\Commands\DiscoverAiModelsCommand::class,
            ]);
        }

        $this->loadRoutesFrom(__DIR__.'/../routes/api.php');
    }
}
