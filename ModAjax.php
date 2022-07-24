<?php
use Core2\Mod\Minsk115;

require_once DOC_ROOT . "core2/inc/ajax.func.php";
require_once 'classes/autoload.php';


/**
 * @property \ModMinsk115Controller $modMinsk115
 */
class ModAjax extends ajaxFunc {


    /**
     * @param array $data
     * @return xajaxResponse
     * @throws Zend_Db_Adapter_Exception
     * @throws Exception
     */
    public function axSaveOrder(array $data): xajaxResponse {

        if (isset($data['control']['coordinates'])) {
            $coordinates = explode(',', $data['control']['coordinates']);

            if ($coordinates) {
                $coordinates = array_map('trim', $coordinates);

                if ( ! empty($coordinates[0])) {
                    $data['control']['lat'] = $coordinates[0];
                }
                if ( ! empty($coordinates[1])) {
                    $data['control']['lng'] = $coordinates[1];
                }
            }

            unset($data['control']['coordinates']);
        }


        $this->db->beginTransaction();
        try {
            switch ($data['status']) {
                case 'send115':
                    $data['control']['status'] = 'moderate_115';
                    break;

                case 'rejected':
                    if (empty($data['moderate_message'])) {
                        throw new \Exception('Укажите причину отклонения');
                    }

                    $data['control']['status']           = 'rejected';
                    $data['control']['moderate_message'] = trim($data['moderate_message']);
                    break;
            }


            $order_id = $this->saveData($data);
            $order    = $this->modMinsk115->dataMinsk115Orders->find($order_id)->current();

            $model = new Minsk115\Index\Model();
            $model->photoProcessingOrder($order);


            switch ($data['status']) {
                case 'send115':
                    $model->sendOrder115($order);
                    $this->response->script("CoreUI.notice.create('Заявка отправлена');");
                    break;

                case 'rejected':
                    $model->sendRejectReason($order);
                    $this->response->script("CoreUI.notice.create('Заявка отклонена');");
                    break;

                default:
                    $this->response->script("CoreUI.notice.create('Сохранено');");
            }


            $this->response->script("load('index.php?module=minsk115&edit={$order_id}')");
            $this->db->commit();

        } catch (\Exception $e) {
            $this->db->rollback();
            $this->error[] = $e->getMessage();
        }


        $this->done($data);
        return $this->response;
    }


    /**
     * @param array $data
     * @return xajaxResponse
     * @throws Zend_Db_Adapter_Exception
     * @throws Exception
     */
    public function axSaveAuthor(array $data): xajaxResponse {

        $fields = [
            'name'        => 'req',
            'telegram_id' => 'req',
        ];

        if ($this->ajaxValidate($data, $fields)) {
            return $this->response;
        }



        $this->saveData($data);

        if (empty($this->error)) {
            $this->response->script("CoreUI.notice.create('Сохранено');load('index.php?module=minsk115&action=authors')");
        }


        $this->done($data);
        return $this->response;
    }


    /**
     * Уведомления
     * @param $data
     * @return xajaxResponse
     * @throws Exception
     */
    public function axSendNotify($data): xajaxResponse {

        $fields = [
            'authors_id' => 'req',
            'message'    => 'req',
        ];

        if ($this->ajaxValidate($data, $fields)) {
            return $this->response;
        }

        if (array_search('all', $data['control']['authors_id']) !== false) {
            $authors_id = $this->db->fetchCol("
                SELECT ma.id
                FROM mod_minsk115_authors AS ma
                WHERE ma.telegram_id IS NOT NULL
                  AND ma.is_banned_sw = 'N'
            ");

        } else {
            foreach ($data['control']['authors_id'] as $author_id) {
                if ($author_id) {
                    $authors_id[] = $author_id;
                }
            }
        }


        if ( ! empty($authors_id)) {
            $model = new Minsk115\Authors\Model();

            foreach ($authors_id as $author_id) {
                $author = $this->modMinsk115->dataMinsk115Authors->find($author_id)->current();
                $model->sendMessageText($author, $data['control']['message']);
            }

            $this->response->script("CoreUI.notice.create('Сообщение отправлено', 'success');");

        } else {
            $this->error[] = 'Не удалось найти получателей, обновите страницу и попробуйте снова';
            $this->displayError($data);
        }



        $this->done($data);
        return $this->response;
    }
}
