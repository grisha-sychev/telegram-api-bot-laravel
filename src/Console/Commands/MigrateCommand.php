<?php

namespace Bot\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\File;
use App\Models\Bot;

class MigrateCommand extends Command
{
    protected $signature = 'bot:migrate 
                            {action? : Action (export, import, clear, backup)}
                            {--format=json : Export format (json, csv)}
                            {--path= : File path for import/export}
                            {--force : Force action without confirmation}';
    
    protected $description = 'Миграция данных ботов';

    public function handle()
    {
        $action = $this->argument('action');
        if (!$action) {
            $action = $this->choice('Выберите действие', ['export', 'import', 'clear', 'backup'], 0);
        }

        switch ($action) {
            case 'export':
                return $this->exportData();
            case 'import':
                return $this->importData();
            case 'clear':
                return $this->clearData();
            case 'backup':
                return $this->backupData();
            default:
                $this->error("Неизвестное действие: {$action}");
                $this->line('Доступные действия: export, import, clear, backup');
                return 1;
        }
    }

    private function exportData(): int
    {
        $this->info('📤 Экспорт данных ботов...');
        $this->newLine();

        $format = $this->option('format');
        $path = $this->option('path') ?? storage_path('app/bot_export_' . date('Y-m-d_H-i-s') . '.' . $format);

        // Собираем данные для экспорта
        $data = $this->collectExportData();

        try {
            if ($format === 'csv') {
                $this->exportToCsv($data, $path);
            } else {
                $this->exportToJson($data, $path);
            }

            $this->info("✅ Данные экспортированы: {$path}");
            $this->line("📊 Записей: " . $this->countRecords($data));
            $this->line("💾 Размер: " . $this->formatFileSize(filesize($path)));

        } catch (\Exception $e) {
            $this->error("❌ Ошибка экспорта: {$e->getMessage()}");
            return 1;
        }

        return 0;
    }

    private function importData(): int
    {
        $path = $this->option('path');
        
        if (!$path) {
            $path = $this->ask('Введите путь к файлу для импорта');
        }

        if (!$path || !file_exists($path)) {
            $this->error('❌ Файл не найден');
            return 1;
        }

        $this->info("📥 Импорт данных из: {$path}");
        $this->newLine();

        if (!$this->option('force') && !$this->confirm('⚠️  Импорт может перезаписать существующие данные. Продолжить?', false)) {
            $this->info('Отменено');
            return 0;
        }

        try {
            $extension = pathinfo($path, PATHINFO_EXTENSION);
            
            if ($extension === 'csv') {
                $data = $this->importFromCsv($path);
            } else {
                $data = $this->importFromJson($path);
            }

            $this->processImportData($data);

            $this->info('✅ Импорт завершен успешно');
            $this->line("📊 Обработано записей: " . $this->countRecords($data));

        } catch (\Exception $e) {
            $this->error("❌ Ошибка импорта: {$e->getMessage()}");
            return 1;
        }

        return 0;
    }

    private function clearData(): int
    {
        $this->info('🗑️  Очистка данных...');
        $this->newLine();

        if (!$this->option('force') && !$this->confirm('⚠️  Это действие удалит все данные. Продолжить?', false)) {
            $this->info('Отменено');
            return 0;
        }

        try {
            $this->clearFiles();
            $this->clearCache();
            $this->clearLogs();

            $this->info('✅ Очистка завершена');

        } catch (\Exception $e) {
            $this->error("❌ Ошибка очистки: {$e->getMessage()}");
            return 1;
        }

        return 0;
    }

    private function backupData(): int
    {
        $this->info('💾 Создание резервной копии...');
        $this->newLine();

        $timestamp = date('Y-m-d_H-i-s');
        $backupPath = $this->option('path') ?? storage_path("app/bot_backup_{$timestamp}");

        if (!is_dir($backupPath)) {
            mkdir($backupPath, 0755, true);
        }

        try {
            $this->backupConfiguration($backupPath);
            $this->backupFiles($backupPath);
            $this->backupUserData($backupPath);
            $this->createBackupManifest($backupPath, $timestamp);

            $this->info("✅ Резервная копия создана: {$backupPath}");
            $this->line("💾 Размер: " . $this->formatDirectorySize($backupPath));

        } catch (\Exception $e) {
            $this->error("❌ Ошибка создания резервной копии: {$e->getMessage()}");
            return 1;
        }

        return 0;
    }

