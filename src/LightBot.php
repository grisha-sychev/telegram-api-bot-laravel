<?php

namespace Bot;

use App\Enums\MediaType;
use Closure;
use Illuminate\Support\Facades\File;
use Bot\Api\Skeleton;
use Bot\Support\Facades\Services;

class LightBot extends Skeleton
{
    public array $commandsList = [];
    
    // Новые свойства для middleware
    protected array $middleware = [];
    protected array $globalMiddleware = [];
    

    
    /**
     * Разрешить обработку медиафайлов без текста
     * @var bool
     */
    protected bool $allowMedia = true;
    
    /**
     * Разрешить обработку обычных текстовых сообщений (не команд)
     * @var bool
     */
    protected bool $allowTextMessages = false;
    
    /**
     * Зарегистрированные типы медиа через метод media()
     * @var array
     */
    protected array $registeredMediaTypes = [];
    
    /**
     * Fallback функция для необработанных сообщений
     * @var callable|null
     */
    protected $failCallback = null;
    
    /**
     * Флаг что сообщение было обработано
     * @var bool
     */
    protected bool $messageProcessed = false;

    public function __construct()
    {
        // Валидация входящих данных
        if (!$this->isValidWebhookRequest()) {
            return;
        }
        
        // Регистрируем базовые middleware
        $this->registerDefaultMiddleware();
        

    }

    /**
     * Получает callback query
     */
    public function getCallback()
    {
        return $this->getCallbackQuery();
    }

    /**
     * Получает сообщение
     */
    public function getMessage()
    {
        return parent::getMessage();
    }

    /**
     * Получает объект From (отправитель)
     */
    public function getFrom()
    {
        $message = $this->getMessage();
        $callback = $this->getCallback();
        
        return isset($message) ? $message->getFrom() : (isset($callback) ? $callback->getFrom() : null);
    }

    /**
     * Получает ID пользователя
     */
    public function getUserId()
    {
        $message = $this->getMessage();
        $callback = $this->getCallback();
        
        return isset($message) ? $message->getFrom()->getId() : (isset($callback) ? $callback->getFrom()->getId() : null);
    }

    /**
     * Получает username пользователя
     */
    public function getUsername()
    {
        $message = $this->getMessage();
        $callback = $this->getCallback();
        
        return isset($message) ? $message->getFrom()->getUsername() : (isset($callback) ? $callback->getFrom()->getUsername() : null);
    }



    /**
     * Получает текст сообщения
     */
    public function getMessageText()
    {
        $message = $this->getMessage();
        return isset($message) ? $message->getText() : null;
    }

    /**
     * Получает ID сообщения
     */
    public function getMessageId()
    {
        $message = $this->getMessage();
        $callback = $this->getCallback();
        
        return isset($message) ? $message->getMessageId() : (isset($callback) ? $callback->getMessage()->getMessageId() : null);
    }

    /**
     * Получает видео из сообщения
     */
    public function getVideo()
    {
        $message = $this->getMessage();
        return isset($message) ? $message->getVideo() : null;
    }

    /**
     * Получает ID видео из сообщения
     */
    public function getVideoId()
    {
        $video = $this->getVideo();
        return isset($video) ? $video->getFileId() : null;
    }

    /**
     * Получает фото из сообщения
     */
    public function getPhoto()
    {
        $message = $this->getMessage();
        return isset($message) ? $message->getPhoto() : null;
    }

    /**
     * Получает ID фото из сообщения
     */
    public function getPhotoId()
    {
        $info = $this->getPhotoInfo();
        return $info ? ($info['largest']['file_id'] ?? null) : null;
    }

    /**
     * Регистрирует middleware для обработки сообщений
     */
    public function middleware($middleware): self
    {
        $this->middleware[] = $middleware;
        return $this;
    }

    /**
     * Регистрирует глобальный middleware для всех сообщений
     */
    public function globalMiddleware($middleware): self
    {
        $this->globalMiddleware[] = $middleware;
        return $this;
    }

    /**
     * Регистрирует базовые middleware
     */
    protected function registerDefaultMiddleware(): void
    {
        // Middleware для логирования активности
        $this->globalMiddleware(function ($update, $next) {
            $this->logActivity('message_received', [
                'user_id' => $this->getUserId(),
                'message_type' => $this->getMessageType(),
                'has_text' => $this->hasMessageText(),
            ]);
            
            return $next($update);
        });

        // Middleware для анти-спама (базовый)
        $this->globalMiddleware(function ($update, $next) {
            if ($this->isSpamMessage()) {
                $this->logActivity('spam_blocked', ['user_id' => $this->getUserId()]);
                return null;
            }
            
            return $next($update);
        });
    }

    /**
     * Выполняет middleware конвейер
     */
    protected function runThroughMiddleware($update, callable $finalCallback)
    {
        $middleware = array_merge($this->globalMiddleware, $this->middleware);
        
        $pipeline = array_reduce(
            array_reverse($middleware),
            function ($next, $middleware) {
                return function ($update) use ($middleware, $next) {
                    return $middleware($update, $next);
                };
            },
            $finalCallback
        );

        return $pipeline($update);
    }

