<?php
namespace Core2\Mod\Minsk115\Notify;

require_once DOC_ROOT . 'core2/inc/classes/Common.php';
require_once DOC_ROOT . 'core2/inc/classes/class.edit.php';


/**
 * @property \ModMinsk115Controller $modMinsk115
 */
class View extends \Common {


    /**
     * @return false|string
     * @throws \Zend_Db_Adapter_Exception
     * @throws \Zend_Exception
     */
    public function getEdit() {

        $edit = new \editTable($this->resId);
        $edit->error = '';

        $edit->SQL = [
            [
                'id'         => 0,
                'authors_id' => '',
                'message'    => '',
            ],
        ];

        $authors = $this->db->fetchPairs("
            SELECT ma.id,
                   CONCAT_WS('', ma.name, ' (', COUNT(mo.id), ')')
            FROM mod_minsk115_authors AS ma
                LEFT JOIN mod_minsk115_orders AS mo ON ma.id = mo.author_id AND mo.status NOT IN ('draft', 'rejected')
            WHERE ma.telegram_id IS NOT NULL
              AND ma.is_banned_sw = 'N'
            GROUP BY ma.id
            ORDER BY ma.id DESC
        ");

        $count_authors    = count($authors);
        $authors_dropdown = ['all' => "-- Всем авторам ({$count_authors}) --"] + $authors;

        $description = '
            <br>
            <small class="text-muted">
                Текст может быть в формате Markdown 
                <a href="https://core.telegram.org/bots/api#formatting-options" target="_blank"><i class="fa fa-external-link"></i></a>.
                (Не допускайте отсутствия закрывающих тегов)  
            </small>
        ';


        $edit->addControl("Получатели", "MULTISELECT2", 'style="width:430px"', '', '', true); $edit->selectSQL[] = $authors_dropdown;
        $edit->addControl("Сообщение",  "TEXTAREA",     'style="min-width:430px;max-width:430px;min-height:150px" maxlength="4096"', $description, '', true);

        $edit->classText['SAVE'] = "Отправить";


        $edit->back = "index.php?module=minsk115&action=notify";
        $edit->firstColWidth = '200px';
        $edit->save("xajax_SendNotify(xajax.getFormValues(this.id))");

        return $edit->render();
    }
}