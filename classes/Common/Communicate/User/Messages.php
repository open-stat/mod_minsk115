<?php
namespace Core2\Mod\Minsk115\Common\Communicate\User;
use \Core2\Mod\Minsk115\Common\Bot;


/**
 *
 */
class Messages {

    /**
     * @param string $author_name
     * @return Bot\Message
     */
    public static function startMessage(string $author_name): Bot\Message {

        $message = new Bot\Message('text', "Здравствуйте {$author_name}. Нажмите на /new чтобы добавить заявку или выберите пункт меню");

        return $message;
    }


    /**
     * @return Bot\Message
     */
    public static function orderDeleteCompleted(): Bot\Message {

        $message = new Bot\Message('text', "Заявка удалена");

        return $message;
    }


    /**
     * @param string $name
     * @return Bot\Message
     */
    public static function changeNameSave(string $name): Bot\Message {

        $message = new Bot\Message('text', "Готово {$name}. Имя изменено");

        return $message;
    }


    /**
     * @return Bot\Message
     */
    public static function changeNameError(): Bot\Message {

        $message = new Bot\Message('text', "Изменение имени.\nУказано некорректное имя. Укажите что-нибудь в пределах от 2 до 30 символов.");;

        return $message;
    }


    /**
     * @return Bot\Message
     */
    public static function changeNameStart(): Bot\Message {

        return new Bot\Message('text', "Изменение имени.\nУкажите как вас называть.");
    }


    /**
     * @param string $name
     * @return Bot\Message
     */
    public static function changeNameConfirm(string $name): Bot\Message {

        $message = new Bot\Message('buttons', "Изменение имени.\nПодтвердите, вас называть \"{$name}\"? Если нет, то укажите другой вариант.");
        $message->setButtons([
            [
                ['text' => 'Подтверждаю', 'callback_data' => "{\"type\": \"save_name\", \"name\":\"{$name}\"}",]
            ]
        ]);

        return $message;
    }


    /**
     * @return Bot\Message
     */
    public static function feedbackError(): Bot\Message {

        return new Bot\Message('text', "Обратная связь.\n\nСообщение должно быть в пределах от 5 до 1000 символов.");
    }


    /**
     * @return Bot\Message
     */
    public static function feedbackSendStart(): Bot\Message {

        $message = new Bot\Message('text', "Обратная связь.\nНапишите ваше сообщение.");

        return $message;
    }


    /**
     * @return Bot\Message
     */
    public static function getOrderStateError(): Bot\Message {

        return new Bot\Message('text', "Получение состояния заявки.\n\nID или номер заявки должен быть до 30 символов.");
    }


    /**
     * @return Bot\Message
     */
    public static function getOrderStatusEmpty(): Bot\Message {

        return new Bot\Message('text', "Указанная заявка не найдена. Проверьте правильность введенных данных");
    }


    /**
     * @return Bot\Message
     */
    public static function getOrderStatusAuthor(): Bot\Message {

        return new Bot\Message('text', "Указанная заявка не принадлежит вам");
    }


    /**
     * @return Bot\Message
     */
    public static function createOrderTextError(): Bot\Message {

        return new Bot\Message('text', "Создание заявки.\n\nОписание проблемы слишком длинное. Сократите его хотя бы до 500 символов.");
    }


    /**
     * @return Bot\Message
     */
    public static function createOrderPhotoErrorFormat(): Bot\Message {

        return new Bot\Message('text', "Создание заявки.\n\nЗагруженный файл некорректен, нужно загружать фото PNG или JPG.");
    }


    /**
     * @return Bot\Message
     */
    public static function createOrderPhotoErrorSize(): Bot\Message {

        return new Bot\Message('text', "Создание заявки.\n\nЗагруженный файл должен быть не более 10Мб");
    }


    /**
     * @return Bot\Message
     */
    public static function setOrderText(): Bot\Message {

        return new Bot\Message('text', "✏ Описание задано");
    }


    /**
     * @return Bot\Message
     */
    public static function createOrderLocationError(): Bot\Message {

        return new Bot\Message('text', "Создание заявки.\n\nНе удалось распознать местоположение, попробуйте снова.");
    }


