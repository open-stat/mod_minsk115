<?php
namespace Core2\Mod\Minsk115\Common\Communicate\User;
use \Core2\Mod\Minsk115\Common\Bot;
use \Core2\Mod\Minsk115;


/**
 * @property  \ModMinsk115Controller $modMinsk115
 */
class Controller extends \Common {

    /**
     * @var \Zend_Db_Table_Row_Abstract
     */
    private $author;

    /**
     * @var Bot Minsk115\Common\Bot
     */
    private $bot;


    /**
     * @param \Zend_Db_Table_Row_Abstract $author
     * @param Bot                         $bot
     */
    public function __construct(\Zend_Db_Table_Row_Abstract $author, Minsk115\Common\Bot $bot) {
        parent::__construct();

        $this->author = $author;
        $this->bot    = $bot;
    }


    /**
     * @param array $messages
     * @return array
     * @throws \Longman\TelegramBot\Exception\TelegramException
     * @throws \Zend_Db_Table_Row_Exception
     * @throws \Exception
     */
    public function getAnswer(array $messages): array {

        $answer  = [];

        foreach ($messages as $message) {
            switch ($message['type']) {
                case 'text':
                    if ($message['text']) {
                        if ($message['text'] == '/start') {
                            $this->setBotState(null);
                            $this->deleteOrder();
                            $answer[] = Messages::startMessage($this->author->name);

                        } elseif ($message['text'] == '/new') {
                            $this->setBotState('create_order');
                            $answer[] = Messages::createOrderStart();
                            break;

                        } elseif ($message['text'] == '/status') {
                            $orders = $this->getOrders($this->author->id);
                            $answer[] = Messages::getOrderStatusStart($orders);
                            break;

                        } elseif (preg_match('~^/status_(\d+)$~', $message['text'], $match)) {
                            $order = $this->modMinsk115->dataMinsk115Orders->getRowByIdNmbr($match[1]);

                            if ($order->author_id != $this->author->id) {
                                $answer[] = Messages::getOrderStatusAuthor();

                            } else {
                                if ($order) {
                                    $status_title  = $this->modMinsk115->dataMinsk115Orders->getStatus($order->status);
                                    $order_history = $this->modMinsk115->dataMinsk115OrdersComments->getRowLastByOrderId($order->id);
                                    $answer[] = Messages::getOrderStatus($order, $status_title, $order_history);

                                } else {
                                    $answer[] = Messages::getOrderStatusEmpty();
                                }
                            }

                        } elseif ($message['text'] == '/feedback') {
                            $this->setBotState('feedback_write');
                            $answer[] = Messages::feedbackSendStart();
                            break;

                        } elseif ($message['text'] == '/my_name') {
                            $this->setBotState('edit_name');
                            $answer[] = Messages::changeNameStart();
                            break;

                        } elseif ($message['text'] == '/help') {
                            $answer[] = Messages::help();
                            break;

                        } else {
                            switch ($this->author->bot_state) {
                                case 'edit_name':
                                    if (mb_strlen($message['text']) < 2 || mb_strlen($message['text']) > 30) {
                                        $answer[] = Messages::changeNameError();

                                    } else {
                                        $name = str_replace('"', '', $message['text']);
                                        $name = trim($name ?? '');

                                        if ($name) {
                                            $this->setBotState(null);
                                            $this->changeNameSave($name);
                                            $answer[] = Messages::changeNameSave($name);

                                        } else {
                                            $answer[] = Messages::changeNameError();
                                        }
                                    }
                                    break;

                                case 'feedback_write':
                                    if (mb_strlen($message['text']) < 5 || mb_strlen($message['text']) > 1000) {
                                        $answer[] = Messages::feedbackError();

                                    } else {
                                        $this->feedbackSend($message['text']);
                                        $answer[] = Messages::feedbackSend();
                                    }
                                    break;

                                case 'create_order':
                                    if (mb_strlen($message['text']) > 500) {
                                        $answer[] = Messages::createOrderTextError();
                                    } else {
                                        $this->setOrderText($message['text']);
                                        $answer[] = Messages::setOrderText();


                                        if ($this->checkOrderParams()) {
                                            $answer[] = Messages::createOrderConfirm();

                                        } else {
                                            $is_description = $this->isDescription();
                                            $is_location    = $this->isLocation();
                                            $is_photos      = $this->isPhotos();

                                            if ( ! $is_description || ! $is_location || ! $is_photos) {
                                                $answer[] = Messages::nextSteps($is_description, $is_location, $is_photos);
                                            }
                                        }
                                    }
                                    break;
                            }
                        }
                    }
                    break;

                case 'photo':
                    switch ($this->author->bot_state) {
                        case 'create_order':
                            $photo = end($message['photo']);
                            if (empty($photo['file_size']) || $photo['file_size'] > (10 * 1024 * 1024)) {
                                $answer[] = Messages::createOrderPhotoErrorSize();

                            } else {
                                $photo_path = $this->bot->downloadFile($photo['file_id']);
                                $this->addOrderPhoto($photo_path, $message['text'] ?? '');
                                $location = $this->setOrderLocationByPhoto($photo_path);

                                $answer[] = Messages::addOrderPhoto( ! empty($message['text']), ! empty($location));

                                if ($location) {
                                    $answer[] = Messages::location($location[0], $location[1]);
                                }


                                if ($this->checkOrderParams()) {
                                    $answer[] = Messages::createOrderConfirm();

                                } else {
                                    $is_description = $this->isDescription();
                                    $is_location    = $this->isLocation();
                                    $is_photos      = $this->isPhotos();

                                    if ( ! $is_description || ! $is_location || ! $is_photos) {
                                        $answer[] = Messages::nextSteps($is_description, $is_location, $is_photos);
                                    }
                                }
                            }
                            break;
                    }
                    break;

                case 'document':
                    switch ($this->author->bot_state) {
                        case 'create_order':
                            if ( ! empty($message['document']['mime_type'])) {
                                if (preg_match('~^image/(jpg|jpeg|png)~', $message['document']['mime_type'])) {
                                    if (empty($message['document']['file_size']) || $message['document']['file_size'] > (10 * 1024 * 1024)) {
                                        $answer[] = Messages::createOrderPhotoErrorSize();

                                    } else {
                                        $photo_path = $this->bot->downloadFile($message['document']['file_id']);
                                        $this->addOrderPhoto($photo_path, $message['text'] ?? '');
                                        $location = $this->setOrderLocationByPhoto($photo_path);

                                        $answer[] = Messages::addOrderPhoto( ! empty($message['text']), ! empty($location));

                                        if ($location) {
                                            $answer[] = Messages::location($location[0], $location[1]);
                                        }


                                        if ($this->checkOrderParams()) {
                                            $answer[] = Messages::createOrderConfirm();

                                        } else {
                                            $is_description = $this->isDescription();
                                            $is_location    = $this->isLocation();
                                            $is_photos      = $this->isPhotos();

                                            if ( ! $is_description || ! $is_location || ! $is_photos) {
                                                $answer[] = Messages::nextSteps($is_description, $is_location, $is_photos);
                                            }
                                        }
                                    }

                                } else {
                                    $answer[] = Messages::createOrderPhotoErrorFormat();
                                }

                            } else {
                                $answer[] = Messages::createOrderPhotoErrorFormat();
                            }

                            break;
                    }
                    break;

                case 'location':
                    switch ($this->author->bot_state) {
                        case 'create_order':
                            if (empty($message['location']) ||
                                empty($message['location']['latitude']) ||
                                empty($message['location']['longitude'])
                            ) {
                                $answer[] = Messages::createOrderLocationError();

                            } else {
                                $this->setOrderLocation($message['location']['latitude'], $message['location']['longitude']);
                                $answer[] = Messages::setOrderLocation();


                                if ($this->checkOrderParams()) {
                                    $answer[] = Messages::createOrderConfirm();

                                } else {
                                    $is_description = $this->isDescription();
                                    $is_location    = $this->isLocation();
                                    $is_photos      = $this->isPhotos();

                                    if ( ! $is_description || ! $is_location || ! $is_photos) {
                                        $answer[] = Messages::nextSteps($is_description, $is_location, $is_photos);
                                    }
                                }
                            }
                            break;
                    }
                    break;

                case 'group':
                    switch ($this->author->bot_state) {
                        case 'create_order':
                            $is_set_text       = false;
                            $photo_location    = [];
                            $count_load_error  = 0;
                            $count_load_photos = 0;

                            foreach ($message['messages'] as $group_message) {
                                switch ($group_message['type']) {
                                    case 'photo':
                                        $photo = end($group_message['photo']);
                                        if (empty($photo['file_size']) || $photo['file_size'] > (10 * 1024 * 1024)) {
                                            $count_load_error++;

                                        } else {
                                            $photo_path = $this->bot->downloadFile($photo['file_id']);
                                            $this->addOrderPhoto($photo_path, $group_message['text'] ?? '');
                                            $location = $this->setOrderLocationByPhoto($photo_path);

                                            if ( ! empty($group_message['text'])) {
                                                $is_set_text = true;
                                            }
                                            if ( ! empty($location)) {
                                                $photo_location = $location;
                                            }
                                            $count_load_photos++;
                                        }
                                        break;

                                    case 'document':
                                        if (preg_match('~^image/(jpg|jpeg|png)~', $group_message['document']['mime_type'])) {
                                            if (empty($group_message['document']['file_size']) || $group_message['document']['file_size'] > (10 * 1024 * 1024)) {
                                                $count_load_error++;

                                            } else {
                                                $photo_path = $this->bot->downloadFile($group_message['document']['file_id']);
                                                $this->addOrderPhoto($photo_path, $group_message['text'] ?? '');
                                                $location = $this->setOrderLocationByPhoto($photo_path);


                                                if ( ! empty($group_message['text'])) {
                                                    $is_set_text = true;
                                                }
                                                if ( ! empty($location)) {
                                                    $photo_location = $location;
                                                }
                                                $count_load_photos++;
                                            }

                                        } else {
                                            $count_load_error++;
                                        }
                                        break;
                                }
                            }

                            $answer[] = Messages::addOrderPhotoGroup($count_load_photos, $is_set_text, !! $photo_location);

                            if ($photo_location) {
                                $answer[] = Messages::location($photo_location[0], $photo_location[1]);
                            }

                            if ($count_load_error) {
                                $answer[] = Messages::addOrderPhotoGroupError($count_load_error);
                            }

                            if ($this->checkOrderParams()) {
                                $answer[] = Messages::createOrderConfirm();

                            } else {
                                $is_description = $this->isDescription();
                                $is_location    = $this->isLocation();
                                $is_photos      = $this->isPhotos();

                                if ( ! $is_description || ! $is_location || ! $is_photos) {
                                    $answer[] = Messages::nextSteps($is_description, $is_location, $is_photos);
                                }
                            }
                            break;
                    }
                    break;

                case 'callback_query':
                    if (isset($message['data'])) {
                        $data = @json_decode($message['data'], true);

                        if (json_last_error() !== JSON_ERROR_NONE) {
                            $type = 'error';
                        } else {
                            $type = $data['type'] ?? 'error';
                        }
                    }

                    switch ($type) {
                        case 'create_order':
                            $order_id = $this->createOrder();

                            if ($order_id) {
                                if ($this->author->is_no_moderate_sw == 'Y') {
                                    $answer[] = Messages::createOrder($order_id);
                                } else {
                                    $answer[] = Messages::createOrderModerate($order_id);
                                }
                            }
                            break;

                        case 'clear_state':
                            $this->setBotState();
                            $this->deleteOrder();
                            $answer[] = Messages::orderDeleteCompleted();
                            break;

                        default:
                            $answer[]= Messages::errorMessage();
                    }
                    break;
            }
        }


        if (empty($answer)) {
            $answer[] = Messages::commandIncorrect();
        }


        return $answer;
    }


