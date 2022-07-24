<?php


/**
 *
 */
class Minsk115Orders extends \Zend_Db_Table_Abstract {

	protected $_name = 'mod_minsk115_orders';

    private array $statuses = [
        "draft"        => 'Черновик',
        "moderate"     => 'На модерации',
        "moderate_115" => 'На модерации в 115',
        "new"          => 'Новая',
        "active"       => 'На контроле',
        "in_process"   => 'В работе',
        "closed"       => 'Закрыта',
        "rejected"     => 'Отклонена',
    ];


    /**
     * Список статусов
     * @return string[]
     */
    public function getStatuses(): array {

        return $this->statuses;
    }


    /**
     * Название статуса
     * @param string $name
     * @return string
     */
    public function getStatus(string $name): string {

        return $this->statuses[$name] ?? '';
    }


    /**
     * @param int $id
     * @return Zend_Db_Table_Row_Abstract|null
     */
    public function getRowById(int $id):? Zend_Db_Table_Row_Abstract {

        $select = $this->select()->where("id = ?", $id);

        return $this->fetchRow($select);
    }


    /**
     * @param int $author_id
     * @return Zend_Db_Table_Row_Abstract|null
     */
    public function getRowDraftByAuthorId(int $author_id):? Zend_Db_Table_Row_Abstract {

        $select = $this->select()
            ->where("status = 'draft'")
            ->where("author_id = ?", $author_id);

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
     * @param string $nmbr
     * @return Zend_Db_Table_Row_Abstract|null
     */
    public function getRowByNmbr(string $nmbr):? Zend_Db_Table_Row_Abstract {

        $select = $this->select()->where("nmbr = ?", $nmbr);

        return $this->fetchRow($select);
    }


    /**
     * @param string $id_nmbr
     * @return Zend_Db_Table_Row_Abstract|null
     */
    public function getRowByIdNmbr(string $id_nmbr):? Zend_Db_Table_Row_Abstract {

        $select = $this->select()
            ->where("id = ?", $id_nmbr)
            ->orWhere("nmbr = ?", $id_nmbr);

        return $this->fetchRow($select);
    }


    /**
     * @return int
     */
    public function getCountActive(): int {

        $select = $this->select()
            ->from($this->_name, ['count' => 'COUNT(*)'])
            ->where("status NOT IN('closed', 'rejected')");

        $row = $this->fetchRow($select);

        return $row ? (int)$row['count'] : 0;
    }


    /**
     * @return int
     */
    public function getCountClosed(): int {

        $select = $this->select()
            ->from($this->_name, ['count' => 'COUNT(*)'])
            ->where("status = 'closed'");

        $row = $this->fetchRow($select);

        return $row ? (int)$row['count'] : 0;
    }


    /**
     * @return int
     */
    public function getCountRejected(): int {

        $select = $this->select()
            ->from($this->_name, ['count' => 'COUNT(*)'])
            ->where("status = 'rejected'");

        $row = $this->fetchRow($select);

        return $row ? (int)$row['count'] : 0;
    }


    /**
     * @param int $author_id
     * @return Zend_Db_Table_Rowset_Abstract
     */
    public function getRowsBuAuthorId(int $author_id): Zend_Db_Table_Rowset_Abstract {

        $select = $this->select()
            ->where("author_id = ?", $author_id);

        return $this->fetchAll($select);
    }
}