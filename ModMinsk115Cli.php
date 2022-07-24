<?php
use \Core2\Mod\Minsk115;

require_once DOC_ROOT . 'core2/inc/classes/Common.php';

require_once 'classes/autoload.php';



/**
 * @property \ModMinsk115Controller $modMinsk115
 */
class ModMinsk115Cli extends Common {


    /**
     * Синхронизация заявок
     * @return void
     * @throws Zend_Config_Exception
     * @throws \GuzzleHttp\Exception\GuzzleException
     * @throws \Longman\TelegramBot\Exception\TelegramException
     */
    public function syncRequests() {

        $config = $this->getModuleConfig('minsk115');

        if ( ! $config?->app?->login ||
             ! $config?->app?->pass ||
             ! $config?->app?->token
        ) {
            return;
        }


        $cabinet115 = new OpenDataWorld\Cabinet115(
            $config->app->login,
            $config->app->pass,
            $config->app->token,
        );


        try {
            $orders = $cabinet115->getOrders();

        } catch (\Exception $e) {
            if (strpos($e->getMessage(), 'SSL_ERROR_SYSCALL') === false) {
                throw $e;
            } else {
                $orders = [];
            }
        }


        $statuses = [
            'Новая заявка'   => 'new',
            'В работе'       => 'in_process',
            'На контроле'    => 'active',
            'Заявка закрыта' => 'closed',
            'Отклонено'      => 'closed',
        ];


        foreach ($orders as $order) {
            if (empty($order['id_request'])) {
                continue;
            }

            $order_row = $this->modMinsk115->dataMinsk115Orders->getRowByExtId($order['id_request']);

            if ( ! $order_row) {
                $order_row = $this->modMinsk115->dataMinsk115Orders->createRow([
                    'ext_id'       => $order['id_request'],
                    'ext_city_id'  => $order['id_city'],
                    'status'       => $statuses[$order['status']] ?? 'new',
                    'nmbr'         => $order['cc_id'] ?? '',
                    'subject'      => $order['subject'] ?? '',
                    'user_comment' => $order['user_comment'] ?? '',
                    'result_text'  => $order['org_comment'] ?? '',
                    'address'      => $order['address'] ?? '',
                    'lat'          => $order['lat'] ?? '',
                    'lng'          => $order['lng'] ?? '',
                    'rating'       => $order['rating'] ?? 0,
                ]);


            } else {
                $order_status    = $statuses[$order['status']] ?? 'new';
                $is_order_closed = $order_row->status != 'closed' && $order_status == 'closed';

                $order_row->ext_city_id = $order['id_city'];
                $order_row->nmbr        = $order['cc_id'] ?? '';
                $order_row->status      = $order_status;
                $order_row->subject     = $order['subject'] ?? '';
                $order_row->result_text = $order['org_comment'] ?? '';
                $order_row->address     = $order['address'] ?? '';
                $order_row->lat         = $order['lat'] ?? '';
                $order_row->lng         = $order['lng'] ?? '';
                $order_row->rating      = $order['rating'] ?? 0;
            }

            $order_row->save();


            if ( ! empty($is_order_closed) && $order_row->author_id) {
                $author = $this->modMinsk115->dataMinsk115Authors->find($order_row->author_id)->current();

                if ( ! empty($author)) {
                    $title   = $order_row->subject ?: $order_row->user_comment;
                    $comment = $this->modMinsk115->dataMinsk115OrdersComments->getRowLastByOrderId($order_row->id);

                    (new Minsk115\Authors\Model())
                        ->sendMessageText($author, implode("\n", [
                        'Ваша заявка была закрыта',
                        "Описание заявки {$title}",
                        "Комментарий исполнителя: {$comment->comment}",
                    ]));
                }
            }



            $user_images = explode(':', $order['user_images'] ?? '');

            foreach ($user_images as $user_image_id) {
                if ($user_image_id) {
                    $image = $this->modMinsk115->dataMinsk115OrdersFiles->getRowsByExtId($user_image_id);

                    if (empty($image)) {
                        $image_content = $cabinet115->getImageContent($user_image_id);
                        $this->modMinsk115->dataMinsk115OrdersFiles->createPhotoByContent($order_row->id, $image_content, [
                            'ext_id'  => $user_image_id,
                            'fieldid' => 'photo_org',
                            'status'  => 'completed',
                        ]);
                    }
                }
            }


            $org_images = explode(':', $order['org_images'] ?? '');

            foreach ($org_images as $org_image_id) {
                if ($org_image_id) {
                    $image = $this->modMinsk115->dataMinsk115OrdersFiles->getRowsByExtId($org_image_id);

                    if (empty($image)) {
                        $image_content = $cabinet115->getImageContent($org_image_id);
                        $this->modMinsk115->dataMinsk115OrdersFiles->createPhotoByContent($order_row->id, $image_content, [
                            'ext_id'  => $org_image_id,
                            'fieldid' => 'photo_org',
                            'status'  => 'completed',
                        ]);
                    }
                }
            }
        }
    }


