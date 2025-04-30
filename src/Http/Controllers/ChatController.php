<?php

namespace Koffielyder\LaravelAiModel\Http\Controllers;

use Illuminate\Routing\Controller;
use Illuminate\Http\Request;
use Koffielyder\LaravelAiModel\Models\AiModel;
use Koffielyder\LaravelAiModel\Services\AiService;
use Koffielyder\LaravelAiModel\Services\AiModelSaver;

class ChatController extends Controller
{
    protected AiService $aiService;
    protected AiModelSaver $aiModelSaver;

    public function __construct(AiService $aiService, AiModelSaver $aiModelSaver)
    {
        $this->aiService = $aiService;
        $this->aiModelSaver = $aiModelSaver;
    }

    public function handle(Request $request)
    {
        $validated = $request->validate([
            'history' => 'required|array',
            'history.*.role' => 'required|string|in:user,assistant,system',
            'history.*.content' => 'required|string',
            'models' => 'array',
            'models.*' => 'string',
        ]);

        $history = $validated['history'];
        $models = $validated['models'] ?? [];

        // System prompt always at the top
        $messages = [
            [
                'role' => 'system',
                'content' => config('ai-model.usage_context', 'You are assisting with creating structured entity data.'),
            ],
            [
                'role' => 'system',
                'content' => AiModel::buildAiPrompt($models),
            ],
        ];

        // Append user's conversation history
        foreach ($history as $message) {
            $messages[] = [
                'role' => $message['role'],
                'content' => $message['content'],
            ];
        }

        // Send the entire conversation
        $reply = $this->aiService->sendMessages($messages);

        return response()->json([
            'reply' => $reply,
        ]);
    }

    public function generateJson(Request $request)
    {
        $validated = $request->validate([
            'history' => 'required|array',
            'history.*.role' => 'required|string|in:user,assistant,system',
            'history.*.content' => 'required|string',
        ]);

        $messages = [];

        $systemPrompt = "Structure the following information into clean, valid JSON following the fields and relations defined earlier. Only output the JSON, nothing else.";

        $messages[] = ['role' => 'system', 'content' => $systemPrompt];

        foreach ($validated['history'] as $msg) {
            $messages[] = [
                'role' => $msg['role'],
                'content' => $msg['content'],
            ];
        }

        $reply = $this->aiService->sendMessages($messages);

        $parsedJson = json_decode($reply, true);

        $preparedJson = $this->aiModelSaver->prepareOperations($parsedJson);

        return response()->json([
            'json' => $preparedJson,
        ]);
    }

    public function saveJson(Request $request)
    {
        $validated = $request->validate([
            'data' => 'required|array',
        ]);

        $this->aiModelSaver->saveFromPreparedJson($validated['data']);


        return response()->json([
            'status' => 'success',
        ]);
    }
}
