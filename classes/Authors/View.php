<?php
namespace Core2\Mod\Minsk115\Authors;
use Core2\Classes\Table;


require_once DOC_ROOT . "core2/inc/classes/Common.php";
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
     */
    public function getTable(string $base_url): Table\Db {

        $table = new Table\Db($this->resId);
        $table->setTable("mod_minsk115_authors");
        $table->setPrimaryKey('id');
        $table->setAddUrl("{$base_url}&edit=0");
        $table->setEditUrl("{$base_url}&edit=TCOL_ID");
        $table->showDelete();

        $table->setQuery("
            SELECT ma.id,
                   ma.name,
                   ma.is_admin_sw,
                   ma.is_no_moderate_sw,
                   ma.is_banned_sw,
                   ma.date_last_activity,
                   ma.date_created,
                   COUNT(mo.id) AS count_orders
                   
            FROM mod_minsk115_authors AS ma
                LEFT JOIN mod_minsk115_orders AS mo ON ma.id = mo.author_id
            GROUP BY ma.id
            ORDER BY ma.date_created DESC
        ");

        $table->addFilter("ma.name", $table::FILTER_TEXT, $this->_("Имя"));


        $table->addColumn($this->_("Имя"),                       'name',               $table::COLUMN_TEXT);
        $table->addColumn($this->_("Количество заявок"),         'count_orders',       $table::COLUMN_TEXT, 150);
        $table->addColumn($this->_("Дата последней активности"), 'date_last_activity', $table::COLUMN_DATETIME, 190);
        $table->addColumn($this->_("Дата регистрации"),          'date_created',       $table::COLUMN_DATETIME, 130);
        $table->addColumn($this->_("Без модерации"),             'is_no_moderate_sw',  $table::COLUMN_SWITCH, 115);
        $table->addColumn($this->_("Бан"),                       'is_banned_sw',       $table::COLUMN_SWITCH, 70)->setOptions(['color' => 'warning']);
        $table->addColumn($this->_("Админ"),                     'is_admin_sw',        $table::COLUMN_SWITCH, 80)->setOptions(['color' => 'danger']);


        return $table;
    }


    /**
     * @param \Zend_Db_Table_Row_Abstract|null $author
     * @return \editTable
     */
    public function getEdit(\Zend_Db_Table_Row_Abstract $author = null): \editTable {

        $edit = new \editTable($this->resId);
        $edit->table = 'mod_minsk115_authors';

        $edit->SQL = [
            [
                'id'                => $author?->id,
                'name'              => $author?->name,
                'telegram_id'       => $author?->telegram_id,
                'is_no_moderate_sw' => $author?->is_no_moderate_sw,
                'is_banned_sw'      => $author?->is_banned_sw,
                'is_admin_sw'       => $author?->is_admin_sw,
            ],
        ];


        $edit->addControl('Имя',           "TEXT",   'style="width:300px;"', '', '', true);
        $edit->addControl('Telegram ID',   "TEXT",   'style="width:300px;"', '', '', true);
        $edit->addControl('Без модерации', "SWITCH");
        $edit->addControl('Бан',           "SWITCH");
        $edit->addControl('Админ',         "SWITCH");


        $edit->firstColWidth = "200px";
        $edit->save("xajax_saveAuthor(xajax.getFormValues(this.id))");

        return $edit;
    }
}
