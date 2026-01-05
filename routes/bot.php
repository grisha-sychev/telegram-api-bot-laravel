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

    // Отвечаем сразу
    while (ob_get_level() > 0) {
        ob_end_clean();
    }
    http_response_code(200);
    header('Content-Type: application/json');
    header('Connection: close');
    header('Content-Length: 13');
    echo '{"ok":true}';
    flush();
    if (function_exists('fastcgi_finish_request')) {
        fastcgi_finish_request();
    }

    // Дедупликация через БД
    if ($updateId) {
        $cacheKey = "tg_upd_{$webhookUrl}_{$updateId}";
        try {
            $exists = \DB::table('cache')->where('key', $cacheKey)->exists();
            if ($exists) {
                return; // Уже обработано
            }
            \DB::table('cache')->insert([
                'key' => $cacheKey,
                'value' => '1',
                'expiration' => time() + 300,
            ]);
        } catch (\Throwable $e) {
            // Если таблицы cache нет - продолжаем без дедупликации
        }
    }

    // Обработка
    $botModel = Bot::where('webhook_url', $webhookUrl)->where('enabled', true)->first();
    if (!$botModel || $secretToken !== $botModel->webhook_secret) {
        return;
    }

    try {
        $class = $botModel->getBotClass();
        if (!class_exists($class)) {
            return;
        }

        $bot = new $class();
        if (method_exists($bot, 'setToken')) {
            $bot->setToken($botModel->token);
        }
        if (method_exists($bot, 'setBotModel')) {
            $bot->setBotModel($botModel);
        }
        if (method_exists($bot, 'main')) {
            $bot->main();
        } elseif (method_exists($bot, 'run')) {
            $bot->run()->main();
        }
    } catch (\Throwable $e) {
        \Log::error("Webhook error: " . $e->getMessage());
    }
})->name('bot.webhook'); 