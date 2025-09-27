<?php

namespace Bot\Api;

use Bot\Support\Facades\Services;

class Basic extends Core
{
    /**
     * Устанавливает вебхук (Webhook) для бота.
     */


    public function setWebhook()
    {
        $hostname = $this->hostname ?? request()->getHost();

        return $this->method('setWebhook', [
            "url" => 'https://' . $hostname . '/bot/' . (new Services)->getToken($this->bot),
        ]);
    }

    /**
     * Удаляет вебхук (Webhook) для бота.
     */
    public function removeWebhook()
    {
        return $this->method('deleteWebhook');
    }

    /**
     * Получает информацию о боте.
     */
    public function getMe()
    {
        return $this->method('getMe');
    }

    /**
     * Завершает сеанс бота.
     */
    public function logOut()
    {
        return $this->method('logOut');
    }

    /**
     * Terminates the bot session.
     */
    public function close()
    {
        return $this->method('close');
    }

    /**
     * Отправляет текстовое сообщение в чат.
     *
     * @param int|string $chat_id Идентификатор чата или имя пользователя канала.
     * @param string $text Текст сообщения.
     * @param string|null $business_connection_id Уникальный идентификатор бизнес-соединения, от имени которого будет отправлено сообщение (необязательно).
     * @param int|null $message_thread_id Уникальный идентификатор темы сообщения (только для форумов).
     * @param string|null $parse_mode Режим HTML (необязательно).
     * @param array|null $entities Массив специальных сущностей, которые появляются в тексте сообщения (необязательно).
     * @param array|null $link_preview_options Опции генерации предпросмотра ссылки для сообщения (необязательно).
     * @param bool $disable_notification Отключить уведомления о сообщении (по умолчанию false).
     * @param bool $protect_content Защищает содержимое отправленного сообщения от пересылки и сохранения (необязательно).
     * @param string|null $message_effect_id Уникальный идентификатор эффекта сообщения, который будет добавлен к сообщению (только для личных чатов).
     * @param array|null $reply_parameters Описание сообщения, на которое нужно ответить (необязательно).
     * @param mixed|null $reply_markup Дополнительные параметры интерфейса (необязательно).
     *
     */
    public function sendMessage($chat_id, $text, $reply_markup = null, $business_connection_id = null, $message_thread_id = null, $parse_mode = null, $entities = null, $link_preview_options = null, $disable_notification = false, $protect_content = false, $message_effect_id = null, $reply_parameters = null)
    {
        return $this->method('sendMessage', [
            "chat_id" => $chat_id,
            "text" => $text,
            "business_connection_id" => $business_connection_id,
            "message_thread_id" => $message_thread_id,
            "parse_mode" => $parse_mode,
            "entities" => $entities,
            "link_preview_options" => $link_preview_options,
            "disable_notification" => $disable_notification,
            "protect_content" => $protect_content,
            "message_effect_id" => $message_effect_id,
            "reply_parameters" => $reply_parameters,
            "reply_markup" => $reply_markup
        ]);
    }

    /**
     * Пересылает сообщение любого типа.
     *
     * @param int|string $chat_id Идентификатор чата или имя пользователя канала.
     * @param int|string $from_chat_id Идентификатор чата, откуда было отправлено оригинальное сообщение.
     * @param int $message_id Идентификатор сообщения в чате, указанном в from_chat_id.
     * @param int|null $message_thread_id Уникальный идентификатор темы сообщения (только для форумов).
     * @param bool $disable_notification Отключить уведомления о сообщении (по умолчанию false).
     * @param bool $protect_content Защищает содержимое пересланного сообщения от пересылки и сохранения (необязательно).
     *
     */
    public function forwardMessage($chat_id, $from_chat_id, $message_id, $message_thread_id = null, $disable_notification = false, $protect_content = false)
    {
        return $this->method('forwardMessage', [
            "chat_id" => $chat_id,
            "from_chat_id" => $from_chat_id,
            "message_id" => $message_id,
            "message_thread_id" => $message_thread_id,
            "disable_notification" => $disable_notification,
            "protect_content" => $protect_content
        ]);
    }

    /**
     * Пересылает несколько сообщений любого типа.
     *
     * @param int|string $chat_id Идентификатор чата или имя пользователя канала.
     * @param int|string $from_chat_id Идентификатор чата, откуда были отправлены оригинальные сообщения.
     * @param array $message_ids Массив идентификаторов сообщений в чате, указанном в from_chat_id.
     * @param int|null $message_thread_id Уникальный идентификатор темы сообщения (только для форумов).
     * @param bool $disable_notification Отключить уведомления о сообщении (по умолчанию false).
     * @param bool $protect_content Защищает содержимое пересланных сообщений от пересылки и сохранения (необязательно).
     *
     */
    public function forwardMessages($chat_id, $from_chat_id, array $message_ids, $message_thread_id = null, $disable_notification = false, $protect_content = false)
    {
        return $this->method('forwardMessages', [
            "chat_id" => $chat_id,
            "from_chat_id" => $from_chat_id,
            "message_ids" => $message_ids,
            "message_thread_id" => $message_thread_id,
            "disable_notification" => $disable_notification,
            "protect_content" => $protect_content
        ]);
    }

    /**
     * Копирует сообщение любого типа.
     *
     * @param int|string $chat_id Идентификатор чата или имя пользователя канала.
     * @param int|string $from_chat_id Идентификатор чата, откуда было отправлено оригинальное сообщение.
     * @param int $message_id Идентификатор сообщения в чате, указанном в from_chat_id.
     * @param int|null $message_thread_id Уникальный идентификатор темы сообщения (только для форумов).
     * @param string|null $caption Новый заголовок для медиа.
     * @param string|null $parse_mode Режим HTML (необязательно).
     * @param array|null $caption_entities Массив специальных сущностей, которые появляются в новом заголовке (необязательно).
     * @param bool|null $show_caption_above_media Показать заголовок над медиа (необязательно).
     * @param bool $disable_notification Отключить уведомления о сообщении (по умолчанию false).
     * @param bool $protect_content Защищает содержимое отправленного сообщения от пересылки и сохранения (необязательно).
     * @param array|null $reply_parameters Описание сообщения, на которое нужно ответить (необязательно).
     * @param mixed|null $reply_markup Дополнительные параметры интерфейса (необязательно).
     *
     */
    public function copyMessage($chat_id, $from_chat_id, $message_id, $message_thread_id = null, $caption = null, $parse_mode = null, $caption_entities = null, $show_caption_above_media = null, $disable_notification = false, $protect_content = false, $reply_parameters = null, $reply_markup = null)
    {
        return $this->method('copyMessage', [
            "chat_id" => $chat_id,
            "from_chat_id" => $from_chat_id,
            "message_id" => $message_id,
            "message_thread_id" => $message_thread_id,
            "caption" => $caption,
            "parse_mode" => $parse_mode,
            "caption_entities" => $caption_entities,
            "show_caption_above_media" => $show_caption_above_media,
            "disable_notification" => $disable_notification,
            "protect_content" => $protect_content,
            "reply_parameters" => $reply_parameters,
            "reply_markup" => $reply_markup
        ]);
    }

    /**
     * Копирует несколько сообщений любого типа.
     *
     * @param int|string $chat_id Идентификатор чата или имя пользователя канала.
     * @param int|string $from_chat_id Идентификатор чата, откуда были отправлены оригинальные сообщения.
     * @param array $message_ids Массив идентификаторов сообщений в чате, указанном в from_chat_id.
     * @param int|null $message_thread_id Уникальный идентификатор темы сообщения (только для форумов).
     * @param bool $disable_notification Отключить уведомления о сообщении (по умолчанию false).
     * @param bool $protect_content Защищает содержимое пересланных сообщений от пересылки и сохранения (необязательно).
     * @param bool|null $remove_caption Удалить заголовок из сообщений (необязательно).
     *
     */
    public function copyMessages($chat_id, $from_chat_id, array $message_ids, $message_thread_id = null, $disable_notification = false, $protect_content = false, $remove_caption = null)
    {
        return $this->method('copyMessages', [
            "chat_id" => $chat_id,
            "from_chat_id" => $from_chat_id,
            "message_ids" => $message_ids,
            "message_thread_id" => $message_thread_id,
            "disable_notification" => $disable_notification,
            "protect_content" => $protect_content,
            "remove_caption" => $remove_caption
        ]);
    }

    /**
     * Отправляет фото в чат.
     *
     * @param int|string $chat_id Идентификатор чата или имя пользователя канала.
     * @param string $photo Фото для отправки.
     * @param string|null $caption Заголовок для фото (необязательно).
     * @param string|null $parse_mode Режим HTML (необязательно).
     * @param array|null $caption_entities Массив специальных сущностей, которые появляются в заголовке (необязательно).
     * @param bool|null $show_caption_above_media Показать заголовок над медиа (необязательно).
     * @param bool|null $has_spoiler Покрыть фото анимацией спойлера (необязательно).
     * @param bool $disable_notification Отключить уведомления о сообщении (по умолчанию false).
     * @param bool $protect_content Защищает содержимое отправленного сообщения от пересылки и сохранения (необязательно).
     * @param string|null $message_effect_id Уникальный идентификатор эффекта сообщения, который будет добавлен к сообщению (только для личных чатов).
     * @param array|null $reply_parameters Описание сообщения, на которое нужно ответить (необязательно).
     * @param mixed|null $reply_markup Дополнительные параметры интерфейса (необязательно).
     *
     */
    public function sendPhoto($chat_id, $photo, $caption = null, $reply_markup = null, $parse_mode = null, $caption_entities = null, $show_caption_above_media = null, $has_spoiler = null, $disable_notification = false, $protect_content = false, $message_effect_id = null, $reply_parameters = null)
    {
        return $this->method('sendPhoto', [
            "chat_id" => $chat_id,
            "photo" => $photo,
            "caption" => $caption,
            "parse_mode" => $parse_mode,
            "caption_entities" => $caption_entities,
            "show_caption_above_media" => $show_caption_above_media,
            "has_spoiler" => $has_spoiler,
            "disable_notification" => $disable_notification,
            "protect_content" => $protect_content,
            "message_effect_id" => $message_effect_id,
            "reply_parameters" => $reply_parameters,
            "reply_markup" => $reply_markup
        ]);
    }

    /**
     * Отправляет аудиофайл в указанный чат.
     *
     * @param int|string $chat_id Идентификатор чата или имя пользователя (в формате @username).
     * @param string $audio Файл аудио для отправки.
     * @param int|null $business_connection_id Идентификатор бизнес-соединения (необязательно).
     * @param int|null $message_thread_id Идентификатор потока сообщений (необязательно).
     * @param string|null $caption Подпись к аудиофайлу (необязательно).
     * @param string|null $parse_mode Режим парсинга для подписи (необязательно).
     * @param array|null $caption_entities Сущности в подписи (необязательно).
     * @param int|null $duration Продолжительность аудиофайла в секундах (необязательно).
     * @param string|null $performer Исполнитель аудиофайла (необязательно).
     * @param string|null $title Название аудиофайла (необязательно).
     * @param string|null $thumbnail Миниатюра для аудиофайла (необязательно).
     * @param bool $disable_notification Отключить уведомления для получателя (по умолчанию false).
     * @param bool $protect_content Защитить содержимое сообщения (по умолчанию false).
     * @param int|null $message_effect_id Идентификатор эффекта сообщения (необязательно).
     * @param array|null $reply_parameters Параметры для ответа (необязательно).
     * @param array|null $reply_markup Дополнительные параметры разметки (необязательно).
     *
     * @return mixed Ответ от API.
     */
    public function sendAudio($chat_id, $audio, $caption = null, $reply_markup = null, $business_connection_id = null, $message_thread_id = null, $parse_mode = null, $caption_entities = null, $duration = null, $performer = null, $title = null, $thumbnail = null, $disable_notification = false, $protect_content = false, $message_effect_id = null, $reply_parameters = null)
    {
        return $this->method('sendAudio', [
            "chat_id" => $chat_id,
            "audio" => $audio,
            "business_connection_id" => $business_connection_id,
            "message_thread_id" => $message_thread_id,
            "caption" => $caption,
            "parse_mode" => $parse_mode,
            "caption_entities" => $caption_entities,
            "duration" => $duration,
            "performer" => $performer,
            "title" => $title,
            "thumbnail" => $thumbnail,
            "disable_notification" => $disable_notification,
            "protect_content" => $protect_content,
            "message_effect_id" => $message_effect_id,
            "reply_parameters" => $reply_parameters,
            "reply_markup" => $reply_markup
        ]);
    }

