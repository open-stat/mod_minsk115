<?php
namespace Core2\Mod\Minsk115\Common;
use GuzzleHttp\Client;
use \Longman\TelegramBot;
use Monolog\Formatter\LineFormatter;
use Monolog\Handler\StreamHandler;
use Monolog\Logger;


/**
 *
 */
class Bot {

    private $telegram;
    private $base_uri = 'https://api.telegram.org';


    /**
     * @param string $token
     * @param array  $options
     * @throws TelegramBot\Exception\TelegramException
     */
    public function __construct(string $token, array $options = []) {

        if (empty($token)) {
            throw new \Exception('Не указан токен бота');
        }

        if ( ! empty($options['log_dir'])) {
            if ( ! is_dir($options['log_dir'])) {
                throw new \Exception('Указанная папка для логов не существует');
            }

            if ( ! is_writable($options['log_dir'])) {
                throw new \Exception('Нет доступа на запись в папку для логов');
            }
        }

        if ( ! empty($options['download_dir'])) {
            if ( ! is_dir($options['download_dir'])) {
                throw new \Exception('Указанная папка для загрузки файлов не существует');
            }

            if ( ! is_writable($options['download_dir'])) {
                throw new \Exception('Нет доступа на запись в папку для загрузки файлов');
            }
        }


        $this->telegram = new TelegramBot\Telegram($token, $options['bot_username'] ?? '');
        TelegramBot\Request::setClient(new Client([
            'base_uri'        => $this->base_uri,
            'timeout'         => 10,
            'connect_timeout' => 10,
        ]));

        if ( ! empty($options['download_dir'])) {
            $this->telegram->setDownloadPath($options['download_dir']);
        }


//        if ($options['log_dir']) {
//            TelegramBot\TelegramLog::initialize(
//                // Main logger that handles all 'debug' and 'error' logs.
//                new Logger('telegram_bot', [
//                    (new StreamHandler("{$options['log_dir']}/tg_bot.debug.log", Logger::DEBUG))->setFormatter(new LineFormatter(null, null, true)),
//                    (new StreamHandler("{$options['log_dir']}/tg_bot.error.log", Logger::ERROR))->setFormatter(new LineFormatter(null, null, true)),
//                ]),
//
//                // Updates logger for raw updates.
//                new Logger('telegram_bot_updates', [
//                    (new StreamHandler("{$options['log_dir']}/tg_bot.info.log", Logger::INFO))->setFormatter(new LineFormatter('%message%' . PHP_EOL)),
//                ])
//            );
//        }
    }


    /**
     * @return array
     * @throws TelegramBot\Exception\TelegramException
     */
    public function getMessagesRaw(): array {

        $this->telegram->useGetUpdatesWithoutDatabase();
        $result = $this->telegram->handleGetUpdates();
        return $result->getRawData();
    }


