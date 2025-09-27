<?php

namespace Bot\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Cache;
use App\Models\Bot;

class StatsCommand extends Command
{
    protected $signature = 'bot:stats 
                            {--bot= : Имя конкретного бота для статистики}
                            {--period=24h : Period for statistics (1h, 24h, 7d, 30d)}
                            {--format=table : Output format (table, json)}
                            {--detailed : Show detailed statistics}
                            {--no-ssl : Отключить проверку SSL сертификатов}';
    
    protected $description = 'Статистика Bot';

    public function handle()
    {
        $this->info('📊 Bot Statistics');
        $this->newLine();

        $botName = $this->option('bot');
        $period = $this->option('period');
        $format = $this->option('format');
        $detailed = $this->option('detailed');

        // Если указан конкретный бот
        if ($botName) {
            $bot = Bot::byName($botName)->first();
            if (!$bot) {
                $this->error("❌ Бот '{$botName}' не найден");
                return 1;
            }
            $stats = $this->gatherBotStatistics($bot, $period, $detailed);
        } else {
            // Статистика по всем ботам
            $bots = Bot::all();
            if ($bots->isEmpty()) {
                $this->warn('⚠️  Нет зарегистрированных ботов');
                $this->line('💡 Используйте команду: php artisan bot:new');
                return 0;
            }
            $stats = $this->gatherAllBotsStatistics($bots, $period, $detailed);
        }

        if ($format === 'json') {
            $this->line(json_encode($stats, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));
        } else {
            $this->displayStatsTable($stats, $period, $detailed, $botName);
        }

        return 0;
    }

    private function gatherBotStatistics(Bot $bot, string $period, bool $detailed): array
    {
        return [
            'bot_info' => $this->getBotInfo($bot),
            'system' => $this->getSystemStats(),
            'performance' => $this->getPerformanceStats($period),
            'errors' => $this->getErrorStats($period),
            'webhook' => $this->getWebhookStats($bot),
        ];
    }

    private function gatherAllBotsStatistics($bots, string $period, bool $detailed): array
    {
        $botStats = [];
        
        foreach ($bots as $bot) {
            $botStats[$bot->name] = [
                'name' => $bot->name,
                'username' => $bot->username,
                'enabled' => $bot->enabled,
                'has_token' => $bot->hasToken(),
                'webhook_configured' => $bot->hasWebhookUrl(),
                'class_exists' => $bot->botClassExists(),
            ];
        }

        return [
            'total_bots' => $bots->count(),
            'enabled_bots' => $bots->where('enabled', true)->count(),
            'bots_with_token' => $bots->filter(function($bot) {
                return $bot->hasToken();
            })->count(),
            'bots' => $botStats,
            'system' => $this->getSystemStats(),
        ];
    }

    private function getBotInfo(Bot $bot): array
    {
        if (!$bot->hasToken()) {
            return ['error' => "Token not configured"];
        }

        $token = $bot->token;

        try {
            $response = Http::timeout(10)->get("https://api.telegram.org/bot{$token}/getMe");
            
            if ($response->successful()) {
                return $response->json()['result'];
            }
            
            return ['error' => 'API error: ' . $response->status()];
        } catch (\Exception $e) {
            return ['error' => $e->getMessage()];
        }
    }

    private function getSystemStats(): array
    {
        return [
            'php_version' => PHP_VERSION,
            'laravel_version' => app()->version(),
            'memory_usage' => memory_get_usage(true),
            'memory_peak' => memory_get_peak_usage(true),
            'memory_limit' => $this->parseMemoryLimit(ini_get('memory_limit')),
            'uptime' => $this->getUptime(),
            'timezone' => config('app.timezone'),
            'environment' => app()->environment(),
        ];
    }

    private function getPerformanceStats(string $period): array
    {
        $hours = $this->periodToHours($period);
        
        // В реальном приложении здесь должны быть данные из логов или БД
        return [
            'messages_processed' => $this->mockStat(100, 1000),
            'commands_executed' => $this->mockStat(50, 500),
            'errors_count' => $this->mockStat(0, 10),
            'avg_response_time' => $this->mockStat(100, 500),
            'period_hours' => $hours,
        ];
    }

    private function getErrorStats(string $period): array
    {
        $hours = $this->periodToHours($period);
        
        return [
            'total_errors' => $this->mockStat(0, 20),
            'api_errors' => $this->mockStat(0, 5),
            'webhook_errors' => $this->mockStat(0, 3),
            'last_error' => $this->getLastError(),
            'period_hours' => $hours,
        ];
    }

    private function getWebhookStats(Bot $bot): array
    {
        if (!$bot->hasToken()) {
            return ['error' => "Token not configured"];
        }

        $token = $bot->token;

        try {
            $http = Http::timeout(10);
            if ($this->option('no-ssl')) {
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
                return [
                    'url' => $webhook['url'] ?? null,
                    'pending_updates' => $webhook['pending_update_count'] ?? 0,
                    'last_error_date' => $webhook['last_error_date'] ?? null,
                    'last_error_message' => $webhook['last_error_message'] ?? null,
                    'max_connections' => $webhook['max_connections'] ?? null,
                ];
            }
            
            return ['error' => 'API error: ' . $response->status()];
        } catch (\Exception $e) {
            return ['error' => $e->getMessage()];
        }
    }

    private function getCacheStats(): array
    {
        return [
            'cache_hits' => $this->mockStat(1000, 5000),
            'cache_misses' => $this->mockStat(50, 200),
            'cache_size' => $this->mockStat(1024 * 1024, 10 * 1024 * 1024),
            'cache_driver' => config('cache.default'),
        ];
    }