    /**
     * Отправляет документ в указанный чат.
     *
     * @param int|string $chat_id Идентификатор чата или имя пользователя.
     * @param string $document Файл документа для отправки.
     * @param int|null $business_connection_id Идентификатор бизнес-соединения (необязательно).
     * @param int|null $message_thread_id Идентификатор потока сообщений (необязательно).
     * @param string|null $thumbnail Миниатюра для документа (необязательно).
     * @param string|null $caption Подпись к документу (необязательно).
     * @param string|null $parse_mode Режим парсинга для подписи (необязательно).
     * @param array|null $caption_entities Сущности в подписи (необязательно).
     * @param bool|null $disable_content_type_detection Отключить определение типа содержимого (необязательно).
     * @param bool $disable_notification Отключить уведомления (по умолчанию false).
     * @param bool $protect_content Защитить содержимое (по умолчанию false).
     * @param int|null $message_effect_id Идентификатор эффекта сообщения (необязательно).
     * @param array|null $reply_parameters Параметры ответа (необязательно).
     * @param array|null $reply_markup Разметка ответа (необязательно).
     *
     * @return mixed Ответ от API.
     */
    public function sendDocument($chat_id, $document, $caption = null, $reply_markup = null, $business_connection_id = null, $message_thread_id = null, $thumbnail = null, $parse_mode = null, $caption_entities = null, $disable_content_type_detection = null, $disable_notification = false, $protect_content = false, $message_effect_id = null, $reply_parameters = null)
    {
        return $this->method('sendDocument', [
            "chat_id" => $chat_id,
            "document" => $document,
            "business_connection_id" => $business_connection_id,
            "message_thread_id" => $message_thread_id,
            "thumbnail" => $thumbnail,
            "caption" => $caption,
            "parse_mode" => $parse_mode,
            "caption_entities" => $caption_entities,
            "disable_content_type_detection" => $disable_content_type_detection,
            "disable_notification" => $disable_notification,
            "protect_content" => $protect_content,
            "message_effect_id" => $message_effect_id,
            "reply_parameters" => $reply_parameters,
            "reply_markup" => $reply_markup
        ]);
    }

    /**
     * Отправляет видео в указанный чат.
     *
     * @param int|string $chat_id Идентификатор чата или имя пользователя.
     * @param string $video Файл видео для отправки.
     * @param int|null $business_connection_id Идентификатор бизнес-соединения (необязательно).
     * @param int|null $message_thread_id Идентификатор потока сообщений (необязательно).
     * @param int|null $duration Продолжительность видео в секундах (необязательно).
     * @param int|null $width Ширина видео (необязательно).
     * @param int|null $height Высота видео (необязательно).
     * @param string|null $thumbnail Миниатюра видео (необязательно).
     * @param string|null $caption Подпись к видео (необязательно).
     * @param string|null $parse_mode Режим парсинга для подписи (необязательно).
     * @param array|null $caption_entities Сущности в подписи (необязательно).
     * @param bool|null $show_caption_above_media Показать подпись над медиа (необязательно).
     * @param bool|null $has_spoiler Указывает, содержит ли видео спойлер (необязательно).
     * @param bool|null $supports_streaming Указывает, поддерживает ли видео потоковую передачу (необязательно).
     * @param bool $disable_notification Отключить уведомления для этого сообщения (по умолчанию false).
     * @param bool $protect_content Защитить содержимое сообщения (по умолчанию false).
     * @param int|null $message_effect_id Идентификатор эффекта сообщения (необязательно).
     * @param array|null $reply_parameters Параметры ответа (необязательно).
     * @param array|null $reply_markup Разметка клавиатуры для ответа (необязательно).
     *
     * @return mixed Ответ от API.
     */
    public function sendVideo($chat_id, $video, $caption = null, $reply_markup = null, $business_connection_id = null, $message_thread_id = null, $duration = null, $width = null, $height = null, $thumbnail = null, $parse_mode = null, $caption_entities = null, $show_caption_above_media = null, $has_spoiler = null, $supports_streaming = null, $disable_notification = false, $protect_content = false, $message_effect_id = null, $reply_parameters = null)
    {
        return $this->method('sendVideo', [
            "chat_id" => $chat_id,
            "video" => $video,
            "business_connection_id" => $business_connection_id,
            "message_thread_id" => $message_thread_id,
            "duration" => $duration,
            "width" => $width,
            "height" => $height,
            "thumbnail" => $thumbnail,
            "caption" => $caption,
            "parse_mode" => $parse_mode,
            "caption_entities" => $caption_entities,
            "show_caption_above_media" => $show_caption_above_media,
            "has_spoiler" => $has_spoiler,
            "supports_streaming" => $supports_streaming,
            "disable_notification" => $disable_notification,
            "protect_content" => $protect_content,
            "message_effect_id" => $message_effect_id,
            "reply_parameters" => $reply_parameters,
            "reply_markup" => $reply_markup
        ]);
    }

    /**
     * Отправляет анимацию в указанный чат.
     *
     * @param int|string $chat_id Идентификатор чата или имя пользователя.
     * @param string $animation Файл анимации для отправки.
     * @param int|null $business_connection_id Идентификатор бизнес-соединения (необязательно).
     * @param int|null $message_thread_id Идентификатор потока сообщений (необязательно).
     * @param int|null $duration Продолжительность анимации в секундах (необязательно).
     * @param int|null $width Ширина анимации (необязательно).
     * @param int|null $height Высота анимации (необязательно).
     * @param string|null $thumbnail Миниатюра анимации (необязательно).
     * @param string|null $caption Подпись к анимации (необязательно).
     * @param string|null $parse_mode Режим парсинга для подписи (необязательно).
     * @param array|null $caption_entities Сущности в подписи (необязательно).
     * @param bool|null $show_caption_above_media Показывать ли подпись над медиа (необязательно).
     * @param bool|null $has_spoiler Содержит ли анимация спойлер (необязательно).
     * @param bool $disable_notification Отключить уведомления (по умолчанию false).
     * @param bool $protect_content Защитить содержимое сообщения (по умолчанию false).
     * @param int|null $message_effect_id Идентификатор эффекта сообщения (необязательно).
     * @param array|null $reply_parameters Параметры ответа (необязательно).
     * @param array|null $reply_markup Дополнительные параметры разметки ответа (необязательно).
     *
     * @return mixed Ответ от API.
     */
    public function sendAnimation($chat_id, $animation, $caption = null, $reply_markup = null, $business_connection_id = null, $message_thread_id = null, $duration = null, $width = null, $height = null, $thumbnail = null, $parse_mode = null, $caption_entities = null, $show_caption_above_media = null, $has_spoiler = null, $disable_notification = false, $protect_content = false, $message_effect_id = null, $reply_parameters = null)
    {
        return $this->method('sendAnimation', [
            "chat_id" => $chat_id,
            "animation" => $animation,
            "business_connection_id" => $business_connection_id,
            "message_thread_id" => $message_thread_id,
            "duration" => $duration,
            "width" => $width,
            "height" => $height,
            "thumbnail" => $thumbnail,
            "caption" => $caption,
            "parse_mode" => $parse_mode,
            "caption_entities" => $caption_entities,
            "show_caption_above_media" => $show_caption_above_media,
            "has_spoiler" => $has_spoiler,
            "disable_notification" => $disable_notification,
            "protect_content" => $protect_content,
            "message_effect_id" => $message_effect_id,
            "reply_parameters" => $reply_parameters,
            "reply_markup" => $reply_markup
        ]);
    }

    /**
     * Отправляет голосовое сообщение в указанный чат.
     *
     * @param int|string $chat_id Идентификатор чата или имя пользователя.
     * @param string $voice Файл голосового сообщения для отправки.
     * @param int|null $business_connection_id Идентификатор бизнес-соединения (опционально).
     * @param int|null $message_thread_id Идентификатор потока сообщений (опционально).
     * @param string|null $caption Подпись к голосовому сообщению (опционально).
     * @param string|null $parse_mode Режим парсинга для подписи (опционально).
     * @param array|null $caption_entities Сущности в подписи (опционально).
     * @param int|null $duration Продолжительность голосового сообщения в секундах (опционально).
     * @param bool $disable_notification Отключить уведомления для получателя (по умолчанию false).
     * @param bool $protect_content Защитить содержимое сообщения (по умолчанию false).
     * @param int|null $message_effect_id Идентификатор эффекта сообщения (опционально).
     * @param array|null $reply_parameters Параметры для ответа (опционально).
     * @param array|null $reply_markup Дополнительный интерфейс для сообщения (опционально).
     *
     * @return mixed Ответ от API после отправки голосового сообщения.
     */
    public function sendVoice($chat_id, $voice, $caption = null, $reply_markup = null, $business_connection_id = null, $message_thread_id = null, $parse_mode = null, $caption_entities = null, $duration = null, $disable_notification = false, $protect_content = false, $message_effect_id = null, $reply_parameters = null)
    {
        return $this->method('sendVoice', [
            "chat_id" => $chat_id,
            "voice" => $voice,
            "business_connection_id" => $business_connection_id,
            "message_thread_id" => $message_thread_id,
            "caption" => $caption,
            "parse_mode" => $parse_mode,
            "caption_entities" => $caption_entities,
            "duration" => $duration,
            "disable_notification" => $disable_notification,
            "protect_content" => $protect_content,
            "message_effect_id" => $message_effect_id,
            "reply_parameters" => $reply_parameters,
            "reply_markup" => $reply_markup
        ]);
    }


    /**
     * Отправляет видеозаметку в указанный чат.
     *
     * @param int|string $chat_id Идентификатор чата или имя пользователя.
     * @param string $video_note Видеозаметка для отправки.
     * @param int|null $business_connection_id Идентификатор бизнес-соединения (необязательно).
     * @param int|null $message_thread_id Идентификатор потока сообщений (необязательно).
     * @param int|null $duration Продолжительность видеозаметки в секундах (необязательно).
     * @param int|null $length Длина стороны видеозаметки (необязательно).
     * @param string|null $thumbnail Миниатюра видеозаметки (необязательно).
     * @param bool $disable_notification Отключить уведомления для получателя (по умолчанию false).
     * @param bool $protect_content Защитить содержимое сообщения от пересылки и копирования (по умолчанию false).
     * @param int|null $message_effect_id Идентификатор эффекта сообщения (необязательно).
     * @param array|null $reply_parameters Параметры для ответа на сообщение (необязательно).
     * @param array|null $reply_markup Дополнительный интерфейс для сообщения (необязательно).
     *
     * @return mixed Ответ от API после отправки видеозаметки.
     */
    public function sendVideoNote($chat_id, $video_note, $reply_markup = null, $business_connection_id = null, $message_thread_id = null, $duration = null, $length = null, $thumbnail = null, $disable_notification = false, $protect_content = false, $message_effect_id = null, $reply_parameters = null)
    {
        return $this->method('sendVideoNote', [
            "chat_id" => $chat_id,
            "video_note" => $video_note,
            "business_connection_id" => $business_connection_id,
            "message_thread_id" => $message_thread_id,
            "duration" => $duration,
            "length" => $length,
            "thumbnail" => $thumbnail,
            "disable_notification" => $disable_notification,
            "protect_content" => $protect_content,
            "message_effect_id" => $message_effect_id,
            "reply_parameters" => $reply_parameters,
            "reply_markup" => $reply_markup
        ]);
    }

    /**
     * Отправляет платный медиа-контент в указанный чат.
     *
     * @param int|string $chat_id Идентификатор чата или имя пользователя.
     * @param int $star_count Количество звезд.
     * @param array $media Массив медиа-объектов.
     * @param int|null $business_connection_id Идентификатор бизнес-соединения (необязательно).
     * @param mixed|null $payload Полезная нагрузка (необязательно).
     * @param string|null $caption Подпись к медиа (необязательно).
     * @param string|null $parse_mode Режим парсинга для подписи (необязательно).
     * @param array|null $caption_entities Массив сущностей для подписи (необязательно).
     * @param bool|null $show_caption_above_media Показать подпись над медиа (необязательно).
     * @param bool $disable_notification Отключить уведомления.
     * @param bool $protect_content Защитить контент.
     * @param array|null $reply_parameters Параметры ответа (необязательно).
     * @param array|null $reply_markup Разметка ответа (необязательно).
     * @return mixed Ответ от сервера.
     */
    public function sendPaidMedia($chat_id, $star_count, array $media, $caption = null, $reply_markup = null,$business_connection_id = null, $payload = null, $parse_mode = null, $caption_entities = null, $show_caption_above_media = null, $disable_notification = false, $protect_content = false, $reply_parameters = null)
    {
        return $this->method('sendPaidMedia', [
            "chat_id" => $chat_id,
            "star_count" => $star_count,
            "media" => $media,
            "business_connection_id" => $business_connection_id,
            "payload" => $payload,
            "caption" => $caption,
            "parse_mode" => $parse_mode,
            "caption_entities" => $caption_entities,
            "show_caption_above_media" => $show_caption_above_media,
            "disable_notification" => $disable_notification,
            "protect_content" => $protect_content,
            "reply_parameters" => $reply_parameters,
            "reply_markup" => $reply_markup
        ]);
    }

