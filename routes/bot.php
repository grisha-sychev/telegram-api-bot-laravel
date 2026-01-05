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
    // Сохраняем ВСЕ данные из request ДО отправки ответа, потому что после
    // fastcgi_finish_request() объект request может быть недоступен
    $secretToken = request()->header('x-telegram-bot-api-secret-token');
    $payload = request()->all();

    // Отдаем 200 СРАЗУ — это критично для Telegram (таймаут 60 сек)
    // Сначала очищаем ВСЕ буферы Laravel/middleware
    while (ob_get_level() > 0) {
        ob_end_clean();
    }
    
    // Отправляем ответ напрямую
    http_response_code(200);
    header('Content-Type: application/json');
    header('Connection: close');
    header('Content-Length: 13');
    echo '{"ok":true}';
    flush();
    
    // Завершаем HTTP-соединение
    if (function_exists('fastcgi_finish_request')) {
        fastcgi_finish_request();
        \Log::debug('Bot: fastcgi_finish_request() called successfully');
    } else {
        \Log::warning('Bot: fastcgi_finish_request() NOT available - response may be delayed');
    }

    // Теперь обрабатываем webhook в фоне (соединение уже закрыто, 502 невозможен)
    try {
        $botModel = Bot::where('webhook_url', $webhookUrl)->where('enabled', true)->first();

        if (!$botModel) {
            return;
        }

        if ($secretToken !== $botModel->webhook_secret) {
            return;
        }

        $class = $botModel->getBotClass();

        if (!class_exists($class)) {
            \Log::error("Bot: Bot class not found: {$class}");
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
        } else {
            \Log::error("Bot: Bot {$class} has no main() or run() method");
        }
    } catch (\Throwable $e) {
        \Log::error("Bot: Error processing webhook for {$webhookUrl}: " . $e->getMessage(), [
            'error' => $e->getMessage(),
            'trace' => $e->getTraceAsString(),
        ]);
    }
})->name('bot.webhook'); 