<?php

namespace Bot\Support\Facades;

use Closure;
use stdClass;


class Services
{
    /**
     * Преобразует данные в формат JSON.
     *
     * @param mixed $body Данные для преобразования в JSON.
     * @return string JSON-представление данных.
     */
    public static function json(mixed $body): string
    {
        return json_encode($body, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
    }

    /**
     * Получает токен для указанного бота.
     *
     * @param string|null $bot Имя бота, для которого нужно получить токен.
     * @return mixed Токен бота или null, если токен не найден.
     */
    public function getToken(string|null $bot)
    {
        if (!$bot) {
            return null;
        }

        try {
            // Получаем токен из базы данных
            $botModel = \App\Models\Bot::byName($bot)->where('enabled', true)->first();
            if (!$botModel) {
                return null;
            }
            
            // Получаем токен бота
            return $botModel->token;
        } catch (\Exception $e) {
            return null;
        }
    }

    /**
     * Извлекает первое слово из заданной строки.
     *
     * @param string $str Входная строка.
     * @return string Первое слово из входной строки или пустая строка, если совпадение не найдено.
     */
    public static function firstWord(string $str): string
    {
        preg_match("/^\/(\w+)/", $str, $matches);
        return isset($matches[1]) ? $matches[1] : "";
    }

    /**
     * Извлекает последнее слово из заданной строки, исключая определенную команду.
     *
     * @param string $str Входная строка.
     * @param string $command Команда, которую нужно исключить из входной строки.
     * @return string Последнее слово из входной строки или пустая строка, если входная строка совпадает с командой.
     */
    public static function lastWord(string $str, string $command): string
    {
        if ($str === $command) {
            return '';
        }
        preg_match('/(\S+)\s(.+)/', $str, $matches);
        return isset($matches[2]) ? $matches[2] : "";
    }

    /**
     * Отправляет сообщение с инлайн-клавиатурой.
     *
     * @param array $keyboard Массив с настройками клавиатуры.
     *
     * @return string JSON-представление инлайн-клавиатуры.
     */
    public static function inlineKeyboard($keyboard)
    {
        return json_encode(['inline_keyboard' => $keyboard]);
    }

    /**
     * Отправляет сообщение с обычной клавиатурой.
     *
     * @param array $keyboard Массив с настройками клавиатуры.
     * @param bool $one_time_keyboard Параметр одноразовой клавиатуры (по умолчанию true).
     * @param bool $resize_keyboard Параметр изменения размера клавиатуры (по умолчанию true).
     *
     * @return string JSON-представление обычной клавиатуры.
     */
    public static function keyboard($keyboard, $one_time_keyboard = true, $resize_keyboard = true)
    {
        return json_encode([
            'keyboard' => $keyboard,
            'one_time_keyboard' => $one_time_keyboard,
            'resize_keyboard' => $resize_keyboard
        ]);
    }

    /**
     * Извлекает аргументы из текста команды.
     *
     * @param string $text Текст команды.
     *
     * @return array Массив аргументов.
     */
    public static function getArguments($text)
    {
        $parts = explode(' ', $text);
        array_shift($parts); // Удаляем первую часть, так как это команда
        return $parts;
    }

    /**
     * Метод для создания подгрупп для клавиатуры с возможностью ручного управления расположением кнопок
     *
     * @param array $array
     * @param int|array $layout Число делений или массив с ручным расположением.
     * 
     * @return array Возвращает новый массив
     */
    public static function grid($array, $layout = 2)
    {
        if (is_array($layout)) {
            $result = [];
            $index = 0;
            foreach ($layout as $count) {
                $result[] = array_slice($array, $index, $count);
                $index += $count;
            }
            return $result;
        } elseif (is_int($layout) && $layout > 0) {
            $result = [];
            $currentSubarray = [];
            foreach ($array as $element) {
                $currentSubarray[] = $element;
                if (count($currentSubarray) == $layout) {
                    $result[] = $currentSubarray;
                    $currentSubarray = [];
                }
            }
            if (!empty($currentSubarray)) {
                $result[] = $currentSubarray;
            }
            return $result;
        } else {
            return [];
        }
    }

    /**
     * Метод для рендеринга HTML сообщений
     *
     * @param array $data строки (необязательно).
     * 
     * @return string Возвращает строку
     */
    public static function html($data = [])
    {
        return implode("\n", $data);
    }

    /**
     * Генерирует простой массив клавиш на основе предоставленных опций.
     *
     * Этот метод обрабатывает массив опций и генерирует соответствующий
     * массив клавиш. Каждая опция представляет собой массив, первым элементом которого является строка
     * который определяет тип кнопки на клавиатуре, а вторым элементом является
     * текст для кнопки.
     *
     * Метод поддерживает три типа кнопок:
     * - Кнопки веб-приложения: если первый элемент параметра начинается с "app:",
     * метод создает кнопку с ключом "web_app".
     * - Кнопки URL: Если первый элемент параметра начинается с "url:",
     * метод создает кнопку с ключом "url".
     * - Кнопки обратного вызова: Во всех остальных случаях метод создает кнопку с ключом "url". 
     * ключ 'callback_data'.
     *
     * @param array $options - массив параметров для создания клавиатуры. Каждый 
     * @param int $type_keyboard - тип клавиватуры
     * параметр - это массив, состоящий из двух элементов: первый элемент 
     */
    public static function simpleKeyboard(array $options, int $type_keyboard)
    {
        $result = [];
        foreach ($options as $option) {
            if ($type_keyboard) {
                if (strpos($option[0], 'app:') === 0) {
                    $url = substr($option[0], 4);
                    $url = self::ensureHttps($url);
                    $result[] = [
                        'text' => $option[1],
                        'web_app' => [
                            'url' => $url
                        ]
                    ];
                } elseif (strpos($option[0], 'url:') === 0) {
                    $url = substr($option[0], 4);
                    $url = self::ensureHttps($url);
                    $result[] = [
                        'url' => $url,
                        'text' => $option[1]
                    ];
                } else {
                    $result[] = [
                        'callback_data' => $option[0],
                        'text' => $option[1]
                    ];
                }
            } else {
                $result[] = [
                    'text' => $option[0]
                ];
            }
        }

        return $result;
    }

    private static function ensureHttps(string $url): string
    {
        if (strpos($url, 'http://') === 0 || strpos($url, 'https://') === 0) {
            return $url;
        }
        return 'https://' . $url;
    }

    /**
     * Метод проверяющий запрос на пустоту
     *
     * @return bool Возвращает true, если запрос пустой, иначе false
     */
    public function blankRequest($request)
    {
        return ($request instanceof stdClass);
    }

    /**
     * Преобразует клавиатуру с инлайн-кнопками.
     *
     * Этот метод принимает массив клавиатуры и преобразует каждую кнопку,
     * содержащую ключ 'web_app', в массив с ключом 'url'.
     *
     * @param array $keyboard Массив клавиатуры с инлайн-кнопками.
     * 
     * @return void
     */
    public static function mapInlineKeyboard($keyboard)
    {
        if ($keyboard) {
            foreach ($keyboard as &$button) {
                if (isset($button['web_app']) && is_string($button['web_app'])) {
                    $button['web_app'] = ['url' => $button['web_app']];
                }
            }
        }
    }

    /**
     * Обрабатывает паттерн и выполняет соответствующий callback, если паттерн совпадает с текстом.
     *
     * @param string|array $pattern Это строка или массив строк/регулярных выражений, по которым будет искать совпадение с текстом.
     * @param string $text Текст для проверки на совпадение с паттерном.
     * @param Closure $callback Функция-обработчик для выполнения, если текст совпадает с паттерном.
     * @param Closure|null $preCallback Функция, которая будет выполнена перед основным callback, если паттерн совпадает.
     *
     * @return mixed Результат выполнения функции-обработчика.
     */
    public static function pattern($pattern, $text, $callback, $preCallback = null)
    {
        // Приводим паттерн к массиву, если это строка
        $patterns = is_array($pattern) ? $pattern : [$pattern];

        // Пробегаемся по каждому паттерну
        foreach ($patterns as $singlePattern) {
            // Проверяем, является ли паттерн регулярным выражением
            $isRegex = preg_match('/^\/.*\/[a-z]*$/i', $singlePattern);

            // Если это не регулярное выражение, преобразуем паттерн с параметрами в регулярное выражение
            if (!$isRegex) {
                $singlePattern = str_replace(['{', '}'], ['(?P<', '>[^}]+)'], $singlePattern);
                $singlePattern = "/^" . str_replace('/', '\/', $singlePattern) . "$/";
            }

            if (preg_match($singlePattern, $text, $matches)) {
                // Извлекаем только значения параметров из совпавших данных и передаем их в функцию-обработчик
                $parameters = array_filter($matches, 'is_string', ARRAY_FILTER_USE_KEY);

                // Выполняем preCallback, если он задан
                if ($preCallback) {
                    $preCallback();
                }

                // Вызываем функцию-обработчик с параметрами
                $callback(...$parameters);
                exit; // Завершаем выполнение скрипта после выполнения callback
            }
        }

        return null;
    }

    /**
     * Находит совпадение для заданного шаблона в предоставленном тексте.
     *
     * @param string $pattern Регулярное выражение для поиска.
     * @param string $text Текст, в котором будет производиться поиск.
     * @return array|null Возвращает массив совпадений, если они найдены, или null, если совпадений не найдено.
     */
    public static function findMatch($data, $array)
    {
        foreach ($array as $value) {
            if (stripos($data, $value) !== false) {
                return true; // Найдено совпадение
            }
        }
        return false; // Совпадений не найдено
    }


    /**
     * Приводит заданное количество к сбалансированному состоянию.
     *
     * @param array $count Количество элементов массива для уравновешивания.
     * @param int $row Ряды
     * @return mixed Результат процесса уравновешивания.
     */
    public static function equalizer($count, $row = 2)
    {
        $count = count($count);

        if ($count % $row == 0) {
            $layout = array_fill(0, $count / $row, $row);
        } else {
            $layout = array_fill(0, ($count - 1) / $row, $row);
            $layout[] = 1;
        }

        return $layout;
    }

    public static function isSSLAvailable()
    {
        $url = self::getCurrentUrl();
        $scheme = parse_url($url, PHP_URL_SCHEME);
        $host = parse_url($url, PHP_URL_HOST);

        if ($scheme !== 'https' || empty($host)) {
            return false;
        }

        $stream = @stream_context_create(["ssl" => ["capture_peer_cert" => true]]);
        $connection = @stream_socket_client(
            "ssl://$host:443",
            $errno,
            $errstr,
            5,
            STREAM_CLIENT_CONNECT,
            $stream
        );

        return $connection !== false;
    }

    private static function getCurrentUrl()
    {
        // CLI/Artisan fallback to APP_URL
        $appUrl = function_exists('config') ? (config('app.url') ?: null) : null;

        // If not in HTTP context, use APP_URL or localhost
        if (php_sapi_name() === 'cli' || !isset($_SERVER) || empty($_SERVER)) {
            return $appUrl ?: 'http://localhost';
        }

        $httpsOn = !empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off';
        $protocol = $httpsOn ? 'https' : 'http';
        $host = $_SERVER['HTTP_HOST'] ?? (parse_url($appUrl ?? '', PHP_URL_HOST) ?: 'localhost');
        $uri = $_SERVER['REQUEST_URI'] ?? '/';

        return $protocol . '://' . $host . $uri;
    }
}