    /**
     * Отправляет группу медиа-сообщений в указанный чат.
     *
     * @param int|string $chat_id Идентификатор чата или имя пользователя.
     * @param array $media Массив медиа-объектов для отправки.
     * @param int|null $business_connection_id (Необязательно) Идентификатор бизнес-соединения.
     * @param int|null $message_thread_id (Необязательно) Идентификатор потока сообщений.
     * @param bool $disable_notification (Необязательно) Отключить уведомления для получателей.
     * @param bool $protect_content (Необязательно) Защитить содержимое сообщения от пересылки и копирования.
     * @param int|null $message_effect_id (Необязательно) Идентификатор эффекта сообщения.
     * @param mixed|null $reply_parameters (Необязательно) Параметры для ответа на сообщение.
     * @param mixed|null $reply_markup (Необязательно) Дополнительный интерфейс для сообщения.
     *
     * @return mixed Ответ от API после отправки группы медиа-сообщений.
     */
    public function sendMediaGroup($chat_id, array $media, $reply_markup = null, $business_connection_id = null, $message_thread_id = null, $disable_notification = false, $protect_content = false, $message_effect_id = null, $reply_parameters = null)
    {
        return $this->method('sendMediaGroup', [
            "chat_id" => $chat_id,
            "media" => $media,
            "business_connection_id" => $business_connection_id,
            "message_thread_id" => $message_thread_id,
            "disable_notification" => $disable_notification,
            "protect_content" => $protect_content,
            "message_effect_id" => $message_effect_id,
            "reply_parameters" => $reply_parameters,
            "reply_markup" => $reply_markup
        ]);
    }

    /**
     * Отправляет местоположение в чат.
     *
     * @param int $chat_id Идентификатор чата.
     * @param float $latitude Широта местоположения.
     * @param float $longitude Долгота местоположения.
     * @param int|null $business_connection_id Идентификатор бизнес-соединения (необязательно).
     * @param int|null $message_thread_id Идентификатор потока сообщений (необязательно).
     * @param float|null $horizontal_accuracy Горизонтальная точность (необязательно).
     * @param int|null $live_period Период времени в секундах, в течение которого местоположение будет обновляться (необязательно).
     * @param int|null $heading Направление движения в градусах (необязательно).
     * @param int|null $proximity_alert_radius Радиус оповещения о приближении в метрах (необязательно).
     * @param bool $disable_notification Отключить уведомления (по умолчанию false).
     * @param bool $protect_content Защитить содержимое сообщения (по умолчанию false).
     * @param int|null $message_effect_id Идентификатор эффекта сообщения (необязательно).
     * @param array|null $reply_parameters Параметры ответа (необязательно).
     * @param array|null $reply_markup Разметка ответа (необязательно).
     *
     * @return mixed Ответ от API.
     */
    public function sendLocation($chat_id, $latitude, $longitude, $reply_markup = null, $business_connection_id = null, $message_thread_id = null, $horizontal_accuracy = null, $live_period = null, $heading = null, $proximity_alert_radius = null, $disable_notification = false, $protect_content = false, $message_effect_id = null, $reply_parameters = null)
    {
        return $this->method('sendLocation', [
            "chat_id" => $chat_id,
            "latitude" => $latitude,
            "longitude" => $longitude,
            "business_connection_id" => $business_connection_id,
            "message_thread_id" => $message_thread_id,
            "horizontal_accuracy" => $horizontal_accuracy,
            "live_period" => $live_period,
            "heading" => $heading,
            "proximity_alert_radius" => $proximity_alert_radius,
            "disable_notification" => $disable_notification,
            "protect_content" => $protect_content,
            "message_effect_id" => $message_effect_id,
            "reply_parameters" => $reply_parameters,
            "reply_markup" => $reply_markup
        ]);
    }

    /**
     * Отправляет информацию о месте в чат.
     *
     * @param int $chat_id Идентификатор чата.
     * @param float $latitude Широта места.
     * @param float $longitude Долгота места.
     * @param string $title Название места.
     * @param string $address Адрес места.
     * @param int|null $business_connection_id Идентификатор бизнес-связи (необязательно).
     * @param int|null $message_thread_id Идентификатор потока сообщений (необязательно).
     * @param string|null $foursquare_id Идентификатор места в Foursquare (необязательно).
     * @param string|null $foursquare_type Тип места в Foursquare (необязательно).
     * @param string|null $google_place_id Идентификатор места в Google Places (необязательно).
     * @param string|null $google_place_type Тип места в Google Places (необязательно).
     * @param bool $disable_notification Отключить уведомления (по умолчанию false).
     * @param bool $protect_content Защитить контент (по умолчанию false).
     * @param int|null $message_effect_id Идентификатор эффекта сообщения (необязательно).
     * @param array|null $reply_parameters Параметры ответа (необязательно).
     * @param array|null $reply_markup Разметка ответа (необязательно).
     *
     * @return mixed Ответ от API.
     */
    public function sendVenue($chat_id, $latitude, $longitude, $title, $address, $reply_markup = null, $business_connection_id = null, $message_thread_id = null, $foursquare_id = null, $foursquare_type = null, $google_place_id = null, $google_place_type = null, $disable_notification = false, $protect_content = false, $message_effect_id = null, $reply_parameters = null)
    {
        return $this->method('sendVenue', [
            "chat_id" => $chat_id,
            "latitude" => $latitude,
            "longitude" => $longitude,
            "title" => $title,
            "address" => $address,
            "business_connection_id" => $business_connection_id,
            "message_thread_id" => $message_thread_id,
            "foursquare_id" => $foursquare_id,
            "foursquare_type" => $foursquare_type,
            "google_place_id" => $google_place_id,
            "google_place_type" => $google_place_type,
            "disable_notification" => $disable_notification,
            "protect_content" => $protect_content,
            "message_effect_id" => $message_effect_id,
            "reply_parameters" => $reply_parameters,
            "reply_markup" => $reply_markup
        ]);
    }

    /**
     * Отправляет контактное сообщение в указанный чат.
     *
     * @param int|string $chat_id Идентификатор чата или имя пользователя.
     * @param string $phone_number Номер телефона контакта.
     * @param string $first_name Имя контакта.
     * @param string|null $last_name Фамилия контакта (необязательно).
     * @param string|null $vcard VCard контакта (необязательно).
     * @param bool $disable_notification Отключить уведомления для этого сообщения (по умолчанию false).
     * @param bool $protect_content Защитить содержимое сообщения от пересылки и копирования (по умолчанию false).
     * @param int|null $message_effect_id Идентификатор эффекта сообщения (необязательно).
     * @param array|null $reply_parameters Параметры ответа (необязательно).
     * @param array|null $reply_markup Дополнительный интерфейс для сообщения (необязательно).
     * @param int|null $business_connection_id Идентификатор бизнес-соединения (необязательно).
     * @param int|null $message_thread_id Идентификатор потока сообщений (необязательно).
     *
     * @return mixed Ответ от API.
     */
    public function sendContact($chat_id, $phone_number, $first_name, $reply_markup = null, $last_name = null, $vcard = null, $disable_notification = false, $protect_content = false, $message_effect_id = null, $reply_parameters = null, $business_connection_id = null, $message_thread_id = null)
    {
        return $this->method('sendContact', [
            "chat_id" => $chat_id,
            "phone_number" => $phone_number,
            "first_name" => $first_name,
            "last_name" => $last_name,
            "vcard" => $vcard,
            "disable_notification" => $disable_notification,
            "protect_content" => $protect_content,
            "message_effect_id" => $message_effect_id,
            "reply_parameters" => $reply_parameters,
            "reply_markup" => $reply_markup,
            "business_connection_id" => $business_connection_id,
            "message_thread_id" => $message_thread_id
        ]);
    }

    /**
     * Отправляет опрос в указанный чат.
     *
     * @param int $chat_id Идентификатор чата, в который отправляется опрос.
     * @param string $question Вопрос, который будет задан в опросе.
     * @param array $options Варианты ответов на опрос.
     * @param bool $is_anonymous (необязательно) Указывает, будет ли опрос анонимным. По умолчанию true.
     * @param string $type (необязательно) Тип опроса, может быть 'regular' или 'quiz'. По умолчанию 'regular'.
     * @param bool $allows_multiple_answers (необязательно) Указывает, разрешены ли множественные ответы. По умолчанию false.
     * @param int|null $correct_option_id (необязательно) Идентификатор правильного ответа (для типа 'quiz').
     * @param string|null $explanation (необязательно) Объяснение правильного ответа (для типа 'quiz').
     * @param string|null $explanation_parse_mode (необязательно) Форматирование объяснения (Markdown или HTML).
     * @param array|null $explanation_entities (необязательно) Сущности для форматирования объяснения.
     * @param int|null $open_period (необязательно) Время в секундах, в течение которого опрос будет активен.
     * @param int|null $close_date (необязательно) Дата и время закрытия опроса в формате Unix.
     * @param bool $is_closed (необязательно) Указывает, закрыт ли опрос. По умолчанию false.
     * @param bool $disable_notification (необязательно) Отключает уведомления для этого сообщения. По умолчанию false.
     * @param bool $protect_content (необязательно) Защищает содержимое сообщения от пересылки и копирования. По умолчанию false.
     * @param int|null $message_effect_id (необязательно) Идентификатор эффекта сообщения.
     * @param array|null $reply_parameters (необязательно) Параметры для ответа на сообщение.
     * @param array|null $reply_markup (необязательно) Дополнительный интерфейс для сообщения (кнопки и т.д.).
     * @param int|null $business_connection_id (необязательно) Идентификатор бизнес-соединения.
     * @param int|null $message_thread_id (необязательно) Идентификатор потока сообщений.
     * @param string|null $question_parse_mode (необязательно) Форматирование вопроса (Markdown или HTML).
     * @param array|null $question_entities (необязательно) Сущности для форматирования вопроса.
     *
     * @return mixed Ответ от API.
     */
    public function sendPoll($chat_id, $question, array $options, $is_anonymous = true, $type = 'regular', $allows_multiple_answers = false, $correct_option_id = null, $explanation = null, $explanation_parse_mode = null, $explanation_entities = null, $open_period = null, $close_date = null, $is_closed = false, $disable_notification = false, $protect_content = false, $message_effect_id = null, $reply_parameters = null, $reply_markup = null, $business_connection_id = null, $message_thread_id = null, $question_parse_mode = null, $question_entities = null)
    {
        return $this->method('sendPoll', [
            "chat_id" => $chat_id,
            "question" => $question,
            "options" => $options,
            "is_anonymous" => $is_anonymous,
            "type" => $type,
            "allows_multiple_answers" => $allows_multiple_answers,
            "correct_option_id" => $correct_option_id,
            "explanation" => $explanation,
            "explanation_parse_mode" => $explanation_parse_mode,
            "explanation_entities" => $explanation_entities,
            "open_period" => $open_period,
            "close_date" => $close_date,
            "is_closed" => $is_closed,
            "disable_notification" => $disable_notification,
            "protect_content" => $protect_content,
            "message_effect_id" => $message_effect_id,
            "reply_parameters" => $reply_parameters,
            "reply_markup" => $reply_markup,
            "business_connection_id" => $business_connection_id,
            "message_thread_id" => $message_thread_id,
            "question_parse_mode" => $question_parse_mode,
            "question_entities" => $question_entities
        ]);
    }

    /**
     * Отправляет сообщение с эмодзи кубика.
     *
     * @param int $chat_id Идентификатор чата, куда будет отправлено сообщение.
     * @param string $emoji Эмодзи, которое будет отправлено (по умолчанию '🎲').
     * @param bool $disable_notification Отключить уведомления для этого сообщения (по умолчанию false).
     * @param bool $protect_content Защитить содержимое сообщения от пересылки и копирования (по умолчанию false).
     * @param int|null $message_effect_id Идентификатор эффекта сообщения (по умолчанию null).
     * @param mixed|null $reply_parameters Параметры для ответа (по умолчанию null).
     * @param mixed|null $reply_markup Дополнительный интерфейс для сообщения (по умолчанию null).
     * @param int|null $business_connection_id Идентификатор бизнес-соединения (по умолчанию null).
     * @param int|null $message_thread_id Идентификатор потока сообщений (по умолчанию null).
     *
     * @return mixed Ответ от API после отправки сообщения.
     */
    public function sendDice($chat_id, $emoji = '🎲', $reply_markup = null, $disable_notification = false, $protect_content = false, $message_effect_id = null, $reply_parameters = null, $business_connection_id = null, $message_thread_id = null)
    {
        return $this->method('sendDice', [
            "chat_id" => $chat_id,
            "emoji" => $emoji,
            "disable_notification" => $disable_notification,
            "protect_content" => $protect_content,
            "message_effect_id" => $message_effect_id,
            "reply_parameters" => $reply_parameters,
            "reply_markup" => $reply_markup,
            "business_connection_id" => $business_connection_id,
            "message_thread_id" => $message_thread_id
        ]);
    }

