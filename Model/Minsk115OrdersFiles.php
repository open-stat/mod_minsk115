<?php


/**
 *
 */
class Minsk115OrdersFiles extends \Zend_Db_Table_Abstract {

	protected $_name = 'mod_minsk115_orders_files';


    /**
     * @param int    $order_id
     * @param string $photo_path
     * @param string $field_id
     * @return bool
     */
    public function createPhotoByFilePath(int $order_id, string $photo_path, array $option = []): bool {

        if ( ! file_exists($photo_path)) {
            return false;
        }

        $row = $this->createRow([
            'content'  => file_get_contents($photo_path),
            'refid'    => $order_id,
            'filename' => basename($photo_path),
            'filesize' => filesize($photo_path),
            'hash'     => md5_file($photo_path),
            'type'     => mime_content_type($photo_path),
            'fieldid'  => $option['fieldid'] ?? 'photo',
            'thumb'    => null,
        ]);

        $row->save();

        return true;
    }


    /**
     * @param int    $order_id
     * @param string $photo_content
     * @param string $field_id
     * @return bool
     */
    public function createPhotoByContent(int $order_id, string $photo_content, array $option = []): bool {

        $row = $this->createRow([
            'ext_id'   => $option['ext_id'] ?? null,
            'content'  => $photo_content,
            'refid'    => $order_id,
            'filename' => $option['filename'] ??  crc32(time()) . ".jpg",
            'filesize' => strlen($photo_content),
            'hash'     => md5($photo_content),
            'type'     => 'image/jpeg',
            'status'   => $option['status'] ?? 'pending',
            'fieldid'  => $option['fieldid'] ?? 'photo',
            'thumb'    => null,
        ]);

        $row->save();

        return true;
    }


    /**
     * @param int $ext_id
     * @return Zend_Db_Table_Row_Abstract|null
     */
    public function getRowsByExtId(int $ext_id):? Zend_Db_Table_Row_Abstract {

        $select = $this->select()
            ->where("ext_id = ?", $ext_id);

        return $this->fetchRow($select);
    }


    /**
     * @param int $order_id
     * @return Zend_Db_Table_Rowset_Abstract
     */
    public function getRowsByOrderId(int $order_id): Zend_Db_Table_Rowset_Abstract {

        $select = $this->select()
            ->where("refid = ?", $order_id)
            ->order("id DESC");

        return $this->fetchAll($select);
    }


    /**
     * @param string $statue
     * @return Zend_Db_Table_Row_Abstract
     */
    public function getRowByStatus(string $statue): Zend_Db_Table_Row_Abstract {

        $select = $this->select()
            ->where("fieldid = 'photo'")
            ->where("status = ?", $statue)
            ->order("id DESC")
            ->limit(1);

        return $this->fetchRow($select);
    }


    /**
     * @param int $order_id
     * @return int
     */
    public function getCountByOrderId(int $order_id): int {

        $select = $this->select()
            ->from($this->_name, ['count' => 'COUNT(*)'])
            ->where("refid = ?", $order_id);

        $row = $this->fetchRow($select);

        return $row ? (int)$row['count'] : 0;
    }
}