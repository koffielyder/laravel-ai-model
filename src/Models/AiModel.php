<?php

namespace Koffielyder\LaravelAiModel\Models;

use Closure;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Schema\Blueprint;

abstract class AiModel extends Model
{
    /**
     * Always required base fields for AI models.
     */
    protected array $baseAiFillable = [
        'name',
        'ai_identifier',
        'data',
        'relations',
    ];

    /**
     * User can define extra fillable fields as normal.
     */
    protected $fillable = [];

    protected $casts = [
        'data' => 'array',
        'relations' => 'array',
    ];

    public function getFillable(): array
    {
        return array_unique(array_merge(
            $this->baseAiFillable,
            $this->fillable // User's extra fields
        ));
    }

    public static function schema(): Closure
    {
        return function (Blueprint $table) {
            static::getTableColumns($table);
        };
    }

    public static function getTableColumns(Blueprint $table): void
    {
        $table->id();
        $table->string('ai_identifier')->unique()->description('Unique readable ID for referencing.');
        $table->string('name')->description('Short display name.');
        $table->json('data')->description('A structured JSON object containing the entity\'s detailed attributes and properties.');
        $table->json('relations')->description('A structured JSON object listing related entities, using ai_identifier IDs to define connections.');
        $table->timestamps();
    }

    public static function getAiDescription(array $allowedModels = []): string
    {
        $name = class_basename(static::class);
        $description = static::aiDescription();
        $dataFields = static::dataSchema();
        $relationFields = static::relationSchema();

        $lines = [];
        $lines[] = "Entity: {$name}";
        $lines[] = '';
        $lines[] = $description;
        $lines[] = '';
        $lines[] = 'Fields:';

        foreach ($dataFields as $field => $desc) {
            $lines[] = "- `$field`: $desc";
        }

        // 🛠 First collect allowed relations
        $allowedRelations = [];

        foreach ($relationFields as $relation => $meta) {
            $relatedModelClass = is_array($meta) ? ($meta['model'] ?? null) : null;

            if (!empty($allowedModels) && $relatedModelClass && !in_array(class_basename($relatedModelClass), $allowedModels)) {
                continue;
            }

            $desc = is_array($meta) ? ($meta['description'] ?? 'No description.') : $meta;
            $allowedRelations[] = "- `$relation`: {$desc} (reference to another entity)";
        }

        // 🛠 Only if we have valid relations, output the "Relations:" section
        if (!empty($allowedRelations)) {
            $lines[] = '';
            $lines[] = 'Relations:';
            $lines = array_merge($lines, $allowedRelations);
        }

        return implode("\n", $lines);
    }

    public static function buildAiPrompt(array $onlyModels = []): string
    {
        $lines = [];

        $usageContext = config('ai-model.usage_context', 'creating structured entity data');
        $lines[] = "You are assisting with {$usageContext}.";
        $lines[] = "Here are the available entity types you can work with:";
        $lines[] = '';

        $modelClasses = config('ai-model.models', []);

        $onlyBasenames = array_map(function ($model) {
            return is_string($model) ? class_basename($model) : class_basename($model);
        }, $onlyModels);

        foreach ($modelClasses as $modelClass) {
            if (!is_subclass_of($modelClass, static::class)) {
                continue;
            }

            if (!empty($onlyBasenames) && !in_array(class_basename($modelClass), $onlyBasenames)) {
                continue;
            }

            /** @var \Koffielyder\LaravelAiModel\Models\AiModel $modelClass */
            $lines[] = $modelClass::getAiDescription($onlyBasenames); // 👈 pass allowed models
            $lines[] = str_repeat('-', 80);
        }

        $lines[] = "Focus on creativity, vivid details, and consistency across entities.";
        $lines[] = "You do not need to provide a structured format yet — we will organize the information later.";

        return implode("\n", $lines);
    }

    abstract public static function dataSchema(): array;
    abstract public static function relationSchema(): array;
    abstract public static function aiDescription(): string;
}