    /**
     * Отправляет действие чата.
     *
     * @param int $chat_id Идентификатор чата.
     * @param string $action Действие, которое будет выполнено.
     * @param int|null $business_connection_id Идентификатор бизнес-соединения (необязательно).
     * @param int|null $message_thread_id Идентификатор потока сообщений (необязательно).
     * @return mixed Ответ от сервера.
     */
    public function sendChatAction($chat_id, $action, $business_connection_id = null, $message_thread_id = null)
    {
        return $this->method('sendChatAction', [
            "chat_id" => $chat_id,
            "action" => $action,
            "business_connection_id" => $business_connection_id,
            "message_thread_id" => $message_thread_id
        ]);
    }

    /**
     * Устанавливает реакцию на сообщение.
     *
     * @param int $chat_id Идентификатор чата.
     * @param int $message_id Идентификатор сообщения.
     * @param string|null $reaction Реакция на сообщение (необязательно).
     * @param bool $is_big Указывает, является ли реакция большой (по умолчанию false).
     * @return mixed Ответ от сервера.
     */
    public function setMessageReaction($chat_id, $message_id, $reaction = null, $is_big = false)
    {
        return $this->method('setMessageReaction', [
            "chat_id" => $chat_id,
            "message_id" => $message_id,
            "reaction" => $reaction,
            "is_big" => $is_big
        ]);
    }

    /**
     * Получает фотографии профиля пользователя.
     *
     * @param int $user_id Идентификатор пользователя.
     * @param int|null $offset Смещение для выборки (необязательно).
     * @param int $limit Лимит на количество фотографий (по умолчанию 100).
     * @return mixed Ответ от сервера.
     */
    public function getUserProfilePhotos($user_id, $offset = null, $limit = 100)
    {
        return $this->method('getUserProfilePhotos', [
            "user_id" => $user_id,
            "offset" => $offset,
            "limit" => $limit
        ]);
    }

    /**
     * Получает файл по его идентификатору.
     *
     * @param string $file_id Идентификатор файла.
     * @return mixed Ответ от сервера.
     */
    public function getFile($file_id)
    {
        return $this->method('getFile', [
            "file_id" => $file_id
        ]);
    }

    /**
     * Банит участника чата.
     *
     * @param int|string $chat_id Идентификатор чата или имя пользователя.
     * @param int $user_id Идентификатор пользователя, которого нужно забанить.
     * @param int|null $until_date Дата, до которой пользователь будет забанен (в виде Unix времени). Если не указано, бан будет бессрочным.
     * @param bool|null $revoke_messages Если true, все сообщения от забаненного пользователя будут удалены. Если false, сообщения останутся.
     *
     * @return mixed Ответ от метода 'banChatMember'.
     */
    public function banChatMember($chat_id, $user_id, $until_date = null, $revoke_messages = null)
    {
        return $this->method('banChatMember', [
            "chat_id" => $chat_id,
            "user_id" => $user_id,
            "until_date" => $until_date,
            "revoke_messages" => $revoke_messages
        ]);
    }

    /**
     * Разбанивает участника чата.
     *
     * @param int|string $chat_id Идентификатор чата или имя пользователя.
     * @param int $user_id Идентификатор пользователя, которого нужно разбанить.
     * @param bool|null $only_if_banned (Необязательно) Разбанить только если пользователь был забанен.
     *
     * @return mixed Ответ от API.
     */
    public function unbanChatMember($chat_id, $user_id, $only_if_banned = null)
    {
        return $this->method('unbanChatMember', [
            "chat_id" => $chat_id,
            "user_id" => $user_id,
            "only_if_banned" => $only_if_banned
        ]);
    }

    /**
     * Ограничивает участника чата.
     *
     * @param int|string $chat_id Идентификатор чата или имя пользователя чата.
     * @param int $user_id Идентификатор пользователя, которого нужно ограничить.
     * @param array $permissions Права доступа, которые будут установлены для пользователя.
     * @param bool|null $use_independent_chat_permissions (Необязательно) Использовать независимые права доступа чата.
     * @param int|null $until_date (Необязательно) Дата, до которой ограничения будут действовать.
     *
     * @return mixed Ответ от метода 'restrictChatMember'.
     */
    public function restrictChatMember($chat_id, $user_id, $permissions, $use_independent_chat_permissions = null, $until_date = null)
    {
        return $this->method('restrictChatMember', [
            "chat_id" => $chat_id,
            "user_id" => $user_id,
            "permissions" => $permissions,
            "use_independent_chat_permissions" => $use_independent_chat_permissions,
            "until_date" => $until_date
        ]);
    }

    /**
     * Повышает участника чата до администратора с заданными правами.
     *
     * @param int|string $chat_id Идентификатор чата или имя пользователя чата.
     * @param int $user_id Идентификатор пользователя, которого нужно повысить.
     * @param bool|null $is_anonymous (необязательно) Если true, администратор будет анонимным.
     * @param bool|null $can_manage_chat (необязательно) Если true, администратор может управлять чатом.
     * @param bool|null $can_delete_messages (необязательно) Если true, администратор может удалять сообщения.
     * @param bool|null $can_manage_video_chats (необязательно) Если true, администратор может управлять видеочатами.
     * @param bool|null $can_restrict_members (необязательно) Если true, администратор может ограничивать участников.
     * @param bool|null $can_promote_members (необязательно) Если true, администратор может повышать участников.
     * @param bool|null $can_change_info (необязательно) Если true, администратор может изменять информацию о чате.
     * @param bool|null $can_invite_users (необязательно) Если true, администратор может приглашать новых участников.
     * @param bool|null $can_post_stories (необязательно) Если true, администратор может публиковать истории.
     * @param bool|null $can_edit_stories (необязательно) Если true, администратор может редактировать истории.
     * @param bool|null $can_delete_stories (необязательно) Если true, администратор может удалять истории.
     * @param bool|null $can_post_messages (необязательно) Если true, администратор может публиковать сообщения.
     * @param bool|null $can_edit_messages (необязательно) Если true, администратор может редактировать сообщения.
     * @param bool|null $can_pin_messages (необязательно) Если true, администратор может закреплять сообщения.
     * @param bool|null $can_manage_topics (необязательно) Если true, администратор может управлять темами.
     *
     * @return mixed Ответ от API после выполнения запроса на повышение участника.
     */
    public function promoteChatMember($chat_id, $user_id, $is_anonymous = null, $can_manage_chat = null, $can_delete_messages = null, $can_manage_video_chats = null, $can_restrict_members = null, $can_promote_members = null, $can_change_info = null, $can_invite_users = null, $can_post_stories = null, $can_edit_stories = null, $can_delete_stories = null, $can_post_messages = null, $can_edit_messages = null, $can_pin_messages = null, $can_manage_topics = null)
    {
        return $this->method('promoteChatMember', [
            "chat_id" => $chat_id,
            "user_id" => $user_id,
            "is_anonymous" => $is_anonymous,
            "can_manage_chat" => $can_manage_chat,
            "can_delete_messages" => $can_delete_messages,
            "can_manage_video_chats" => $can_manage_video_chats,
            "can_restrict_members" => $can_restrict_members,
            "can_promote_members" => $can_promote_members,
            "can_change_info" => $can_change_info,
            "can_invite_users" => $can_invite_users,
            "can_post_stories" => $can_post_stories,
            "can_edit_stories" => $can_edit_stories,
            "can_delete_stories" => $can_delete_stories,
            "can_post_messages" => $can_post_messages,
            "can_edit_messages" => $can_edit_messages,
            "can_pin_messages" => $can_pin_messages,
            "can_manage_topics" => $can_manage_topics
        ]);
    }

    /**
     * Устанавливает пользовательский заголовок администратора чата.
     *
     * @param int|string $chat_id Идентификатор чата или имя пользователя чата.
     * @param int $user_id Идентификатор пользователя.
     * @param string $custom_title Новый пользовательский заголовок для администратора чата.
     * @return mixed Ответ от метода 'setChatAdministratorCustomTitle'.
     */
    public function setChatAdministratorCustomTitle($chat_id, $user_id, $custom_title)
    {
        return $this->method('setChatAdministratorCustomTitle', [
            "chat_id" => $chat_id,
            "user_id" => $user_id,
            "custom_title" => $custom_title
        ]);
    }

    /**
     * Запрещает отправку сообщений от указанного отправителя в чате.
     *
     * @param int|string $chat_id Идентификатор чата или имя пользователя чата.
     * @param int $sender_chat_id Идентификатор отправителя, которому запрещено отправлять сообщения.
     * @return mixed Ответ от метода 'banChatSenderChat'.
     */
    public function banChatSenderChat($chat_id, $sender_chat_id)
    {
        return $this->method('banChatSenderChat', [
            "chat_id" => $chat_id,
            "sender_chat_id" => $sender_chat_id
        ]);
    }

    /**
     * Разблокирует отправителя сообщений в чате.
     *
     * @param int|string $chat_id Идентификатор чата или имя пользователя чата.
     * @param int $sender_chat_id Идентификатор отправителя сообщений в чате.
     * @return mixed Ответ от метода 'unbanChatSenderChat'.
     */
    public function unbanChatSenderChat($chat_id, $sender_chat_id)
    {
        return $this->method('unbanChatSenderChat', [
            "chat_id" => $chat_id,
            "sender_chat_id" => $sender_chat_id
        ]);
    }

    /**
     * Устанавливает разрешения чата.
     *
     * @param int|string $chat_id Идентификатор чата или имя пользователя.
     * @param array $permissions Массив разрешений для чата.
     * @param bool|null $use_independent_chat_permissions (Необязательно) Использовать независимые разрешения чата.
     * @return mixed Ответ от метода 'setChatPermissions'.
     */
    public function setChatPermissions($chat_id, $permissions, $use_independent_chat_permissions = null)
    {
        return $this->method('setChatPermissions', [
            "chat_id" => $chat_id,
            "permissions" => $permissions,
            "use_independent_chat_permissions" => $use_independent_chat_permissions
        ]);
    }

    /**
     * Экспортирует ссылку приглашения в чат.
     *
     * @param int|string $chat_id Идентификатор чата или имя пользователя.
     * @return mixed Ответ от метода 'exportChatInviteLink'.
     */
    public function exportChatInviteLink($chat_id)
    {
        return $this->method('exportChatInviteLink', [
            "chat_id" => $chat_id
        ]);
    }

    /**
     * Создает ссылку-приглашение для чата.
     *
     * @param int|string $chat_id Идентификатор чата или имя пользователя чата.
     * @param string|null $name Название ссылки-приглашения (необязательно).
     * @param int|null $expire_date Дата истечения срока действия ссылки в формате Unix (необязательно).
     * @param int|null $member_limit Максимальное количество участников, которые могут присоединиться по этой ссылке (необязательно).
     * @param bool|null $creates_join_request Указывает, требуется ли запрос на присоединение (необязательно).
     *
     * @return mixed Ответ от API.
     */
    public function createChatInviteLink($chat_id, $name = null, $expire_date = null, $member_limit = null, $creates_join_request = null)
    {
        return $this->method('createChatInviteLink', [
            "chat_id" => $chat_id,
            "name" => $name,
            "expire_date" => $expire_date,
            "member_limit" => $member_limit,
            "creates_join_request" => $creates_join_request
        ]);
    }

    /**
     * Редактирует ссылку приглашения в чат.
     *
     * @param int|string $chat_id Идентификатор чата или имя пользователя (в формате @username).
     * @param string $invite_link Ссылка приглашения, которую нужно отредактировать.
     * @param string|null $name Новое имя ссылки приглашения (необязательно).
     * @param int|null $expire_date Новая дата истечения срока действия ссылки (в виде метки времени Unix, необязательно).
     * @param int|null $member_limit Новое ограничение на количество участников, которые могут присоединиться по этой ссылке (необязательно).
     * @param bool|null $creates_join_request Указывает, должна ли ссылка создавать запрос на присоединение (необязательно).
     *
     * @return mixed Ответ от API.
     */
    public function editChatInviteLink($chat_id, $invite_link, $name = null, $expire_date = null, $member_limit = null, $creates_join_request = null)
    {
        return $this->method('editChatInviteLink', [
            "chat_id" => $chat_id,
            "invite_link" => $invite_link,
            "name" => $name,
            "expire_date" => $expire_date,
            "member_limit" => $member_limit,
            "creates_join_request" => $creates_join_request
        ]);
    }