    /**
     * Определяет тип сообщения (включает все типы из Telegram Bot API)
     */
    public function getMessageType(): string
    {
        $request = $this->updateData ?? request()->all();
        $message = $request['message'] ?? [];

        // Медиа контент
        if (isset($message['photo'])) return 'photo';
        if (isset($message['video'])) return 'video';
        if (isset($message['audio'])) return 'audio';
        if (isset($message['document'])) return 'document';
        if (isset($message['sticker'])) return 'sticker';
        if (isset($message['voice'])) return 'voice';
        if (isset($message['video_note'])) return 'video_note';
        if (isset($message['animation'])) return 'animation';
        
        // Контактная информация
        if (isset($message['contact'])) return 'contact';
        if (isset($message['location'])) return 'location';
        if (isset($message['venue'])) return 'venue';
        
        // Интерактивный контент
        if (isset($message['poll'])) return 'poll';
        if (isset($message['dice'])) return 'dice';
        if (isset($message['game'])) return 'game';
        if (isset($message['story'])) return 'story';
        
        // Платежи
        if (isset($message['invoice'])) return 'invoice';
        if (isset($message['successful_payment'])) return 'successful_payment';
        
        // Видеочаты
        if (isset($message['video_chat_started'])) return 'video_chat_started';
        if (isset($message['video_chat_ended'])) return 'video_chat_ended';
        if (isset($message['video_chat_participants_invited'])) return 'video_chat_participants_invited';
        if (isset($message['video_chat_scheduled'])) return 'video_chat_scheduled';
        
        // Системные сообщения чата
        if (isset($message['message_auto_delete_timer_changed'])) return 'message_auto_delete_timer_changed';
        if (isset($message['migrate_to_chat_id'])) return 'migrate_to_chat_id';
        if (isset($message['migrate_from_chat_id'])) return 'migrate_from_chat_id';
        if (isset($message['pinned_message'])) return 'pinned_message';
        if (isset($message['new_chat_members'])) return 'new_chat_members';
        if (isset($message['left_chat_member'])) return 'left_chat_member';
        if (isset($message['new_chat_title'])) return 'new_chat_title';
        if (isset($message['new_chat_photo'])) return 'new_chat_photo';
        if (isset($message['delete_chat_photo'])) return 'delete_chat_photo';
        if (isset($message['group_chat_created'])) return 'group_chat_created';
        if (isset($message['supergroup_chat_created'])) return 'supergroup_chat_created';
        if (isset($message['channel_chat_created'])) return 'channel_chat_created';
        
        // Форумы
        if (isset($message['forum_topic_created'])) return 'forum_topic_created';
        if (isset($message['forum_topic_edited'])) return 'forum_topic_edited';
        if (isset($message['forum_topic_closed'])) return 'forum_topic_closed';
        if (isset($message['forum_topic_reopened'])) return 'forum_topic_reopened';
        if (isset($message['general_forum_topic_hidden'])) return 'general_forum_topic_hidden';
        if (isset($message['general_forum_topic_unhidden'])) return 'general_forum_topic_unhidden';
        
        // Права и разрешения
        if (isset($message['write_access_allowed'])) return 'write_access_allowed';
        
        // Sharing и расшаривание
        if (isset($message['user_shared'])) return 'user_shared';
        if (isset($message['chat_shared'])) return 'chat_shared';
        
        // Конкурсы и подарки
        if (isset($message['giveaway'])) return 'giveaway';
        if (isset($message['giveaway_winners'])) return 'giveaway_winners';
        if (isset($message['giveaway_completed'])) return 'giveaway_completed';
        
        // Буст каналов
        if (isset($message['boost_added'])) return 'boost_added';
        
        // Фон чата
        if (isset($message['chat_background_set'])) return 'chat_background_set';
        
        // Веб-приложения
        if (isset($message['web_app_data'])) return 'web_app_data';
        
        // Passport данные
        if (isset($message['passport_data'])) return 'passport_data';
        
        // Proximity alert
        if (isset($message['proximity_alert_triggered'])) return 'proximity_alert_triggered';
        
        // Автоудаление
        if (isset($message['message_auto_delete_timer_changed'])) return 'message_auto_delete_timer_changed';
        
        // Текстовое сообщение
        if ($this->hasMessageText()) return 'text';
        
        return 'unknown';
    }

    /**
     * Простая проверка на спам (можно расширить)
     */
    protected function isSpamMessage(): bool
    {
        // Проверка частоты сообщений от пользователя
        $cacheKey = "telegram_user_messages_{$this->getUserId()}";
        $messages = cache()->get($cacheKey, 0);
        
        if ($messages > 20) { // Больше 20 сообщений в минуту
            return true;
        }
        
        cache()->put($cacheKey, $messages + 1, 60); // Счетчик на 1 минуту
        
        return false;
    }

    /**
     * Логирование активности бота
     */
    protected function logActivity(string $event, array $data = []): void
    {
        if (config('bot.settings.enable_detailed_logging', false)) {
            \Log::info("Telegram Bot Activity: {$event}", array_merge([
                'bot' => $this->bot,
                'timestamp' => now()->toISOString(),
            ], $data));
        }
    }

    /**
     * Регистрирует команду с расширенными возможностями
     */
    public function registerCommand(string $command, callable $callback, array $options = []): self
    {
        $commandData = [
            'command' => $command,
            'callback' => $callback,
            'description' => $options['description'] ?? '',
            'args' => $options['args'] ?? [],
            'middleware' => $options['middleware'] ?? [],
            'private_only' => $options['private_only'] ?? true,
            'admin_only' => $options['admin_only'] ?? false,
        ];

        $this->commandsList[$command] = $commandData;
        return $this;
    }

    /**
     * Парсит аргументы команды
     */
    public function parseCommandArgs(string $text): array
    {
        $parts = explode(' ', trim($text));
        $command = array_shift($parts);
        
        $args = [];
        $currentArg = '';
        $inQuotes = false;
        
        foreach ($parts as $part) {
            if (!$inQuotes && (str_starts_with($part, '"') || str_starts_with($part, "'"))) {
                $inQuotes = true;
                $currentArg = substr($part, 1);
                
                if (str_ends_with($part, $part[0]) && strlen($part) > 1) {
                    $args[] = substr($currentArg, 0, -1);
                    $currentArg = '';
                    $inQuotes = false;
                }
            } elseif ($inQuotes) {
                if (str_ends_with($part, '"') || str_ends_with($part, "'")) {
                    $currentArg .= ' ' . substr($part, 0, -1);
                    $args[] = $currentArg;
                    $currentArg = '';
                    $inQuotes = false;
                } else {
                    $currentArg .= ' ' . $part;
                }
            } else {
                $args[] = $part;
            }
        }
        
        if ($currentArg) {
            $args[] = $currentArg;
        }

        return [
            'command' => $command,
            'args' => $args,
            'raw' => $text,
        ];
    }

    /**
     * Проверяет права доступа для команды
     */
    protected function hasCommandAccess(array $commandData): bool
    {
        // Проверка на приватный чат
        if ($commandData['private_only'] && $this->getChatType() !== 'private') {
            return false;
        }

        // Проверка на админа (можно расширить)
        if ($commandData['admin_only'] && !$this->isAdmin()) {
            return false;
        }

        return true;
    }

    /**
     * Получает тип чата
     */
    public function getChatType(): string
    {
        $data = $this->updateData ?? request()->all();
        $message = $data['message'] ?? [];
        return $message['chat']['type'] ?? 'unknown';
    }

    /**
     * Проверяет является ли пользователь админом (базовая проверка)
     */
    protected function isAdmin(): bool
    {
        $adminIds = config('bot.settings.admin_ids', []);
        return in_array($this->getUserId(), $adminIds);
    }

    /**
     * Генерирует справку по командам
     */
    public function generateHelp(): string
    {
        $help = "🤖 **Доступные команды:**\n\n";
        
        foreach ($this->commandsList as $command => $data) {
            if (is_array($data) && isset($data['description'])) {
                $help .= "/{$command}";
                
                if (!empty($data['args'])) {
                    foreach ($data['args'] as $arg) {
                        $help .= " `{$arg}`";
                    }
                }
                
                $help .= " - {$data['description']}\n";
            }
        }
        
        return $help;
    }

