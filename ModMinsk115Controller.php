<?php
use Core2\Mod\Minsk115;

require_once DOC_ROOT . "core2/inc/classes/Common.php";
require_once DOC_ROOT . "core2/inc/classes/Alert.php";
require_once DOC_ROOT . "core2/inc/classes/Panel.php";

require_once 'classes/autoload.php';


/**
 * @property \Minsk115Orders         $dataMinsk115Orders
 * @property \Minsk115OrdersComments $dataMinsk115OrdersComments
 * @property \Minsk115OrdersFiles    $dataMinsk115OrdersFiles
 * @property \Minsk115Authors        $dataMinsk115Authors
 */
class ModMinsk115Controller extends Common {

    /**
     * @return string
     * @throws Exception
     */
    public function action_index(): string {

        $base_url = 'index.php?module=minsk115&action=index';
        $view     = new Minsk115\Index\View();
        $panel    = new Panel('tab');
        $content  = [];

        try {
            ob_start();
            $this->printCssModule('minsk115', '/assets/css/index/orders.css');
            $this->printJsModule('minsk115', '/assets/js/index/orders.js');
            $content[] = ob_get_clean();

            if (isset($_GET['edit'])) {
                if ( ! empty($_GET['edit'])) {
                    $order = $this->dataMinsk115Orders->getRowById((int)$_GET['edit']);

                    if (empty($order)) {
                        throw new Exception('Указанная заявка не найдена');
                    }


                    $status_name = $this->dataMinsk115Orders->getStatus($order->status);
                    $title = $order->nmbr ? "Заявка №{$order->nmbr}" : "Заявка";
                    $panel->setTitle($title, $status_name, $base_url);

                    $base_url     .= "&edit={$order->id}";
                    $count_comments = $this->dataMinsk115OrdersComments->getCountByOrderId($order->id);

                    $panel->addTab("Заявка",                        'order',    $base_url);
                    $panel->addTab("Комментарии ($count_comments)", 'comments', $base_url);

                    switch ($panel->getActiveTab()) {
                        case 'order':
                            if (in_array($order->status, ['draft', 'moderate'])) {
                                $content[] = $view->getEdit($order)->render();
                            } else {
                                if ($order->status == 'rejected') {
                                    $content[] = Alert::warning($order->moderate_message, 'Причина отклонения');
                                }
                                $content[] = $view->getEditReadonly($order)->render();
                            }
                            break;

                        case 'comments': $content[] = $view->getTableComments($order)->render(); break;
                    }

                } else {
                    $panel->setTitle("Добавление заявки", '', $base_url);
                    $content[] = $view->getEdit()->render();
                }


            } else {
                $count_active   = $this->dataMinsk115Orders->getCountActive();
                $count_closed   = $this->dataMinsk115Orders->getCountClosed();
                $count_rejected = $this->dataMinsk115Orders->getCountRejected();

                $panel->addTab("Активные ($count_active)",      'active',   $base_url);
                $panel->addTab("Закрытые ($count_closed)",      'closed',   $base_url);
                $panel->addTab("Отклоненные ($count_rejected)", 'rejected', $base_url);


                switch ($panel->getActiveTab()) {
                    case 'active':   $content[] = $view->getTableActive($base_url)->render(); break;
                    case 'closed':   $content[] = $view->getTableClosed($base_url)->render(); break;
                    case 'rejected': $content[] = $view->getTableRejected($base_url)->render(); break;
                }
            }

        } catch (\Exception $e) {
            $content[] = Alert::danger($e->getMessage(), 'Ошибка');
        }

        $panel->setContent(implode('', $content));
        return $panel->render();
    }


    /**
     * @return string
     * @throws Exception
     */
    public function action_authors(): string {

        $base_url = 'index.php?module=minsk115&action=authors';
        $view     = new Minsk115\Authors\View();
        $panel    = new Panel('tab');
        $content  = [];

        try {
            if (isset($_GET['edit'])) {

                if ( ! empty($_GET['edit'])) {
                    $author = $this->dataMinsk115Authors->getRowById((int)$_GET['edit']);

                    if (empty($author)) {
                        throw new Exception('Указанный автор не найден');
                    }

                    $panel->setTitle($author->name, '', $base_url);

                } else {
                    $panel->setTitle("Добавление автора", '', $base_url);
                }

                $content[] = $view->getEdit($author ?? null)->render();


            } else {
                $content[] = $view->getTable($base_url)->render();
            }

        } catch (\Exception $e) {
            $content[] = Alert::danger($e->getMessage(), 'Ошибка');
        }

        $panel->setContent(implode('', $content));
        return $panel->render();
    }


    /**
     * Уведомления
     * @return string
     * @throws Exception
     */
    public function action_notify(): string {

        try {
            $view    = new Minsk115\Notify\View();
            $panel   = new Panel('tab');
            $content = [];

            $panel->setTitle('Отправка уведомления', 'Сообщение будет отправлено в телеграм бот');
            $content[] = $view->getEdit();

            $panel->setContent(implode('', $content));
            return $panel->render();

        } catch (\Exception $e) {
            return Alert::danger($e->getMessage(), 'Ошибка');
        }
    }
}