    /**
     * Создает ссылку-приглашение для подписки на чат.
     *
     * @param int $chat_id Идентификатор чата.
     * @param string|null $name Название подписки (необязательно).
     * @param int $subscription_period Период подписки в секундах (по умолчанию 2592000 секунд, что равно 30 дням).
     * @param int $subscription_price Цена подписки (по умолчанию 1).
     *
     * @return mixed Ответ от метода createChatSubscriptionInviteLink.
     */
    public function createChatSubscriptionInviteLink($chat_id, $name = null, $subscription_period = 2592000, $subscription_price = 1)
    {
        return $this->method('createChatSubscriptionInviteLink', [
            "chat_id" => $chat_id,
            "name" => $name,
            "subscription_period" => $subscription_period,
            "subscription_price" => $subscription_price
        ]);
    }

    /**
     * Редактирует ссылку-приглашение на подписку в чате.
     *
     * @param int|string $chat_id Идентификатор чата или имя пользователя.
     * @param string $invite_link Новая ссылка-приглашение.
     * @param string|null $name (Необязательно) Новое имя для ссылки-приглашения.
     * @return mixed Ответ от метода 'editChatSubscriptionInviteLink'.
     */
    public function editChatSubscriptionInviteLink($chat_id, $invite_link, $name = null)
    {
        return $this->method('editChatSubscriptionInviteLink', [
            "chat_id" => $chat_id,
            "invite_link" => $invite_link,
            "name" => $name
        ]);
    }

    /**
     * Отзывает ссылку приглашения в чат.
     *
     * @param int|string $chat_id Идентификатор чата или имя пользователя.
     * @param string $invite_link Ссылка приглашения, которую необходимо отозвать.
     * @return mixed Ответ от API.
     */
    public function revokeChatInviteLink($chat_id, $invite_link)
    {
        return $this->method('revokeChatInviteLink', [
            "chat_id" => $chat_id,
            "invite_link" => $invite_link
        ]);
    }

    /**
     * Одобряет запрос на присоединение к чату.
     *
     * @param int|string $chat_id Идентификатор чата или имя пользователя.
     * @param int $user_id Идентификатор пользователя.
     * @return mixed Ответ от метода approveChatJoinRequest.
     */
    public function approveChatJoinRequest($chat_id, $user_id)
    {
        return $this->method('approveChatJoinRequest', [
            "chat_id" => $chat_id,
            "user_id" => $user_id
        ]);
    }

    /**
     * Отклоняет запрос на присоединение к чату.
     *
     * @param int|string $chat_id Идентификатор чата или имя пользователя чата.
     * @param int $user_id Идентификатор пользователя, запрос которого нужно отклонить.
     * @return mixed Ответ от метода declineChatJoinRequest.
     */
    public function declineChatJoinRequest($chat_id, $user_id)
    {
        return $this->method('declineChatJoinRequest', [
            "chat_id" => $chat_id,
            "user_id" => $user_id
        ]);
    }

    /**
     * Устанавливает фото чата.
     *
     * @param int|string $chat_id Идентификатор чата или имя пользователя.
     * @param string $photo Путь к файлу фото.
     * @return mixed Ответ от API.
     */
    public function setChatPhoto($chat_id, $photo)
    {
        return $this->method('setChatPhoto', [
            "chat_id" => $chat_id,
            "photo" => $photo
        ]);
    }

    /**
     * Удаляет фотографию чата.
     *
     * @param int|string $chat_id Идентификатор чата или имя пользователя (в формате @username).
     * @return mixed Ответ от API после выполнения запроса на удаление фотографии чата.
     */
    public function deleteChatPhoto($chat_id)
    {
        return $this->method('deleteChatPhoto', [
            "chat_id" => $chat_id
        ]);
    }

    /**
     * Устанавливает заголовок чата.
     *
     * @param int|string $chat_id Идентификатор чата или имя пользователя.
     * @param string $title Новый заголовок чата.
     * @return mixed Ответ от метода 'setChatTitle'.
     */
    public function setChatTitle($chat_id, $title)
    {
        return $this->method('setChatTitle', [
            "chat_id" => $chat_id,
            "title" => $title
        ]);
    }

    /**
     * Устанавливает описание чата.
     *
     * @param int|string $chat_id Идентификатор чата или имя пользователя.
     * @param string|null $description Описание чата. По умолчанию null.
     * @return mixed Ответ от метода 'setChatDescription'.
     */
    public function setChatDescription($chat_id, $description = null)
    {
        return $this->method('setChatDescription', [
            "chat_id" => $chat_id,
            "description" => $description
        ]);
    }

    /**
     * Закрепляет сообщение в чате.
     *
     * @param int|string $chat_id Идентификатор чата или имя пользователя чата.
     * @param int $message_id Идентификатор сообщения, которое нужно закрепить.
     * @param bool $disable_notification Отключить уведомления для участников чата (по умолчанию false).
     * @return mixed Ответ от метода 'pinChatMessage'.
     */
    public function pinChatMessage($chat_id, $message_id, $disable_notification = false)
    {
        return $this->method('pinChatMessage', [
            "chat_id" => $chat_id,
            "message_id" => $message_id,
            "disable_notification" => $disable_notification
        ]);
    }

    /**
     * Открепляет сообщение в чате.
     *
     * @param int|string $chat_id Идентификатор чата или имя пользователя (в формате @username).
     * @param int|null $message_id Идентификатор сообщения, которое нужно открепить. Если не указано, открепляется последнее закрепленное сообщение.
     * @return mixed Ответ от метода 'unpinChatMessage'.
     */
    public function unpinChatMessage($chat_id, $message_id = null)
    {
        return $this->method('unpinChatMessage', [
            "chat_id" => $chat_id,
            "message_id" => $message_id
        ]);
    }

    /**
     * Открепляет все закрепленные сообщения в чате.
     *
     * @param int|string $chat_id Идентификатор чата или имя пользователя (в формате @username).
     * @return mixed Ответ от метода 'unpinAllChatMessages'.
     */
    public function unpinAllChatMessages($chat_id)
    {
        return $this->method('unpinAllChatMessages', [
            "chat_id" => $chat_id
        ]);
    }

    /**
     * Покидает чат с указанным идентификатором.
     *
     * @param int|string $chat_id Идентификатор чата, который нужно покинуть.
     * @return mixed Ответ от метода 'leaveChat'.
     */
    public function leaveChat($chat_id)
    {
        return $this->method('leaveChat', [
            "chat_id" => $chat_id
        ]);
    }

    /**
     * Получает информацию о чате по его идентификатору.
     *
     * @param int $chat_id Идентификатор чата.
     * @return mixed Ответ от API с информацией о чате.
     */
    public function getChat($chat_id)
    {
        return $this->method('getChat', [
            "chat_id" => $chat_id
        ]);
    }

    /**
     * Получает список администраторов чата.
     *
     * @param int|string $chat_id Идентификатор чата или имя пользователя (в формате @username).
     * @return array Ответ от API с информацией об администраторах чата.
     */
    public function getChatAdministrators($chat_id)
    {
        return $this->method('getChatAdministrators', [
            "chat_id" => $chat_id
        ]);
    }

    /**
     * Получает количество участников чата.
     *
     * @param string $chat_id Идентификатор чата.
     * @return mixed Ответ от API с количеством участников чата.
     */
    public function getChatMemberCount($chat_id)
    {
        return $this->method('getChatMemberCount', [
            "chat_id" => $chat_id
        ]);
    }

    /**
     * Получает информацию о пользователе в чате.
     *
     * @param int|string $chat_id Идентификатор чата или имя пользователя.
     * @param int $user_id Идентификатор пользователя.
     * @return mixed Ответ от метода 'getChatMember'.
     */
    public function getChatMember($chat_id, $user_id)
    {
        return $this->method('getChatMember', [
            "chat_id" => $chat_id,
            "user_id" => $user_id
        ]);
    }

    /**
     * Устанавливает набор стикеров для чата.
     *
     * @param int|string $chat_id Идентификатор чата или имя пользователя.
     * @param string $sticker_set_name Название набора стикеров.
     * @return mixed Ответ от API.
     */
    public function setChatStickerSet($chat_id, $sticker_set_name)
    {
        return $this->method('setChatStickerSet', [
            "chat_id" => $chat_id,
            "sticker_set_name" => $sticker_set_name
        ]);
    }

    /**
     * Удаляет набор стикеров чата.
     *
     * @param int|string $chat_id Идентификатор чата или имя пользователя (в формате @username).
     * @return mixed Ответ от API после выполнения запроса на удаление набора стикеров чата.
     */
    public function deleteChatStickerSet($chat_id)
    {
        return $this->method('deleteChatStickerSet', [
            "chat_id" => $chat_id
        ]);
    }

    /**
     * Получает иконки стикеров для темы форума.
     *
     * @return mixed Ответ с иконками стикеров для темы форума.
     */
    public function getForumTopicIconStickers()
    {
        return $this->method('getForumTopicIconStickers');
    }

    /**
     * Создает новую тему форума в указанном чате.
     *
     * @param int|string $chat_id Идентификатор чата или имя пользователя чата.
     * @param string $name Название темы форума.
     * @param int|null $icon_color (Необязательно) Цвет иконки темы форума.
     * @param string|null $icon_custom_emoji_id (Необязательно) Идентификатор пользовательского эмодзи для иконки темы форума.
     *
     * @return mixed Ответ от API после создания темы форума.
     */
    public function createForumTopic($chat_id, $name, $icon_color = null, $icon_custom_emoji_id = null)
    {
        return $this->method('createForumTopic', [
            "chat_id" => $chat_id,
            "name" => $name,
            "icon_color" => $icon_color,
            "icon_custom_emoji_id" => $icon_custom_emoji_id
        ]);
    }

    /**
     * Редактирует тему форума.
     *
     * @param int $chat_id Идентификатор чата.
     * @param int $message_thread_id Идентификатор потока сообщений.
     * @param string|null $name Новое имя темы (необязательно).
     * @param string|null $icon_custom_emoji_id Идентификатор пользовательского эмодзи для иконки (необязательно).
     * 
     * @return mixed Ответ от API.
     */
    public function editForumTopic($chat_id, $message_thread_id, $name = null, $icon_custom_emoji_id = null)
    {
        return $this->method('editForumTopic', [
            "chat_id" => $chat_id,
            "message_thread_id" => $message_thread_id,
            "name" => $name,
            "icon_custom_emoji_id" => $icon_custom_emoji_id
        ]);
    }

    /**
     * Закрывает тему форума в чате.
     *
     * @param int $chat_id Идентификатор чата.
     * @param int $message_thread_id Идентификатор темы сообщения.
     * @return mixed Ответ от метода 'closeForumTopic'.
     */
    public function closeForumTopic($chat_id, $message_thread_id)
    {
        return $this->method('closeForumTopic', [
            "chat_id" => $chat_id,
            "message_thread_id" => $message_thread_id
        ]);
    }

    /**
     * Повторно открывает тему форума.
     *
     * @param int $chat_id Идентификатор чата.
     * @param int $message_thread_id Идентификатор темы сообщения.
     * @return mixed Ответ от метода reopenForumTopic.
     */
    public function reopenForumTopic($chat_id, $message_thread_id)
    {
        return $this->method('reopenForumTopic', [
            "chat_id" => $chat_id,
            "message_thread_id" => $message_thread_id
        ]);
    }

    /**
     * Удаляет тему форума.
     *
     * @param int $chat_id Идентификатор чата.
     * @param int $message_thread_id Идентификатор темы сообщения.
     * @return mixed Ответ от метода 'deleteForumTopic'.
     */
    public function deleteForumTopic($chat_id, $message_thread_id)
    {
        return $this->method('deleteForumTopic', [
            "chat_id" => $chat_id,
            "message_thread_id" => $message_thread_id
        ]);
    }

    /**
     * Открепляет все сообщения темы форума в чате.
     *
     * @param int|string $chat_id Идентификатор чата или имя пользователя чата (в формате @username).
     * @param int $message_thread_id Идентификатор темы сообщения.
     * @return mixed Ответ от API после выполнения запроса на открепление всех сообщений темы форума.
     */
    public function unpinAllForumTopicMessages($chat_id, $message_thread_id)
    {
        return $this->method('unpinAllForumTopicMessages', [
            "chat_id" => $chat_id,
            "message_thread_id" => $message_thread_id
        ]);
    }

    /**
     * Редактирует общую тему форума.
     *
     * @param int|string $chat_id Идентификатор чата или имя пользователя.
     * @param string $name Новое имя темы форума.
     * @return mixed Ответ от метода 'editGeneralForumTopic'.
     */
    public function editGeneralForumTopic($chat_id, $name)
    {
        return $this->method('editGeneralForumTopic', [
            "chat_id" => $chat_id,
            "name" => $name
        ]);
    }

