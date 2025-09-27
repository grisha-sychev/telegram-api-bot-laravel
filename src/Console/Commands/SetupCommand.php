<?php

namespace Bot\Console\Commands;

use Bot\Support\Facades\Services;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;
use App\Models\Bot;

class SetupCommand extends Command
{
    protected $signature = 'bot:new {--webhook= : Webhook URL} {--api-host= : Custom API host} {--no-ssl : Disable SSL verification} {--force : Force setup without confirmation}';
    protected $description = 'Настройка бота';
    private $shouldExit = false;

    public function handle()
    {
        // Устанавливаем обработчик сигналов для graceful завершения
        if (function_exists('pcntl_signal')) {
            pcntl_signal(SIGINT, [$this, 'handleSignal']);  // Ctrl+C
            pcntl_signal(SIGTERM, [$this, 'handleSignal']);
        }

        $this->info('🚀 Bot Setup Wizard');
        $this->newLine();
        $this->line('💡 Для выхода из команды нажмите Ctrl+C');
        $this->newLine();

        // Показываем существующие боты
        $this->showExistingBots();

        // Интерактивный ввод данных нового бота
        $botData = $this->collectBotData();
        if (!$botData || $this->shouldExit) {
            $this->warn('👋 Настройка бота отменена');
            return 1;
        }

        // Проверяем токены и получаем информацию о боте
        $apiHost = $this->option('api-host') ?: 'https://api.telegram.org';
        $noSsl = $botData['no_ssl'] ?? $this->option('no-ssl') ?? false;
        
        // Проверяем токен
        $token = $botData['token'];
        
        if (!$token) {
            $this->error("❌ Токен бота обязателен");
            return 1;
        }

        $botInfo = $this->getBotInfo($token, $apiHost, $noSsl);
        if (!$botInfo || $this->shouldExit) {
            return 1;
        }

        // Дополняем данные информацией от Telegram
        $botData = array_merge($botData, [
            'username' => $botInfo['username'],
            'display_name' => $botInfo['first_name'],
            'description' => $botInfo['description'] ?? null,
            'bot_id' => $botInfo['id'],
        ]);

        $this->displayBotInfo($botInfo);

        // Сохраняем полный URL для webhook
        $fullWebhookUrl = $botData['full_webhook_url'] ?? null;
        
        // Убираем временное поле перед сохранением в БД
        unset($botData['full_webhook_url']);
        
        // Сохраняем бота в базу данных
        $bot = $this->saveBotToDatabase($botData);
        if (!$bot || $this->shouldExit) {
            return 1;
        }

        // Создаем класс бота если не существует
        $this->createBotClass($botData['name']);

        // Настраиваем webhook
        if ($fullWebhookUrl) {
            $this->setupWebhook($bot, $apiHost, $fullWebhookUrl, $noSsl);
        } else {
            $this->warn('⏭️  Webhook не настроен (webhook_url не указан)');
        }

        // Проверяем и создаем конфигурацию
        $this->setupConfiguration();

        // Создаем директории
        $this->createDirectories();

        $this->newLine();
        $this->info('✅ Настройка бота завершена!');
        $this->line("🤖 Бот '{$bot->name}' успешно добавлен");
        $this->line('📖 Документация: vendor/bot/bot/docs/');
        $this->line('🔍 Проверка: php artisan bot:health');

        return 0;
    }

    public function handleSignal(int $signal, int|false $previousExitCode = 0): int|false
    {
        $this->shouldExit = true;
        $this->newLine();
        $this->warn('⚠️  Завершение работы...');
        $this->newLine();
        return 0;
    }

    private function showExistingBots(): void
    {
        try {
            $bots = Bot::all();
            if ($bots->isNotEmpty()) {
                $this->info('📋 Существующие боты:');
                $this->table(
                    ['ID', 'Имя', 'Username', 'Token', 'Webhook URL', 'Статус', 'Создан'],
                    $bots->map(function ($bot) {
                        return [
                            $bot->id,
                            $bot->name,
                            '@' . $bot->username,
                            $bot->hasToken() ? '✅' : '❌',
                            $bot->hasWebhookUrl() ? '✅' : '❌',
                            $bot->enabled ? '✅ Активен' : '❌ Отключен',
                            $bot->created_at->format('d.m.Y H:i')
                        ];
                    })->toArray()
                );
                $this->newLine();
            }
        } catch (\Exception $e) {
            $this->warn('⚠️  Таблица ботов не найдена. Запустите миграции: php artisan migrate');
            $this->newLine();
        }
    }