    /**
     *
     * @param string $order_id
     * @return Bot\Message
     */
    public static function createOrder(string $order_id): Bot\Message {

        return new Bot\Message('text', "Заявка отправлена в 115. ID - {$order_id}");
    }


    /**
     * @param string $order_id
     * @return Bot\Message
     */
    public static function createOrderModerate(string $order_id): Bot\Message {

        return new Bot\Message('text', "Заявка отправлена на модерацию. ID - {$order_id}");
    }


    /**
     * @return Bot\Message
     * @throws \Exception
     */
    public static function createOrderConfirm(): Bot\Message {

        $message = new Bot\Message('buttons', "✅ *Все данные для отправки заявки заполнены*\n _При необходимость вы можете дополнить фотографии, заменить местоположение или описание_");
        $message->setParseMode($message::PARSE_MODE_MARKDOWN);
        $message->setButtons([
            [
                ['text' => 'Отправить заявку', 'callback_data' => '{"type": "create_order"}'],
                ['text' => 'Удалить заявку', 'callback_data' => '{"type": "clear_state"}'],
            ]
        ]);
        return $message;
    }


    /**
     * @return Bot\Message
     */
    public static function commandIncorrect(): Bot\Message {

        return new Bot\Message('text', "Команда не распознана. Выберите пункт меню");
    }


    /**
     * @return Bot\Message
     */
    public static function errorMessage(): Bot\Message {

        $message = new Bot\Message('text', "Что-то не то. Давайте еще раз. Выберите пункт меню");

        return $message;
    }


    /**
     * @return Bot\Message
     */
    public static function feedbackSend(): Bot\Message {

        $message = new Bot\Message('text', "Сообщение отправлено.");

        return $message;
    }


    /**
     * @param array $orders
     * @return Bot\Message
     * @throws \Exception
     */
    public static function getOrderStatusStart(array $orders): Bot\Message {

        if ($orders) {
            $orders_text = [];

            foreach ($orders as $order) {
                $user_comment = mb_strlen($order['user_comment']) > 20
                    ? mb_substr($order['user_comment'], 0, 20) . '...'
                    : $order['user_comment'];

                $orders_text[] = "/status_{$order['id']} [{$order['status']}] {$user_comment}";
            }

            return new Bot\Message('text', "Список ваших заявок:\n" . implode("\n", $orders_text) . "\n\nДля более детального описания нажмите на ссылку напротив заявки");

        } else {
            return new Bot\Message('text', "Вы пока еще не отправляли заявки");
        }
    }


    /**
     * @param \Zend_Db_Table_Row_Abstract      $order
     * @param string                           $status_title
     * @param \Zend_Db_Table_Row_Abstract|null $order_comment
     * @return Bot\Message
     */
    public static function getOrderStatus(
        \Zend_Db_Table_Row_Abstract $order,
        string $status_title,
        \Zend_Db_Table_Row_Abstract $order_comment = null
    ): Bot\Message {

        $order_nmbr = $order->nmbr ?: 'б/н';
        $message    = new Bot\Message('text');

        if ($order->status == 'rejected') {
            $message->setMessageText(implode("\n", [
                "Заявка ID {$order->id}",
                "Состояние: {$status_title}.",
                "Причина отклонения: {$order->moderate_message}.",
            ]));

        } elseif ($order_comment) {
            $message->setMessageText(implode("\n", [
                "Заявка ID {$order->id}",
                "Состояние: {$status_title}",
                "Номер: {$order_nmbr}\n",
                "Последний комментарий из 115",
                $order_comment->creator,
                $order_comment->comment
            ]));

        } else {
            $message->setMessageText(implode("\n", [
                "Заявка ID {$order->id}",
                "Состояние: {$status_title}",
                "Номер: {$order_nmbr}",
                "Комментарии от 115 отсутствуют"
            ]));
        }



        return $message;
    }