    private function getStorageStats(): array
    {
        $downloadPath = config('bot.files.download_path', storage_path('app/bot/downloads'));
        $tempPath = config('bot.files.temp_path', storage_path('app/bot/temp'));
        
        return [
            'downloads_size' => is_dir($downloadPath) ? $this->formatFileSize($this->getDirectorySize($downloadPath)) : '0 B',
            'temp_size' => is_dir($tempPath) ? $this->formatFileSize($this->getDirectorySize($tempPath)) : '0 B',
            'downloads_count' => is_dir($downloadPath) ? count(scandir($downloadPath)) - 2 : 0,
            'temp_count' => is_dir($tempPath) ? count(scandir($tempPath)) - 2 : 0,
        ];
    }

    private function getMemoryStats(): array
    {
        return [
            'current_usage' => $this->formatFileSize(memory_get_usage(true)),
            'peak_usage' => $this->formatFileSize(memory_get_peak_usage(true)),
            'limit' => ini_get('memory_limit'),
            'free_memory' => $this->formatFileSize($this->parseMemoryLimit(ini_get('memory_limit')) - memory_get_usage(true)),
        ];
    }

    private function displayStatsTable(array $stats, string $period, bool $detailed, string $botName = null): void
    {
        if ($botName) {
            $this->displaySingleBotStats($stats, $period, $detailed);
        } else {
            $this->displayAllBotsStats($stats, $period, $detailed);
        }
    }

    private function displaySingleBotStats(array $stats, string $period, bool $detailed): void
    {
        $this->info('🤖 Информация о боте:');
        if (isset($stats['bot_info']['error'])) {
            $this->error("  ❌ {$stats['bot_info']['error']}");
        } else {
            $this->line("  📝 Имя: {$stats['bot_info']['first_name']}");
            $this->line("  🆔 Username: @{$stats['bot_info']['username']}");
        }

        $this->newLine();
        $this->info('📊 Производительность:');
        $this->table(
            ['Метрика', 'Значение'],
            [
                ['Обработано сообщений', $stats['performance']['messages_processed']],
                ['Выполнено команд', $stats['performance']['commands_executed']],
                ['Ошибок', $stats['performance']['errors_count']],
                ['Среднее время ответа', $stats['performance']['avg_response_time'] . 'ms'],
                ['Период', $period],
            ]
        );

        if ($detailed) {
            $this->displayDetailedStats($stats);
        }
    }

    private function displayAllBotsStats(array $stats, string $period, bool $detailed): void
    {
        $this->info('🤖 Общая статистика:');
        $this->line("  🌍 Окружение: " . app()->environment());
        $this->line("  📊 Всего ботов: {$stats['total_bots']}");
        $this->line("  ✅ Активных: {$stats['enabled_bots']}");
        $this->line("  🗝️  С токенами: {$stats['bots_with_token']}");

        $this->newLine();
        $this->info('📋 Список ботов:');
        $this->table(
            ['Имя', 'Username', 'Статус', 'Токен', 'Webhook', 'Класс'],
            array_map(function($bot) {
                return [
                    $bot['name'],
                    '@' . $bot['username'],
                    $bot['enabled'] ? '✅' : '❌',
                    $bot['has_token'] ? '✅' : '❌',
                    $bot['webhook_configured'] ? '✅' : '❌',
                    $bot['class_exists'] ? '✅' : '❌',
                ];
            }, $stats['bots'])
        );
    }

    private function displayDetailedStats(array $stats): void
    {
        if (isset($stats['webhook'])) {
            $this->newLine();
            $this->info('🌐 Webhook статистика:');
            if (isset($stats['webhook']['error'])) {
                $this->error("  ❌ {$stats['webhook']['error']}");
            } else {
                $this->line("  🌐 URL: {$stats['webhook']['url']}");
                $this->line("  📊 Ожидающие обновления: {$stats['webhook']['pending_updates']}");
                if ($stats['webhook']['last_error_message']) {
                    $this->line("  ❌ Последняя ошибка: {$stats['webhook']['last_error_message']}");
                }
            }
        }
    }

    private function getLastError(): ?string
    {
        // В реальном приложении здесь должна быть логика получения последней ошибки
        return null;
    }

    private function getUptime(): string
    {
        // В реальном приложении здесь должна быть логика получения времени работы
        return 'Unknown';
    }

    private function periodToHours(string $period): int
    {
        $value = (int) $period;
        $unit = substr($period, -1);
        
        switch ($unit) {
            case 'h': return $value;
            case 'd': return $value * 24;
            default: return 24;
        }
    }

    private function mockStat(int $min, int $max): int
    {
        return rand($min, $max);
    }

    private function parseMemoryLimit(string $limit): int
    {
        $value = (int) $limit;
        $unit = strtolower(substr($limit, -1));
        
        switch ($unit) {
            case 'k': return $value * 1024;
            case 'm': return $value * 1024 * 1024;
            case 'g': return $value * 1024 * 1024 * 1024;
            default: return $value;
        }
    }

    private function formatFileSize(int $bytes): string
    {
        $units = ['B', 'KB', 'MB', 'GB'];
        $bytes = max($bytes, 0);
        $pow = floor(($bytes ? log($bytes) : 0) / log(1024));
        $pow = min($pow, count($units) - 1);
        
        $bytes /= pow(1024, $pow);
        
        return round($bytes, 2) . ' ' . $units[$pow];
    }

    private function getDirectorySize(string $path): int
    {
        $size = 0;
        foreach (new \RecursiveIteratorIterator(new \RecursiveDirectoryIterator($path)) as $file) {
            if ($file->isFile()) {
                $size += $file->getSize();
            }
        }
        return $size;
    }
} 