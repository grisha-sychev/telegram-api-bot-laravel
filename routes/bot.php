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
    try {
        // Ищем бота в базе данных
        $botModel = Bot::where('webhook_url', $webhookUrl)->where('enabled', true)->first();
        
        if (!$botModel) {
            return response()->json(['error' => 'Not found'], 404);
        }

        // Проверяем заголовок x-telegram-bot-api-secret-token
        $secretToken = request()->header('x-telegram-bot-api-secret-token');
        $expectedSecret = $botModel->webhook_secret;
        
        if ($secretToken !== $expectedSecret) {
            return response()->json(['error' => 'Not found'], 404);
        }

        // Формируем имя класса бота
        $class = $botModel->getBotClass();

        if (!class_exists($class)) {
            \Log::error("Bot: Bot class not found: {$class}");
            return response()->json(['error' => 'Bot class not found'], 404);
        }
        
        // Создаем экземпляр бота
        $bot = new $class();
        
        // Устанавливаем токен для LightBot
        if (method_exists($bot, 'setToken')) {
            $bot->setToken($botModel->token);
        }

        // Устанавливаем дополнительные настройки если метод существует
        if (method_exists($bot, 'setBotModel')) {
            $bot->setBotModel($botModel);
        }
        
        // ВАЖНО: webhook должен отвечать быстро, иначе Telegram начнёт ретраить update.
        // Поэтому отвечаем 200 сразу, а обработку делаем после отправки ответа.
        $response = response()->json(['ok' => true]);

        register_shutdown_function(function () use ($bot, $class, $webhookUrl) {
            try {
                // Запускаем обработку (приоритет: main > run)
                if (method_exists($bot, 'main')) {
                    $bot->main();
                    return;
                }

                if (method_exists($bot, 'run')) {
                    $bot->run()->main();
                    return;
                }

                \Log::error("Bot: Bot {$class} has no main() or run() method");
            } catch (\Throwable $e) {
                \Log::error("Bot: Error processing webhook for {$webhookUrl}: ".$e->getMessage(), [
                    'bot_class' => $class,
                    'error' => $e->getMessage(),
                    'trace' => $e->getTraceAsString(),
                ]);
            }
        });

        return $response;
        
    } catch (\Exception $e) {
        \Log::error("Bot: Error processing webhook for {$webhookUrl}: " . $e->getMessage(), [
            'bot' => $botModel->name,
            'error' => $e->getMessage(),
            'trace' => $e->getTraceAsString()
        ]);
        
        return response()->json(['error' => 'Internal server error'], 500);
    }
})->name('bot.webhook'); 