    private function collectBotData(): ?array
    {
        $this->info('➕ Добавление нового бота');
        $this->newLine();

        $name = null;
        $token = null;
        $webhookUrl = null;
        $webhookSecret = null;
        $adminIdsArray = [];
        $noSsl = false;

        // Запрашиваем имя бота с возможностью повтора
        do {
            if ($this->shouldExit) {
                return null;
            }
            
            // Обрабатываем сигналы во время ожидания ввода
            if (function_exists('pcntl_signal_dispatch')) {
                pcntl_signal_dispatch();
            }
            
            $name = $this->ask('Введите имя бота (латинские буквы, цифры, пробелы)');
            if (!$name) {
                $this->error('❌ Имя бота обязательно');
                continue;
            }

            // Проверяем имя на корректность
            if (!preg_match('/^[a-zA-Z][a-zA-Z0-9\s]*$/', $name)) {
                $this->error('❌ Имя бота должно начинаться с буквы и содержать только латинские буквы, цифры и пробелы');
                continue;
            }

            // Проверяем уникальность имени
            try {
                if (Bot::byName($name)->exists()) {
                    $this->error("❌ Бот с именем '{$name}' уже существует");
                    continue;
                }
            } catch (\Exception $e) {
                // Игнорируем ошибку если таблица не существует
            }

            break; // Если все проверки пройдены, выходим из цикла
        } while (true);

        // Запрашиваем токен с возможностью повтора
        $this->info('🔧 Токен бота:');
        do {
            if ($this->shouldExit) {
                return null;
            }
            
            // Обрабатываем сигналы во время ожидания ввода
            if (function_exists('pcntl_signal_dispatch')) {
                pcntl_signal_dispatch();
            }
            
            $token = $this->ask('Введите токен бота (полученный от @BotFather)');
            if (!$token) {
                $this->error('❌ Токен бота обязателен');
                continue;
            }
            
            if (!preg_match('/^\d+:[A-Za-z0-9_-]{35}$/', $token)) {
                $this->error('❌ Неверный формат токена');
                $this->line('Токен должен иметь формат: 123456789:AABBccDDeeFFggHHiiJJkkLLmmNNooP');
                continue;
            }

            // Проверяем уникальность токена
            try {
                if (Bot::byToken($token)->exists()) {
                    $this->error('❌ Бот с таким токеном уже существует');
                    continue;
                }
            } catch (\Exception $e) {
                // Игнорируем ошибку если таблица не существует
            }

            break; // Если все проверки пройдены, выходим из цикла
        } while (true);

        // Генерируем webhook URL и секрет отдельно для безопасности
        $this->info('🌐 Webhook URL:');
        do {
            if ($this->shouldExit) {
                return null;
            }
            
            // Обрабатываем сигналы во время ожидания ввода
            if (function_exists('pcntl_signal_dispatch')) {
                pcntl_signal_dispatch();
            }
            
            $appUrl = env('APP_URL');
            if (!$appUrl) {
                $this->error('❌ APP_URL не установлен в .env файле');
                $this->line('Добавьте APP_URL=https://your-domain.com в .env файл');
                continue;
            }
            
            // Генерируем простой индикатор для webhook_url
            $webhookUrl = Str::random(12);
            
            // Генерируем длинный секрет для webhook_secret
            $webhookSecret = Str::random(32);
            
            // Формируем полный URL для отправки в Telegram
            $fullWebhookUrl = rtrim($appUrl, '/') . '/webhook/' . $webhookUrl;
            
            $this->line("  🌐 URL будет: {$fullWebhookUrl}");
            $this->line("  🔐 Секрет для проверки: {$webhookSecret}");
            
            if (!$this->confirm('Продолжить с этим webhook URL?', true)) {
                continue;
            }

            break; // Если пользователь подтвердил, выходим из цикла
        } while (true);

        // Запрашиваем администраторов (опционально)
        if ($this->shouldExit) {
            return null;
        }
        $adminIds = $this->ask('Введите ID администраторов через запятую (опционально)');
        if ($adminIds) {
            $adminIdsArray = array_filter(array_map('trim', explode(',', $adminIds)));
            $adminIdsArray = array_map('intval', $adminIdsArray);
        }

        // Запрашиваем отключение SSL проверки (опционально)
        if ($this->shouldExit) {
            return null;
        }

        $noSsl = $this->option('no-ssl') ?: $this->confirm('Отключить проверку SSL сертификатов? (только для разработки)', Services::isSSLAvailable() ? true : false);

        return [
            'name' => $name,
            'token' => $token,
            'admin_ids' => $adminIdsArray,
            'enabled' => true,
            'webhook_url' => $webhookUrl, // Простой индикатор (12 символов)
            'webhook_secret' => $webhookSecret, // Длинный секрет (32 символа)
            'no_ssl' => $noSsl,
            'full_webhook_url' => $fullWebhookUrl, // Временное поле для передачи в setupWebhook
        ];
    }