    private function collectExportData(): array
    {
        $data = [
            'metadata' => [
                'export_date' => now()->toISOString(),
                'version' => '2.0',
                'total_bots' => 0,
                'bots_with_token' => 0,
            ],
            'configuration' => config('bot', []),
            'bots' => [],
            'users' => [],
            'chats' => [],
            'files' => [],
            'logs' => [],
        ];

        // Экспортируем ботов
        try {
            $bots = Bot::all();
            $data['metadata']['total_bots'] = $bots->count();
            $data['metadata']['bots_with_token'] = $bots->filter(function($bot) {
                return $bot->hasToken();
            })->count();

            foreach ($bots as $bot) {
                $data['bots'][] = [
                    'name' => $bot->name,
                    'username' => $bot->username,
                    'first_name' => $bot->first_name,
                    'description' => $bot->description,
                    'bot_id' => $bot->bot_id,
                    'enabled' => $bot->enabled,
                    'token' => $bot->token ? substr($bot->token, 0, 10) . '...' : null,
                    'webhook_url' => $bot->webhook_url,
                    'webhook_secret' => $bot->webhook_secret ? '***' : null,
                    'settings' => $bot->settings,
                    'admin_ids' => $bot->admin_ids,
                    'created_at' => $bot->created_at->toISOString(),
                    'updated_at' => $bot->updated_at->toISOString(),
                ];
            }
        } catch (\Exception $e) {
            $this->warn("⚠️  Ошибка экспорта ботов: {$e->getMessage()}");
        }
        
        // Пример сбора файлов
        $downloadPath = config('bot.files.download_path', storage_path('app/bot/downloads'));
        if (is_dir($downloadPath)) {
            $files = File::allFiles($downloadPath);
            foreach ($files as $file) {
                $data['files'][] = [
                    'path' => $file->getRelativePathname(),
                    'size' => $file->getSize(),
                    'modified' => date('Y-m-d H:i:s', $file->getMTime()),
                ];
            }
        }

        return $data;
    }

    private function exportToJson(array $data, string $path): void
    {
        $json = json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
        file_put_contents($path, $json);
    }

    private function exportToCsv(array $data, string $path): void
    {
        $handle = fopen($path, 'w');
        
        // Заголовки
        fputcsv($handle, ['Type', 'ID', 'Data', 'Created']);

        // Боты
        foreach ($data['bots'] as $bot) {
            fputcsv($handle, ['bot', $bot['name'] ?? '', json_encode($bot), $bot['created_at'] ?? '']);
        }

        // Пользователи
        foreach ($data['users'] as $user) {
            fputcsv($handle, ['user', $user['id'] ?? '', json_encode($user), $user['created_at'] ?? '']);
        }

        // Чаты
        foreach ($data['chats'] as $chat) {
            fputcsv($handle, ['chat', $chat['id'] ?? '', json_encode($chat), $chat['created_at'] ?? '']);
        }

        // Файлы
        foreach ($data['files'] as $file) {
            fputcsv($handle, ['file', $file['path'] ?? '', json_encode($file), $file['modified'] ?? '']);
        }

        fclose($handle);
    }

    private function importFromJson(string $path): array
    {
        $content = file_get_contents($path);
        $data = json_decode($content, true);

        if (json_last_error() !== JSON_ERROR_NONE) {
            throw new \Exception('Неверный формат JSON: ' . json_last_error_msg());
        }

        return $data;
    }

    private function importFromCsv(string $path): array
    {
        $data = [
            'bots' => [],
            'users' => [],
            'chats' => [],
            'files' => [],
        ];

        $handle = fopen($path, 'r');
        $header = fgetcsv($handle); // Пропускаем заголовки

        while (($row = fgetcsv($handle)) !== false) {
            [$type, $id, $jsonData, $created] = $row;
            $recordData = json_decode($jsonData, true);

            if ($recordData) {
                if (in_array($type, ['bot','user','chat','file'], true)) {
                    $data[$type . 's'][] = $recordData;
                }
            }
        }

        fclose($handle);
        return $data;
    }

    private function processImportData(array $data): void
    {
        // Обрабатываем ботов
        if (isset($data['bots']) && is_array($data['bots'])) {
            $this->info("📥 Импорт ботов: " . count($data['bots']));
            
            foreach ($data['bots'] as $botData) {
                try {
                    // Проверяем существование бота
                    $existingBot = Bot::byName($botData['name'])->first();
                    
                    if ($existingBot) {
                        $this->warn("⚠️  Бот '{$botData['name']}' уже существует, пропускаем");
                        continue;
                    }

                    // Создаем нового бота (без токенов, так как они замаскированы)
                    $newBot = Bot::create([
                        'name' => $botData['name'],
                        'username' => $botData['username'],
                        'first_name' => $botData['first_name'],
                        'description' => $botData['description'],
                        'bot_id' => $botData['bot_id'],
                        'enabled' => $botData['enabled'] ?? false,
                        'webhook_url' => $botData['webhook_url'],
                        'webhook_secret' => $botData['webhook_secret'],
                        'settings' => $botData['settings'] ?? [],
                        'admin_ids' => $botData['admin_ids'] ?? [],
                    ]);

                    $this->line("  ✅ Импортирован бот: {$newBot->name}");
                } catch (\Exception $e) {
                    $this->error("  ❌ Ошибка импорта бота '{$botData['name']}': {$e->getMessage()}");
                }
            }
        }

        // Здесь можно добавить импорт других данных (пользователи, чаты и т.д.)
    }

