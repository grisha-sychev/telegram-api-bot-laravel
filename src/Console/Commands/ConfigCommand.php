<?php

namespace Bot\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Arr;

class ConfigCommand extends Command
{
    protected $signature = 'bot:config 
                            {action? : Action (show, get, set, reset)}
                            {key? : Configuration key}
                            {value? : Configuration value}
                            {--format=table : Output format (table, json, yaml)}';
    
    protected $description = 'Управление конфигурацией бота';

    public function handle()
    {
        $action = $this->argument('action') ?? 'show';

        switch ($action) {
            case 'show':
                return $this->showConfig();
            case 'get':
                return $this->getConfig();
            case 'set':
                return $this->setConfig();
            case 'reset':
                return $this->resetConfig();
            case 'validate':
                return $this->validateConfig();
            default:
                $this->error("Неизвестное действие: {$action}");
                $this->line('Доступные действия: show, get, set, reset, validate');
                return 1;
        }
    }

    private function showConfig(): int
    {
        $this->info('⚙️  Bot Configuration');
        $this->newLine();

        $config = config('bot', []);
        $format = $this->option('format');

        switch ($format) {
            case 'json':
                $this->line(json_encode($config, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));
                break;
            case 'yaml':
                $this->line($this->arrayToYaml($config));
                break;
            default:
                $this->displayConfigTable($config);
        }

        return 0;
    }

    private function getConfig(): int
    {
        $key = $this->argument('key');
        
        if (!$key) {
            $key = $this->ask('Введите ключ конфигурации (например: api.timeout)');
        }

        if (!$key) {
            $this->error('❌ Ключ не указан');
            return 1;
        }

                    $value = config("bot.{$key}");
        
        if ($value === null) {
            $this->warn("⚠️  Ключ '{$key}' не найден");
            return 1;
        }

                    $this->info("bot.{$key}:");
        
        if (is_array($value)) {
            $this->line(json_encode($value, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));
        } else {
            $this->line((string) $value);
        }

        return 0;
    }

    private function setConfig(): int
    {
        $key = $this->argument('key');
        $value = $this->argument('value');

        if (!$key) {
            $key = $this->ask('Введите ключ конфигурации');
        }

        if (!$value) {
            $value = $this->ask("Введите значение для '{$key}'");
        }

        if (!$key || $value === null) {
            $this->error('❌ Ключ и значение обязательны');
            return 1;
        }

        // Парсим значение
        $parsedValue = $this->parseValue($value);

        // Валидируем ключ
        if (!$this->isValidConfigKey($key)) {
            $this->error("❌ Недопустимый ключ конфигурации: {$key}");
            return 1;
        }

                    $this->info("Установка bot.{$key} = " . json_encode($parsedValue));
        
        if (!$this->confirm('Продолжить?', true)) {
            $this->info('Отменено');
            return 0;
        }

        // В реальном приложении здесь должна быть запись в БД или файл
        $this->warn('⚠️  Примечание: Эта команда показывает пример. В реальном приложении нужно реализовать сохранение в БД или .env файл');
        
        $this->info('✅ Конфигурация обновлена (требуется перезапуск для применения)');

        return 0;
    }

    private function resetConfig(): int
    {
        if (!$this->confirm('⚠️  Сбросить всю конфигурацию к значениям по умолчанию?', false)) {
            $this->info('Отменено');
            return 0;
        }

        $this->warn('⚠️  Примечание: Эта команда показывает пример. В реальном приложении нужно реализовать очистку кастомных настроек');
        
        $this->info('✅ Конфигурация сброшена к значениям по умолчанию');

        return 0;
    }

    private function validateConfig(): int
    {
        $this->info('🔍 Валидация конфигурации бота...');
        $this->newLine();

        $errors = [];
        $warnings = [];

        // Проверка наличия ботов в базе данных
        try {
            $botsCount = \App\Models\Bot::where('enabled', true)->count();
            if ($botsCount === 0) {
                $errors[] = 'Нет активных ботов в базе данных';
            } else {
                $this->info("✅ Найдено активных ботов: {$botsCount}");
            }
        } catch (\Exception $e) {
            $errors[] = 'Ошибка подключения к базе данных: ' . $e->getMessage();
        }

        // Проверка webhook secret
        $webhookSecret = config('bot.security.webhook_secret');
        if (!$webhookSecret) {
            $warnings[] = 'BOT_WEBHOOK_SECRET не установлен (риск безопасности)';
        } elseif (strlen($webhookSecret) < 16) {
            $warnings[] = 'BOT_WEBHOOK_SECRET слишком короткий (рекомендуется минимум 16 символов)';
        }

        // Проверка admin IDs
        $adminIds = config('bot.security.admin_ids', []);
        if (empty($adminIds)) {
            $warnings[] = 'BOT_ADMIN_IDS не указаны';
        } else {
            foreach ($adminIds as $id) {
                if (!is_numeric($id)) {
                    $errors[] = "Неверный формат admin ID: {$id}";
                }
            }
        }

        // Проверка путей
        $downloadPath = config('bot.files.download_path');
        if ($downloadPath && !is_dir($downloadPath)) {
            $warnings[] = "Путь для загрузок не существует: {$downloadPath}";
        } elseif ($downloadPath && !is_writable($downloadPath)) {
            $errors[] = "Путь для загрузок недоступен для записи: {$downloadPath}";
        }

        // Проверка лимитов
        $maxFileSize = config('bot.files.max_file_size');
        if ($maxFileSize && $maxFileSize > 50 * 1024 * 1024) {
            $warnings[] = "Лимит размера файла очень большой: " . $this->formatFileSize($maxFileSize);
        }

        // Проверка API настроек
        $apiTimeout = config('bot.api.timeout');
        if ($apiTimeout && $apiTimeout > 60) {
            $warnings[] = "API timeout очень большой: {$apiTimeout}s";
        }

        // Отображаем результаты
        if (empty($errors) && empty($warnings)) {
            $this->info('✅ Конфигурация корректна');
            return 0;
        }

        if (!empty($errors)) {
            $this->error('❌ Критические ошибки:');
            foreach ($errors as $error) {
                $this->line("  - {$error}");
            }
            $this->newLine();
        }

        if (!empty($warnings)) {
            $this->warn('⚠️  Предупреждения:');
            foreach ($warnings as $warning) {
                $this->line("  - {$warning}");
            }
        }

        return empty($errors) ? 0 : 1;
    }

