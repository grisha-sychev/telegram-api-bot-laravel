<?php

use Illuminate\Support\Facades\Route;
use App\Models\Bot;

/*
|--------------------------------------------------------------------------
| Bot Webhook Routes  
|--------------------------------------------------------------------------
*/

/**
 * Роут для мультиботной архитектуры с базой данных
 * URL: /webhook/{botName}
 * Боты загружаются из базы данных
 */
Route::post('/webhook/{webhookUrl}', function ($webhookUrl) {
    $secretToken = request()->header('x-telegram-bot-api-secret-token');
    $payload = request()->all();

    // Проверяем бота
    $botModel = Bot::where('webhook_url', $webhookUrl)->where('enabled', true)->first();
    if (!$botModel || $secretToken !== $botModel->webhook_secret) {
        return response()->json(['ok' => true]);
    }

    // Диспатчим в очередь default и сразу отвечаем
    \App\Jobs\ProcessTelegramWebhookJob::dispatch($botModel->id, $payload);

    return response()->json(['ok' => true]);
})->name('bot.webhook'); 