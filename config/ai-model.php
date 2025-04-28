<?php

return [
    'api_key' => env('AI_MODEL_API_KEY', ''),
    'endpoint' => env('AI_MODEL_ENDPOINT', 'https://api.openai.com/v1/chat/completions'),
    'model' => env('AI_MODEL_NAME', 'gpt-4.1'),
    'context' => env('AI_MODEL_CONTEXT', 'This system is used to create a Dungeons & Dragons campaign world, including worlds, regions, and places with rich backstories.'),
];
