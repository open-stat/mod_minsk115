<?php
namespace Core2\Mod\Minsk115\Index;
use Core2\Mod\Minsk115;


/**
 * @property \ModMinsk115Controller $modMinsk115
 */
class Model extends \Common {

    /**
     * @param \Zend_Db_Table_Row_Abstract $order
     * @return bool
     * @throws \Zend_Config_Exception
     * @throws \Exception
     * @throws \GuzzleHttp\Exception\GuzzleException
     */
    public function sendOrder115(\Zend_Db_Table_Row_Abstract $order): bool {

        if (empty($order->user_comment)) {
            throw new \Exception('В заявке отсутствует описание пользователя');
        }

        if (empty($order->lat) || empty($order->lng)) {
            throw new \Exception('В заявке отсутствуют координаты');
        }

        if ( ! is_numeric($order->lat) || ! is_numeric($order->lng)) {
            throw new \Exception('В заявке указаны некорректные координаты');
        }

        $this->photoProcessingOrder($order);

        $images = $this->loadOrderPhotos($order);

        register_shutdown_function(function () use ($images) {
            foreach ($images as $file) {
                if (file_exists($file)) {
                    unlink($file);
                }
            }
        });


        $config = $this->getModuleConfig('minsk115');

        if ( ! $config?->app?->login || ! $config?->app?->pass) {
            throw new \Exception('Не заданы настройки для подключения к 115');
        }

        $cabinet115 = new OpenDataWorld\Cabinet115bel($config->app->login, $config->app->pass);
        $cabinet115->start();
        $cabinet115->login();
        $cabinet115->createReport($order->user_comment, $images, $order->lat, $order->lng);
        $data_reports = $cabinet115->getReportsMe();

        if ( ! empty($data_reports['reports'][0])) {
            $order->ext_id = $data_reports['reports'][0]['report_id'];
            $order->save();
        }

        return true;
    }


    /**
     * @param \Zend_Db_Table_Row_Abstract $order
     * @return bool
     * @throws \Longman\TelegramBot\Exception\TelegramException
     * @throws \Zend_Config_Exception
     * @throws \Zend_Db_Table_Exception
     */
    public function sendRejectReason(\Zend_Db_Table_Row_Abstract $order): bool {

        if ( ! $order->author_id) {
            return false;
        }

        $author = $this->modMinsk115->dataMinsk115Authors->find($order->author_id)->current();


        $reject_reason = implode("\n", [
            "Ваша заявка была отклонена.",
            "Описание заявки: {$order->user_comment}",
            "Причина отклонения: {$order->moderate_message}",
        ]);

        $model = new Minsk115\Authors\Model();
        $model->sendMessageText($author, $reject_reason);

        return true;
    }


    /**
     * @param \Zend_Db_Table_Row_Abstract $order
     * @return void
     * @throws \ImagickException
     * @throws \Zend_Db_Adapter_Exception
     */
    public function photoProcessingOrder(\Zend_Db_Table_Row_Abstract $order): void {

        $photos = $this->modMinsk115->dataMinsk115OrdersFiles->getRowsByOrderId($order->id);

        if ( ! empty($photos)) {
            foreach ($photos as $photo) {
                if ($photo->fieldid == 'photo' && $photo->status == 'pending') {
                    $this->photoProcessing($photo->id);
                }
            }
        }
    }


