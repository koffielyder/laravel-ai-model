<?php

namespace Koffielyder\LaravelAiModel\Services;

use Illuminate\Support\Facades\Http;

class AiService
{
    public function sendMessages(array $messages): string
    {
        $apiKey = config('ai-model.api_key');
        $model = config('ai-model.default_model', 'gpt-4');
        $endpoint = config('ai-model.endpoint');

        $response = Http::withToken($apiKey)
            ->post($endpoint, [
                'model' => $model,
                'messages' => $messages,
                'temperature' => 0.7,
            ]);

        if ($response->failed()) {
            throw new \Exception('Failed to communicate with AI service: ' . $response->body());
        }

        return $response->json('choices.0.message.content');
    }
}