    /**
     * @param string $name
     * @return void
     */
    private function changeNameSave(string $name) {

        $this->author->name = $name;
        $this->author->save();
    }


    /**
     * @param string $feedback_message
     * @return void
     * @throws \Longman\TelegramBot\Exception\TelegramException
     */
    private function feedbackSend(string $feedback_message) {

        $feedback_message = "Сообщение из обратной связи от пользователя {$this->author->name} - ID {$this->author->id}:\n\n{$feedback_message}";

        $model = new Minsk115\Authors\Model();
        $model->sendMessageAdmins($feedback_message);

        $this->author->bot_state = null;
        $this->author->save();
    }


    /**
     * @return void
     */
    private function getOrderStateStart() {

        $this->author->bot_state = 'get_order_state';
        $this->author->save();
    }


    /**
     * @param string|null $bot_state
     * @return void
     */
    private function setBotState(string $bot_state = null): void {

        $this->author->bot_state = $bot_state;
        $this->author->save();
    }


    /**
     * @return void
     */
    private function createOrderStart() {

        $this->author->bot_state = 'create_order';
        $this->author->save();
    }


    /**
     * @param string $message_text
     * @return void
     */
    private function setOrderText(string $message_text): void {

        $draft = new Minsk115\Index\Draft($this->author->id);
        $draft->setUserComment($message_text);
    }