    /**
     * @param $photo_id
     * @throws \ImagickException
     * @throws \Zend_Db_Adapter_Exception
     * @throws \Exception
     */
    public function photoProcessing($photo_id) {

        $photo = $this->modMinsk115->dataMinsk115OrdersFiles->find($photo_id)->current();

        if (empty($photo)) {
            throw new \Exception("Файл фотографии не найден в базе данных");
        }

        if ( ! in_array($photo->type, ['image/png', 'image/jpg', 'image/jpeg'])) {
            throw new \Exception('Формат картинки не подходит для обработки');
        }

        $photo->status = 'processing';
        $photo->save();


        $upload_module_dir = $this->config->temp . '/minsk115';
        $upload_dir        = "{$upload_module_dir}/{$this->order_id}";

        if ( ! is_dir($upload_module_dir)) {
            mkdir($upload_module_dir);
            chmod($upload_module_dir, 0755);
        }

        if ( ! is_dir($upload_dir)) {
            mkdir($upload_dir);
            chmod($upload_dir, 0755);
        }

        $filename    = "origin_{$photo->filename}";
        $file_origin = "{$upload_dir}/{$filename}";

        file_put_contents($file_origin, $photo->content);

        if ( ! file_exists($file_origin)) {
            throw new \Exception(sprintf($this->_("Файл %s не найден"), $file_origin));
        }

        $uniqid_filename = uniqid() . $filename;
        $file_base       = [
            'filename' => $photo->filename,
            'type'     => $photo->type,
        ];

        $file_origin_correction = $file_base + ['path' => "{$upload_dir}/correction_{$uniqid_filename}"];
        $file_large             = $file_base + ['path' => "{$upload_dir}/large_{$uniqid_filename}"];

        $this->correctOrientation($file_origin, $file_origin_correction['path']);

        // large
        $image = \WideImage\WideImage::loadFromFile($file_origin_correction['path']);
        $image = $image->resizeDown(1920, 1080, 'inside');

        $image->saveToFile($file_large['path']);



        $this->db->beginTransaction();
        try {
            $photo->content  = file_get_contents($file_large['path']);
            $photo->filesize = filesize($file_large['path']);
            $photo->status  = 'completed';
            $photo->save();

            $this->db->commit();

            if (file_exists($file_origin))                    unlink($file_origin);
            if (file_exists($file_origin_correction['path'])) unlink($file_origin_correction['path']);
            if (file_exists($file_large['path']))             unlink($file_large['path']);

        } catch (\Exception $e) {
            $this->db->rollback();

            if (file_exists($file_origin))                    unlink($file_origin);
            if (file_exists($file_origin_correction['path'])) unlink($file_origin_correction['path']);
            if (file_exists($file_large['path']))             unlink($file_large['path']);


            $photo->status     = 'error';
            $photo->error_text = $e->getMessage();
            $photo->save();

            throw $e;
        }
    }


    /**
     * @param $file_name
     * @param $file_name_new
     * @throws \ImagickException
     */
    private function correctOrientation($file_name, $file_name_new) {

        $img = new \Imagick($file_name);
        $orientation = $img->getImageOrientation();

        switch($orientation) {
            case \imagick::ORIENTATION_BOTTOMRIGHT:
                $img->rotateimage("#fff", 180); // rotate 180 degrees
                break;
            case \imagick::ORIENTATION_RIGHTTOP:
                $img->rotateimage("#fff", 90); // rotate 90 degrees CW
                break;
            case \imagick::ORIENTATION_LEFTBOTTOM:
                $img->rotateimage("#fff", -90); // rotate 90 degrees CCW
                break;
        }

        $img->setImageOrientation(\imagick::ORIENTATION_TOPLEFT);
        $img->writeImage($file_name_new);
        $img->clear();
        $img->destroy();
    }


    /**
     * @param \Zend_Db_Table_Row_Abstract $order
     * @return array
     * @throws \Exception
     */
    private function loadOrderPhotos(\Zend_Db_Table_Row_Abstract $order): array {

        $files       = [];
        $order_files = $this->modMinsk115->dataMinsk115OrdersFiles->getRowsByOrderId($order->id);

        if (empty($order_files)) {
            throw new \Exception('В заявке отсутствуют фотографии');
        }

        foreach ($order_files as $order_file) {
            if ($order_file->status != 'completed') {
                throw new \Exception('Одна из фотографий не обработана');
            }
        }


        $tmp_dir = $this->config->temp . '/minsk115';

        if ( ! is_dir($tmp_dir)) {
            mkdir($tmp_dir);
            chmod($tmp_dir, 0755);
        }

        foreach ($order_files as $order_file) {
            $file_path = "{$tmp_dir}/{$order_file->filename}";
            $files[]   = $file_path;

            file_put_contents($file_path, $order_file->content);
        }

        return $files;
    }
}