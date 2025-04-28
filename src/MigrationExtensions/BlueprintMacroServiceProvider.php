<?php

declare(strict_types=1);

namespace Koffielyder\LaravelAiModel\MigrationExtensions;

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\ServiceProvider;

class BlueprintMacroServiceProvider extends ServiceProvider
{
    public function boot(): void
    {
        Blueprint::macro('description', function (string $description) {
            /** @var \Illuminate\Database\Schema\ColumnDefinition $this */
            $this->comment($description);
            return $this;
        });
    }
}
