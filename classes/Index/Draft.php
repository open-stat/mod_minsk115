<?php
namespace Core2\Mod\Minsk115\Index;


/**
 * @property \ModMinsk115Controller $modMinsk115
 */
class Draft extends \Common {

    private int $author_id;


    /**
     * @param int $author_id
     */
    public function __construct(int $author_id) {
        parent::__construct();
        $this->author_id = $author_id;
    }


    /**
     * @param string $user_comment
     * @return void
     */
    public function setUserComment(string $user_comment): void {

        $order = $this->modMinsk115->dataMinsk115Orders->getRowDraftByAuthorId($this->author_id);

        if ( ! $order) {
            $order = $this->modMinsk115->dataMinsk115Orders->createRow([
                'author_id'    => $this->author_id,
                'user_comment' => $user_comment,
                'status'       => 'draft',
            ]);

        } else {
            $order->user_comment = $user_comment;
        }

        $order->save();
    }


    /**
     * @param string $photo_path
     * @param string $user_comment
     * @return void
     * @throws \Zend_Db_Table_Row_Exception
     */
    public function addPhoto(string $photo_path, string $user_comment = ''): void {

        $order = $this->modMinsk115->dataMinsk115Orders->getRowDraftByAuthorId($this->author_id);
        if ( ! $order) {
            $order = $this->modMinsk115->dataMinsk115Orders->createRow([
                'author_id'    => $this->author_id,
                'user_comment' => $user_comment,
                'status'       => 'draft',
            ]);
        } else {
            if ($user_comment) {
                $order->user_comment = $user_comment;
            }
        }

        $order->save();


        $this->modMinsk115->dataMinsk115OrdersFiles->createPhotoByFilePath($order->id, $photo_path);


        // Удаление лишних файлов
        $files = $this->modMinsk115->dataMinsk115OrdersFiles->getRowsByOrderId($order->id);

        if (count($files) >= 4) {
            $i = 1;
            foreach ($files as $file) {
                if ($i++ > 3) {
                    $file->delete();
                }
            }
        }
    }


    /**
     * @param string $lat
     * @param string $lng
     * @return void
     */
    public function setLocation(string $lat, string $lng) {

        $order = $this->modMinsk115->dataMinsk115Orders->getRowDraftByAuthorId($this->author_id);
        if ( ! $order) {
            $order = $this->modMinsk115->dataMinsk115Orders->createRow([
                'author_id' => $this->author_id,
                'lat'       => $lat,
                'lng'       => $lng,
                'status'    => 'draft',
            ]);

        } else {
            $order->lat = $lat;
            $order->lng = $lng;
        }

        $order->save();
    }


    /**
     * @return bool
     */
    public function issetCoordinates(): bool {

        $order = $this->modMinsk115->dataMinsk115Orders->getRowDraftByAuthorId($this->author_id);

        return $order && $order->lat && $order->lng;
    }


    /**
     * @return bool
     * @throws \Exception
     */
    public function checkOrderParams(): bool {

        $order = $this->modMinsk115->dataMinsk115Orders->getRowDraftByAuthorId($this->author_id);

        if ($order) {
            if (empty($order->user_comment)) {
                return false;
            }

            if (empty($order->lat) || empty($order->lng)) {
                return false;
            }

            $count_photos = $this->modMinsk115->dataMinsk115OrdersFiles->getCountByOrderId($order->id);

            if ( ! $count_photos) {
                return false;
            }

            return true;
        }

        return false;
    }


    /**
     * @return bool
     */
    public function checkDescription(): bool {

        $order = $this->modMinsk115->dataMinsk115Orders->getRowDraftByAuthorId($this->author_id);

        if ($order && ! empty($order->user_comment)) {
            return true;
        }

        return false;
    }


    /**
     * @return bool
     */
    public function checkLocation(): bool {

        $order = $this->modMinsk115->dataMinsk115Orders->getRowDraftByAuthorId($this->author_id);

        if ($order && ! empty($order->lat) && ! empty($order->lng)) {
            return true;
        }

        return false;
    }