    /**
     * Улучшенный обработчик команд с валидацией аргументов
     */
    public function handleCommand(string $text): bool
    {
        $parsed = $this->parseCommandArgs($text);
        $commandName = ltrim($parsed['command'], '/');
        
        if (!isset($this->commandsList[$commandName])) {
            return false;
        }

        $commandData = $this->commandsList[$commandName];
        
        // Проверяем права доступа
        if (!$this->hasCommandAccess($commandData)) {
            $this->sendSelf('❌ У вас нет прав для выполнения этой команды.');
            return true;
        }

        // Проверяем количество аргументов
        if (isset($commandData['args']) && count($parsed['args']) < count($commandData['args'])) {
            $help = "❌ Недостаточно аргументов.\n\n";
            $help .= "**Использование:** /{$commandName}";
            foreach ($commandData['args'] as $arg) {
                $help .= " `{$arg}`";
            }
            $this->sendSelf($help);
            return true;
        }

        try {
            // Выполняем middleware команды
            if (!empty($commandData['middleware'])) {
                foreach ($commandData['middleware'] as $middleware) {
                    if (!$middleware($this, $parsed)) {
                        return true; // Middleware заблокировал выполнение
                    }
                }
            }

            // Выполняем команду
            $callback = $commandData['callback'];
            $callback = $callback->bindTo($this, $this);
            $callback($parsed['args'], $parsed);

            $this->logActivity('command_executed', [
                'command' => $commandName,
                'args_count' => count($parsed['args']),
            ]);
            
            $this->messageProcessed = true;

        } catch (\Exception $e) {
            $this->logError($e);
            $this->sendSelf('❌ Произошла ошибка при выполнении команды.');
        }

        return true;
    }

    /**
     * Валидация входящего webhook запроса
     */
    private function isValidWebhookRequest(): bool
    {
        $request = request();
        
        // Проверяем что это POST запрос
        if (!$request->isMethod('POST')) {
            return false;
        }

        // Проверяем что есть данные
        if (!$request->hasAny(['message', 'callback_query', 'channel_post', 'edited_message'])) {
            return false;
        }

        return true;
    }

    /**
     * Проверяет есть ли текст в сообщении
     */
    public function hasMessageText(): bool
    {
        return !empty($this->getMessageText());
    }

    /**
     * Проверяет является ли сообщение командой (начинается с /)
     */
    public function isMessageCommand(): bool
    {
        return $this->hasMessageText() && str_starts_with($this->getMessageText(), '/');
    }

    /**
     * Проверяет содержит ли сообщение медиа контент без текста
     */
    public function hasMediaWithoutText(): bool
    {
        if (!$this->getMessage()) {
            return false;
        }

        $request = $this->updateData ?? request()->all();
        $message = $request['message'] ?? [];

        // Проверяем наличие медиа контента
        $hasMedia = isset($message['photo']) || 
                   isset($message['video']) || 
                   isset($message['audio']) || 
                   isset($message['document']) || 
                   isset($message['sticker']) || 
                   isset($message['voice']) || 
                   isset($message['video_note']) || 
                   isset($message['animation']) || 
                   isset($message['contact']) || 
                   isset($message['location']) || 
                   isset($message['venue']) || 
                   isset($message['poll']) || 
                   isset($message['dice']) || 
                   isset($message['game']) || 
                   isset($message['story']);

        return $hasMedia && empty($this->getMessageText());
    }

    /**
     * Умная версия safeMain с настраиваемой логикой игнорирования
     */
    public function safeMain(): void
    {
        try {
            // Сбрасываем флаг обработки для нового сообщения
            $this->messageProcessed = false;
            
            // Проверяем есть ли сообщение
            if (!$this->getMessage() && !$this->getCallback()) {
                return;
            }

            // Запускаем через middleware конвейер
            $this->runThroughMiddleware($this->updateData ?? request()->all(), function ($update) {
                // Проверяем нужно ли игнорировать медиа
                if ($this->hasMediaWithoutText() && $this->shouldIgnoreMedia()) {
                    return;
                }

                // Вызываем основной метод если он существует
                if (method_exists($this, 'main')) {
                    $this->main();
                }
                
                // Если сообщение не было обработано и есть fallback - вызываем его
                if (!$this->messageProcessed && $this->failCallback) {
                    if ($this->failCallback instanceof \Closure) {
                        $callback = $this->failCallback->bindTo($this, $this);
                    } else {
                        $callback = $this->failCallback;
                    }
                    $callback();
                }
            });

        } catch (\Exception $e) {
            $this->logError($e);
            
            // В продакшне не показываем детали ошибки
            if (app()->environment('production')) {
                // Можно отправить общее сообщение об ошибке
                // $this->sendSelf('Произошла ошибка. Попробуйте позже.');
            }
        }
    }

    /**
     * Логирование с указанием конкретного бота
     */
    private function logError(\Exception $e): void
    {
        \Log::error('Telegram Bot Error', [
            'bot' => $this->bot ?? class_basename(static::class),
            'user_id' => $this->getUserId() ?? 'unknown',
            'message' => $e->getMessage(),
            'file' => $e->getFile(),
            'line' => $e->getLine(),
            'trace' => $e->getTraceAsString(),
        ]);
    }

    /**
     * Определяет нужно ли игнорировать медиафайлы без текста
     * Боты могут переопределить этот метод для кастомной логики
     * 
     * @return bool true если нужно игнорировать, false если обрабатывать
     */
    protected function shouldIgnoreMedia(): bool
    {
        // Если медиа разрешены - не игнорируем
        if ($this->allowMedia) {
            return false;
        }
        
        // Если есть зарегистрированные типы медиа через media() - не игнорируем
        if (!empty($this->registeredMediaTypes)) {
            return false;
        }

        // Проверяем есть ли методы для обработки конкретных типов медиа
        $mediaHandlers = [
            // Медиа контент
            'handlePhoto', 'handleVideo', 'handleAudio', 'handleDocument', 
            'handleSticker', 'handleVoice', 'handleAnimation', 'handleVideoNote',
            
            // Контактная информация
            'handleContact', 'handleLocation', 'handleVenue',
            
            // Интерактивный контент  
            'handlePoll', 'handleDice', 'handleGame', 'handleStory',
            
            // Платежи
            'handleInvoice', 'handleSuccessfulPayment',
            
            // Видеочаты
            'handleVideoChatStarted', 'handleVideoChatEnded', 
            'handleVideoChatParticipantsInvited', 'handleVideoChatScheduled',
            
            // Системные сообщения
            'handleNewChatMembers', 'handleLeftChatMember', 'handleNewChatTitle',
            'handleNewChatPhoto', 'handleDeleteChatPhoto', 'handlePinnedMessage',
            
            // Форумы
            'handleForumTopicCreated', 'handleForumTopicEdited', 'handleForumTopicClosed',
            'handleForumTopicReopened', 'handleGeneralForumTopicHidden', 'handleGeneralForumTopicUnhidden',
            
            // Sharing
            'handleUserShared', 'handleChatShared',
            
            // Конкурсы
            'handleGiveaway', 'handleGiveawayWinners', 'handleGiveawayCompleted',
            
            // Буст и фоны
            'handleBoostAdded', 'handleChatBackgroundSet',
            
            // Веб-приложения и данные
            'handleWebAppData', 'handlePassportData', 'handleProximityAlertTriggered'
        ];

        foreach ($mediaHandlers as $handler) {
            if (method_exists($this, $handler)) {
                return false; // Есть обработчик - не игнорируем
            }
        }

        // Проверяем универсальный обработчик медиа
        if (method_exists($this, 'handleMedia')) {
            return false;
        }

        // По умолчанию игнорируем медиа
        return true;
    }