    /**
     * Закрывает общую тему форума в чате.
     *
     * @param int|string $chat_id Идентификатор чата или имя пользователя.
     * @return mixed Ответ от метода 'closeGeneralForumTopic'.
     */
    public function closeGeneralForumTopic($chat_id)
    {
        return $this->method('closeGeneralForumTopic', [
            "chat_id" => $chat_id
        ]);
    }

    /**
     * Переоткрывает общую тему форума.
     *
     * @param int|string $chat_id Идентификатор чата или имя пользователя.
     * @return mixed Ответ от метода reopenGeneralForumTopic.
     */
    public function reopenGeneralForumTopic($chat_id)
    {
        return $this->method('reopenGeneralForumTopic', [
            "chat_id" => $chat_id
        ]);
    }

    /**
     * Скрывает общую тему форума в чате.
     *
     * @param int|string $chat_id Идентификатор чата или имя пользователя.
     * @return mixed Ответ от метода 'hideGeneralForumTopic'.
     */
    public function hideGeneralForumTopic($chat_id)
    {
        return $this->method('hideGeneralForumTopic', [
            "chat_id" => $chat_id
        ]);
    }

    /**
     * Снимает скрытие с общей темы форума.
     *
     * @param int|string $chat_id Идентификатор чата или имя пользователя.
     * @return mixed Ответ от метода 'unhideGeneralForumTopic'.
     */
    public function unhideGeneralForumTopic($chat_id)
    {
        return $this->method('unhideGeneralForumTopic', [
            "chat_id" => $chat_id
        ]);
    }

    /**
     * Открепляет все сообщения в общем форуме.
     *
     * @param int|string $chat_id Идентификатор чата или имя пользователя.
     * @return mixed Ответ от API.
     */
    public function unpinAllGeneralForumTopicMessages($chat_id)
    {
        return $this->method('unpinAllGeneralForumTopicMessages', [
            "chat_id" => $chat_id
        ]);
    }

    /**
     * Отвечает на callback-запрос.
     *
     * @param string $callback_query_id Идентификатор callback-запроса.
     * @param string|null $text Текст уведомления. По умолчанию null.
     * @param bool $show_alert Показывать ли уведомление в виде алерта. По умолчанию false.
     * @param string|null $url URL, который будет открыт при нажатии на уведомление. По умолчанию null.
     * @param int $cache_time Время кэширования результата в секундах. По умолчанию 0.
     *
     * @return mixed Ответ от метода method.
     */
    public function answerCallbackQuery($callback_query_id, $text = null, $show_alert = false, $url = null, $cache_time = 0)
    {
        return $this->method('answerCallbackQuery', [
            "callback_query_id" => $callback_query_id,
            "text" => $text,
            "show_alert" => $show_alert,
            "url" => $url,
            "cache_time" => $cache_time
        ]);
    }


    /**
     * Получает бусты чата пользователя.
     *
     * @param int $chat_id Идентификатор чата.
     * @param int $user_id Идентификатор пользователя.
     * @return mixed Ответ с бустами чата пользователя.
     */
    public function getUserChatBoosts($chat_id, $user_id)
    {
        return $this->method('getUserChatBoosts', [
            "chat_id" => $chat_id,
            "user_id" => $user_id
        ]);
    }

    /**
     * Получает информацию о бизнес-соединении.
     *
     * @param int $business_connection_id Идентификатор бизнес-соединения.
     * @return mixed Ответ с информацией о бизнес-соединении.
     */
    public function getBusinessConnection($business_connection_id)
    {
        return $this->method('getBusinessConnection', [
            "business_connection_id" => $business_connection_id
        ]);
    }

    /**
     * Устанавливает команды для бота.
     *
     * @param array $commands Массив команд для установки.
     * @param mixed $scope (необязательно) Область действия команд.
     * @param string|null $language_code (необязательно) Код языка для команд.
     * @return mixed Ответ от метода 'setMyCommands'.
     */
    public function setMyCommands($commands, $scope = null, $language_code = null)
    {
        return $this->method('setMyCommands', [
            "commands" => $commands,
            "scope" => $scope,
            "language_code" => $language_code
        ]);
    }

    /**
     * Удаляет команды пользователя в указанной области и на указанном языке.
     *
     * @param string|null $scope Область, в которой будут удалены команды. Может быть null.
     * @param string|null $language_code Код языка, для которого будут удалены команды. Может быть null.
     * @return mixed Ответ от метода 'deleteMyCommands'.
     */
    public function deleteMyCommands($scope = null, $language_code = null)
    {
        return $this->method('deleteMyCommands', [
            "scope" => $scope,
            "language_code" => $language_code
        ]);
    }

    /**
     * Возвращает команды пользователя.
     *
     * @param string|null $scope Область применения команд (необязательно).
     * @param string|null $language_code Код языка для команд (необязательно).
     * @return mixed Ответ с командами пользователя.
     */
    public function getMyCommands($scope = null, $language_code = null)
    {
        return $this->method('getMyCommands', [
            "scope" => $scope,
            "language_code" => $language_code
        ]);
    }

    /**
     * Устанавливает имя пользователя.
     *
     * @param string|null $name Имя пользователя.
     * @param string|null $language_code Код языка.
     * @return mixed Ответ с установленным именем и кодом языка.
     */
    public function setMyName($name = null, $language_code = null)
    {
        return $this->method('setMyName', [
            "name" => $name,
            "language_code" => $language_code
        ]);
    }

    /**
     * Возвращает имя пользователя.
     *
     * @param string|null $language_code Код языка для получения имени на определенном языке (необязательно).
     * @return mixed Ответ с именем пользователя.
     */
    public function getMyName($language_code = null)
    {
        return $this->method('getMyName', [
            "language_code" => $language_code
        ]);
    }

    /**
     * Устанавливает описание.
     *
     * @param string|null $description Описание, которое нужно установить.
     * @param string|null $language_code Код языка для описания.
     * @return mixed Ответ от метода 'setMyDescription'.
     */
    public function setMyDescription($description = null, $language_code = null)
    {
        return $this->method('setMyDescription', [
            "description" => $description,
            "language_code" => $language_code
        ]);
    }

    /**
     * Возвращает описание на указанном языке.
     *
     * @param string|null $language_code Код языка для описания. Если не указан, используется язык по умолчанию.
     * @return mixed Ответ с описанием на указанном языке.
     */
    public function getMyDescription($language_code = null)
    {
        return $this->method('getMyDescription', [
            "language_code" => $language_code
        ]);
    }

    /**
     * Устанавливает краткое описание.
     *
     * @param string|null $short_description Краткое описание.
     * @param string|null $language_code Код языка.
     * @return mixed Ответ метода 'setMyShortDescription'.
     */
    public function setMyShortDescription($short_description = null, $language_code = null)
    {
        return $this->method('setMyShortDescription', [
            "short_description" => $short_description,
            "language_code" => $language_code
        ]);
    }

    /**
     * Возвращает краткое описание на указанном языке.
     *
     * @param string|null $language_code Код языка для получения описания. Если не указан, используется язык по умолчанию.
     * @return mixed Ответ с кратким описанием.
     */
    public function getMyShortDescription($language_code = null)
    {
        return $this->method('getMyShortDescription', [
            "language_code" => $language_code
        ]);
    }

    /**
     * Устанавливает кнопку меню чата.
     *
     * @param int|null $chat_id Идентификатор чата. Может быть null.
     * @param array|null $menu_button Массив с параметрами кнопки меню. Может быть null.
     * @return mixed Ответ от метода 'setChatMenuButton'.
     */
    public function setChatMenuButton($chat_id = null, $menu_button = null)
    {
        return $this->method('setChatMenuButton', [
            "chat_id" => $chat_id,
            "menu_button" => $menu_button
        ]);
    }

    /**
     * Получает кнопку меню чата.
     *
     * @param int|null $chat_id Идентификатор чата. Если не указан, используется текущий чат.
     * @return mixed Ответ от метода 'getChatMenuButton'.
     */
    public function getChatMenuButton($chat_id = null)
    {
        return $this->method('getChatMenuButton', [
            "chat_id" => $chat_id
        ]);
    }

    /**
     * Устанавливает права администратора по умолчанию.
     *
     * @param mixed $rights Права администратора. Может быть null.
     * @param mixed $for_channels Каналы, для которых устанавливаются права. Может быть null.
     * @return mixed Ответ от метода 'setMyDefaultAdministratorRights'.
     */
    public function setMyDefaultAdministratorRights($rights = null, $for_channels = null)
    {
        return $this->method('setMyDefaultAdministratorRights', [
            "rights" => $rights,
            "for_channels" => $for_channels
        ]);
    }

    /**
     * Получает права администратора по умолчанию.
     *
     * @param mixed|null $for_channels Каналы, для которых нужно получить права администратора. Может быть null.
     * @return mixed Ответ с правами администратора по умолчанию.
     */
    public function getMyDefaultAdministratorRights($for_channels = null)
    {
        return $this->method('getMyDefaultAdministratorRights', [
            "for_channels" => $for_channels
        ]);
    }

    /**
     * Блок
     * 
     * 
     * Updating messages
     */

    /**
     * Редактирует текст сообщения.
     *
     * @param string $text Текст сообщения.
     * @param int|null $business_connection_id Идентификатор бизнес-соединения (опционально).
     * @param int|null $chat_id Идентификатор чата (опционально).
     * @param int|null $message_id Идентификатор сообщения (опционально).
     * @param string|null $inline_message_id Идентификатор встроенного сообщения (опционально).
     * @param string|null $parse_mode Режим парсинга текста (опционально).
     * @param array|null $entities Сущности в тексте (опционально).
     * @param array|null $link_preview_options Опции предпросмотра ссылок (опционально).
     * @param array|null $reply_markup Разметка ответа (опционально).
     *
     * @return mixed Ответ от сервера.
     */
    public function editMessageText($chat_id, $message_id, $text, $reply_markup = null, $parse_mode = null,  $business_connection_id = null, $inline_message_id = null, $entities = null, $link_preview_options = null)
    {
        return $this->method('editMessageText', [
            "business_connection_id" => $business_connection_id,
            "chat_id" => $chat_id,
            "message_id" => $message_id,
            "inline_message_id" => $inline_message_id,
            "text" => $text,
            "parse_mode" => $parse_mode,
            "entities" => $entities,
            "link_preview_options" => $link_preview_options,
            "reply_markup" => $reply_markup
        ]);
    }

    /**
     * Редактирует подпись сообщения.
     *
     * @param string|null $caption Новый текст подписи (может быть null).
     * @param int|null $business_connection_id Идентификатор бизнес-соединения (может быть null).
     * @param int|null $chat_id Идентификатор чата (может быть null).
     * @param int|null $message_id Идентификатор сообщения (может быть null).
     * @param string|null $inline_message_id Идентификатор встроенного сообщения (может быть null).
     * @param string|null $parse_mode Режим парсинга текста (может быть null).
     * @param array|null $caption_entities Сущности в тексте подписи (может быть null).
     * @param bool|null $show_caption_above_media Показать подпись над медиа (может быть null).
     * @param array|null $reply_markup Разметка для ответа (может быть null).
     *
     * @return mixed Ответ от метода 'editMessageCaption'.
     */
    public function editMessageCaption($caption = null, $business_connection_id = null, $chat_id = null, $message_id = null, $inline_message_id = null, $parse_mode = null, $caption_entities = null, $show_caption_above_media = null, $reply_markup = null)
    {
        return $this->method('editMessageCaption', [
            "business_connection_id" => $business_connection_id,
            "chat_id" => $chat_id,
            "message_id" => $message_id,
            "inline_message_id" => $inline_message_id,
            "caption" => $caption,
            "parse_mode" => $parse_mode,
            "caption_entities" => $caption_entities,
            "show_caption_above_media" => $show_caption_above_media,
            "reply_markup" => $reply_markup
        ]);
    }

    /**
     * Редактирует медиа-сообщение.
     *
     * @param mixed $media Медиа-контент для редактирования.
     * @param int|null $business_connection_id Идентификатор бизнес-соединения (необязательно).
     * @param int|null $chat_id Идентификатор чата (необязательно).
     * @param int|null $message_id Идентификатор сообщения (необязательно).
     * @param string|null $inline_message_id Идентификатор встроенного сообщения (необязательно).
     * @param mixed|null $reply_markup Разметка ответа (необязательно).
     *
     * @return mixed Ответ от сервера.
     */
    public function editMessageMedia($media, $business_connection_id = null, $chat_id = null, $message_id = null, $inline_message_id = null, $reply_markup = null)
    {
        return $this->method('editMessageMedia', [
            "business_connection_id" => $business_connection_id,
            "chat_id" => $chat_id,
            "message_id" => $message_id,
            "inline_message_id" => $inline_message_id,
            "media" => $media,
            "reply_markup" => $reply_markup
        ]);
    }

