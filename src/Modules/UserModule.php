<?php

namespace Bot\Modules;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Cache;
use Exception;

trait UserModule
{
    public $updateUserTelegram = true;
    public $cacheUserData = true;
    public $logUserActions = true;
    /**
     * Класс модели пользователя. Можно переопределить в наследнике.
     * По умолчанию используется App\Models\UserTelegram
     */
    public string $model = \App\Models\UserTelegram::class;

    /**
     * Поля пользователя для сравнения при обновлении (можно переопределить/расширить)
     */
    protected array $userFieldsToCheck = ['first_name', 'last_name', 'username', 'language_code', 'is_premium'];

    /**
     * Initialize user module
     */
    public function userModule()
    {
        try {
            $this->setUserTelegram();

            if ($this->updateUserTelegram) {
                $this->command("start", function () {
                    $this->handleStartCommand();
                });
            }
        } catch (Exception $e) {
            $this->logError('userModule initialization failed', $e);
        }
    }

    /**
     * Handle start command
     */
    private function handleStartCommand(): bool
    {
        try {
            $user = $this->setUserTelegram(true);
            
            if ($user && $this->logUserActions) {
                Log::info('User started bot', [
                    'telegram_id' => $this->getUserId(),
                    'user_id' => $user->id,
                    'username' => $user->username,
                ]);
            }

            return true;
        } catch (Exception $e) {
            $this->logError('Start command failed', $e);
            return false;
        }
    }

    /**
     * Get user telegram with caching
     */
    protected function getUserTelegram(): ?Model
    {
        try {
            $telegramId = $this->getUserId();
            
            if (!$telegramId) {
                return null;
            }

            if ($this->cacheUserData) {
                $cacheKey = "user_telegram_{$telegramId}";
                return Cache::remember($cacheKey, 3600, function () use ($telegramId) {
                    $modelClass = $this->getUserModelClass();
                    if (method_exists($modelClass, 'findByTelegramId')) {
                        return $modelClass::findByTelegramId($telegramId);
                    }
                    return $modelClass::where('telegram_id', $telegramId)->first();
                });
            }

            $modelClass = $this->getUserModelClass();
            if (method_exists($modelClass, 'findByTelegramId')) {
                return $modelClass::findByTelegramId($telegramId);
            }
            return $modelClass::where('telegram_id', $telegramId)->first();
        } catch (Exception $e) {
            $this->logError('Failed to get user telegram', $e);
            return null;
        }
    }

    /**
     * Set or update user telegram data
     */
    protected function setUserTelegram(bool $forceUpdate = false): ?Model
    {
        try {
            $message = $this->getMessage();
            
            if (!$message) {
                return null;
            }

            $data = $message->getFrom();
            
            if (!$data) {
                return null;
            }

            $telegramData = $this->extendTelegramData($this->extractTelegramData($data));
            
            if (!$this->validateTelegramData($telegramData)) {
                Log::warning('Invalid telegram data received', $telegramData);
                return null;
            }

            $user = $this->getUserTelegram();
            $modelClass = $this->getUserModelClass();

            if (!$user) {
                // Create new user
                if (method_exists($modelClass, 'createOrUpdateFromTelegram')) {
                    $user = $modelClass::createOrUpdateFromTelegram($telegramData);
                } else {
                    // Универсальный путь через updateOrCreate
                    $user = $modelClass::updateOrCreate(
                        ['telegram_id' => $telegramData['id']],
                        $this->filterFillableAttributes($telegramData, new $modelClass)
                    );
                }
                
                if ($this->logUserActions) {
                    Log::info('New user registered', [
                        'telegram_id' => $telegramData['id'],
                        'username' => $telegramData['username'] ?? null,
                        'first_name' => $telegramData['first_name'] ?? null,
                    ]);
                }
            } elseif ($forceUpdate || $this->shouldUpdateUser($user, $telegramData)) {
                // Update existing user
                if (method_exists($user, 'updateFromTelegramData')) {
                    $user->updateFromTelegramData($telegramData);
                } else {
                    $user->fill($this->filterFillableAttributes($telegramData, $user));
                    $user->save();
                }
                
                if ($this->logUserActions) {
                    Log::info('User data updated', [
                        'telegram_id' => $telegramData['id'],
                        'changes' => $this->getChangedFields($user, $telegramData),
                    ]);
                }
            }

            // Clear cache after update
            if ($this->cacheUserData) {
                Cache::forget("user_telegram_{$telegramData['id']}");
            }

            return $user;
        } catch (Exception $e) {
            $this->logError('Failed to set user telegram', $e);
            return null;
        }
    }

