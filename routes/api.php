<?php

use Illuminate\Support\Facades\Route;
use Koffielyder\LaravelAiModel\Http\Controllers\ChatController;

Route::post('api/ai/chat', [ChatController::class, 'handle']);
Route::post('api/ai/generate-json', [ChatController::class, 'generateJson']);
Route::post('api/ai/save-json', [ChatController::class, 'saveJson']);