    /**
     * Редактирует текущее местоположение сообщения в реальном времени.
     *
     * @param float $latitude Широта нового местоположения.
     * @param float $longitude Долгота нового местоположения.
     * @param int|null $business_connection_id Идентификатор бизнес-соединения (необязательно).
     * @param int|null $chat_id Идентификатор чата (необязательно).
     * @param int|null $message_id Идентификатор сообщения (необязательно).
     * @param string|null $inline_message_id Идентификатор встроенного сообщения (необязательно).
     * @param int|null $live_period Период времени в секундах, в течение которого местоположение будет обновляться (необязательно).
     * @param float|null $horizontal_accuracy Горизонтальная точность, в метрах (необязательно).
     * @param int|null $heading Направление движения пользователя, в градусах (необязательно).
     * @param int|null $proximity_alert_radius Радиус оповещения о приближении, в метрах (необязательно).
     * @param array|null $reply_markup Дополнительные параметры разметки ответа (необязательно).
     *
     * @return mixed Ответ от API после редактирования местоположения.
     */
    public function editMessageLiveLocation($latitude, $longitude, $business_connection_id = null, $chat_id = null, $message_id = null, $inline_message_id = null, $live_period = null, $horizontal_accuracy = null, $heading = null, $proximity_alert_radius = null, $reply_markup = null)
    {
        return $this->method('editMessageLiveLocation', [
            "business_connection_id" => $business_connection_id,
            "chat_id" => $chat_id,
            "message_id" => $message_id,
            "inline_message_id" => $inline_message_id,
            "latitude" => $latitude,
            "longitude" => $longitude,
            "live_period" => $live_period,
            "horizontal_accuracy" => $horizontal_accuracy,
            "heading" => $heading,
            "proximity_alert_radius" => $proximity_alert_radius,
            "reply_markup" => $reply_markup
        ]);
    }

    /**
     * Останавливает передачу живого местоположения сообщения.
     *
     * @param int|null $business_connection_id Идентификатор бизнес-соединения (опционально).
     * @param int|null $chat_id Идентификатор чата (опционально).
     * @param int|null $message_id Идентификатор сообщения (опционально).
     * @param string|null $inline_message_id Идентификатор встроенного сообщения (опционально).
     * @param array|null $reply_markup Разметка ответа (опционально).
     *
     * @return mixed Ответ от метода 'stopMessageLiveLocation'.
     */
    public function stopMessageLiveLocation($business_connection_id = null, $chat_id = null, $message_id = null, $inline_message_id = null, $reply_markup = null)
    {
        return $this->method('stopMessageLiveLocation', [
            "business_connection_id" => $business_connection_id,
            "chat_id" => $chat_id,
            "message_id" => $message_id,
            "inline_message_id" => $inline_message_id,
            "reply_markup" => $reply_markup
        ]);
    }

    /**
     * Редактирует разметку ответа на сообщение.
     *
     * @param int|null $business_connection_id Идентификатор бизнес-соединения.
     * @param int|null $chat_id Идентификатор чата.
     * @param int|null $message_id Идентификатор сообщения.
     * @param string|null $inline_message_id Идентификатор встроенного сообщения.
     * @param array|null $reply_markup Разметка ответа.
     *
     * @return mixed Ответ от сервера.
     */
    public function editMessageReplyMarkup($chat_id = null, $message_id = null, $reply_markup = null, $inline_message_id = null, $business_connection_id = null,)
    {
        return $this->method('editMessageReplyMarkup', [
            "business_connection_id" => $business_connection_id,
            "chat_id" => $chat_id,
            "message_id" => $message_id,
            "inline_message_id" => $inline_message_id,
            "reply_markup" => $reply_markup
        ]);
    }

    /**
     * Останавливает опрос в чате.
     *
     * @param int $chat_id Идентификатор чата.
     * @param int $message_id Идентификатор сообщения.
     * @param int|null $business_connection_id Идентификатор бизнес-соединения (необязательный).
     * @param mixed|null $reply_markup Разметка ответа (необязательная).
     * 
     * @return mixed Ответ от метода 'stopPoll'.
     */
    public function stopPoll($chat_id, $message_id, $business_connection_id = null, $reply_markup = null)
    {
        return $this->method('stopPoll', [
            "chat_id" => $chat_id,
            "message_id" => $message_id,
            "business_connection_id" => $business_connection_id,
            "reply_markup" => $reply_markup
        ]);
    }

    /**
     * Удаляет сообщение в чате.
     *
     * @param int $chat_id Идентификатор чата.
     * @param int $message_id Идентификатор сообщения.
     * @return mixed Ответ от метода 'deleteMessage'.
     */
    public function deleteMessage($chat_id, $message_id)
    {
        return $this->method('deleteMessage', [
            "chat_id" => $chat_id,
            "message_id" => $message_id
        ]);
    }

    /**
     * Удаляет сообщения в чате.
     *
     * @param int $chat_id Идентификатор чата.
     * @param array $message_ids Массив идентификаторов сообщений для удаления.
     * @return mixed Ответ от метода 'deleteMessages'.
     */
    public function deleteMessages($chat_id, array $message_ids)
    {
        return $this->method('deleteMessages', [
            "chat_id" => $chat_id,
            "message_ids" => $message_ids
        ]);
    }

    /**
     * Блок
     * 
     * 
     * Stickers
     */

    /**
     * Отправляет стикер в указанный чат.
     *
     * @param int|string $chat_id Идентификатор чата или имя пользователя (в формате @username).
     * @param string $sticker Файл стикера для отправки.
     * @param int|null $business_connection_id (Необязательно) Идентификатор бизнес-соединения.
     * @param int|null $message_thread_id (Необязательно) Идентификатор потока сообщений.
     * @param string|null $emoji (Необязательно) Эмодзи, связанный со стикером.
     * @param bool $disable_notification (Необязательно) Отключить уведомления для этого сообщения.
     * @param bool $protect_content (Необязательно) Защитить содержимое сообщения от пересылки и копирования.
     * @param int|null $message_effect_id (Необязательно) Идентификатор эффекта сообщения.
     * @param array|null $reply_parameters (Необязательно) Параметры для ответа на сообщение.
     * @param array|null $reply_markup (Необязательно) Дополнительный интерфейс для сообщения (кнопки и т.д.).
     *
     * @return mixed Ответ от API после отправки стикера.
     */
    public function sendSticker($chat_id, $sticker, $business_connection_id = null, $message_thread_id = null, $emoji = null, $disable_notification = false, $protect_content = false, $message_effect_id = null, $reply_parameters = null, $reply_markup = null)
    {
        return $this->method('sendSticker', [
            "chat_id" => $chat_id,
            "sticker" => $sticker,
            "business_connection_id" => $business_connection_id,
            "message_thread_id" => $message_thread_id,
            "emoji" => $emoji,
            "disable_notification" => $disable_notification,
            "protect_content" => $protect_content,
            "message_effect_id" => $message_effect_id,
            "reply_parameters" => $reply_parameters,
            "reply_markup" => $reply_markup
        ]);
    }

    /**
     * Получает набор стикеров по имени.
     *
     * @param string $name Имя набора стикеров.
     * @return mixed Ответ с информацией о наборе стикеров.
     */
    public function getStickerSet($name)
    {
        return $this->method('getStickerSet', [
            "name" => $name
        ]);
    }

    /**
     * Получает пользовательские стикеры-эмодзи.
     *
     * @param array $custom_emoji_ids Массив идентификаторов пользовательских эмодзи.
     * @return mixed Ответ от метода 'getCustomEmojiStickers'.
     */
    public function getCustomEmojiStickers(array $custom_emoji_ids)
    {
        return $this->method('getCustomEmojiStickers', [
            "custom_emoji_ids" => $custom_emoji_ids
        ]);
    }

    /**
     * Загружает файл стикера для указанного пользователя.
     *
     * @param int $user_id Идентификатор пользователя.
     * @param mixed $sticker Данные стикера.
     * @param string $sticker_format Формат стикера.
     * @return mixed Ответ от сервера.
     */
    public function uploadStickerFile($user_id, $sticker, $sticker_format)
    {
        return $this->method('uploadStickerFile', [
            "user_id" => $user_id,
            "sticker" => $sticker,
            "sticker_format" => $sticker_format
        ]);
    }

    /**
     * Создает новый набор стикеров.
     *
     * @param int $user_id Идентификатор пользователя.
     * @param string $name Имя набора стикеров.
     * @param string $title Заголовок набора стикеров.
     * @param array $stickers Массив стикеров.
     * @param string|null $sticker_type Тип стикера (необязательно).
     * @param bool|null $needs_repainting Требуется ли перекраска (необязательно).
     * @return mixed Ответ от сервера.
     */
    public function createNewStickerSet($user_id, $name, $title, array $stickers, $sticker_type = null, $needs_repainting = null)
    {
        return $this->method('createNewStickerSet', [
            "user_id" => $user_id,
            "name" => $name,
            "title" => $title,
            "stickers" => $stickers,
            "sticker_type" => $sticker_type,
            "needs_repainting" => $needs_repainting
        ]);
    }

    /**
     * Добавляет стикер в набор.
     *
     * @param int $user_id Идентификатор пользователя.
     * @param string $name Название набора стикеров.
     * @param mixed $sticker Стикер, который нужно добавить.
     * @return mixed Ответ от метода 'addStickerToSet'.
     */
    public function addStickerToSet($user_id, $name, $sticker)
    {
        return $this->method('addStickerToSet', [
            "user_id" => $user_id,
            "name" => $name,
            "sticker" => $sticker
        ]);
    }

    /**
     * Устанавливает позицию наклейки в наборе.
     *
     * @param string $sticker Идентификатор наклейки.
     * @param int $position Позиция наклейки в наборе.
     * @return mixed Ответ от метода 'setStickerPositionInSet'.
     */
    public function setStickerPositionInSet($sticker, $position)
    {
        return $this->method('setStickerPositionInSet', [
            "sticker" => $sticker,
            "position" => $position
        ]);
    }

    /**
     * Удаляет стикер из набора.
     *
     * @param mixed $sticker Стикер, который нужно удалить из набора.
     * @return mixed Ответ на запрос удаления стикера из набора.
     */
    public function deleteStickerFromSet($sticker)
    {
        return $this->method('deleteStickerFromSet', [
            "sticker" => $sticker
        ]);
    }

    /**
     * Заменяет стикер в наборе пользователя.
     *
     * @param int $user_id Идентификатор пользователя.
     * @param string $name Имя набора стикеров.
     * @param string $old_sticker Название старого стикера, который нужно заменить.
     * @param string $sticker Название нового стикера.
     * @return mixed Ответ на запрос замены стикера.
     */
    public function replaceStickerInSet($user_id, $name, $old_sticker, $sticker)
    {
        return $this->method('replaceStickerInSet', [
            "user_id" => $user_id,
            "name" => $name,
            "old_sticker" => $old_sticker,
            "sticker" => $sticker
        ]);
    }

    /**
     * Устанавливает список эмодзи для стикера.
     *
     * @param string $sticker Идентификатор стикера.
     * @param array $emoji_list Массив эмодзи, связанных со стикером.
     * @return mixed Ответ от метода 'setStickerEmojiList'.
     */
    public function setStickerEmojiList($sticker, array $emoji_list)
    {
        return $this->method('setStickerEmojiList', [
            "sticker" => $sticker,
            "emoji_list" => $emoji_list
        ]);
    }

    /**
     * Устанавливает ключевые слова для стикера.
     *
     * @param string $sticker Идентификатор стикера.
     * @param array|null $keywords Массив ключевых слов для стикера. Может быть null.
     * @return mixed Ответ от метода 'setStickerKeywords'.
     */
    public function setStickerKeywords($sticker, array $keywords = null)
    {
        return $this->method('setStickerKeywords', [
            "sticker" => $sticker,
            "keywords" => $keywords
        ]);
    }

    /**
     * Устанавливает позицию маски для стикера.
     *
     * @param string $sticker Идентификатор стикера.
     * @param array|null $mask_position Позиция маски (может быть null).
     * @return mixed Ответ от метода 'setStickerMaskPosition'.
     */
    public function setStickerMaskPosition($sticker, $mask_position = null)
    {
        return $this->method('setStickerMaskPosition', [
            "sticker" => $sticker,
            "mask_position" => $mask_position
        ]);
    }