    private function getBotInfo(string $token, string $apiHost = 'https://api.telegram.org', bool $noSsl = false): ?array
    {
        $this->info('🔍 Проверка токена...');
        $this->line("  🌐 API хост: {$apiHost}");
        if ($noSsl) {
            $this->warn('  ⚠️  SSL проверка отключена');
        }

        try {
            $url = rtrim($apiHost, '/') . "/bot{$token}/getMe";

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

            $response = $http->get($url);

            if ($response->successful()) {
                $this->info('✅ Токен бота валиден');
                return $response->json()['result'];
            } else {
                $result = $response->json();
                $errorMessage = $result['description'] ?? 'Unknown error';
                $this->error('❌ Ошибка API: ' . $response->status() . ' - ' . $errorMessage);
                return null;
            }
        } catch (\Exception $e) {
            $this->error('❌ Ошибка соединения: ' . $e->getMessage());
            return null;
        }
    }

    private function displayBotInfo(array $botInfo): void
    {
        $this->info('🤖 Информация о боте:');
        $this->line("  📝 Имя: {$botInfo['first_name']}");
        $this->line("  🆔 Username: @{$botInfo['username']}");
        $this->line("  📡 ID: {$botInfo['id']}");

        if (isset($botInfo['description'])) {
            $this->line("  📄 Описание: {$botInfo['description']}");
        }

        $this->newLine();
    }

    private function saveBotToDatabase(array $botData): ?Bot
    {
        $this->info('💾 Сохранение бота в базу данных...');

        try {
            $bot = Bot::create($botData);
            $this->info('✅ Бот сохранен в базу данных');
            $this->line("  🔐 Webhook секрет: {$botData['webhook_secret']}");
            return $bot;
        } catch (\Exception $e) {
            $this->error('❌ Ошибка сохранения в БД: ' . $e->getMessage());
            $this->warn('💡 Убедитесь что запущены миграции: php artisan migrate');
            return null;
        }
    }

    private function createBotClass(string $botName): void
    {
        // Преобразуем имя в PascalCase: убираем пробелы и делаем каждое слово с заглавной буквы
        $className = $this->toPascalCase($botName) . 'Bot';
        $classPath = app_path("Bots/{$className}.php");

        if (file_exists($classPath)) {
            $this->info("✅ Класс бота {$className} уже существует");
            return;
        }

        $this->info("📝 Создание класса бота {$className}...");

        // Создаем директорию если не существует
        $botsDir = app_path('Bots');
        if (!is_dir($botsDir)) {
            mkdir($botsDir, 0755, true);
        }

        // Шаблон класса бота
        $classTemplate = "<?php

namespace App\\Bots;

class {$className} extends AbstractBot
{
    public function main(): void
    {
        \$this->commands();
        // Обрабатываем команды
        if (\$this->hasMessageText() && \$this->isMessageCommand()) {
            \$this->handleCommand(\$this->getMessageText());
        }


        // Не обязательно, но рекомендуется, так как обработка автоматическая, будет просто игнорироваться
        \$this->fallback(function () {
            \$this->sendSelf('❌ Ошибка'); // Или что то другое, на ваше усмотрение
        });
    }

    public function commands(): void
    {
        // Регистрируем команды, description не обязательно, но рекомендуется
        \$this->registerCommand('start', function () {
            \$this->sendSelf('🎉 Привет! Я бот {$botName}');
        }, [
            'description' => 'Запуск бота'
        ]);

        \$this->registerCommand('help', function () {
            \$this->sendSelf([
                '📋 Доступные команды:', 
                '', 
                '/start - Запуск бота', 
                '/help - Помощь'
                ]
            );
        }, [
            'description' => 'Помощь'
        ]);
    }

}
";

        try {
            file_put_contents($classPath, $classTemplate);
            $this->info("✅ Класс бота создан: {$classPath}");
        } catch (\Exception $e) {
            $this->error("❌ Ошибка создания класса: {$e->getMessage()}");
        }
    }