    private function clearFiles(): void
    {
        $this->line('🗑️  Очистка файлов...');
        
        $downloadPath = config('bot.files.download_path', storage_path('app/bot/downloads'));
        $tempPath = config('bot.files.temp_path', storage_path('app/bot/temp'));

        if (is_dir($downloadPath)) {
            File::deleteDirectory($downloadPath);
            mkdir($downloadPath, 0755, true);
            $this->line("  ✅ Очищена папка загрузок");
        }

        if (is_dir($tempPath)) {
            File::deleteDirectory($tempPath);
            mkdir($tempPath, 0755, true);
            $this->line("  ✅ Очищена временная папка");
        }
    }

    private function clearCache(): void
    {
        $this->line('🗑️  Очистка кэша...');
        
        try {
            \Artisan::call('cache:clear');
            $this->line("  ✅ Кэш очищен");
        } catch (\Exception $e) {
            $this->warn("  ⚠️  Ошибка очистки кэша: {$e->getMessage()}");
        }
    }

    private function clearLogs(): void
    {
        $this->line('🗑️  Очистка логов...');
        
        $logPath = storage_path('logs/bot');
        if (is_dir($logPath)) {
            File::deleteDirectory($logPath);
            mkdir($logPath, 0755, true);
            $this->line("  ✅ Логи очищены");
        }
    }

    private function backupConfiguration(string $backupPath): void
    {
        $this->line('💾 Резервное копирование конфигурации...');
        
        $configPath = config_path('bot.php');
        if (file_exists($configPath)) {
            $backupConfigPath = $backupPath . '/config';
            if (!is_dir($backupConfigPath)) {
                mkdir($backupConfigPath, 0755, true);
            }
            copy($configPath, $backupConfigPath . '/bot.php');
            $this->line("  ✅ Конфигурация сохранена");
        }
    }

    private function backupFiles(string $backupPath): void
    {
        $this->line('💾 Резервное копирование файлов...');
        
        $downloadPath = config('bot.files.download_path', storage_path('app/bot/downloads'));
        $backupFilesPath = $backupPath . '/files';
        
        if (is_dir($downloadPath)) {
            if (!is_dir($backupFilesPath)) {
                mkdir($backupFilesPath, 0755, true);
            }
            File::copyDirectory($downloadPath, $backupFilesPath);
            $this->line("  ✅ Файлы сохранены");
        }
    }

    private function backupUserData(string $backupPath): void
    {
        $this->line('💾 Резервное копирование данных пользователей...');
        
        try {
            $bots = Bot::all();
            // Маскируем чувствительные данные
            $botsData = $bots->map(function ($bot) {
                $arr = $bot->toArray();
                if (isset($arr['token']) && $arr['token']) {
                    $arr['token'] = substr($arr['token'], 0, 10) . '...';
                }
                return $arr;
            })->toArray();
            
            $backupDataPath = $backupPath . '/data';
            if (!is_dir($backupDataPath)) {
                mkdir($backupDataPath, 0755, true);
            }
            
            file_put_contents($backupDataPath . '/bots.json', json_encode($botsData, JSON_PRETTY_PRINT));
            $this->line("  ✅ Данные ботов сохранены");
        } catch (\Exception $e) {
            $this->warn("  ⚠️  Ошибка сохранения данных: {$e->getMessage()}");
        }
    }

    private function createBackupManifest(string $backupPath, string $timestamp): void
    {
        $manifest = [
            'backup_date' => now()->toISOString(),
            'timestamp' => $timestamp,
            'version' => '2.0',
            'environment' => app()->environment(),
            'laravel_version' => app()->version(),
            'php_version' => PHP_VERSION,
            'total_size' => $this->formatDirectorySize($backupPath),
        ];

        file_put_contents($backupPath . '/manifest.json', json_encode($manifest, JSON_PRETTY_PRINT));
        $this->line("  ✅ Манифест создан");
    }

    private function countRecords(array $data): int
    {
        $count = 0;
        
        if (isset($data['bots'])) $count += count($data['bots']);
        if (isset($data['users'])) $count += count($data['users']);
        if (isset($data['chats'])) $count += count($data['chats']);
        if (isset($data['files'])) $count += count($data['files']);
        
        return $count;
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

    private function formatDirectorySize(string $path): string
    {
        $size = 0;
        foreach (new \RecursiveIteratorIterator(new \RecursiveDirectoryIterator($path)) as $file) {
            if ($file->isFile()) {
                $size += $file->getSize();
            }
        }
        return $this->formatFileSize($size);
    }
} 