    /**
     * Определяет нужно ли игнорировать текстовые сообщения (не команды)
     * Боты могут переопределить этот метод для кастомной логики
     * 
     * @return bool true если нужно игнорировать, false если обрабатывать
     */
    protected function shouldIgnoreTextMessage(): bool
    {
        // Если текстовые сообщения разрешены - не игнорируем
        if ($this->allowTextMessages) {
            return false;
        }

        // Проверяем есть ли обработчик текстовых сообщений
        if (method_exists($this, 'handleTextMessage')) {
            return false;
        }

        // По умолчанию игнорируем обычные текстовые сообщения
        return true;
    }

    /**
     * Включает обработку медиафайлов
     * 
     * @return static
     */
    public function enableMedia(): static
    {
        $this->allowMedia = true;
        return $this;
    }

    /**
     * Включает обработку текстовых сообщений
     * 
     * @return static
     */
    public function enableTextMessages(): static
    {
        $this->allowTextMessages = true;
        return $this;
    }

    /**
     * Отключает обработку медиафайлов
     * 
     * @return static
     */
    public function disableMedia(): static
    {
        $this->allowMedia = false;
        return $this;
    }

    /**
     * Отключает обработку текстовых сообщений
     * 
     * @return static
     */
    public function disableTextMessages(): static
    {
        $this->allowTextMessages = false;
        return $this;
    }

    /**
     * Регистрирует обработчик для конкретного типа медиа
     * 
     * @param MediaType $mediaType Тип медиа (photo, video, document, sticker, voice, etc.)
     * @param callable $callback Функция-обработчик
     * @return mixed
     */
    public function media(MediaType $mediaType, callable $callback)
    {
        // Автоматически регистрируем этот тип медиа
        $this->registeredMediaTypes[] = $mediaType;
        
        $currentMediaType = $this->getMessageType();
        
        if ($currentMediaType === $mediaType->value) {
            $this->messageProcessed = true;
            if ($callback instanceof \Closure) {
                $callback = $callback->bindTo($this, $this);
            }
            return $callback();
        }
        
        return null;
    }

    /**
     * Регистрирует fallback обработчик для необработанных сообщений
     * 
     * @param callable $callback Функция-обработчик
     * @return static
     */
    public function fallback(callable $callback): static
    {
        $this->failCallback = $callback;
        return $this;
    }

    /**
     * Регистрирует обработчик для обычных текстовых сообщений (не команд)
     * 
     * @param callable $callback Функция-обработчик
     * @return mixed
     */
    public function text(callable $callback)
    {
        // Только если это текстовое сообщение и не команда
        if ($this->hasMessageText() && !$this->isMessageCommand()) {
            $this->messageProcessed = true;
            if ($callback instanceof \Closure) {
                $callback = $callback->bindTo($this, $this);
            }
            return $callback($this->getMessageText());
        }
        
        return null;
    }

    /**
     * Получает информацию о фото в сообщении
     */
    public function getPhotoInfo(): ?array
    {
        $request = $this->updateData ?? request()->all();
        $message = $request['message'] ?? [];
        
        if (!isset($message['photo'])) {
            return null;
        }

        // Telegram отправляет массив размеров фото
        $photos = $message['photo'];
        
        return [
            'count' => count($photos),
            'sizes' => $photos,
            'largest' => end($photos), // Самый большой размер
            'caption' => $message['caption'] ?? null,
        ];
    }

    /**
     * Получает информацию о видео в сообщении  
     */
    public function getVideoInfo(): ?array
    {
        $request = $this->updateData ?? request()->all();
        $message = $request['message'] ?? [];
        
        if (!isset($message['video'])) {
            return null;
        }

        $video = $message['video'];
        
        return [
            'file_id' => $video['file_id'],
            'file_unique_id' => $video['file_unique_id'],
            'width' => $video['width'] ?? 0,
            'height' => $video['height'] ?? 0,
            'duration' => $video['duration'] ?? 0,
            'file_size' => $video['file_size'] ?? null,
            'mime_type' => $video['mime_type'] ?? null,
            'caption' => $message['caption'] ?? null,
        ];
    }

    /**
     * Получает информацию о документе в сообщении
     */
    public function getDocumentInfo(): ?array
    {
        $request = $this->updateData ?? request()->all();
        $message = $request['message'] ?? [];
        
        if (!isset($message['document'])) {
            return null;
        }

        $document = $message['document'];
        
        return [
            'file_id' => $document['file_id'],
            'file_unique_id' => $document['file_unique_id'],
            'file_name' => $document['file_name'] ?? null,
            'mime_type' => $document['mime_type'] ?? null,
            'file_size' => $document['file_size'] ?? null,
            'caption' => $message['caption'] ?? null,
        ];
    }

    /**
     * Скачивает файл по file_id
     */
    public function downloadFile(string $fileId): ?array
    {
        try {
            // Получаем информацию о файле
            $fileInfo = $this->method('getFile', ['file_id' => $fileId]);
            
            if (!isset($fileInfo['ok']) || !$fileInfo['ok']) {
                return null;
            }

            $filePath = $fileInfo['result']['file_path'];
            $fileUrl = $this->file($filePath);
            
            // Скачиваем файл
            $response = \Http::withoutVerifying()->timeout(60)->get($fileUrl);
            
            if ($response->successful()) {
                return [
                    'content' => $response->body(),
                    'size' => $response->header('Content-Length'),
                    'type' => $response->header('Content-Type'),
                    'url' => $fileUrl,
                    'path' => $filePath,
                ];
            }

        } catch (\Exception $e) {
            $this->logError($e);
        }

        return null;
    }

    /**
     * Сохраняет файл на диск
     */
    public function saveFile(string $fileId, string $directory = 'telegram'): ?string
    {
        $fileData = $this->downloadFile($fileId);
        
        if (!$fileData) {
            return null;
        }

        try {
            $fileName = uniqid() . '_' . basename($fileData['path']);
            $fullPath = storage_path("app/public/{$directory}/{$fileName}");
            
            // Создаем директорию если не существует
            $dir = dirname($fullPath);
            if (!file_exists($dir)) {
                mkdir($dir, 0755, true);
            }

            file_put_contents($fullPath, $fileData['content']);
            
            return "storage/{$directory}/{$fileName}";

        } catch (\Exception $e) {
            $this->logError($e);
            return null;
        }
    }

    /**
     * Обработчик для медиа сообщений с текстом
     */
    public function mediaWithCaption($callback): void
    {
        if (!$this->getMessage()) {
            return;
        }

        $request = $this->updateData ?? request()->all();
        $message = $request['message'] ?? [];
        $caption = $message['caption'] ?? null;

        // Если есть подпись к медиа
        if ($caption && $this->hasMediaWithoutText() === false) {
            $mediaInfo = null;
            
            if (isset($message['photo'])) {
                $mediaInfo = ['type' => 'photo', 'data' => $this->getPhotoInfo()];
            } elseif (isset($message['video'])) {
                $mediaInfo = ['type' => 'video', 'data' => $this->getVideoInfo()];
            } elseif (isset($message['document'])) {
                $mediaInfo = ['type' => 'document', 'data' => $this->getDocumentInfo()];
            }

            if ($mediaInfo) {
                $callback = $callback->bindTo($this, $this);
                $callback($mediaInfo, $caption);
            }
        }
    }