    /**
     * Получение списка сообщений
     * @throws TelegramBot\Exception\TelegramException
     */
    public function getMessages(): array {

        $raw_data      = $this->getMessagesRaw();
        $chat_messages = [];

        if ( ! empty($raw_data) && ! empty($raw_data['result'])) {
            foreach ($raw_data['result'] as $item) {
                if ( ! empty($item['message']) &&
                     ! empty($item['message']['chat']) &&
                     ! empty($item['message']['chat']['id'])
                ) {
                    $chat_id = $item['message']['chat']['id'];

                    if (empty($chat_messages[$chat_id])) {
                        $chat_messages[$chat_id] = [
                            'chat_id'    => $chat_id,
                            'user_id'    => $item['message']['from']['id'] ?? null,
                            'first_name' => $item['message']['chat']['first_name'] ?? '',
                            'messages'   => [],
                        ];
                    }

                    if ( ! empty($item['message']['text'])) {
                        $message = [
                            'type'       => 'text',
                            'message_id' => $item['message']['message_id'] ?? null,
                            'date'       => $item['message']['date'] ?? null,
                            'text'       => $item['message']['text'],
                        ];

                    } elseif ( ! empty($item['message']['contact'])) {
                        if ( ! empty($item['message']['from']) &&
                             ! empty($item['message']['from']['id']) &&
                             ! empty($item['message']['contact']['user_id']) &&
                            $item['message']['from']['id'] == $item['message']['contact']['user_id']
                        ) {
                            $type = 'contact_me';

                        } else {
                            $type = 'contact';
                        }

                        $message = [
                            'type'       => $type,
                            'message_id' => $item['message']['message_id'] ?? null,
                            'date'       => $item['message']['date'] ?? null,
                            'contact'    => $item['message']['contact'],
                        ];

                    } elseif ( ! empty($item['message']['location'])) {
                        if ( ! empty($item['message']['reply_to_message']) &&
                             ! empty($item['message']['reply_to_message']['from']) &&
                             ! empty($item['message']['reply_to_message']['from']['username']) &&
                            $item['message']['reply_to_message']['from']['username'] == $this->telegram->getBotUsername()
                        ) {
                            $type = 'location_me';

                        } else {
                            $type = 'location';
                        }

                        $message = [
                            'type'       => $type,
                            'message_id' => $item['message']['message_id'] ?? null,
                            'date'       => $item['message']['date'] ?? null,
                            'location'   => $item['message']['location'],
                        ];

                    } elseif ( ! empty($item['message']['photo'])) {
                        $message = [
                            'type'       => 'photo',
                            'message_id' => $item['message']['message_id'] ?? null,
                            'date'       => $item['message']['date'] ?? null,
                            'photo'      => $item['message']['photo'],
                            'text'       => $item['message']['caption'] ?? '',
                        ];

                    } elseif ( ! empty($item['message']['document'])) {
                        $message = [
                            'type'       => 'document',
                            'message_id' => $item['message']['message_id'] ?? null,
                            'date'       => $item['message']['date'] ?? null,
                            'document'   => $item['message']['document'],
                            'text'       => $item['message']['caption'] ?? '',
                        ];

                    } else {
                        $message = $item['message'];
                    }


                    if ( ! empty($item['message']['media_group_id'])) {
                        if (empty($chat_messages[$chat_id]['messages'][$item['message']['media_group_id']])) {
                            $chat_messages[$chat_id]['messages'][$item['message']['media_group_id']] = [
                                'type'     => 'group',
                                'messages' => [],
                            ];
                        }

                        $chat_messages[$chat_id]['messages'][$item['message']['media_group_id']]['messages'][] = $message;

                    } else {
                        $chat_messages[$chat_id]['messages'][] = $message;
                    }


                } elseif ( ! empty($item['callback_query'])) {
                    $chat_id = $item['callback_query']['message']['chat']['id'];

                    if (empty($chat_messages[$chat_id])) {
                        $chat_messages[$chat_id]['user_id']  = $item['callback_query']['from']['id'] ?? null;
                        $chat_messages[$chat_id]['messages'] = [];
                    }

                    $chat_messages[$chat_id]['messages'][] = [
                        'type'    => 'callback_query',
                        'id'      => $item['callback_query']['id'] ?? null,
                        'data'    => $item['callback_query']['data'] ?? null,
                    ];
                }
            }
        }

        return $chat_messages;
    }


    /**
     * @param string $file_id
     * @return string
     * @throws TelegramBot\Exception\TelegramException
     */
    public function downloadFile(string $file_id): string {

        $response = TelegramBot\Request::getFile(['file_id' => $file_id]);

        if ($response->isOk()) {
            $file = $response->getResult();
            TelegramBot\Request::downloadFile($file);


            $tg_file_path  = $file->getFilePath();
            $download_path = $this->telegram->getDownloadPath() . '/' . $tg_file_path;

            register_shutdown_function(function () use ($download_path) {
                if (file_exists($download_path)) {
                    unlink($download_path);
                }
            });


            return $download_path;

        } else {
            throw new \Exception('Не удалось скачать файл');
        }
    }