    /**
     * @return false
     */
    public function checkPhotos(): bool {

        $order = $this->modMinsk115->dataMinsk115Orders->getRowDraftByAuthorId($this->author_id);

        if ($order) {
            $count_photos = $this->modMinsk115->dataMinsk115OrdersFiles->getCountByOrderId($order->id);

            if ($count_photos > 0) {
                return true;
            }
        }

        return false;
    }


    /**
     * @return void
     * @throws \Exception
     */
    public function doModerate(): void {

        $order = $this->modMinsk115->dataMinsk115Orders->getRowDraftByAuthorId($this->author_id);

        if ($order) {
            if (empty($order->user_comment)) {
                throw new \Exception('Не указан текст с описанием для заявки');
            }

            if (empty($order->lat) || empty($order->lng)) {
                throw new \Exception('Не указано местоположение для заявки');
            }

            $count_photos = $this->modMinsk115->dataMinsk115OrdersFiles->getCountByOrderId($order->id);

            if ( ! $count_photos) {
                throw new \Exception('Не загружены фотографии для заявки');
            }


            $order->status = 'moderate';
            $order->save();
        }
    }


    /**
     * @return void
     * @throws \Exception
     */
    public function doModerate115(): void {

        $order = $this->modMinsk115->dataMinsk115Orders->getRowDraftByAuthorId($this->author_id);

        if ($order) {
            if (empty($order->user_comment)) {
                throw new \Exception('Не указан текст с описанием для заявки');
            }

            if (empty($order->lat) || empty($order->lng)) {
                throw new \Exception('Не указано местоположение для заявки');
            }

            $count_photos = $this->modMinsk115->dataMinsk115OrdersFiles->getCountByOrderId($order->id);

            if ( ! $count_photos) {
                throw new \Exception('Не загружены фотографии для заявки');
            }


            $order->status = 'moderate115';
            $order->save();
        }
    }


    /**
     * @return void
     * @throws \Exception
     */
    public function delete(): void {

        $order = $this->modMinsk115->dataMinsk115Orders->getRowDraftByAuthorId($this->author_id);

        if ($order) {
            $order->delete();
        }
    }


    /**
     * @return \Zend_Db_Table_Row_Abstract|null
     */
    public function getOrder(): ?\Zend_Db_Table_Row_Abstract {

        return $this->modMinsk115->dataMinsk115Orders->getRowDraftByAuthorId($this->author_id);
    }


    /**
     * @param string $image_path
     * @return array|null
     */
    public function getCoordinates(string $image_path):? array {

        if ( ! file_exists($image_path) || ! is_file($image_path)) {
            return null;
        }

        $mime = mime_content_type($image_path);

        if ($mime != 'image/jpeg') {
            return null;
        }

        $exif = exif_read_data($image_path);

        if (empty($exif) ||
            empty($exif['GPSLatitude']) ||
            empty($exif['GPSLatitudeRef']) ||
            empty($exif['GPSLongitude']) ||
            empty($exif['GPSLongitudeRef'])
        ) {
            return null;
        }

        $lat = $this->getGps($exif["GPSLatitude"], $exif['GPSLatitudeRef']);
        $lng = $this->getGps($exif["GPSLongitude"], $exif['GPSLongitudeRef']);

        return [$lat, $lng];
    }


    /**
     * @param array  $exif_coordinate
     * @param string $hemisphere
     * @return float
     */
    private function getGps(array $exif_coordinate, string $hemisphere): float {

        $degrees = count($exif_coordinate) > 0 ? $this->gps2Num($exif_coordinate[0]) : 0;
        $minutes = count($exif_coordinate) > 1 ? $this->gps2Num($exif_coordinate[1]) : 0;
        $seconds = count($exif_coordinate) > 2 ? $this->gps2Num($exif_coordinate[2]) : 0;

        $flip = ($hemisphere == 'W' || $hemisphere == 'S') ? -1 : 1;

        return $flip * ($degrees + $minutes / 60 + $seconds / 3600);
    }


    /**
     * @param string $coordinate_part
     * @return float
     */
    private function gps2Num(string $coordinate_part): float {

        $parts = explode('/', $coordinate_part);

        if (count($parts) <= 0)
            return 0;

        if (count($parts) == 1)
            return (float)$parts[0];

        return floatval($parts[0]) / floatval($parts[1]);
    }
}