    /**
     * Запускает основной процесс для клиента.
     *
     * Этот метод определяет класс, который его вызвал, извлекает части пространства имен
     * и устанавливает свойство bot в нижний регистр предпоследней части пространства имен.
     *
     * @return void
     */
    public function run()
    {
        $this->bot = $this->classNameBot();
        $this->modules();

        return $this;
    }

    /**
     * Преобразует имя бота в имя класса.
     *
     * @return string
     */
    protected function classNameBot(): string
    {
        return strtolower(str_replace('Bot', '', class_basename(static::class)));
    }

    /**
     * Запускает все модули.
     *
     * @return void
     */
    public function modules()
    {
        collect($this->getModules())
            ->filter(fn($module) => method_exists($this, $module))
            ->each(fn($module) => $this->$module());
    }

    /**
     * Получает имена модулей из папки Modules в формате методов.
     *
     * @return array
     */
    protected function getModules(): array
    {
        $modulePath = dirname(__DIR__) . '/src/Modules';

        $files = File::files($modulePath);

        return collect($files)
            ->map(fn($file) => class_basename($file->getFilenameWithoutExtension()))
            ->map(fn($className) => lcfirst($className))
            ->toArray();
    }

    public function getUserAvatarFileId()
    {
        return $this->getUserProfilePhotos($this->getUserId(), null, 1)["result"]["photos"][0][0]["file_id"] ?? null;
    }

    public function getUserAvatarFilePath()
    {
        $fileId = $this->getUserAvatarFileId();

        if (empty($fileId)) {
            return null;
        }

        $file = $this->getFile($fileId);

        if (!is_array($file) || !isset($file['result'])) {
            return null;
        }

        if (!isset($file['result']['file_path'])) {
            return null;
        }

        return $file['result']['file_path'];
    }

    public function getUserAvatarUrl()
    {
        return $this->file($this->getUserAvatarFilePath());
    }

    /**
     * Отправляет отладочную информацию о текущем запросе в формате текста или JSON.
     */
    public function debug($data = null, $tg_id = null)
    {
        $data = $data ?? $this->request()->toJson();

        if (is_string($data)) {
            $decodedData = json_decode($data, true);
            if (json_last_error() === JSON_ERROR_NONE) {
                $data = $decodedData;
            }
        }

        $jsonData = json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);

        if ($jsonData === false) {
            $data = print_r($data, true);
            $jsonData = json_encode(['text' => $data], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
        }

        $tg_id = $tg_id ?? $this->getUserId();

        $this->sendOut($tg_id, "<pre>" . $jsonData . "</pre>");
        exit;
    }

    /**
     * Метод отправки сообщения другому пользователю
     *
     * @param int $id Идентификатор пользователя.
     * @param array|string $message Текст сообщения.
     * @param array|null $keyboard Клавиатура для сообщения (необязательно).
     * @param int $layout Число делений или массив с ручным расположением.
     * @param int $type_keyboard Тип каливатуры 0 - keyboard 1 - inlineKeyboard
     *
     */
    public function sendOut($id, $message, $keyboard = null, $layout = 2, $type_keyboard = 0)
    {
        $trans = 'trans';

        if (method_exists($this, $trans)) {
            $message = $this->$trans($message);
            $keyboard = $this->$trans($keyboard);
        }

        $keyboard = $keyboard !== null ? Services::simpleKeyboard($keyboard, $type_keyboard) : $keyboard;
        is_array($message) ? $message = Services::html($message) : $message;
        $keyboard ? $keygrid = Services::grid($keyboard, $layout) : $keyboard;
        $type_keyboard === 1 ? $type = "inlineKeyboard" : $type = "keyboard";
        return $this->sendMessage($id, $message, $keyboard ? Services::{$type}($keygrid) : $keyboard, null, null, "HTML", null, null, false, false, null, null);
    }

    /**
     * Метод отправки сообщения текущему пользователю
     *
     * @param string|array $message Текст сообщения.
     * @param array|null $keyboard Клавиатура для сообщения (необязательно).
     * @param int|array $layout Число делений или массив с ручным расположением.
     * @param int $type_keyboard Тип каливатуры 1 - keyboard 2 - inlineKeyboard
     * 
     */
    public function sendSelf($message, $keyboard = null, $layout = 2, $type_keyboard = 0)
    {
        return $this->sendOut($this->getUserId(), $message, $keyboard, $layout, $type_keyboard);
    }

    /**
     * Метод отправки фото другому пользователю
     *
     * @param int $chat_id Идентификатор чата.
     * @param string $photo URL или файл фото.
     * @param string|null $caption Подпись к фото (необязательно).
     * @param array|null $keyboard Клавиатура для сообщения (необязательно).
     * @param int $layout Число делений или массив с ручным расположением.
     * @param int $type_keyboard Тип клавиатуры 0 - keyboard 1 - inlineKeyboard
     * 
     */
    public function sendOutPhoto($chat_id, $photo, $caption = null, $keyboard = null, $layout = 2, $type_keyboard = 0)
    {
        $keyboard = $keyboard !== null ? Services::simpleKeyboard($keyboard, $type_keyboard) : $keyboard;
        $keyboard ? $keygrid = Services::grid($keyboard, $layout) : $keyboard;
        $type_keyboard === 1 ? $type = "inlineKeyboard" : $type = "keyboard";
        return $this->sendPhoto(
            $chat_id,
            $photo,
            $caption,
            $keyboard ? Services::{$type}($keygrid) : $keyboard,
            'HTML'
        );
    }

    /**
     * Метод отправки фото текущему пользователю
     *
     * @param string $photo URL или файл фото.
     * @param string|null $caption Подпись к фото (необязательно).
     * @param array|null $keyboard Клавиатура для сообщения (необязательно).
     * @param int $layout Число делений или массив с ручным расположением.
     * @param int $type_keyboard Тип клавиатуры 0 - keyboard 1 - inlineKeyboard
     * 
     */
    public function sendSelfPhoto($photo, $caption = null, $keyboard = null, $layout = 2, $type_keyboard = 0)
    {
        return $this->sendOutPhoto($this->getUserId(), $photo, $caption, $keyboard, $layout, $type_keyboard);
    }

    /**
     * Метод отправки фото текущему пользователю
     *
     * @param string $photo URL или файл фото.
     * @param string|null $caption Подпись к фото (необязательно).
     * @param array|null $keyboard Клавиатура для сообщения (необязательно).
     * @param int $layout Число делений или массив с ручным расположением.
     * 
     */
    public function sendSelfPhotoInline($photo, $caption = null, $keyboard = null, $layout = 2)
    {
        return $this->sendOutPhoto($this->getUserId(), $photo, $caption, $keyboard, $layout, 1);
    }

