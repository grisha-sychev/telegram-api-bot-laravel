<?php

namespace Bot\Types;

use Illuminate\Support\Arr;

class DynamicData
{
    private array $data;

    /**
     * Карта ключей верхнего уровня -> специализированный класс данных
     * message -> MessageData, from -> UserData, chat -> ChatData, callback_query -> CallbackQueryData
     */
    private static array $wrapperMap = [
        // базовые
        'message' => MessageData::class,
        'from' => UserData::class,
        'chat' => ChatData::class,
        'callback_query' => CallbackQueryData::class,
        'callbackquery' => CallbackQueryData::class,

        // message ссылки на объекты
        'reply_to_message' => MessageData::class,
        'pinned_message' => MessageData::class,
        'via_bot' => UserData::class,
        'sender_chat' => ChatData::class,
        'sender_user' => UserData::class,
        'left_chat_member' => UserData::class,
        'new_chat_member' => UserData::class,
        'forward_from' => UserData::class,
        'forward_from_chat' => ChatData::class,

        // чатовые обновления
        'chat_member' => ChatMemberUpdatedData::class,
        'my_chat_member' => ChatMemberUpdatedData::class,
        'chat_join_request' => ChatJoinRequestData::class,

        // инлайн режим
        'inline_query' => InlineQueryData::class,
        'chosen_inline_result' => ChosenInlineResultData::class,

        // платежи и доставка
        'shipping_query' => ShippingQueryData::class,
        'pre_checkout_query' => PreCheckoutQueryData::class,

        // опросы
        'poll' => PollData::class,
        'poll_answer' => PollAnswerData::class,

        // sharing
        'user_shared' => UserSharedData::class,
        'chat_shared' => ChatSharedData::class,

        // реакции (новые объекты)
        'message_reaction' => MessageReactionUpdatedData::class,
        'message_reaction_count' => MessageReactionCountUpdatedData::class,
    ];

    /**
     * DynamicData constructor.
     *
     * @param array $data
     */
    public function __construct(array $data)
    {
        $this->data = $data;
    }

    /**
     * Динамическая обработка методов
     *
     * @param string $name
     * @param array $arguments
     * @return mixed|null
     */
    public function __call(string $name, array $arguments)
    {
        $property = $this->toSnakeCase(lcfirst(preg_replace('/^get/', '', $name)));

        // Если свойство существует как ключ
        if (Arr::exists($this->data, $property)) {
            $value = $this->data[$property];

            if (is_array($value)) {
                // Если это известная сущность — вернуть специализированную обёртку
                $wrapperClass = self::$wrapperMap[$property] ?? null;
                if ($wrapperClass && class_exists($wrapperClass)) {
                    return new $wrapperClass($value);
                }
                return new self($value);
            }

            return $value;
        }

        // Если массив индексированный (только числовые индексы)
        if (is_numeric($property) && isset($this->data[(int) $property])) {
            $value = $this->data[(int) $property];

            if (is_array($value)) {
                return new self($value);
            }

            return $value;
        }

        return null;
    }

    /**
     * Преобразование в строку
     *
     * @return string
     */
    public function __toString(): string
    {
        if (is_array($this->data) && count($this->data) > 0 && is_string(current($this->data))) {
            return implode(", ", $this->data);
        }

        return json_encode($this->data, JSON_UNESCAPED_UNICODE);
    }

    /**
     * Преобразование строки в snake_case
     *
     * @param string $string
     * @return string
     */
    private function toSnakeCase(string $string): string
    {
        if ($string === strtolower($string)) {
            return $string;
        }

        return strtolower(preg_replace('/([a-z])([A-Z])/', '$1_$2', $string));
    }

    /**
     * Метод для преобразования объекта в JSON.
     *
     * @return string
     */
    public function toJson(): string
    {
        return json_encode($this->data, JSON_UNESCAPED_UNICODE);
    }
}

/**
 * Типизированные обёртки для ключевых сущностей Telegram
 */
class MessageData extends DynamicData {}
class UserData extends DynamicData {}
class ChatData extends DynamicData {}
class CallbackQueryData extends DynamicData {}
class ChatMemberUpdatedData extends DynamicData {}
class ChatJoinRequestData extends DynamicData {}
class InlineQueryData extends DynamicData {}
class ChosenInlineResultData extends DynamicData {}
class ShippingQueryData extends DynamicData {}
class PreCheckoutQueryData extends DynamicData {}
class PollData extends DynamicData {}
class PollAnswerData extends DynamicData {}
class UserSharedData extends DynamicData {}
class ChatSharedData extends DynamicData {}
class MessageReactionUpdatedData extends DynamicData {}
class MessageReactionCountUpdatedData extends DynamicData {}