    /**
     * Синхронизация комментариев по заявкам
     * @return void
     * @throws Zend_Config_Exception
     * @throws \GuzzleHttp\Exception\GuzzleException
     */
    public function syncRequestsComments() {

        $config = $this->getModuleConfig('minsk115');

        if ( ! $config?->app?->login ||
             ! $config?->app?->pass ||
             ! $config?->app?->token
        ) {
            return;
        }


        $cabinet115 = new OpenDataWorld\Cabinet115(
            $config->app->login,
            $config->app->pass,
            $config->app->token,
        );

        $where   = [];
        $where[] = "(status != 'closed' OR DATE_FORMAT(date_last_update, '%Y-%m-%d') = DATE_FORMAT(NOW(), '%Y-%m-%d'))";
        $where[] = "ext_id IS NOT NULL";

        $orders   = $this->modMinsk115->dataMinsk115Orders->fetchAll($where);
        $statuses = [
            'Заявка успешно прошла модерацию' => 'new',
            'Новая заявка'                    => 'new',
            'Заявка закрыта'                  => 'closed',
            'В план текущего ремонта'         => 'plan',
            'moderate_115'                    => 'moderate_115',
        ];

        foreach ($orders as $order) {
            if (empty($order->ext_id)) {
                continue;
            }

            try {
                $comments = $cabinet115->getOrderComments((int)$order->ext_id);

            } catch (\Exception $e) {
                if (strpos($e->getMessage(), 'SSL_ERROR_SYSCALL') === false) {
                    throw $e;
                } else {
                    $comments = [];
                }
            }

            foreach ($comments as $comment) {
                if ( ! isset($comment['id_ral'])) {
                    continue;
                }

                $order_history = $this->modMinsk115->dataMinsk115OrdersComments->getRowByOrderIdExtId((int)$order->id, (int)$comment['id_ral']);

                if ( ! $order_history) {
                    $status         = '';
                    $emergency_mark = null;
                    $date_event     = null;
                    $comment_text   = $comment['comments'] ?? '';

                    if ($comment_text) {
                        if ($comment['id_ral'] != -2) {
                            if (preg_match('~.*Заявка успешно прошла модерацию.*~', $comment_text, $math)) {
                                $status = 'Заявка успешно прошла модерацию';

                            } elseif (preg_match('~.*Статус заявки: ([^\.]*)~', $comment_text, $math)) {
                                $status = $math[1] ?? '';
                            }

                        } else {
                            $status = 'moderate_115';
                        }

                        if (preg_match('~.*Отметка аварийной службы: ([^\.]*)~', $comment_text, $math)) {
                            $emergency_mark = $math[1] ?? '';
                        }
                    }

                    if ( ! empty($comment['date_created'])) {
                        $date_event = date('Y-m-d H:i:s', strtotime($comment['date_created']));
                    }

                    $order_history = $this->modMinsk115->dataMinsk115OrdersComments->createRow([
                        'order_id'       => $order->id,
                        'ext_id'         => $comment['id_ral'],
                        'status'         => $statuses[$status] ?? $status,
                        'creator'        => trim($comment['creator'] ?? ''),
                        'emergency_mark' => $emergency_mark ?: '',
                        'comment'        => trim($comment_text),
                        'date_event'     => $date_event ?? null,
                    ]);

                    $order_history->save();
                }
            }
        }
    }


