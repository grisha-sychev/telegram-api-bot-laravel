<?php

namespace Bot\Enums;

/**
 * Enum для типов медиа в Telegram Bot API
 * 
 * Этот enum обеспечивает:
 * - ✅ Автодополнение в IDE
 * - ✅ Типобезопасность
 * - ✅ Защиту от опечаток
 * - ✅ Легкое расширение
 * 
 * @example
 * $this->media(MediaType::PHOTO->value, function () {
 *     // обработка фото
 * });
 */
enum MediaType: string
{
    // Основные медиа типы
    case TEXT = 'text';
    case PHOTO = 'photo';
    case VIDEO = 'video';
    case DOCUMENT = 'document';
    case AUDIO = 'audio';
    case VOICE = 'voice';
    case STICKER = 'sticker';
    case ANIMATION = 'animation';
    
    // Контакты и локация
    case CONTACT = 'contact';
    case LOCATION = 'location';
    case VENUE = 'venue';
    
    // Игры и развлечения
    case GAME = 'game';
    case POLL = 'poll';
    case DICE = 'dice';
    case INVOICE = 'invoice';
    case SUCCESSFUL_PAYMENT = 'successful_payment';
    
    // Видеозвонки и чаты
    case VIDEO_CHAT_STARTED = 'video_chat_started';
    case VIDEO_CHAT_ENDED = 'video_chat_ended';
    case VIDEO_CHAT_PARTICIPANTS_INVITED = 'video_chat_participants_invited';
    case VIDEO_CHAT_SCHEDULED = 'video_chat_scheduled';
    
    // Форумы и темы
    case FORUM_TOPIC_CREATED = 'forum_topic_created';
    case FORUM_TOPIC_EDITED = 'forum_topic_edited';
    case FORUM_TOPIC_CLOSED = 'forum_topic_closed';
    case FORUM_TOPIC_REOPENED = 'forum_topic_reopened';
    case GENERAL_FORUM_TOPIC_HIDDEN = 'general_forum_topic_hidden';
    case GENERAL_FORUM_TOPIC_UNHIDDEN = 'general_forum_topic_unhidden';
    
    // Дополнительные типы
    case VIDEO_NOTE = 'video_note';
    case PASSPORT_DATA = 'passport_data';
    case PROXIMITY_ALERT_TRIGGERED = 'proximity_alert_triggered';
    case WEB_APP_DATA = 'web_app_data';
    case MESSAGE_AUTO_DELETE_TIMER_CHANGED = 'message_auto_delete_timer_changed';
    case MIGRATE_TO_CHAT_ID = 'migrate_to_chat_id';
    case MIGRATE_FROM_CHAT_ID = 'migrate_from_chat_id';
    case PINNED_MESSAGE = 'pinned_message';
    case NEW_CHAT_TITLE = 'new_chat_title';
    case NEW_CHAT_PHOTO = 'new_chat_photo';
    case DELETE_CHAT_PHOTO = 'delete_chat_photo';
    case GROUP_CHAT_CREATED = 'group_chat_created';
    case SUPERGROUP_CHAT_CREATED = 'supergroup_chat_created';
    case CHANNEL_CHAT_CREATED = 'channel_chat_created';
    case LEFT_CHAT_MEMBER = 'left_chat_member';
    case NEW_CHAT_MEMBERS = 'new_chat_members';
    case CONNECTED_WEBSITE = 'connected_website';
    case WRITE_ACCESS_ALLOWED = 'write_access_allowed';
    case USER_SHARED = 'user_shared';
    case CHAT_SHARED = 'chat_shared';
    case STORY = 'story';
    
    // Giveaway и бустеры
    case GIVEAWAY = 'giveaway';
    case GIVEAWAY_WINNERS = 'giveaway_winners';
    case GIVEAWAY_COMPLETED = 'giveaway_completed';
    case BOOST_ADDED = 'boost_added';
    
    /**
     * Получить все медиа типы (исключая системные сообщения)
     */
    public static function getMediaTypes(): array
    {
        return [
            self::TEXT,
            self::PHOTO,
            self::VIDEO,
            self::DOCUMENT,
            self::AUDIO,
            self::VOICE,
            self::STICKER,
            self::ANIMATION,
            self::VIDEO_NOTE,
            self::CONTACT,
            self::LOCATION,
            self::VENUE,
            self::GAME,
            self::POLL,
            self::DICE,
            self::WEB_APP_DATA,
        ];
    }
    
    /**
     * Получить только файловые медиа типы
     */
    public static function getFileTypes(): array
    {
        return [
            self::PHOTO,
            self::VIDEO,
            self::DOCUMENT,
            self::AUDIO,
            self::VOICE,
            self::STICKER,
            self::ANIMATION,
            self::VIDEO_NOTE,
        ];
    }
    
    /**
     * Получить типы сообщений, которые содержат текст
     */
    public static function getTextTypes(): array
    {
        return [
            self::TEXT,
            self::PHOTO,      // может содержать caption
            self::VIDEO,      // может содержать caption
            self::DOCUMENT,   // может содержать caption
            self::AUDIO,      // может содержать caption
            self::VOICE,      // может содержать caption
            self::ANIMATION,  // может содержать caption
            self::VIDEO_NOTE, // может содержать caption
        ];
    }
    
    /**
     * Проверить, является ли тип файловым медиа
     */
    public function isFileType(): bool
    {
        return in_array($this, self::getFileTypes());
    }
    
    /**
     * Проверить, может ли тип содержать текст
     */
    public function canHaveText(): bool
    {
        return in_array($this, self::getTextTypes());
    }
    
    /**
     * Получить иконку для типа медиа
     */
    public function getIcon(): string
    {
        return match($this) {
            self::TEXT => '💬',
            self::PHOTO => '📸',
            self::VIDEO => '🎥',
            self::DOCUMENT => '📄',
            self::AUDIO => '🎵',
            self::VOICE => '🎤',
            self::STICKER => '🎭',
            self::ANIMATION => '🎬',
            self::VIDEO_NOTE => '📹',
            self::CONTACT => '👤',
            self::LOCATION => '📍',
            self::VENUE => '🏢',
            self::GAME => '🎮',
            self::POLL => '📊',
            self::DICE => '🎲',
            self::INVOICE => '💳',
            self::SUCCESSFUL_PAYMENT => '💰',
            self::WEB_APP_DATA => '🌐',
            default => '📎',
        };
    }
    
    /**
     * Получить человекочитаемое название
     */
    public function getLabel(): string
    {
        return match($this) {
            self::TEXT => 'Текст',
            self::PHOTO => 'Фотография',
            self::VIDEO => 'Видео',
            self::DOCUMENT => 'Документ',
            self::AUDIO => 'Аудио',
            self::VOICE => 'Голосовое сообщение',
            self::STICKER => 'Стикер',
            self::ANIMATION => 'GIF анимация',
            self::VIDEO_NOTE => 'Видеосообщение',
            self::CONTACT => 'Контакт',
            self::LOCATION => 'Местоположение',
            self::VENUE => 'Место',
            self::GAME => 'Игра',
            self::POLL => 'Опрос',
            self::DICE => 'Кубик',
            self::INVOICE => 'Счет',
            self::SUCCESSFUL_PAYMENT => 'Платеж',
            self::WEB_APP_DATA => 'Данные веб-приложения',
            default => ucfirst($this->value),
        };
    }
} 