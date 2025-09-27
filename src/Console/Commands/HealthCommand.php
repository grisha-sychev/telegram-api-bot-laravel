<?php

namespace Bot\Console\Commands;

use Bot\Support\Facades\Services;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Cache;
use App\Models\Bot;

class HealthCommand extends Command
{
    protected $signature = 'bot:health {--bot= : Имя конкретного бота для проверки} {--no-ssl : Отключить проверку SSL сертификатов} {--verbose-errors : Показать подробные ошибки из логов}';
    protected $description = 'Проверка состояния ботов';

    public function handle()
    {
        $this->info('🔍 Проверка состояния ботов');
        $this->newLine();

        // Интерактивное меню если команда запущена без опций
        $botName = $this->option('bot');
        $noSsl = $this->option('no-ssl');
        $verboseErrors = $this->option('verbose-errors');
        
        // Проверяем были ли переданы какие-либо опции
        $hasOptionsProvided = count(array_filter($this->options())) > 0;
        
        if (!$hasOptionsProvided && $this->input->isInteractive()) {
            $options = $this->collectHealthOptions();
            $botName = $options['bot_name'];
            $noSsl = $options['no_ssl'];
            $verboseErrors = $options['verbose_errors'];
        }
        
        // Показываем используемые настройки
        if ($botName) {
            $this->line('  🎯 Проверка конкретного бота: ' . $botName);
        } else {
            $this->line('  🎯 Проверка всех ботов');
        }
        if ($noSsl) {
            $this->warn('  ⚠️  SSL проверка отключена');
        }
        if ($verboseErrors) {
            $this->line('  🔍 Подробные ошибки: ВКЛЮЧЕНЫ');
        }
        $this->newLine();

        // Получаем ботов из базы данных
        try {
            if ($botName) {
                // Проверяем конкретного бота
                $bot = Bot::byName($botName)->first();
                if (!$bot) {
                    $this->error("❌ Бот '{$botName}' не найден");
                    $this->line('💡 Доступные боты:');
                    $availableBots = Bot::pluck('name')->toArray();
                    if (empty($availableBots)) {
                        $this->line('   (нет зарегистрированных ботов)');
                    } else {
                        foreach ($availableBots as $name) {
                            $this->line("   - {$name}");
                        }
                    }
                    return 1;
                }
                
                $this->info("🤖 Проверка бота: {$bot->name}");
                $this->newLine();
                $this->checkBot($bot, $noSsl, $verboseErrors);
                $this->newLine();
            } else {
                // Проверяем всех ботов
                $bots = Bot::all();
                
                if ($bots->isEmpty()) {
                    $this->warn('⚠️  Нет зарегистрированных ботов');
                    $this->line('💡 Используйте команду: php artisan bot:new');
                    $this->newLine();
                } else {
                    $this->info("🤖 Найдено ботов: {$bots->count()}");
                    $this->newLine();

                    // Проверяем каждого бота
                    foreach ($bots as $bot) {
                        $this->checkBot($bot, $noSsl, $verboseErrors);
                        $this->newLine();
                    }
                }
            }
        } catch (\Exception $e) {
            $this->error('❌ Ошибка подключения к базе данных: ' . $e->getMessage());
            $this->warn('💡 Убедитесь что запущены миграции: php artisan migrate');
            return 1;
        }

        // Проверяем конфигурацию системы
        $this->checkConfiguration();

        // Проверяем хранилище
        $this->checkStorage();

        // Проверяем состояние системы
        $this->checkSystemHealth();

        $this->newLine();
        $this->info('✅ Проверка завершена');

        return 0;
    }

    private function collectHealthOptions(): array
    {
        $this->info('⚙️ Настройки проверки (опционально)');
        $this->newLine();

        // Получаем список ботов для выбора
        try {
            $bots = Bot::pluck('name')->toArray();
        } catch (\Exception $e) {
            $this->error('❌ Ошибка получения списка ботов: ' . $e->getMessage());
            return [
                'bot_name' => null,
                'no_ssl' => false,
            ];
        }

        // Выбор бота
        $botName = null;
        if (!empty($bots)) {
            $choices = array_merge(['Все боты'], $bots);
            $choice = $this->choice('Какого бота проверить?', $choices, 0);
            
            if ($choice !== 'Все боты') {
                $botName = $choice;
            }
        }

        // Настройки SSL
        $noSsl = $this->confirm('Отключить проверку SSL сертификатов? (только для разработки)', Services::isSSLAvailable() ? true : false);
        
        // Подробные ошибки
        $verboseErrors = $this->confirm('Показать подробный анализ ошибок из логов?', false);

        $this->newLine();

        return [
            'bot_name' => $botName,
            'no_ssl' => $noSsl,
            'verbose_errors' => $verboseErrors,
        ];
    }