    /**
     * Метод отправки видео другому пользователю
     *
     * @param int $chat_id Идентификатор чата.
     * @param string $video URL или файл видео.
     * @param string|null $caption Подпись к видео (необязательно).
     * @param array|null $keyboard Клавиатура для сообщения (необязательно).
     * @param int $layout Число делений или массив с ручным расположением.
     * @param int $type_keyboard Тип клавиатуры 0 - keyboard 1 - inlineKeyboard
     * 
     */
    public function sendOutVideo($chat_id, $video, $caption = null, $keyboard = null, $layout = 2, $type_keyboard = 0)
    {
        $keyboard = $keyboard !== null ? Services::simpleKeyboard($keyboard, $type_keyboard) : $keyboard;
        $keyboard ? $keygrid = Services::grid($keyboard, $layout) : $keyboard;
        $type_keyboard === 1 ? $type = "inlineKeyboard" : $type = "keyboard";
        return $this->sendVideo(
            $chat_id,
            $video,
            $caption,
            $keyboard ? Services::{$type}($keygrid) : $keyboard,
            null,
            null,
            null,
            null,
            null,
            null,
            'HTML'
        );
    }

    /**
     * Метод отправки видео текущему пользователю
     *
     * @param string $video URL или файл видео.
     * @param string|null $caption Подпись к видео (необязательно).
     * @param array|null $keyboard Клавиатура для сообщения (необязательно).
     * @param int $layout Число делений или массив с ручным расположением.
     * @param int $type_keyboard Тип клавиатуры 0 - keyboard 1 - inlineKeyboard
     * 
     */
    public function sendSelfVideo($video, $caption = null, $keyboard = null, $layout = 2, $type_keyboard = 0)
    {
        return $this->sendOutVideo($this->getUserId(), $video, $caption, $keyboard, $layout, $type_keyboard);
    }

    /**
     * Метод отправки видео текущему пользователю
     *
     * @param string $video URL или файл видео.
     * @param string|null $caption Подпись к видео (необязательно).
     * @param array|null $keyboard Клавиатура для сообщения (необязательно).
     * @param int $layout Число делений или массив с ручным расположением.
     * 
     */
    public function sendSelfVideoInline($video, $caption = null, $keyboard = null, $layout = 2)
    {
        return $this->sendOutVideo($this->getUserId(), $video, $caption, $keyboard, $layout, 1);
    }

    /**
     * Метод отправки сообщения текущему пользователю использует inlineKeyboard
     *
     * @param string|array $message Текст сообщения.
     * @param array|null $keyboard Клавиатура для сообщения (необязательно).
     * @param int $layout Число делений или массив с ручным расположением.
     * 
     */
    public function sendSelfInline($message, $keyboard = null, $layout = 2)
    {
        return $this->sendSelf($message, $keyboard, $layout, 1);
    }

    /**
     * Метод отправки сообщения другому пользователю использует inlineKeyboard
     *
     * @param int $id Идентификатор пользователя.
     * @param string|array $message Текст сообщения.
     * @param array|null $keyboard Клавиатура для сообщения (необязательно).
     * @param int $layout Число делений или массив с ручным расположением.
     * 
     */
    public function sendOutInline($id, $message, $keyboard = null, $layout = 2)
    {
        return $this->sendOut($id, $message, $keyboard, $layout, 1);
    }

    /**
     * Метод удаления сообщений в чате для другого пользователя
     *
     * @param int $chat_id Идентификатор чата.
     * @param string|array $message_id ID сообщения.
     * 
     */
    public function deleteOut($chat_id, $message_id)
    {
        return $this->deleteMessage($chat_id, $message_id);
    }

    /**
     * Метод удаления сообщений в чате для текущего пользователя
     *
     * @param string|array $message_id ID сообщения.
     * 
     */
    public function deleteSelf($message_id)
    {
        return $this->deleteOut($this->getUserId(), $message_id);
    }

    /**
     * Метод редактирования сообщения другому пользователю
     *
     * @param int $chat_id Идентификатор чата.
     * @param string $message_id id сообщения
     * @param string|array $message Текст сообщения.
     * @param array|null $keyboard Клавиатура для сообщения (необязательно).
     * @param int $layout Число делений или массив с ручным расположением.
     * @param int $type_keyboard Тип каливатуры 1 - keyboard 2 - inlineKeyboard
     * @param string|null $parse_mode Включение HTML мода, по умолчанию включен (необязательно).
     * 
     */
    public function editOut($chat_id, $message_id, $message, $keyboard = null, $layout = 2, $type_keyboard = 0)
    {
        $keyboard = $keyboard !== null ? Services::simpleKeyboard($keyboard, $type_keyboard) : $keyboard;
        is_array($message) ? $message = Services::html($message) : $message;
        $keyboard ? $keygrid = Services::grid($keyboard, $layout) : $keyboard;
        $type_keyboard === 1 ? $type = "inlineKeyboard" : $type = "keyboard";
        return $this->editMessageText($chat_id, $message_id, $message, $keyboard ? Services::{$type}($keygrid) : $keyboard, "HTML");
    }

    /**
     * Метод редактирования сообщения текущему пользователю
     *
     * @param string|array $message Текст сообщения.
     * @param string $message_id id сообщения
     * @param array|null $keyboard Клавиатура для сообщения (необязательно).
     * @param int $layout Число делений или массив с ручным расположением.
     * @param int $type_keyboard Тип каливатуры 1 - keyboard 2 - inlineKeyboard
     * @param string|null $parse_mode Включение HTML мода, по умолчанию включен (необязательно).
     * 
     */
    public function editSelf($message_id, $message, $keyboard = null, $layout = 2, $type_keyboard = 0, $parse_mode = "HTML")
    {
        return $this->editOut($this->getUserId(), $message_id, $message, $keyboard, $layout, $type_keyboard);
    }

    /**
     * Метод редактирования сообщения текущему пользователю
     *
     * @param string|array $message Текст сообщения.
     * @param string $message_id id сообщения
     * @param array|null $keyboard Клавиатура для сообщения (необязательно).
     * @param int $layout Число делений или массив с ручным расположением.
     * 
     */
    public function editSelfInline($message_id, $message, $keyboard = null, $layout = 2)
    {
        return $this->editOut($this->getUserId(), $message_id, $message, $keyboard, $layout, 1);
    }

    /**
     * Метод редактирования разметки клавиатуры для другого пользователя
     *
     * @param int $chat_id Идентификатор чата.
     * @param string $message_id id сообщения
     * @param array $keyboard Клавиатура для сообщения (необязательно).
     * @param int $layout Число делений или массив с ручным расположением.
     * 
     */
    public function editReplyMarkupOut($chat_id, $message_id, $keyboard, $layout = 2)
    {
        $keyboard = Services::simpleKeyboard($keyboard, 1);
        $keyboard ? $keygrid = Services::grid($keyboard, $layout) : $keyboard;
        return $this->editMessageReplyMarkup($chat_id, $message_id, $keyboard ? Services::inlineKeyboard($keygrid) : $keyboard);
    }

