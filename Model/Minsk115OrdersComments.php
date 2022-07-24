<?php


/**
 *
 */
class Minsk115OrdersComments extends \Zend_Db_Table_Abstract {

	protected $_name = 'mod_minsk115_orders_comments';


    /**
     * @param int $id
     * @return Zend_Db_Table_Row_Abstract|null
     */
    public function getRowById(int $id):? Zend_Db_Table_Row_Abstract {

        $select = $this->select()->where("id = ?", $id);

        return $this->fetchRow($select);
    }


    /**
     * @param int $ext_id
     * @return Zend_Db_Table_Row_Abstract|null
     */
    public function getRowByExtId(int $ext_id):? Zend_Db_Table_Row_Abstract {

        $select = $this->select()->where("ext_id = ?", $ext_id);

        return $this->fetchRow($select);
    }


    /**
     * @param int $order_id
     * @param int $ext_id
     * @return Zend_Db_Table_Row_Abstract|null
     */
    public function getRowByOrderIdExtId(int $order_id, int $ext_id):? Zend_Db_Table_Row_Abstract {

        $select = $this->select()
            ->where("order_id = ?", $order_id)
            ->where("ext_id = ?", $ext_id);

        return $this->fetchRow($select);
    }


    /**
     * @param int $order_id
     * @return Zend_Db_Table_Row_Abstract|null
     */
    public function getRowLastByOrderId(int $order_id):? Zend_Db_Table_Row_Abstract {

        $select = $this->select()
            ->where("order_id = ?", $order_id)
            ->order('date_event DESC')
            ->limit(1);

        return $this->fetchRow($select);
    }


    /**
     * @param int $order_id
     * @return int
     */
    public function getCountByOrderId(int  $order_id): int {

        $select = $this->select()
            ->from($this->_name, ['count' => 'COUNT(*)'])
            ->where("order_id = ?", $order_id);

        $row = $this->fetchRow($select);

        return $row ? (int)$row['count'] : 0;
    }
}