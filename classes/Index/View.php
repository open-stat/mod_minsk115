<?php
namespace Core2\Mod\Minsk115\Index;
use Core2\Classes\Table;


require_once DOC_ROOT . "core2/inc/classes/Common.php";
require_once DOC_ROOT . "core2/inc/classes/Alert.php";
require_once DOC_ROOT . "core2/inc/classes/class.edit.php";
require_once DOC_ROOT . "core2/inc/classes/Table/Db.php";


/**
 * @property \ModMinsk115Controller $modMinsk115
 */
class View extends \Common {


    /**
     * @param string $base_url
     * @return Table\Db
     * @throws Table\Exception
     * @throws \Zend_Db_Select_Exception
     */
    public function getTableActive(string $base_url): Table\Db {

        $table = new Table\Db($this->resId . 'xxx_active');
        $table->setTable("mod_minsk115_orders");
        $table->setPrimaryKey('id');
        $table->setAddUrl("{$base_url}&edit=0");
        $table->setEditUrl("{$base_url}&edit=TCOL_ID");
        $table->showDelete();
        $table->showColumnManage();

        $table->setQuery("
            SELECT mo.id,
                   mo.nmbr,
                   ma.name AS author_name,
                   mo.subject,
                   mo.user_comment,
                   mo.result_text,
                   mo.status,
                   mo.address,
                   mo.lat,
                   mo.lng,
                   mo.date_order,
                   mo.date_created,
                   
                   (SELECT mof.id
                    FROM mod_minsk115_orders_files AS mof                    
                    WHERE mof.refid = mo.id
                    ORDER BY mof.date_created DESC,
                             mof.id DESC 
                    LIMIT 1) AS last_photo_id,
                    
                   (SELECT CONCAT_WS(' ', DATE_FORMAT(moc.date_event, '%d.%m.%Y'), moc.comment)
                    FROM mod_minsk115_orders_comments AS moc                    
                    WHERE moc.order_id = mo.id
                    ORDER BY moc.date_event DESC,
                             moc.id DESC 
                    LIMIT 1) AS last_history
            
            FROM mod_minsk115_orders AS mo
                LEFT JOIN mod_minsk115_orders_comments AS moc ON mo.id = moc.order_id 
                LEFT JOIN mod_minsk115_authors AS ma ON ma.id = mo.author_id 
            WHERE mo.status NOT IN('closed', 'rejected')
            GROUP BY mo.id
            ORDER BY mo.status = 'moderate' DESC, 
                     mo.date_created DESC
        ");

        $statuses = $this->modMinsk115->dataMinsk115Orders->getStatuses();

        $table->addFilter("CONCAT_WS('|', mo.nmbr, mo.user_comment, mo.address)", $table::FILTER_TEXT, $this->_("Номер, Описание, Адрес"));

        $table->addSearch($this->_("Дата заявки"),           "mo.date_order",   $table::SEARCH_DATE);
        $table->addSearch($this->_("Номер"),                 "mo.nmbr",         $table::SEARCH_TEXT);
        $table->addSearch($this->_("Тема"),                  "mo.subject",      $table::SEARCH_TEXT);
        $table->addSearch($this->_("Описание пользователя"), "mo.user_comment", $table::SEARCH_TEXT);
        $table->addSearch($this->_("Адрес"),                 "mo.address",      $table::SEARCH_TEXT);
        $table->addSearch($this->_("Статус"),                "mo.status",       $table::SEARCH_SELECT)->setData($statuses);
        $table->addSearch($this->_("Сообщения истории"),     "moc.comment",     $table::SEARCH_TEXT);


        $table->addColumn($this->_("Фото"),                  'last_photo_id', $table::COLUMN_HTML, 100);
        $table->addColumn($this->_("Дата заявки"),           'date_order',    table::COLUMN_DATETIME, 130);
        $table->addColumn($this->_("Дата создания"),         'date_created',  $table::COLUMN_DATETIME, 130)->hide();
        $table->addColumn($this->_("Номер"),                 'nmbr',          table::COLUMN_TEXT, 100);
        $table->addColumn($this->_("Автор"),                 'author_name',   table::COLUMN_TEXT, 120);
        $table->addColumn($this->_("Тема"),                  'subject',       table::COLUMN_TEXT);
        $table->addColumn($this->_("Описание пользователя"), 'user_comment',  table::COLUMN_TEXT);
        $table->addColumn($this->_("Адрес / Координаты"),    'address',       table::COLUMN_HTML, 300);
        $table->addColumn($this->_("Статус"),                'status',        table::COLUMN_HTML, 100);
        $table->addColumn($this->_("Последнее сообщение"),   'last_history',  table::COLUMN_HTML, 300);



        $rows = $table->fetchRows();
        if ( ! empty($rows)) {
            foreach ($rows as $key => $row) {

                // Статус
                switch ($row->status->getValue()) {
                    case 'draft':
                        $row->setAttr('style', "background-color: #d7ccc8;");
                        $row->status = '<span class="label label-default">Черновик</span>';
                        break;

                    case 'moderate':
                        $row->setAttr('style', "background-color: #ffe0b2;");
                        $row->status = '<span class="label label-danger">На модерации</span>';
                        break;

                    case 'moderate_115': $row->status = '<span class="label label-warning">На модерации 115</span>'; break;
                    case 'new':          $row->status = '<span class="label label-primary">Новая</span>'; break;
                    case 'active':       $row->status = '<span class="label label-primary">На контроле</span>'; break;
                    case 'in_process':   $row->status = '<span class="label label-primary">В работе</span>'; break;
                }


                // Фото
                if ($row->last_photo_id->getValue()) {
                    $row->last_photo_id = "<img class=\"img-rounded\" src=\"{$base_url}&filehandler=mod_minsk115_orders&thumbid={$row->last_photo_id}\" />";
                }

                // Адрес
                if ($row->lat->getValue() && $row->lng->getValue()) {
                    $row->address .= " <span class=\"label label-info\" title=\"{$row->lat}, $row->lng\"><i class=\"fa fa-map-marker\"></i></span>";
                }
            }
        }

        return $table;
    }


    /**
     * @param string $base_url
     * @return Table\Db
     * @throws Table\Exception
     * @throws \Zend_Db_Select_Exception
     */
    public function getTableClosed(string $base_url): Table\Db {

        $table = new Table\Db($this->resId . 'xxx_closed');
        $table->setTable("mod_minsk115_orders");
        $table->setPrimaryKey('id');
        $table->setEditUrl("{$base_url}&edit=TCOL_ID");
        $table->showDelete();
        $table->showColumnManage();

        $table->setQuery("
            SELECT mo.id,
                   mo.nmbr,
                   mo.subject,
                   mo.user_comment,
                   mo.status,
                   mo.address,
                   mo.lat,
                   mo.lng,
                   mo.date_order,
                   mo.date_created,
                   ma.name AS author_name,
                   
                   (SELECT mof.id
                    FROM mod_minsk115_orders_files AS mof                    
                    WHERE mof.refid = mo.id
                    ORDER BY mof.date_created DESC,
                             mof.id DESC 
                    LIMIT 1) AS last_photo_id,
                   
                   (SELECT CONCAT_WS(' ', DATE_FORMAT(moc.date_event, '%d.%m.%Y'), moc.comment)
                    FROM mod_minsk115_orders_comments AS moc                    
                    WHERE moc.order_id = mo.id
                    ORDER BY moc.date_event DESC,
                             moc.id DESC 
                    LIMIT 1) AS last_history
            
            FROM mod_minsk115_orders AS mo
                LEFT JOIN mod_minsk115_orders_comments AS moc ON mo.id = moc.order_id 
                LEFT JOIN mod_minsk115_authors AS ma ON ma.id = mo.author_id 
            WHERE mo.status = 'closed'
            GROUP BY mo.id
            ORDER BY mo.date_created DESC
        ");

        $table->addFilter("CONCAT_WS('|', mo.nmbr, mo.user_comment, mo.address)", $table::FILTER_TEXT, $this->_("Номер, Описание, Адрес"));

        $table->addSearch($this->_("Дата заявки"),           "mo.date_order",   $table::SEARCH_DATE);
        $table->addSearch($this->_("Номер"),                 "mo.nmbr",         $table::SEARCH_TEXT);
        $table->addSearch($this->_("Тема"),                  "mo.subject",      $table::SEARCH_TEXT);
        $table->addSearch($this->_("Описание пользователя"), "mo.user_comment", $table::SEARCH_TEXT);
        $table->addSearch($this->_("Адрес"),                 "mo.address",      $table::SEARCH_TEXT);
        $table->addSearch($this->_("Сообщения истории"),     "moc.comment",     $table::SEARCH_TEXT);


        $table->addColumn($this->_("Фото"),                  'last_photo_id', $table::COLUMN_HTML, 100);
        $table->addColumn($this->_("Дата заявки"),           'date_order',    $table::COLUMN_DATETIME, 130);
        $table->addColumn($this->_("Дата создания"),         'date_created',  $table::COLUMN_DATETIME, 130)->hide();
        $table->addColumn($this->_("Номер"),                 'nmbr',          $table::COLUMN_TEXT, 100);
        $table->addColumn($this->_("Автор"),                 'author_name',   $table::COLUMN_TEXT, 120);
        $table->addColumn($this->_("Тема"),                  'subject',       $table::COLUMN_TEXT);
        $table->addColumn($this->_("Описание пользователя"), 'user_comment',  $table::COLUMN_TEXT);
        $table->addColumn($this->_("Адрес / Координаты"),    'address',       $table::COLUMN_HTML, 300);
        $table->addColumn($this->_("Последнее сообщение"),   'last_history',  $table::COLUMN_HTML, 300);


        $rows = $table->fetchRows();
        if ( ! empty($rows)) {
            foreach ($rows as $key => $row) {

                // Фото
                if ($row->last_photo_id->getValue()) {
                    $row->last_photo_id = "<img class=\"img-rounded\" src=\"{$base_url}&filehandler=mod_minsk115_orders&thumbid={$row->last_photo_id}\" />";
                }


                // Адрес
                if ($row->lat->getValue() && $row->lng->getValue()) {
                    $row->address .= " <span class=\"label label-info\" title=\"{$row->lat}, $row->lng\"><i class=\"fa fa-map-marker\"></i></span>";
                }
            }
        }

        return $table;
    }


    /**
     * @param string $base_url
     * @return Table\Db
     * @throws Table\Exception
     */
    public function getTableRejected(string $base_url): Table\Db {

        $table = new Table\Db($this->resId . 'xxx_rejected');
        $table->setTable("mod_minsk115_orders");
        $table->setPrimaryKey('id');
        $table->setEditUrl("{$base_url}&edit=TCOL_ID");
        $table->showDelete();
        $table->showColumnManage();

        $table->setQuery("
            SELECT mo.id,
                   mo.user_comment,
                   mo.date_created,
                   mo.moderate_message,
                   ma.name AS author_name,
                   
                   (SELECT mof.id
                    FROM mod_minsk115_orders_files AS mof                    
                    WHERE mof.refid = mo.id
                    ORDER BY mof.date_created DESC,
                             mof.id DESC 
                    LIMIT 1) AS last_photo_id
            
            FROM mod_minsk115_orders AS mo 
                LEFT JOIN mod_minsk115_authors AS ma ON ma.id = mo.author_id 
            WHERE mo.status = 'rejected'
            GROUP BY mo.id
            ORDER BY mo.date_order DESC
        ");

        $table->addFilter("CONCAT_WS('|', mo.user_comment, mo.moderate_message, ma.name)", $table::FILTER_TEXT, $this->_("Поиск"));

        $table->addSearch($this->_("Дата создания"),         "mo.date_created",   $table::SEARCH_DATE);
        $table->addSearch($this->_("Описание пользователя"), "mo.user_comment", $table::SEARCH_TEXT);

        $table->addColumn($this->_("Фото"),                  'last_photo_id',    $table::COLUMN_HTML, 100);
        $table->addColumn($this->_("Дата создания"),         'date_created',     $table::COLUMN_DATETIME, 130);
        $table->addColumn($this->_("Автор"),                 'author_name',      $table::COLUMN_TEXT, 120);
        $table->addColumn($this->_("Описание пользователя"), 'user_comment',     $table::COLUMN_TEXT);
        $table->addColumn($this->_("Причина отклонения"),    'moderate_message', $table::COLUMN_TEXT, 300);

        $rows = $table->fetchRows();
        if ( ! empty($rows)) {
            foreach ($rows as $key => $row) {

                // Фото
                if ($row->last_photo_id->getValue()) {
                    $row->last_photo_id = "<img class=\"img-rounded\" src=\"{$base_url}&filehandler=mod_minsk115_orders&thumbid={$row->last_photo_id}\" />";
                }
            }
        }

        return $table;
    }


    /**
     * @param \Zend_Db_Table_Row_Abstract $order
     * @return Table\Db
     * @throws Table\Exception
     * @throws \Zend_Db_Select_Exception
     */
    public function getTableComments(\Zend_Db_Table_Row_Abstract $order): Table\Db {

        $table = new Table\Db($this->resId. 'xxx_history');
        $table->setTable("mod_minsk115_orders_comments");
        $table->setPrimaryKey('id');
        $table->showColumnManage();
        $table->hideCheckboxes();

        $table->setQuery("
            SELECT moc.id,
                   moc.status,
                   moc.creator,
                   moc.comment,
                   moc.date_event
            FROM mod_minsk115_orders_comments AS moc 
            WHERE moc.order_id = ?
            ORDER BY moc.date_event DESC
        ", [
            $order->id
        ]);

        $table->addFilter("CONCAT_WS('|', moc.comment, moc.creator)", $table::FILTER_TEXT, $this->_("Автор / комментарий"));

        $table->addSearch($this->_("Дата события"), "moc.date_event", $table::SEARCH_DATE);
        $table->addSearch($this->_("Автор"),        "moc.creator",    $table::SEARCH_TEXT);
        $table->addSearch($this->_("Комментарий"),  "moc.comment",    $table::SEARCH_TEXT);


        $table->addColumn($this->_("Дата события"), "date_event", $table::COLUMN_DATE, 120);
        $table->addColumn($this->_("Статус"),       "status",     $table::COLUMN_HTML, 120);
        $table->addColumn($this->_("Автор"),        "creator",    $table::COLUMN_TEXT, 400);
        $table->addColumn($this->_("Комментарий"),  "comment",    $table::COLUMN_TEXT);



        $rows = $table->fetchRows();
        if ( ! empty($rows)) {
            foreach ($rows as $key => $row) {

                // Статус
                switch ($row->status->getValue()) {
                    case 'new':          $row->status = '<span class="label label-success">Новая заявка</span>'; break;
                    case 'moderate_115': $row->status = '<span class="label label-default">На модерации 115</span>'; break;
                    case 'plan':         $row->status = '<span class="label label-info">В план текущего ремонта</span>'; break;
                    case 'closed':       $row->status = '<span class="label label-primary">Заявка закрыта</span>'; break;
                    default:             $row->status = "<span class=\"label label-warning\">{$row->status}</span>"; break;
                }
            }
        }

        return $table;
    }


    /**
     * @return string
     * @throws \Zend_Config_Exception
     */
    public function getMap(): string {

        $config = $this->getModuleConfig('autoservice');
        $apikey = $config->ymap && $config->ymap->apikey
            ? $config->ymap->apikey
            : '';

        $tpl = new \Templater3(__DIR__ . '/../../assets/html/shops/map.html');
        $tpl->assign('[APIKEY]', $apikey);

        return $tpl;
    }


    /**
     * @param \Zend_Db_Table_Row_Abstract|null $order
     * @return \editTable
     * @throws \Zend_Config_Exception
     */
    public function getEdit(\Zend_Db_Table_Row_Abstract $order = null): \editTable {

        $edit = new \editTable($this->resId);
        $edit->table = 'mod_minsk115_orders';

        $edit->SQL = [
            [
                'id'           => $order?->id,
                'nmbr'         => $order?->nmbr,
                'subject'      => $order?->subject,
                'author_id'    => $order?->author_id,
                'date_order'   => $order?->date_order,
                'rating'       => $order?->rating,
                'user_comment' => $order?->user_comment,
                'address'      => $order?->address,
                'coordinates'  => $order?->lat ? "{$order->lat}, {$order->lng}" : '',
                'photo'        => 'photo',
                'photo_org'    => 'photo_org',
                'status'       => $order?->status,
            ],
        ];

        $author = $order?->author_id ? $this->modMinsk115->dataMinsk115Authors->find($order->author_id)->current() : null;

        $edit->addControl('Номер',                 "CUSTOM", $order?->nmbr ?: '<i class="text-muted">не указано</i>');
        $edit->addControl('Тема',                  "CUSTOM", $order?->subject ?: '<i class="text-muted">не указано</i>');
        $edit->addControl('Автор',                 "CUSTOM", $author ? "<a href=\"index.php#module=minsk115&action=authors&edit={$author->id}\">{$author->name}</a>" : '<i class="text-muted">неизвестно</i>');
        $edit->addControl('Дата заявки',           "CUSTOM", $order?->date_order ? date('d.m.Y', strtotime($order?->date_order)) : '<i class="text-muted">не отправлено</i>');
        $edit->addControl('Рейтинг',               "CUSTOM", $order?->rating ?: '<i class="text-muted">не указано</i>');

        $edit->addGroup('Пользовательские данные');

        $edit->addControl('Описание пользователя',  "TEXTAREA",   'style="min-width:300px;max-width:300px;min-height:50px"');
        $edit->addControl('Адрес',                  "TEXT",       'style="width:300px;"');
        $edit->addControl('Координаты',             "CUSTOM",      $this->getEditMapAddress($order));
        $edit->addControl('Фотографии автора',      "XFILES_AUTO", [ 'acceptFileTypes' => 'jpg,jpeg,png' ]);
        $edit->addControl('Фотографии организации', "XFILES_AUTO", [ 'acceptFileTypes' => 'jpg,jpeg,png' ]);


        $edit->addButtonCustom('<input type="button" class="btn btn-sm btn-warning" type="button"
                                        onclick="minsk115Index.send115(this.form);" value="Отправить в 115"> ');

        if ($order && $order->status != 'draft') {
            $edit->addButtonCustom('<input type="button" class="btn btn-danger btn-sm" value="Отклонить" 
                                           onclick="minsk115Index.rejected(this.form);"> ');
        }

        $edit->addParams('status',           '');
        $edit->addParams('moderate_message', '');
        $edit->firstColWidth = "200px";
        $edit->save("xajax_saveOrder(xajax.getFormValues(this.id))");

        return $edit;
    }


    /**
     * @param \Zend_Db_Table_Row_Abstract|null $order
     * @return \editTable
     * @throws \Zend_Config_Exception
     */
    public function getEditReadonly(\Zend_Db_Table_Row_Abstract $order = null): \editTable {

        $edit = new \editTable($this->resId);
        $edit->table    = 'mod_minsk115_orders';
        $edit->readOnly = true;
        $edit->SQL      = [
            [
                'id'           => $order?->id,
                'nmbr'         => $order?->nmbr,
                'subject'      => $order?->subject,
                'author_id'    => $order?->author_id,
                'date_order'   => $order?->date_order,
                'rating'       => $order?->rating,
                'user_comment' => $order?->user_comment,
                'address'      => $order?->address,
                'coordinates'  => $order?->lat ? "{$order->lat}, {$order->lng}" : '',
                'photo'        => 'photo',
                'photo_org'    => 'photo_org',
            ],
        ];

        $author = $order?->author_id ? $this->modMinsk115->dataMinsk115Authors->find($order->author_id)->current() : null;

        $edit->addControl('Номер',                 "CUSTOM", $order?->nmbr ?: '<i class="text-muted">не указано</i>');
        $edit->addControl('Тема',                  "CUSTOM", $order?->subject ?: '<i class="text-muted">не указано</i>');
        $edit->addControl('Автор',                 "CUSTOM", $author ? "<a href=\"index.php#module=minsk115&action=authors&edit={$author->id}\">{$author->name}</a>" : '<i class="text-muted">неизвестно</i>');
        $edit->addControl('Дата заявки',           "CUSTOM", $order?->date_order ? date('d.m.Y', strtotime($order?->date_order)) : 'Не отправлено в 115');
        $edit->addControl('Рейтинг',               "CUSTOM", $order?->rating ?: '<i class="text-muted">не указано</i>');

        $edit->addGroup('Пользовательские данные');

        $edit->addControl('Описание пользователя',  "PROTECTED");
        $edit->addControl('Адрес',                  "PROTECTED");
        $edit->addControl('Координаты',             "CUSTOM",      $this->getEditMapAddress($order, true));
        $edit->addControl('Фотографии автора',      "XFILES_AUTO");
        $edit->addControl('Фотографии организации', "XFILES_AUTO");


        $edit->firstColWidth = "200px";

        return $edit;
    }


    /**
     * @param \Zend_Db_Table_Row_Abstract|null $order
     * @param bool                             $readonly
     * @return string
     * @throws \Zend_Config_Exception
     */
    private function getEditMapAddress(\Zend_Db_Table_Row_Abstract $order = null, bool $readonly = false): string {

        $config = $this->getModuleConfig('minsk115');
        $apikey = $config->ymap && $config->ymap->apikey
            ? $config->ymap->apikey
            : '';


        $coordinates = $order && $order?->lat && $order?->lng
            ? "{$order->lat}, {$order->lng}"
            : '';

        $tpl = $readonly
            ? new \Templater3(__DIR__ . '/../../assets/html/index/map-address-readonly.html')
            : new \Templater3(__DIR__ . '/../../assets/html/index/map-address.html');

        $tpl->assign('[COORDINATES_TEXT]', $coordinates ?: '<i class="text-muted">не указано</i>');
        $tpl->assign('[COORDINATES]',      $coordinates);
        $tpl->assign('[APIKEY]',           $apikey);

        return $tpl;
    }
}