    /**
     * Устанавливает заголовок набора стикеров.
     *
     * @param string $name Имя набора стикеров.
     * @param string $title Новый заголовок набора стикеров.
     * @return mixed Ответ от метода 'setStickerSetTitle'.
     */
    public function setStickerSetTitle($name, $title)
    {
        return $this->method('setStickerSetTitle', [
            "name" => $name,
            "title" => $title
        ]);
    }

    /**
     * Устанавливает миниатюру для набора стикеров.
     *
     * @param string $name Название набора стикеров.
     * @param int $user_id Идентификатор пользователя.
     * @param mixed $thumbnail Миниатюра для набора стикеров.
     * @param string $format Формат миниатюры.
     * @return mixed Ответ от метода 'setStickerSetThumbnail'.
     */
    public function setStickerSetThumbnail($name, $user_id, $thumbnail, $format)
    {
        return $this->method('setStickerSetThumbnail', [
            "name" => $name,
            "user_id" => $user_id,
            "thumbnail" => $thumbnail,
            "format" => $format
        ]);
    }

    /**
     * Устанавливает миниатюру набора стикеров с пользовательскими эмодзи.
     *
     * @param string $name Название набора стикеров.
     * @param string|null $custom_emoji_id Идентификатор пользовательского эмодзи (необязательный).
     * @return mixed Ответ от метода 'setCustomEmojiStickerSetThumbnail'.
     */
    public function setCustomEmojiStickerSetThumbnail($name, $custom_emoji_id = null)
    {
        return $this->method('setCustomEmojiStickerSetThumbnail', [
            "name" => $name,
            "custom_emoji_id" => $custom_emoji_id
        ]);
    }

    /**
     * Удаляет набор стикеров по указанному имени.
     *
     * @param string $name Имя набора стикеров, который нужно удалить.
     * @return mixed Ответ от сервера после попытки удаления набора стикеров.
     */
    public function deleteStickerSet($name)
    {
        return $this->method('deleteStickerSet', [
            "name" => $name
        ]);
    }

    /**
     * Блок
     * 
     * 
     * Inline mode
     */


    /**
     * Блок
     * 
     * 
     * Payments
     */

    /**
     * Отправляет счет-фактуру в указанный чат.
     *
     * @param int $chat_id Идентификатор чата, в который отправляется счет.
     * @param string $title Заголовок счета.
     * @param string $description Описание счета.
     * @param string $payload Полезная нагрузка счета.
     * @param string $provider_token Токен провайдера платежей.
     * @param string $currency Валюта счета.
     * @param array $prices Массив цен.
     * @param int|null $message_thread_id Идентификатор потока сообщений (опционально).
     * @param int|null $max_tip_amount Максимальная сумма чаевых (опционально).
     * @param array|null $suggested_tip_amounts Массив предложенных сумм чаевых (опционально).
     * @param string|null $start_parameter Параметр запуска (опционально).
     * @param string|null $provider_data Данные провайдера (опционально).
     * @param string|null $photo_url URL фотографии (опционально).
     * @param int|null $photo_size Размер фотографии (опционально).
     * @param int|null $photo_width Ширина фотографии (опционально).
     * @param int|null $photo_height Высота фотографии (опционально).
     * @param bool|null $need_name Требуется ли имя (опционально).
     * @param bool|null $need_phone_number Требуется ли номер телефона (опционально).
     * @param bool|null $need_email Требуется ли email (опционально).
     * @param bool|null $need_shipping_address Требуется ли адрес доставки (опционально).
     * @param bool|null $send_phone_number_to_provider Отправить ли номер телефона провайдеру (опционально).
     * @param bool|null $send_email_to_provider Отправить ли email провайдеру (опционально).
     * @param bool|null $is_flexible Гибкий ли счет (опционально).
     * @param bool $disable_notification Отключить ли уведомления.
     * @param bool $protect_content Защитить ли контент.
     * @param string|null $message_effect_id Идентификатор эффекта сообщения (опционально).
     * @param array|null $reply_parameters Параметры ответа (опционально).
     * @param array|null $reply_markup Разметка ответа (опционально).
     *
     * @return mixed Ответ от метода 'sendInvoice'.
     */
    public function sendInvoice($chat_id, $title, $description, $payload, $provider_token, $currency, array $prices, $message_thread_id = null, $max_tip_amount = null, $suggested_tip_amounts = null, $start_parameter = null, $provider_data = null, $photo_url = null, $photo_size = null, $photo_width = null, $photo_height = null, $need_name = null, $need_phone_number = null, $need_email = null, $need_shipping_address = null, $send_phone_number_to_provider = null, $send_email_to_provider = null, $is_flexible = null, $disable_notification = false, $protect_content = false, $message_effect_id = null, $reply_parameters = null, $reply_markup = null)
    {
        return $this->method('sendInvoice', [
            "chat_id" => $chat_id,
            "title" => $title,
            "description" => $description,
            "payload" => $payload,
            "provider_token" => $provider_token,
            "currency" => $currency,
            "prices" => $prices,
            "message_thread_id" => $message_thread_id,
            "max_tip_amount" => $max_tip_amount,
            "suggested_tip_amounts" => $suggested_tip_amounts,
            "start_parameter" => $start_parameter,
            "provider_data" => $provider_data,
            "photo_url" => $photo_url,
            "photo_size" => $photo_size,
            "photo_width" => $photo_width,
            "photo_height" => $photo_height,
            "need_name" => $need_name,
            "need_phone_number" => $need_phone_number,
            "need_email" => $need_email,
            "need_shipping_address" => $need_shipping_address,
            "send_phone_number_to_provider" => $send_phone_number_to_provider,
            "send_email_to_provider" => $send_email_to_provider,
            "is_flexible" => $is_flexible,
            "disable_notification" => $disable_notification,
            "protect_content" => $protect_content,
            "message_effect_id" => $message_effect_id,
            "reply_parameters" => $reply_parameters,
            "reply_markup" => $reply_markup
        ]);
    }

    /**
     * Создает ссылку на счет.
     *
     * @param string $title Название счета.
     * @param string $description Описание счета.
     * @param string $payload Полезная нагрузка для счета.
     * @param string $provider_token Токен провайдера.
     * @param string $currency Валюта счета.
     * @param array $prices Массив цен.
     * @param int|null $max_tip_amount Максимальная сумма чаевых (необязательно).
     * @param array|null $suggested_tip_amounts Массив предложенных сумм чаевых (необязательно).
     * @param string|null $provider_data Данные провайдера (необязательно).
     * @param string|null $photo_url URL фотографии (необязательно).
     * @param int|null $photo_size Размер фотографии (необязательно).
     * @param int|null $photo_width Ширина фотографии (необязательно).
     * @param int|null $photo_height Высота фотографии (необязательно).
     * @param bool|null $need_name Требуется ли имя (необязательно).
     * @param bool|null $need_phone_number Требуется ли номер телефона (необязательно).
     * @param bool|null $need_email Требуется ли email (необязательно).
     * @param bool|null $need_shipping_address Требуется ли адрес доставки (необязательно).
     * @param bool|null $send_phone_number_to_provider Отправить ли номер телефона провайдеру (необязательно).
     * @param bool|null $send_email_to_provider Отправить ли email провайдеру (необязательно).
     * @param bool|null $is_flexible Гибкий ли счет (необязательно).
     *
     * @return mixed Ответ от метода 'createInvoiceLink'.
     */
    public function createInvoiceLink($title, $description, $payload, $provider_token, $currency, array $prices, $max_tip_amount = null, $suggested_tip_amounts = null, $provider_data = null, $photo_url = null, $photo_size = null, $photo_width = null, $photo_height = null, $need_name = null, $need_phone_number = null, $need_email = null, $need_shipping_address = null, $send_phone_number_to_provider = null, $send_email_to_provider = null, $is_flexible = null)
    {
        return $this->method('createInvoiceLink', [
            "title" => $title,
            "description" => $description,
            "payload" => $payload,
            "provider_token" => $provider_token,
            "currency" => $currency,
            "prices" => $prices,
            "max_tip_amount" => $max_tip_amount,
            "suggested_tip_amounts" => $suggested_tip_amounts,
            "provider_data" => $provider_data,
            "photo_url" => $photo_url,
            "photo_size" => $photo_size,
            "photo_width" => $photo_width,
            "photo_height" => $photo_height,
            "need_name" => $need_name,
            "need_phone_number" => $need_phone_number,
            "need_email" => $need_email,
            "need_shipping_address" => $need_shipping_address,
            "send_phone_number_to_provider" => $send_phone_number_to_provider,
            "send_email_to_provider" => $send_email_to_provider,
            "is_flexible" => $is_flexible
        ]);
    }

    /**
     * Получает транзакции звезд.
     *
     * @param int|null $offset Смещение для выборки транзакций. По умолчанию null.
     * @param int $limit Лимит на количество транзакций. По умолчанию 100.
     * @return mixed Ответ с транзакциями звезд.
     */
    public function getStarTransactions($offset = null, $limit = 100)
    {
        return $this->method('getStarTransactions', [
            "offset" => $offset,
            "limit" => $limit
        ]);
    }

    /**
     * Возвращает платеж через Telegram.
     *
     * @param int $user_id Идентификатор пользователя.
     * @param string $telegram_payment_charge_id Идентификатор платежа в Telegram.
     * @return mixed Ответ на запрос возврата платежа.
     */
    public function refundStarPayment($user_id, $telegram_payment_charge_id)
    {
        return $this->method('refundStarPayment', [
            "user_id" => $user_id,
            "telegram_payment_charge_id" => $telegram_payment_charge_id
        ]);
    }

    /**
     * Блок
     * 
     * 
     * Games
     */

    /**
     * Отправляет игру в указанный чат.
     *
     * @param int $chat_id Идентификатор чата, в который будет отправлена игра.
     * @param string $game_short_name Короткое имя игры.
     * @param int|null $business_connection_id Идентификатор бизнес-соединения (необязательно).
     * @param int|null $message_thread_id Идентификатор потока сообщений (необязательно).
     * @param bool $disable_notification Отключить уведомления для этого сообщения (по умолчанию false).
     * @param bool $protect_content Защитить содержимое сообщения (по умолчанию false).
     * @param int|null $message_effect_id Идентификатор эффекта сообщения (необязательно).
     * @param mixed|null $reply_parameters Параметры ответа (необязательно).
     * @param mixed|null $reply_markup Разметка ответа (необязательно).
     *
     * @return mixed Ответ от метода 'sendGame'.
     */
    public function sendGame($chat_id, $game_short_name, $business_connection_id = null, $message_thread_id = null, $disable_notification = false, $protect_content = false, $message_effect_id = null, $reply_parameters = null, $reply_markup = null)
    {
        return $this->method('sendGame', [
            "chat_id" => $chat_id,
            "game_short_name" => $game_short_name,
            "business_connection_id" => $business_connection_id,
            "message_thread_id" => $message_thread_id,
            "disable_notification" => $disable_notification,
            "protect_content" => $protect_content,
            "message_effect_id" => $message_effect_id,
            "reply_parameters" => $reply_parameters,
            "reply_markup" => $reply_markup
        ]);
    }

    /**
     * Устанавливает игровой счет для пользователя.
     *
     * @param int $user_id Идентификатор пользователя.
     * @param int $score Новый счет пользователя.
     * @param bool|null $force Принудительное обновление счета, даже если он меньше текущего.
     * @param bool|null $disable_edit_message Отключение редактирования сообщения.
     * @param int|null $chat_id Идентификатор чата.
     * @param int|null $message_id Идентификатор сообщения.
     * @param string|null $inline_message_id Идентификатор встроенного сообщения.
     * @return mixed Ответ от сервера.
     */
    public function setGameScore($user_id, $score, $force = null, $disable_edit_message = null, $chat_id = null, $message_id = null, $inline_message_id = null)
    {
        return $this->method('setGameScore', [
            "user_id" => $user_id,
            "score" => $score,
            "force" => $force,
            "disable_edit_message" => $disable_edit_message,
            "chat_id" => $chat_id,
            "message_id" => $message_id,
            "inline_message_id" => $inline_message_id
        ]);
    }

    /**
     * Получает высокие результаты игры для указанного пользователя.
     *
     * @param int $user_id Идентификатор пользователя.
     * @param int|null $chat_id (Необязательно) Идентификатор чата.
     * @param int|null $message_id (Необязательно) Идентификатор сообщения.
     * @param string|null $inline_message_id (Необязательно) Идентификатор встроенного сообщения.
     * @return mixed Ответ с высокими результатами игры.
     */
    public function getGameHighScores($user_id, $chat_id = null, $message_id = null, $inline_message_id = null)
    {
        return $this->method('getGameHighScores', [
            "user_id" => $user_id,
            "chat_id" => $chat_id,
            "message_id" => $message_id,
            "inline_message_id" => $inline_message_id
        ]);
    }
}