    private function setupWebhook(Bot $bot, string $apiHost = 'https://api.telegram.org', string $webhookUrl = null, bool $noSsl = false): void
    {
        if (!$webhookUrl) {
            $this->warn('⏭️  Пропускаем настройку webhook (URL не определен)');
            return;
        }

        // Проверяем URL
        if (!filter_var($webhookUrl, FILTER_VALIDATE_URL)) {
            $this->error('❌ Неверный формат URL');
            return;
        }

        // Проверяем HTTPS (кроме локальных адресов или если указан --force)
        $isLocal = str_contains($webhookUrl, 'localhost') ||
            str_contains($webhookUrl, '127.0.0.1') ||
            str_contains($webhookUrl, '192.168.') ||
            str_contains($webhookUrl, '.local');

        if (!str_starts_with($webhookUrl, 'https://') && !$isLocal && !$this->option('force')) {
            $this->error('❌ URL должен быть HTTPS (используйте --force для обхода)');
            return;
        }

        if (!str_starts_with($webhookUrl, 'https://') && ($isLocal || $this->option('force'))) {
            $this->warn('⚠️  Используется HTTP соединение (только для разработки!)');
        }

        // Используем секрет из данных бота или генерируем новый
        $secret = $bot->webhook_secret ?? Str::random(32);

        // Устанавливаем webhook
        $this->info('🔧 Настройка webhook...');
        $this->line("  🌐 API хост: {$apiHost}");
        if ($noSsl) {
            $this->warn('  ⚠️  SSL проверка отключена');
        }

        try {
            $payload = [
                'url' => $webhookUrl,
                'max_connections' => 40,
                'allowed_updates' => [
                    'message',
                    'callback_query',
                    'inline_query',
                    'chosen_inline_result'
                ]
            ];

            if ($secret) {
                $payload['secret_token'] = $secret;
            }

            $token = $bot->token;
            $url = rtrim($apiHost, '/') . "/bot{$token}/setWebhook";

            $http = Http::timeout(30);
            if ($noSsl) {
                $http = $http->withOptions([
                    'verify' => false,
                    'curl' => [
                        CURLOPT_SSL_VERIFYPEER => false,
                        CURLOPT_SSL_VERIFYHOST => false,
                    ]
                ]);
            }

            $response = $http->post($url, $payload);

            if ($response->successful()) {
                // Сохраняем webhook данные в БД (только секрет)
                $bot->update([
                    'webhook_secret' => $secret,
                ]);

                $this->info('✅ Webhook настроен успешно');
                $this->line("  🌐 URL: {$webhookUrl}");
                $this->line("  🔐 Secret: {$secret}");
                $this->line("  🔒 Secret используется для проверки подлинности запросов от Telegram");
            } else {
                $result = $response->json();
                $errorMessage = $result['description'] ?? 'Unknown error';
                $this->error('❌ Ошибка установки webhook: ' . $errorMessage);
            }
        } catch (\Exception $e) {
            $this->error('❌ Ошибка установки webhook: ' . $e->getMessage());
        }
    }

    private function setupConfiguration(): void
    {
        $this->info('⚙️  Проверка конфигурации...');

        $configPath = config_path('bot.php');

        if (!file_exists($configPath)) {
            $this->warn('⚠️  Конфигурационный файл не найден');

            if ($this->shouldExit) {
                return;
            }

            if ($this->confirm('Опубликовать конфигурацию?', true)) {
                $this->call('vendor:publish', [
                    '--provider' => 'Bot\Providers\BotServiceProvider',
                    '--tag' => 'config'
                ]);
            }
        } else {
            $this->info('✅ Конфигурационный файл найден');
        }
    }

    private function createDirectories(): void
    {
        $this->info('📁 Создание директорий...');

        $directories = [
            storage_path('app/bot/downloads'),
            storage_path('app/bot/temp'),
            storage_path('logs/bot'),
        ];

        foreach ($directories as $dir) {
            if (!is_dir($dir)) {
                try {
                    mkdir($dir, 0755, true);
                    $this->line("  ✅ Создана: {$dir}");
                } catch (\Exception $e) {
                    $this->error("  ❌ Ошибка создания {$dir}: {$e->getMessage()}");
                }
            } else {
                $this->line("  ✅ Существует: {$dir}");
            }
        }
    }

    /**
     * Преобразует строку в PascalCase формат
     * Примеры: "agent shop" -> "AgentShop", "my bot" -> "MyBot"
     */
    private function toPascalCase(string $input): string
    {
        // Убираем лишние пробелы и разделяем по пробелам
        $words = array_filter(explode(' ', trim($input)));
        
        // Делаем каждое слово с заглавной буквы и объединяем
        $pascalCase = '';
        foreach ($words as $word) {
            $pascalCase .= ucfirst(strtolower($word));
        }
        
        return $pascalCase;
    }
}