    private function displayConfigTable(array $config): void
    {
        $rows = [];
        $this->flattenConfig($config, '', $rows);

        $this->table(['Ключ', 'Значение', 'Тип'], $rows);
    }

    private function flattenConfig(array $config, string $prefix, array &$rows): void
    {
        foreach ($config as $key => $value) {
            $fullKey = $prefix ? "{$prefix}.{$key}" : $key;

            if (is_array($value)) {
                if (empty($value)) {
                    $rows[] = [$fullKey, '[]', 'array'];
                } else {
                    $this->flattenConfig($value, $fullKey, $rows);
                }
            } else {
                $type = gettype($value);
                $displayValue = $this->formatValue($value);
                $rows[] = [$fullKey, $displayValue, $type];
            }
        }
    }

    private function formatValue($value): string
    {
        if (is_bool($value)) {
            return $value ? 'true' : 'false';
        }

        if (is_null($value)) {
            return 'null';
        }

        if (is_string($value) && strlen($value) > 50) {
            return substr($value, 0, 47) . '...';
        }

        return (string) $value;
    }

    private function parseValue(string $value)
    {
        // Булевы значения
        if (in_array(strtolower($value), ['true', 'false'])) {
            return strtolower($value) === 'true';
        }

        // Null
        if (strtolower($value) === 'null') {
            return null;
        }

        // Числа
        if (is_numeric($value)) {
            return str_contains($value, '.') ? (float) $value : (int) $value;
        }

        // Массивы (JSON)
        if (str_starts_with($value, '[') || str_starts_with($value, '{')) {
            $decoded = json_decode($value, true);
            if (json_last_error() === JSON_ERROR_NONE) {
                return $decoded;
            }
        }

        // Строка
        return $value;
    }

    private function isValidConfigKey(string $key): bool
    {
        $allowedKeys = [
            'token', 'debug', 'timezone',
            'api.base_url', 'api.timeout', 'api.retries', 'api.retry_delay', 'api.user_agent',
            'security.webhook_secret', 'security.admin_ids', 'security.allowed_ips',
            'security.spam_protection.enabled', 'security.spam_protection.max_messages_per_minute',
            'security.rate_limits.global', 'security.rate_limits.per_user', 'security.rate_limits.per_chat',
            'files.download_path', 'files.temp_path', 'files.max_file_size', 'files.allowed_types',
            'logging.enabled', 'logging.level', 'logging.retention_days',
            'cache.enabled', 'cache.driver', 'cache.ttl',
            'monitoring.health_checks.enabled', 'monitoring.alerts.enabled',
        ];

        return in_array($key, $allowedKeys) || str_starts_with($key, 'experimental.');
    }

    private function arrayToYaml(array $array, int $indent = 0): string
    {
        $yaml = '';
        $spaces = str_repeat('  ', $indent);

        foreach ($array as $key => $value) {
            if (is_array($value)) {
                $yaml .= "{$spaces}{$key}:\n";
                $yaml .= $this->arrayToYaml($value, $indent + 1);
            } else {
                $formattedValue = $this->formatYamlValue($value);
                $yaml .= "{$spaces}{$key}: {$formattedValue}\n";
            }
        }

        return $yaml;
    }

    private function formatYamlValue($value): string
    {
        if (is_bool($value)) {
            return $value ? 'true' : 'false';
        }

        if (is_null($value)) {
            return 'null';
        }

        if (is_string($value) && (str_contains($value, ' ') || str_contains($value, ':'))) {
            return '"' . str_replace('"', '\"', $value) . '"';
        }

        return (string) $value;
    }

    private function formatFileSize(int $bytes): string
    {
        $units = ['B', 'KB', 'MB', 'GB'];
        $i = 0;

        while ($bytes >= 1024 && $i < count($units) - 1) {
            $bytes /= 1024;
            $i++;
        }

        return round($bytes, 2) . ' ' . $units[$i];
    }
} 