    /**
     * Extract telegram data from API response
     */
    protected function extractTelegramData($data): array
    {
        return [
            'id' => $data->getId(),
            'is_bot' => $data->getIsBot() ?? false,
            'first_name' => $data->getFirstName() ?? '',
            'last_name' => $data->getLastName(),
            'username' => $data->getUsername(),
            'language_code' => $data->getLanguageCode() ?? 'en',
            'is_premium' => $data->getIsPremium() ?? false,
        ];
    }

    /**
     * Validate telegram data
     */
    protected function validateTelegramData(array $data): bool
    {
        return isset($data['id']) && 
               is_numeric($data['id']) && 
               !empty($data['first_name']) &&
               strlen($data['first_name']) <= 255;
    }

    /**
     * Check if user should be updated
     */
    protected function shouldUpdateUser(Model $user, array $newData): bool
    {
        foreach ($this->userFieldsToCheck as $field) {
            if (array_key_exists($field, $newData) && $user->{$field} !== $newData[$field]) {
                return true;
            }
        }
        return false;
    }

    /**
     * Get changed fields for logging
     */
    protected function getChangedFields(Model $user, array $newData): array
    {
        $changes = [];
        foreach ($this->userFieldsToCheck as $field) {
            if (array_key_exists($field, $newData) && $user->{$field} !== $newData[$field]) {
                $changes[$field] = [
                    'old' => $user->{$field},
                    'new' => $newData[$field],
                ];
            }
        }
        
        return $changes;
    }

    /**
     * Get current user with error handling
     */
    public function getCurrentUser(): ?Model
    {
        try {
            return $this->getUserTelegram();
        } catch (Exception $e) {
            $this->logError('Failed to get current user', $e);
            return null;
        }
    }

    /**
     * Check if user exists
     */
    public function userExists(): bool
    {
        return $this->getCurrentUser() !== null;
    }

    /**
     * Get user display name
     */
    public function getUserDisplayName(): string
    {
        $user = $this->getCurrentUser();
        if (!$user) return 'Unknown User';
        // Если модель имеет аксессор display_name
        if (isset($user->display_name)) {
            return $user->display_name;
        }
        // Иначе собираем имя
        $username = $user->username ?? null;
        if ($username) return '@' . ltrim($username, '@');
        $first = $user->first_name ?? '';
        $last = $user->last_name ?? '';
        return trim($first . ' ' . $last) ?: 'Unknown User';
    }

    /**
     * Check if user is premium
     */
    public function isUserPremium(): bool
    {
        $user = $this->getCurrentUser();
        if (!$user) return false;
        return method_exists($user, 'isPremium') ? $user->isPremium() : (bool) ($user->is_premium ?? false);
    }

    /**
     * Get user language code
     */
    public function getUserLanguage(): string
    {
        $user = $this->getCurrentUser();
        return $user ? ($user->language_code ?? 'en') : 'en';
    }

    /**
     * Log error with context
     */
    private function logError(string $message, Exception $e): void
    {
        if ($this->logUserActions) {
            Log::error($message, [
                'error' => $e->getMessage(),
                'telegram_id' => $this->getUserId() ?? 'unknown',
                'file' => $e->getFile(),
                'line' => $e->getLine(),
            ]);
        }
    }

    /**
     * Get user statistics
     */
    public function getUserStats(): array
    {
        try {
            $modelClass = $this->getUserModelClass();
            return [
                'new_users_today' => method_exists($modelClass, 'getNewUsersToday') ? $modelClass::getNewUsersToday() : 0,
                'active_users_week' => method_exists($modelClass, 'getActiveUsers') ? $modelClass::getActiveUsers(7) : 0,
                'total_users' => $modelClass::count(),
            ];
        } catch (Exception $e) {
            $this->logError('Failed to get user stats', $e);
            return [];
        }
    }

    /**
     * Класс модели пользователя (можно переопределить или сконфигурировать)
     */
    protected function getUserModelClass(): string
    {
        return $this->model;
    }

    /**
     * Фильтрация входящих атрибутов по fillable модели
     */
    protected function filterFillableAttributes(array $data, Model $model): array
    {
        $fillable = method_exists($model, 'getFillable') ? $model->getFillable() : array_keys($data);
        $map = [
            // соответствие ключей телеграма полям модели по умолчанию
            'id' => 'telegram_id',
        ];
        $result = [];
        foreach ($data as $key => $value) {
            $field = $map[$key] ?? $key;
            if (in_array($field, $fillable, true)) {
                $result[$field] = $value;
            }
        }
        return $result;
    }

    /**
     * Точка расширения для добавления/трансформации данных перед сохранением
     */
    protected function extendTelegramData(array $data): array
    {
        return $data;
    }
}