    /**
     * Метод редактирования разметки клавиатуры текущему пользователю
     *
     * @param string $message_id id сообщения
     * @param array $keyboard Клавиатура для сообщения (необязательно).
     * @param int $layout Число делений или массив с ручным расположением.
     * 
     */
    public function editReplyMarkupSelf($message_id, $keyboard = [], $layout = 2)
    {
        return $this->editReplyMarkupOut($this->getUserId(), $message_id, $keyboard, $layout);
    }

    /**
     * Определяет команду для бота и выполняет соответствующий обработчик, если команда совпадает с текстом сообщения или callback.
     *
     * @param string|array $command Команда, начинающаяся с символа "/" (например, "/start") или массив команд.
     * @param Closure $callback Функция-обработчик для выполнения, если команда или callback совпадают.
     *
     * @return mixed Результат выполнения функции-обработчика.
     */
    public function command($command, $callback)
    {
        // Приводим команду к массиву, если это строка
        $commands = is_array($command) ? $command : [$command];

        $this->commandsList[] = $commands;

        // Преобразуем команды, добавляя "/" к каждой, если необходимо
        $commands = array_map(function ($cmd) {
            return "/" . ltrim($cmd, '/');
        }, $commands);

        // Привязываем callback к текущему объекту
        $callback = $callback->bindTo($this, $this);

        // Получаем текст сообщения и данные callback
        $messageText = $this->getMessageText();

        // Проверка для текста сообщения
        foreach ($commands as $cmd) {
            if ($messageText && strpos($messageText, $cmd) === 0) {
                $arguments = Services::getArguments($messageText);
                $callback($arguments); // Завершаем выполнение после нахождения совпадения
                return;
            }
        }

        return null;
    }


    /**
     * Собирает все реализованные команды, команды использовать в самом низу всех команд
     */

    public function getCommandList()
    {
        return $this->commandsList ? array_merge(...$this->commandsList) : [];
    }

    /**
     * Проверка на сущестование, команды использовать в самом низу всех команд
     */

    public function isCommand()
    {
        return in_array($this->getCommandNoSlash(), $this->getCommandList(), true);
    }

    /**
     * Возварщает команду или null
     * 
     * @return string|null
     */
    public function getCommand()
    {
        if (str_starts_with($this->getMessageText(), '/')) {
            return $this->getMessageText();
        }

        return null;
    }

    /**
     * Возварщает команду без слеша или null
     * 
     * @return string|null
     */
    public function getCommandNoSlash()
    {
        if ($this->getCommand()) {
            return ltrim($this->getCommand(), '/');
        }

        return null;
    }

    /**
     * Аругменты любой команды.
     * 
     * @return int|string|null Результат выполнения функции-обработчика.
     */
    public function commandArguments()
    {
        $this->anyCommand(function ($command) {
            return self::getArgument($this->getCommand(), $command);
        });
    }

    /**
     * Возвращает аргумент команды.
     *
     * @param string $str Входная строка.
     * @param string $command Команда, которую нужно исключить из входной строки.
     * @return string Последнее слово из входной строки или пустая строка, если входная строка совпадает с командой.
     */
    private static function getArgument(string $str, string $command): string
    {
        if ($str === $command) {
            return '';
        }
        preg_match('/(\S+)\s(.+)/', $str, $matches);
        return isset($matches[2]) ? $matches[2] : "";
    }

    /**
     * Определяет сообщение для бота и выполняет соответствующий обработчик, если сообщение совпадает с паттерном.
     *
     * @param string|array $pattern Это строка или массив строк/регулярных выражений, по которым будет искать совпадение с сообщением.
     * @param Closure $callback Функция-обработчик для выполнения, если сообщение совпадает с паттерном.
     *
     * @return mixed Результат выполнения функции-обработчика.
     */
    public function message($pattern, $callback)
    {
        return Services::pattern($pattern, $this->getMessageText(), $callback);
    }

    /**
     * Определяет сообщение от пользователя и выполняет ошибку.
     *
     * @param mixed $message Любое сообщение кроме команды.
     * @param array|null $array Данные
     * @param Closure $callback Функция-обработчик для выполнения, если команда совпадает.
     *
     * @return mixed Результат выполнения функции-обработчика.
     */
    public function error($message, $array, $callback)
    {
        $callback = $callback->bindTo($this);

        if ($array === null) {
            if ($message === $this->getMessageText()) {
                $callback();
            }
        } else {
            if (Services::findMatch($message, $array)) {
                $callback();
            }
        }
    }

    /**
     * Определяет действие для бота и выполняет соответствующий обработчик, если текст сообщения не начинается с "/".
     *
     * @param Closure $callback Функция-обработчик для выполнения, если текст сообщения не является командой.
     *
     * @return mixed Результат выполнения функции-обработчика.
     */
    public function anyMessage($callback)
    {
        $text = $this->getMessageText();
        $callbackData = $this->getCallback();
        if ($text !== null && mb_substr($text, 0, 1) !== "/" && !$callbackData) {
            return $callback($text);
        }
    }

    /**
     * Определяет действие для бота и выполняет соответствующий обработчик, если текст сообщения начинается с "/".
     *
     * @param Closure $callback Функция-обработчик для выполнения, если текст сообщения не является командой.
     *
     * @return mixed Результат выполнения функции-обработчика.
     */
    public function anyCommand($callback)
    {
        $command = $this->getMessageText();
        $callbackData = $this->getCallback();
        if ($command !== null && mb_substr($command, 0, 1) === "/" && !$callbackData) {
            return $callback($command);
        }
    }

    /**
     * Обрабатывает callback-запросы.
     *
     * @param string $pattern Шаблон для сопоставления с данными callback-запроса.
     * @param Closure $callback Функция, которая будет вызвана при совпадении шаблона.
     * @param string|null $text Текст сообщения, который будет отправлен в ответ на callback-запрос (по умолчанию null).
     * @param bool $show_alert Флаг, указывающий, нужно ли показывать alert при ответе на callback-запрос (по умолчанию false).
     * @param string|null $url URL, который будет открыт при ответе на callback-запрос (по умолчанию null).
     * @param int $cache_time Время кэширования ответа на callback-запрос в секундах (по умолчанию 0).
     */
    public function callback($pattern, $callback, $text = null, $show_alert = false, $url = null, $cache_time = 0)
    {
        $callbackQuery = $this->getCallback();

        // Добавляем проверку на существование и тип переменной $cb
        if ($callbackQuery) {
            return Services::pattern($pattern, $callbackQuery->getData(), $callback, function () use ($callbackQuery, $text, $show_alert, $url, $cache_time) {
                $this->answerCallbackQuery($callbackQuery->getId(), $text, $show_alert, $url, $cache_time);
            });
        }

        return null;
    }

