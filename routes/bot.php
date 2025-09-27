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
        
        // Запускаем обработку (приоритет: main > run)
        if (method_exists($bot, 'main')) {
            return $bot->main();
        } elseif (method_exists($bot, 'run')) {
            return $bot->run()->main();
        } else {
            \Log::error("Bot: Bot {$class} has no main() or run() method");
            return response()->json(['error' => 'Bot method not found'], 500);
        }
        
    } catch (\Exception $e) {
        \Log::error("Bot: Error processing webhook for {$webhookUrl}: " . $e->getMessage(), [
            'bot' => $botModel->name,
            'error' => $e->getMessage(),
            'trace' => $e->getTraceAsString()
        ]);
        
        return response()->json(['error' => 'Internal server error'], 500);
    }
})->name('bot.webhook'); 