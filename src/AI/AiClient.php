<?php

namespace Koffielyder\LaravelAiModel\AI;

use Illuminate\Support\Facades\Http;

class AiClient
{
    public function generateWithMemory(array $messages): string
    {
        $response = Http::withToken(config('ai-model.api_key'))
            ->post(config('ai-model.endpoint'), [
                'model' => config('ai-model.model', 'gpt-3.5-turbo'),
                'messages' => $messages,
            ]);

        $data = $response->json();

        if (isset($data['choices'][0]['message']['content'])) {
            $content = $data['choices'][0]['message']['content'];

            if (!is_string($content)) {
                throw new \RuntimeException('AI returned non-string content.');
            }

            return trim($content);
        }

        throw new \RuntimeException('Invalid AI response: No content available.');
    }
}