    /**
     * Общение с телеграм ботом
     * @throws Exception
     */
    public function answerBot() {

        $config = $this->getModuleConfig('minsk115');
        $token  = $config?->bot?->tg?->token;

        $bot = new Minsk115\Common\Bot($token, [
            'bot_username' => $config?->bot?->tg?->bot_username ?? '',
            'download_dir' => $config?->bot?->tg?->download_dir ?: $this->config->temp,
            'log_dir'      => $config?->bot?->tg?->log_dir ?: dirname(realpath($this->config?->log?->path)),
        ]);

        $seconds = 55;
        $start   = time();


        while ($seconds >= (time() - $start)) {
            try {
                $messages = $bot->getMessages();

                if ( ! empty($messages)) {
                    foreach ($messages as $chat_id => $chat) {

                        if ( ! empty($chat['messages']) && ! empty($chat['user_id'])) {

                            try {
                                $communicate = new Minsk115\Common\Communicate($chat_id, $bot);
                                $communicate->processAnswer($chat['messages']);
                                $answer_messages = $communicate->getMessages();

                            } catch (\Exception $e) {
                                $answer_messages = [];
                                $answer_messages[] = new Minsk115\Common\Bot\Message('text', "Что-то пошло не так.\n {$e->getMessage()}");
                                $this->sendErrorMessage("Ошибка при попытке обработать телеграм сообщение пользователя", $e);
                            }


                            foreach ($answer_messages as $answer_message) {
                                $bot->sendMessage($chat_id, $answer_message);
                            }
                        }
                    }
                }

//                break;

            } catch (\Exception $e) {
                echo $e->getMessage() . "\n";
            }

            usleep(300000);
        }
    }


    /**
     * Напоминание о не законченных заявках (созданных вчера)
     * @return void
     */
    public function notifyDraftOrders(): void {

        $orders = $this->db->fetchAll("
            SELECT mo.id,
                   mo.author_id
            FROM mod_minsk115_orders AS mo
            WHERE mo.status = 'draft'
              AND DATE_ADD(DATE_FORMAT(mo.date_created, '%Y-%m-%d'), INTERVAL 1 DAY) = DATE_FORMAT(NOW(), '%Y-%m-%d')   
            GROUP BY mo.author_id
        ");


        if ( ! empty($orders)) {
            foreach ($orders as $order) {
                if ($order['author_id']) {
                    $author = $this->modMinsk115->dataMinsk115Authors->find($order['author_id'])->current();
                    $text   = implode("\n", [
                        "Ваша заявка не была завершена. Для продолжения нажмите на /new"
                    ]);

                    (new Minsk115\Authors\Model())
                        ->sendMessageText($author, $text);
                }
            }
        }
    }


    /**
     * Обработка фотографий в заявках
     * @return false
     * @throws \ImagickException
     * @throws Zend_Db_Adapter_Exception
     * @throws Zend_Exception
     * @cron_skip
     */
    public function processingPhoto(): bool {

        $where = [];
        $where[] = "status = 'processing'";
        $where[] = "fieldid = 'photo'";
        $where[] = "DATE_ADD(date_last_update, INTERVAL 5 MINUTE) < NOW()";
        $this->modMinsk115->dataMinsk115OrdersFiles->update([
            'status' => 'pending',
        ], $where);


        $photo_processing = $this->modMinsk115->dataMinsk115OrdersFiles->getRowByStatus('processing');
        if ($photo_processing) {
            return false;
        }

        $model = new Minsk115\Index\Model();
        $count = 0;

        do {
            $photo = $this->modMinsk115->dataMinsk115OrdersFiles->getRowByStatus('pending');

            if ( ! empty($photo)) {
                $model->photoProcessing($photo->id);

                $photo_processing = $this->modMinsk115->dataMinsk115OrdersFiles->getRowByStatus('processing');
                if ($photo_processing && $photo_processing->id != $photo->id) {
                    break;
                }
            }

            $count++;

            if ($count > 100) {
                break;
            }

        } while ( ! empty($photo));

        return true;
    }
}