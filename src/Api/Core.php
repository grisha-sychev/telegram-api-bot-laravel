<?php

namespace Bot\Api;

use Bot\Support\Facades\Services;
use Illuminate\Support\Facades\Http;
use Bot\Types\DynamicData;

class Core
{

    /**
     * @var string|null $bot Идентификатор бота.
     */
    public ?string $bot = null;

    /**
     * @var string|null $token Токен бота.
     */
    public ?string $token = null;

    /**
     * @var string|null $hostname host, связанный с ботом.
     */
    public ?string $hostname = null;

    /**
     * Устанавливает токен бота напрямую
     */
    public function setToken(string $token): void
    {
        $this->token = $token;
    }

    /**
     * Устанавливает имя бота для получения токена из БД
     */
    public function setBotName(string $botName): void
    {
        $this->bot = $botName;
    }

    /**
     * Отправляет все данные запроса от Telegram и возвращает их в виде массива.
     *
     * Данные запроса от Telegram в виде обьекта.
     */
public function method($method, $query = [])
{
    $maxRetries = (int) config('bot.api.retries', 3);
    $maxRetries = $maxRetries > 0 ? $maxRetries : 1;

    $baseDelay = (int) config('bot.api.retry_delay', 1);
    $baseDelay = $baseDelay > 0 ? $baseDelay : 1;

    $timeout = (int) config('bot.api.timeout', 30);
    $timeout = $timeout > 0 ? $timeout : 30;

    $connectTimeout = (int) config('bot.api.connect_timeout', 5);
    $connectTimeout = $connectTimeout > 0 ? $connectTimeout : 5;

    $baseUrl = (string) config('bot.api.base_url', 'https://api.telegram.org');
    $baseUrl = rtrim($baseUrl, '/');

    for ($attempt = 1; $attempt <= $maxRetries; $attempt++) {
        try {
            $token = $this->token ?? (new Services)->getToken($this->bot);
            $url = $baseUrl . "/bot" . $token . "/" . $method;

            // Добавляем CURLOPT_IPRESOLVE => CURL_IPRESOLVE_V4
            $request = Http::withoutVerifying()
                ->timeout($timeout)
                ->withOptions([
                    'curl' => [
                        CURLOPT_IPRESOLVE => CURL_IPRESOLVE_V4,
                        CURLOPT_CONNECTTIMEOUT => $connectTimeout,
                    ],
                ]);

            $fileFields = ['photo','video','audio','document','animation','thumbnail','sticker'];
            $hasFiles = false;
            $data = [];

            foreach ($query as $key => $value) {
                $isLocalFile = false;
                if (is_string($value)) {
                    $isUrl = (bool) filter_var($value, FILTER_VALIDATE_URL);
                    if (!$isUrl && @is_file($value) && @is_readable($value)) {
                        $isLocalFile = true;
                    }
                }

                if (in_array($key, $fileFields, true) && $isLocalFile) {
                    $hasFiles = true;
                }
            }

            if ($hasFiles) {
                foreach ($query as $key => $value) {
                    $isUrl = is_string($value) && (bool) filter_var($value, FILTER_VALIDATE_URL);
                    $isLocalFile = is_string($value) && !$isUrl && @is_file($value) && @is_readable($value);

                    if ($isLocalFile) {
                        $request = $request->attach($key, fopen($value, 'r'), basename($value));
                    } else {
                        $data[$key] = is_array($value) ? json_encode($value) : $value;
                    }
                }

                $response = $request->post($url, $data);
            } else {
                $normalized = [];
                foreach ($query as $key => $value) {
                    $normalized[$key] = is_array($value) ? json_encode($value) : $value;
                }
                $response = $request->asForm()->post($url, $normalized);
            }

            if ($response->status() === 429) {
                $body = $response->json();
                $retryAfter = (int) (($body['parameters']['retry_after'] ?? null) ?: $response->header('Retry-After', $baseDelay * $attempt));
                $retryAfter = $retryAfter > 0 ? $retryAfter : ($baseDelay * $attempt);
                \Log::warning('Telegram API rate limit hit', [
                    'bot' => $this->bot,
                    'method' => $method,
                    'retry_after' => $retryAfter,
                    'attempt' => $attempt
                ]);

                if ($attempt < $maxRetries) {
                    sleep($retryAfter);
                    continue;
                }
            }

            $result = $response->json();

            if (isset($result['ok']) && !$result['ok']) {
                \Log::warning('Telegram API error', [
                    'bot' => $this->bot,
                    'method' => $method,
                    'error' => $result['description'] ?? 'Unknown error',
                    'error_code' => $result['error_code'] ?? 'Unknown'
                ]);
            }

            return $result;

        } catch (\Exception $e) {
            \Log::error('Telegram API request failed', [
                'bot' => $this->bot,
                'method' => $method,
                'attempt' => $attempt,
                'error' => $e->getMessage()
            ]);

            if ($attempt === $maxRetries) {
                return [
                    'ok' => false,
                    'error_code' => 500,
                    'description' => 'Request failed: ' . $e->getMessage()
                ];
            }

            sleep($baseDelay * $attempt);
        }
    }

    return ['ok' => false, 'error_code' => 500, 'description' => 'Max retries exceeded'];
}


    public function file($file_path)
    {
        // Используем прямой токен если установлен, иначе получаем через Services
        $token = $this->token ?? (new Services)->getToken($this->bot);
        $url = "https://api.telegram.org/file/bot" . $token . "/" . $file_path;
        return $url;
    }

    /**
     * Получает все данные запроса от Telegram и возвращает их в виде массива.
     *
     * Данные запроса от Telegram в виде обьекта.
     */
    public function request()
    {
        return new DynamicData(request()->all());
    }
}