    /**
     * @param string $photo_path
     * @param string $user_comment
     * @return void
     * @throws \Zend_Db_Table_Row_Exception
     */
    private function addOrderPhoto(string $photo_path, string $user_comment = ''): void {

        $draft = new Minsk115\Index\Draft($this->author->id);
        $draft->addPhoto($photo_path, $user_comment);
    }


    /**
     * @param $photo_path
     * @return array
     */
    private function setOrderLocationByPhoto($photo_path): array {

        $draft    = new Minsk115\Index\Draft($this->author->id);
        $location = [];

        if ( ! $draft->issetCoordinates()) {
            $coordinates = $draft->getCoordinates($photo_path);

            if ($coordinates &&
                isset($coordinates[0]) &&
                isset($coordinates[1])
            ) {
                $location = [$coordinates[0], $coordinates[1]];
                $draft->setLocation($coordinates[0], $coordinates[1]);
            }
        }

        return $location;
    }


    /**
     * @param string $lat
     * @param string $lng
     * @return void
     */
    private function setOrderLocation(string $lat, string $lng) {

        $draft = new Minsk115\Index\Draft($this->author->id);
        $draft->setLocation($lat, $lng);
    }


    /**
     * @return int
     * @throws \Longman\TelegramBot\Exception\TelegramException
     * @throws \Zend_Config_Exception
     */
    private function createOrder(): int {

        $draft = new Minsk115\Index\Draft($this->author->id);
        $order = $draft->getOrder();

        if ($order) {
            if ($this->author->is_no_moderate_sw == 'Y') {
                $draft->doModerate115();
            } else {
                $draft->doModerate();
            }

            if ($this->author->is_admin_sw != 'Y') {
                $model = new Minsk115\Authors\Model();
                $model->sendMessageAdmins("Создана новая заявка.\nАвтор: {$this->author->name}.\nТема: {$order->user_comment}");
            }

            return $order->id;

        } else {
            return 0;
        }
    }


