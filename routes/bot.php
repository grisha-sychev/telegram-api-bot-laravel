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
    $updateId = $payload['update_id'] ?? null;

    // Дедупликация
    if ($updateId) {
        $cacheKey = "tg_update_{$webhookUrl}_{$updateId}";
        if (cache()->has($cacheKey)) {
            return response()->json(['ok' => true]);
        }
        cache()->put($cacheKey, true, 300);
    }

    // Проверяем бота
    $botModel = Bot::where('webhook_url', $webhookUrl)->where('enabled', true)->first();
    if (!$botModel || $secretToken !== $botModel->webhook_secret) {
        return response()->json(['ok' => true]);
    }

    // Диспатчим в очередь и сразу отвечаем
    \App\Jobs\ProcessTelegramWebhookJob::dispatch($botModel->id, $payload);

    return response()->json(['ok' => true]);
})->name('bot.webhook'); 