<?php

declare(strict_types=1);

namespace Koffielyder\LaravelAiModel\Traits;

use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

trait HasAi
{
    public static function getAiSchema(): array
    {
        $instance = new static();
        $table = $instance->getTable();

        $columns = Schema::getColumnListing($table);

        $schema = [];

        foreach ($columns as $name) {
            if (in_array($name, ['id', 'created_at', 'updated_at', 'deleted_at'])) {
                continue;
            }

            $schema[$name] = Str::headline(str_replace('_', ' ', $name));
        }

        return $schema;
    }

    public static function getAiRelations(): array
    {
        $instance = new static();

        if (property_exists($instance, 'aiRelations')) {
            return $instance->aiRelations;
        }

        return [];
    }
}