    /**
     * @return bool
     * @throws \Exception
     */
    private function checkOrderParams(): bool {

        $draft = new Minsk115\Index\Draft($this->author->id);
        return $draft->checkOrderParams();
    }


    /**
     * @return bool
     */
    private function isDescription(): bool {

        $draft = new Minsk115\Index\Draft($this->author->id);
        return $draft->checkDescription();
    }


    /**
     * @return bool
     */
    private function isLocation(): bool {

        $draft = new Minsk115\Index\Draft($this->author->id);
        return $draft->checkLocation();
    }


    /**
     * @return bool
     */
    private function isPhotos(): bool {

        $draft = new Minsk115\Index\Draft($this->author->id);
        return $draft->checkPhotos();
    }


    /**
     * @return void
     * @throws \Exception
     */
    private function deleteOrder() {

        $draft = new Minsk115\Index\Draft($this->author->id);
        $draft->delete();
    }


    /**
     * @param int $author_id
     * @return array
     */
    private function getOrders(int $author_id): array {

        $orders_rows = $this->modMinsk115->dataMinsk115Orders->getRowsBuAuthorId($author_id);
        $orders      = [];

        foreach ($orders_rows as $orders_row) {
            $orders[] = [
                'id'           => $orders_row->id,
                'status'       => $this->modMinsk115->dataMinsk115Orders->getStatus($orders_row->status),
                'user_comment' => $orders_row->user_comment,
            ];
        }

        return $orders;
    }
}