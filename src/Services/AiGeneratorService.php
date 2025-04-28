<?php

declare(strict_types=1);

namespace Koffielyder\LaravelAiModel\Services;

use Koffielyder\LaravelAiModel\AI\AiClient;
use Koffielyder\LaravelAiModel\Contracts\AiCreatable;

class AiGeneratorService
{
    protected AiClient $aiClient;

    public function __construct(AiClient $aiClient)
    {
        $this->aiClient = $aiClient;
    }

    public function generate(AiCreatable $model, string $userPrompt): array
    {
        $schema = $model::getAiSchema();
        $relations = $model::getAiRelations();

        $systemPrompt = $this->buildSystemPrompt($schema, $relations);
        $aiResponse = $this->aiClient->generate($systemPrompt, $userPrompt);

        return $this->parseAiResponse($aiResponse);
    }

    protected function buildSystemPrompt(array $schema, array $relations): string
    {
        $context = config('ai-model.context', '');

        $prompt = "Context: {$context}\n\n";
        $prompt .= "You are an assistant that generates JSON data for the following model:\n\n";
        $prompt .= "Fields:\n";
        foreach ($schema as $field => $description) {
            $prompt .= "- {$field}: {$description}\n";
        }

        if (!empty($relations)) {
            $prompt .= "\nRelationships:\n";
            foreach ($relations as $relation => $relatedModel) {
                $prompt .= "- {$relation}: relates to {$relatedModel}\n";
            }
        }

        $prompt .= "\nPlease provide a JSON object matching this structure.";

        return $prompt;
    }

    protected function parseAiResponse(string $aiResponse): array
    {
        $data = json_decode($aiResponse, true);

        if (json_last_error() !== JSON_ERROR_NONE) {
            throw new \RuntimeException('Failed to parse AI response: ' . json_last_error_msg());
        }

        return $data;
    }
}