    /**
     * @param int         $chat_id
     * @param Bot\Message $message
     * @return TelegramBot\Entities\ServerResponse
     * @throws TelegramBot\Exception\TelegramException
     * @throws \Exception
     */
    public function sendMessage(int $chat_id, Bot\Message $message): TelegramBot\Entities\ServerResponse {

        switch ($message->getType()) {
            case $message::TYPE_TEXT:             return $this->sendTextMessage($chat_id, $message); break;
            case $message::TYPE_BUTTONS:          return $this->sendInlineKeyboard($chat_id, $message); break;
            case $message::TYPE_LOCATION:         return $this->sendLocation($chat_id, $message->getLocation()); break;
            case $message::TYPE_REQUEST_CONTACT:  return $this->sendRequestContact($chat_id, $message->getMessageText(), $message->getBtnText()); break;
            case $message::TYPE_REQUEST_LOCATION: return $this->sendRequestLocation($chat_id, $message->getMessageText(), $message->getBtnText()); break;
            default:
                throw new \Exception('Некорректный тип сообщения');
        }
    }


    /**
     * Отправка сообщения
     * @param int         $chat_id
     * @param Bot\Message $message
     * @return TelegramBot\Entities\ServerResponse
     * @throws TelegramBot\Exception\TelegramException
     */
    private function sendTextMessage(int $chat_id, Bot\Message $message): TelegramBot\Entities\ServerResponse {

        $request = [
            'chat_id' => $chat_id,
            'text'    => $message->getMessageText(),
        ];


        if ($parse_mode = $message->getParseMode()) {
            $request['parse_mode'] = $parse_mode;
        }

        return TelegramBot\Request::sendMessage($request);
    }


    /**
     * Отправка сообщения с кнопками
     * @param int         $chat_id
     * @param Bot\Message $message
     * @return TelegramBot\Entities\ServerResponse
     * @throws TelegramBot\Exception\TelegramException
     */
    private function sendInlineKeyboard(int $chat_id, Bot\Message $message): TelegramBot\Entities\ServerResponse {

        $request = [
            'chat_id'      => $chat_id,
            'text'         => $message->getMessageText(),
            'reply_markup' => json_encode([
                'inline_keyboard'   => $message->getButtons(),
                'resize_keyboard'   => true,
                'one_time_keyboard' => true,
            ]),
        ];

        if ($parse_mode = $message->getParseMode()) {
            $request['parse_mode'] = $parse_mode;
        }

        return TelegramBot\Request::sendMessage($request);
    }


    /**
     * Отправка запроса на получение контакта (телефон и имя)
     * @param int    $chat_id
     * @param string $message_text
     * @param string $btn_text
     * @return TelegramBot\Entities\ServerResponse
     * @throws TelegramBot\Exception\TelegramException
     */
    private function sendRequestContact(int $chat_id, string $message_text, string $btn_text): TelegramBot\Entities\ServerResponse {

        $markup = [
            'keyboard' => [
                [
                    ['text' => $btn_text, 'request_contact' => true]
                ],
            ],
            'resize_keyboard'   => true,
            'one_time_keyboard' => true
        ];

        return TelegramBot\Request::sendMessage([
            'chat_id'      => $chat_id,
            'text'         => $message_text,
            'reply_markup' => json_encode($markup),
        ]);
    }


    /**
     * Отправка запроса на получение текущего местоположения
     * @param int    $chat_id
     * @param string $message_text
     * @param string $btn_text
     * @return TelegramBot\Entities\ServerResponse
     * @throws TelegramBot\Exception\TelegramException
     */
    private function sendRequestLocation(int $chat_id, string $message_text, string $btn_text): TelegramBot\Entities\ServerResponse {

        $markup = [
            'keyboard' => [
                [
                    ['text' => $btn_text, 'request_location' => true]
                ],
            ],
            'resize_keyboard'   => true,
            'one_time_keyboard' => true
        ];

        return TelegramBot\Request::sendMessage([
            'chat_id'      => $chat_id,
            'text'         => $message_text,
            'reply_markup' => json_encode($markup),
        ]);
    }


    /**
     * Отправка местоположения
     * @param int   $chat_id
     * @param array $location
     * @return TelegramBot\Entities\ServerResponse
     * @throws TelegramBot\Exception\TelegramException
     */
    private function sendLocation(int $chat_id, array $location): TelegramBot\Entities\ServerResponse {

        return TelegramBot\Request::sendLocation([
            'chat_id'   => $chat_id,
            'latitude'  => $location[0],
            'longitude' => $location[1],
        ]);
    }
}