    private function checkBot(Bot $bot, bool $noSsl = false, bool $verboseErrors = false): void
    {
        $statusIcon = $bot->enabled ? '🟢' : '🔴';
        $status = $bot->enabled ? 'активен' : 'отключен';
        
        $this->line("{$statusIcon} Бот: {$bot->name} (@{$bot->username}) - {$status}");
        $this->line("  📝 Имя: {$bot->display_name}");
        $this->line("  🆔 ID: {$bot->bot_id}");
        
        // Проверяем наличие токена
        if (!$bot->hasToken()) {
            $this->error("  ❌ Токен не установлен");
            return;
        }
        
        if (!$bot->enabled) {
            $this->warn("  ⚠️  Бот отключен");
            return;
        }

        // Проверяем API связность  
        $token = $bot->token;
        $apiStatus = $this->checkTelegramAPI($token, $noSsl);
        if ($apiStatus['status'] === 'ok') {
            $this->line("  ✅ API: Соединение OK");
            
            // Проверяем класс бота
            if ($bot->botClassExists()) {
                $this->line("  ✅ Класс: {$bot->getBotClass()}");
            } else {
                $this->warn("  ⚠️  Класс бота не найден: {$bot->getBotClass()}");
            }
        } else {
            $this->error("  ❌ API: {$apiStatus['message']}");
        }

        // Проверяем webhook
        $this->checkBotWebhook($bot, $noSsl);
        
        // Показываем подробные ошибки если запрошено
        if ($verboseErrors) {
            $this->showDetailedErrors($bot, $noSsl);
        }
    }

    private function checkTelegramAPI(string $token, bool $noSsl = false): array
    {
        try {
            $http = Http::timeout(10);
            
            if ($noSsl) {
                $http = $http->withOptions([
                    'verify' => false,
                    'curl' => [
                        CURLOPT_SSL_VERIFYPEER => false,
                        CURLOPT_SSL_VERIFYHOST => false,
                    ]
                ]);
            }
            
            $response = $http->get("https://api.telegram.org/bot{$token}/getMe");
            
            if ($response->successful()) {
                $botInfo = $response->json()['result'];
                return [
                    'status' => 'ok',
                    'bot_info' => $botInfo,
                ];
            }
            
            return [
                'status' => 'error',
                'message' => 'API returned: ' . $response->status(),
            ];
        } catch (\Exception $e) {
            return [
                'status' => 'error',
                'message' => $e->getMessage(),
            ];
        }
    }

    private function checkBotWebhook(Bot $bot, bool $noSsl = false): void
    {
        $token = $bot->token;
        
        try {
            $http = Http::timeout(10);
            
            if ($noSsl) {
                $http = $http->withOptions([
                    'verify' => false,
                    'curl' => [
                        CURLOPT_SSL_VERIFYPEER => false,
                        CURLOPT_SSL_VERIFYHOST => false,
                    ]
                ]);
            }
            
            $response = $http->get("https://api.telegram.org/bot{$token}/getWebhookInfo");
            
            if ($response->successful()) {
                $webhook = $response->json()['result'];
                
                if ($webhook['url']) {
                    $this->line("  🌐 Webhook: {$webhook['url']}");
                    
                    if ($webhook['pending_update_count'] > 0) {
                        $this->warn("  ⚠️  Ожидают обработки: {$webhook['pending_update_count']} сообщений");
                    }
                    
                    if ($webhook['last_error_date']) {
                        $errorDate = date('d.m.Y H:i:s', $webhook['last_error_date']);
                        $this->error("  ❌ Последняя ошибка ({$errorDate}): {$webhook['last_error_message']}");
                    }
                } else {
                    $this->warn("  ⚠️  Webhook не настроен");
                }
            } else {
                $this->error("  ❌ Не удалось получить информацию о webhook");
            }
        } catch (\Exception $e) {
            $this->error("  ❌ Ошибка проверки webhook: {$e->getMessage()}");
        }
    }



