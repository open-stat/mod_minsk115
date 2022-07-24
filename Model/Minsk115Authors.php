<?php


/**
 *
 */
class Minsk115Authors extends \Zend_Db_Table_Abstract {

	protected $_name = 'mod_minsk115_authors';


    /**
     * @param int $id
     * @return Zend_Db_Table_Row_Abstract|null
     */
    public function getRowById(int $id):? Zend_Db_Table_Row_Abstract {

        $select = $this->select()->where("id = ?", $id);

        return $this->fetchRow($select);
    }


    /**
     * @param int $telegram_id
     * @return Zend_Db_Table_Row_Abstract|null
     */
    public function getRowByTelegramId(int $telegram_id):? Zend_Db_Table_Row_Abstract {

        $select = $this->select()->where("telegram_id = ?", $telegram_id);

        return $this->fetchRow($select);
    }


    /**
     * @return Zend_Db_Table_Rowset_Abstract|null
     */
    public function getRowsAdmin():? Zend_Db_Table_Rowset_Abstract {

        $select = $this->select()->where("is_admin_sw = 'Y'");

        return $this->fetchAll($select);
    }
}