    /**
     * @return Bot\Message
     */
    public static function help(): Bot\Message {

        $message = new Bot\Message('text');
        $message->setMessageText(implode("\n", [
            "Этот бот дает возможность отправлять заявки в 115.бел без необходимости регистрации.",
            "Для отправки заявки нажмите /new, загрузите фотографии проблемы, опишите проблему, подтвердите отправку. ",
            "Дополнительно, укажите геолокацию места проблемы, если ее не удастся получить по координатам из фотографии.",
            "После подачи заявки вы сможете получить ее текущее состояние, для этого нажмите на /status.",
            "Жалобы и предложения можно писать в обратную связь /feedback.",
        ]));

        return $message;
    }


    /**
     * @return Bot\Message
     * @throws \Exception
     */
    public static function createOrderStart(): Bot\Message {

        $message = new Bot\Message('text');
        $message->setMessageText(implode("\n", [
            "Создание заявки:",
            "1⃣  *Отправьте фотографии проблемы* \n_Желательно так, чтобы проблему можно было легко найти. До 3х фото_\n",
            "2⃣  *Отправьте геолокацию* \n_Ее можно добавить через мобильное приложение_\n",
            "3⃣  *Опишите проблему* \n_Например: яма на дороге, отслоение плитки или не горит лампочка в подъезде_",
        ]));
        $message->setParseMode($message::PARSE_MODE_MARKDOWN);
        return $message;
    }


    /**
     * @param bool $id_user_comment
     * @param bool $is_location
     * @return Bot\Message
     * @throws \Exception
     */
    public static function addOrderPhoto(bool $id_user_comment, bool $is_location): Bot\Message {

        $message_text = "🌄 Фото добавлено";

        if ($id_user_comment) {
            $message_text .= "\n✏ Описание задано";
        }
        if ($is_location) {
            $message_text .= "\n🌍 местоположение задано";
        }

        return new Bot\Message('text', $message_text);
    }


    /**
     * @param int  $count_load_photos
     * @param bool $id_user_comment
     * @param bool $is_location
     * @return Bot\Message
     * @throws \Exception
     */
    public static function addOrderPhotoGroup(int $count_load_photos, bool $id_user_comment, bool $is_location): Bot\Message {

        $message_text = "🌄 Фото добавлено: {$count_load_photos}";

        if ($id_user_comment) {
            $message_text .= "\n✏ Описание задано";
        }
        if ($is_location) {
            $message_text .= "\n🌍 Местоположение задано";
        }

        return new Bot\Message('text', $message_text);
    }


    /**
     * @param int $count_load_error
     * @return Bot\Message
     * @throws \Exception
     */
    public static function addOrderPhotoGroupError(int $count_load_error): Bot\Message {

        return new Bot\Message('text', implode("\n", [
            "Не удалось добавить фото: {$count_load_error}",
            "Проверьте корректность файлов. Возможно они слишком большие (более 10 мб)"
        ]));
    }


    /**
     * @return Bot\Message
     */
    public static function setOrderLocation(): Bot\Message {

        return new Bot\Message('text', "🌍 местоположение задано");
    }


    /**
     * @param string $text
     * @return Bot\Message
     * @throws \Exception
     */
    public static function debug(string $text): Bot\Message {

        $message = new Bot\Message('text', $text);

        return $message;
    }


    /**
     * @param string $lat
     * @param string $lng
     * @return Bot\Message
     */
    public static function location(string $lat, string $lng): Bot\Message {

        $message = new Bot\Message('location');
        $message->setLocation($lat, $lng);

        return $message;
    }


    /**
     * @param bool $is_description
     * @param bool $is_location
     * @param bool $is_photos
     * @return Bot\Message
     * @throws \Exception
     */
    public static function nextSteps(bool $is_description, bool $is_location, bool $is_photos): Bot\Message {

        $text = "Осталось заполнить:";

        if ( ! $is_description) {
            $text .= "\n✏ Описание проблемы";
        }

        if ( ! $is_location) {
            $text .= "\n🌍 Местоположение проблемы";
        }

        if ( ! $is_photos) {
            $text .= "\n🌄 Фотографию";
        }


        $message = new Bot\Message('text', $text);

        return $message;
    }
}