    private function checkConfiguration(): void
    {
        $this->info("🔧 Конфигурация системы:");
        
        // Проверяем общие настройки
        $logging = config('bot.logging.enabled', false);
        $this->line('  📊 Логирование: ' . ($logging ? 'ВКЛЮЧЕНО' : 'ОТКЛЮЧЕНО'));

        $fileStorage = config('bot.files.download_path', storage_path('app/bot'));
        $this->line("  📁 Хранилище файлов: " . basename($fileStorage));

        $timeout = config('bot.api.timeout', 30);
        $this->line("  ⏱️  Таймаут API: {$timeout}s");

        $retries = config('bot.api.retries', 3);
        $this->line("  🔄 Повторы при ошибках: {$retries}");

        // Проверяем кэширование
        $cacheEnabled = config('bot.cache.enabled', false);
        $cacheDriver = config('bot.cache.driver', 'file');
        $this->line("  💾 Кэширование: " . ($cacheEnabled ? "ВКЛЮЧЕНО ({$cacheDriver})" : 'ОТКЛЮЧЕНО'));

        // Проверяем очереди
        $queueEnabled = config('bot.queue.enabled', false);
        $queueDriver = config('bot.queue.connection', 'sync');
        $this->line("  🚀 Очереди: " . ($queueEnabled ? "ВКЛЮЧЕНО ({$queueDriver})" : 'ОТКЛЮЧЕНО'));
        
        $this->newLine();
    }

    private function checkStorage(): void
    {
        $downloadPath = config('bot.files.download_path', storage_path('app/bot/downloads'));
        
        if (!is_dir($downloadPath)) {
            try {
                mkdir($downloadPath, 0755, true);
                $this->line('  ✅ Storage directory created: ' . basename(dirname($downloadPath)));
            } catch (\Exception $e) {
                $this->error("  ❌ Cannot create storage directory: {$e->getMessage()}");
                return;
            }
        } else {
            $this->line('  ✅ Storage directory exists: ' . basename(dirname($downloadPath)));
        }

        if (!is_writable($downloadPath)) {
            $this->error('  ❌ Storage directory is not writable');
        } else {
            $this->line('  ✅ Storage directory is writable');
        }
    }

    private function checkSystemHealth(): void
    {
        $this->info('🏥 Состояние системы:');

        // Проверка памяти
        $memoryUsage = memory_get_usage(true);
        $memoryLimit = $this->parseMemoryLimit(ini_get('memory_limit'));
        
        if ($memoryLimit > 0) {
            $percentage = round(($memoryUsage / $memoryLimit) * 100, 1);
            $this->line("  💾 Память: " . $this->formatBytes($memoryUsage) . " / " . $this->formatBytes($memoryLimit) . " ({$percentage}%)");
            
            if ($percentage > 80) {
                $this->warn('  ⚠️  Высокое использование памяти');
            }
        } else {
            $this->line("  💾 Память: " . $this->formatBytes($memoryUsage) . " (без лимита)");
        }

        // Проверка Redis (если используется)
        if (config('bot.cache.enabled') && config('bot.cache.driver') === 'redis') {
            try {
                Cache::store('redis')->put('bot_health_test', 'ok', 10);
                $test = Cache::store('redis')->get('bot_health_test');
                
                if ($test === 'ok') {
                    $this->line('  🟢 Redis: Подключен');
                } else {
                    $this->warn('  ⚠️  Redis: Проблемы соединения');
                }
            } catch (\Exception $e) {
                $this->error("  ❌ Redis: {$e->getMessage()}");
            }
        }

        // Последняя активность (если есть логи)
        $this->checkLastActivity();
    }

    private function checkLastActivity(): void
    {
        $logPath = storage_path('logs/laravel.log');
        
        if (file_exists($logPath)) {
            $lastModified = filemtime($logPath);
            $timeDiff = time() - $lastModified;
            
            if ($timeDiff < 300) { // 5 минут
                $this->line('  ⚡ Последняя активность: ' . $this->formatTimeDiff($timeDiff) . ' назад');
            } else {
                $this->warn('  ⚠️  Последняя активность: ' . $this->formatTimeDiff($timeDiff) . ' назад');
            }
        } else {
            $this->line('  📝 Логи отсутствуют');
        }
    }

    private function parseMemoryLimit(string $limit): int
    {
        if ($limit === '-1') return 0;
        
        $limit = trim($limit);
        $bytes = (int) $limit;
        
        if (preg_match('/(\d+)(.)/', $limit, $matches)) {
            $bytes = (int) $matches[1];
            switch (strtoupper($matches[2])) {
                case 'G': $bytes *= 1024;
                case 'M': $bytes *= 1024;
                case 'K': $bytes *= 1024;
            }
        }
        
        return $bytes;
    }

    private function formatBytes(int $bytes): string
    {
        $units = ['B', 'KB', 'MB', 'GB'];
        $i = 0;
        
        while ($bytes >= 1024 && $i < count($units) - 1) {
            $bytes /= 1024;
            $i++;
        }
        
        return round($bytes, 1) . ' ' . $units[$i];
    }