    // /**
    //  * Определяет обработчик для события pre-checkout.
    //  *
    //  * @param Closure $callback Функция-обработчик для выполнения, если событие pre-checkout происходит.
    //  *
    //  * @return mixed Результат выполнения функции-обработчика.
    //  */
    // public function preCheckout($callback)
    // {
    //     $preCheckoutQuery = $this->getPreCheckoutData();

    //     if ($preCheckoutQuery !== null) {
    //         $callback = $callback->bindTo($this, $this);
    //         return $callback((object) $preCheckoutQuery);
    //     }

    //     return null;
    // }

    // /**
    //  * Обрабатывает запрос pre-checkout и автоматически подтверждает его.
    //  *
    //  * @param bool $ok Указывает, следует ли подтвердить запрос pre-checkout (по умолчанию: true).
    //  * @param string|null $error_message Сообщение об ошибке в читаемом виде, объясняющее причину невозможности продолжить оформление заказа (обязательно, если ok равно False).
    //  * @return mixed Результат выполнения функции-обработчика.
    //  */
    // public function preCheckoutOk($ok = true, $error_message = null)
    // {
    //     $data = (object) $this->getPreCheckoutData();
    //     return $this->answerPreCheckoutQuery(isset($data->id) ? $data->id : null, $ok, $error_message);
    // }

    // /**
    //  * Отправляет счет самому себе.
    //  *
    //  * @param int $chat_id Идентификатор чата.
    //  * @param string $title Название счета.
    //  * @param string $description Описание счета.
    //  * @param string $payload Полезная нагрузка счета.
    //  * @param string $provider_token Токен провайдера.
    //  * @param string $start_parameter Параметр запуска.
    //  * @param string $currency Валюта счета.
    //  * @param array $prices Массив цен.
    //  * @param int|null $reply_to_message_id ID сообщения, на которое нужно ответить (необязательно).
    //  * @param bool $disable_notification Отключить уведомления (по умолчанию false).
    //  * @param string|null $photo_url URL фотографии (необязательно).
    //  * @param int|null $photo_size Размер фотографии (необязательно).
    //  * @param int|null $photo_width Ширина фотографии (необязательно).
    //  * @param int|null $photo_height Высота фотографии (необязательно).
    //  * @param bool $need_name Требуется ли имя (по умолчанию false).
    //  * @param bool $need_phone_number Требуется ли номер телефона (по умолчанию false).
    //  * @param bool $need_email Требуется ли email (по умолчанию false).
    //  * @param bool $need_shipping_address Требуется ли адрес доставки (по умолчанию false).
    //  * @param bool $send_phone_number_to_provider Отправить ли номер телефона провайдеру (по умолчанию false).
    //  * @param bool $send_email_to_provider Отправить ли email провайдеру (по умолчанию false).
    //  * @param bool $is_flexible Гибкий ли счет (по умолчанию false).
    //  *
    //  * @return mixed Результат отправки счета.
    //  */
    // public function sendInvoiceOut($chat_id, $title, $description, $payload, $provider_token, $start_parameter, $currency, $prices, $reply_to_message_id = null, $disable_notification = false, $photo_url = null, $photo_size = null, $photo_width = null, $photo_height = null, $need_name = false, $need_phone_number = false, $need_email = false, $need_shipping_address = false, $send_phone_number_to_provider = false, $send_email_to_provider = false, $is_flexible = false)
    // {
    //     return $this->sendInvoice($chat_id, $title, $description, $payload, $provider_token, $start_parameter, $currency, $prices, $reply_to_message_id, $disable_notification, $photo_url, $photo_size, $photo_width, $photo_height, $need_name, $need_phone_number, $need_email, $need_shipping_address, $send_phone_number_to_provider, $send_email_to_provider, $is_flexible);
    // }

    // /**
    //  * Отправляет счет самому себе.
    //  *
    //  * @param string $title Название счета.
    //  * @param string $description Описание счета.
    //  * @param string $payload Полезная нагрузка счета.
    //  * @param string $provider_token Токен провайдера.
    //  * @param string $currency Валюта счета.
    //  * @param array $prices Массив цен.
    //  * @param int|null $max_tip_amount Максимально допустимая сумма чаевых в наименьших единицах валюты (целое число, не float/double). По умолчанию 0.
    //  * @param array|null $suggested_tip_amounts JSON-сериализованный массив предложенных сумм чаевых в наименьших единицах валюты (целое число, не float/double). Максимум 4 предложенные суммы чаевых.
    //  * @param string|null $start_parameter Параметр запуска.
    //  * @param string|null $provider_data JSON-сериализованные данные о счете, которые будут переданы провайдеру платежей.
    //  * @param string|null $photo_url URL фотографии продукта для счета.
    //  * @param int|null $photo_size Размер фотографии в байтах.
    //  * @param int|null $photo_width Ширина фотографии.
    //  * @param int|null $photo_height Высота фотографии.
    //  * @param bool $need_name Требуется ли имя.
    //  * @param bool $need_phone_number Требуется ли номер телефона.
    //  * @param bool $need_email Требуется ли email.
    //  * @param bool $need_shipping_address Требуется ли адрес доставки.
    //  * @param bool $send_phone_number_to_provider Отправить ли номер телефона провайдеру.
    //  * @param bool $send_email_to_provider Отправить ли email провайдеру.
    //  * @param bool $is_flexible Гибкий ли счет.
    //  * @param bool $disable_notification Отключить уведомления.
    //  * @param bool $protect_content Защитить содержимое отправленного сообщения от пересылки и сохранения.
    //  * @param string|null $message_effect_id Уникальный идентификатор эффекта сообщения, который будет добавлен к сообщению; только для личных чатов.
    //  * @param array|null $reply_parameters Описание сообщения, на которое нужно ответить.
    //  * @param array|null $reply_markup JSON-сериализованный объект для встроенной клавиатуры.
    //  *
    //  * @return \Illuminate\Http\Client\Response|null Ответ от Telegram API.
    //  */
    // public function sendInvoiceSelf($title, $description, $payload, $provider_token, $currency, $prices, $max_tip_amount = null, $suggested_tip_amounts = null, $start_parameter = null, $provider_data = null, $photo_url = null, $photo_size = null, $photo_width = null, $photo_height = null, $need_name = false, $need_phone_number = false, $need_email = false, $need_shipping_address = false, $send_phone_number_to_provider = false, $send_email_to_provider = false, $is_flexible = false, $disable_notification = false, $protect_content = false, $message_effect_id = null, $reply_parameters = null, $reply_markup = null)
    // {
    //     return $this->sendInvoiceOut($this->getUserId(), $title, $description, $payload, $provider_token, $currency, $prices, $max_tip_amount, $suggested_tip_amounts, $start_parameter, $provider_data, $photo_url, $photo_size, $photo_width, $photo_height, $need_name, $need_phone_number, $need_email, $need_shipping_address, $send_phone_number_to_provider, $send_email_to_provider, $is_flexible, $disable_notification, $protect_content, $message_effect_id, $reply_parameters, $reply_markup);
    // }


}
