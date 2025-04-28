<?php

declare(strict_types=1);

namespace Koffielyder\LaravelAiModel\AI;

use Illuminate\Support\Facades\Http;

class AiClient
{
    public function generate(string $systemPrompt, string $userPrompt): string
    {
        $response = Http::withToken(config('ai-model.api_key'))
            ->post(config('ai-model.endpoint'), [
                'model' => config('ai-model.model', 'gpt-4'),
                'messages' => [
                    ['role' => 'system', 'content' => $systemPrompt],
                    ['role' => 'user', 'content' => $userPrompt],
                ],
            ]);

        $data = $response->json();
        if (isset($data['choices'][0]['message']['content'])) {
            return (string) $data['choices'][0]['message']['content'];
        }

        throw new \RuntimeException('Invalid AI response: no content returned.');
    }
}