    private function formatTimeDiff(int $seconds): string
    {
        if ($seconds < 60) {
            return "{$seconds} сек";
        } elseif ($seconds < 3600) {
            $minutes = floor($seconds / 60);
            return "{$minutes} мин";
        } else {
            $hours = floor($seconds / 3600);
            $minutes = floor(($seconds % 3600) / 60);
            return "{$hours}ч {$minutes}м";
        }
    }

    private function showDetailedErrors(Bot $bot, bool $noSsl = false): void
    {
        $this->newLine();
        $this->info("🔍 Подробный анализ ошибок для бота '{$bot->name}':");
        
        // Проверяем последние ошибки в логах
        $this->checkRecentLogs($bot);
        
        // Проверяем настройки бота
        $this->checkBotSettings($bot);
        
        // Проверяем доступность webhook URL
        $this->testWebhookEndpoint($bot, $noSsl);
    }

    private function checkRecentLogs(Bot $bot): void
    {
        $logPath = storage_path('logs/laravel.log');
        
        if (!file_exists($logPath)) {
            $this->warn('  📝 Лог файл не найден');
            return;
        }
        
        $this->line('  📝 Анализ логов за последние 24 часа...');
        
        try {
            $lines = file($logPath, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
            $botErrors = [];
            $count = 0;
            
            // Анализируем последние 1000 строк
            $recentLines = array_slice($lines, -1000);
            
            foreach ($recentLines as $line) {
                if (strpos($line, $bot->name) !== false && 
                    (strpos($line, 'ERROR') !== false || strpos($line, 'WARN') !== false)) {
                    
                    $botErrors[] = $line;
                    $count++;
                    
                    if ($count >= 10) break; // Показываем максимум 10 ошибок
                }
            }
            
            if (empty($botErrors)) {
                $this->line('    ✅ Ошибок для этого бота не найдено');
            } else {
                $this->warn("    ⚠️  Найдено ошибок: " . count($botErrors));
                foreach ($botErrors as $error) {
                    $this->line('    ' . trim($error));
                }
            }
            
        } catch (\Exception $e) {
            $this->error("    ❌ Ошибка чтения логов: {$e->getMessage()}");
        }
    }

    private function checkBotSettings(Bot $bot): void
    {
        $this->line('  ⚙️  Анализ настроек бота...');
        
        // Проверяем настройки SSL
        $settings = $bot->settings ?? [];
        if (isset($settings['no_ssl']) && $settings['no_ssl']) {
            $this->warn('    ⚠️  SSL проверка отключена в настройках бота');
        }
        
        // Проверяем администраторов
        if (empty($bot->admin_ids)) {
            $this->warn('    ⚠️  Не указаны ID администраторов');
        } else {
            $adminCount = count($bot->admin_ids);
            $this->line("    👥 Администраторов: {$adminCount}");
        }
        
        // Проверяем webhook secret
        if (empty($bot->webhook_secret)) {
            $this->warn('    ⚠️  Webhook secret не установлен');
        } else {
            $this->line('    🔐 Webhook secret: установлен');
        }
    }

    private function testWebhookEndpoint(Bot $bot, bool $noSsl = false): void
    {
        if (empty($bot->webhook_url)) {
            $this->warn('  🌐 Webhook URL не установлен');
            return;
        }
        
        $this->line('  🌐 Тестирование webhook endpoint...');
        $this->line("    URL: {$bot->webhook_url}");
        
        try {
            // Делаем простой GET запрос к webhook
            $http = \Illuminate\Support\Facades\Http::timeout(30);
            
            if ($noSsl) {
                $http = $http->withOptions([
                    'verify' => false,
                    'curl' => [
                        CURLOPT_SSL_VERIFYPEER => false,
                        CURLOPT_SSL_VERIFYHOST => false,
                    ]
                ]);
                $this->line('    ⚠️  SSL проверка отключена для теста');
            }
            
            $response = $http->get($bot->webhook_url);
            
            $this->line("    📡 HTTP статус: {$response->status()}");
            
            if ($response->status() === 405) {
                $this->line('    ✅ Endpoint отвечает (405 ожидаем для GET запроса)');
            } elseif ($response->status() >= 200 && $response->status() < 300) {
                $this->line('    ✅ Endpoint доступен');
            } else {
                $this->warn("    ⚠️  Неожиданный статус: {$response->status()}");
            }
            
            // Проверяем время ответа
            $responseTime = $response->transferStats?->getTransferTime() * 1000;
            if ($responseTime) {
                $this->line("    ⏱️  Время ответа: " . round($responseTime, 2) . "ms");
                if ($responseTime > 5000) {
                    $this->warn('    ⚠️  Медленный ответ (>5с)');
                }
            }
            
        } catch (\Exception $e) {
            $this->error("    ❌ Ошибка подключения: {$e->getMessage()}");
        }
    }
} 