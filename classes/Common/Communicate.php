<?php
namespace Core2\Mod\Minsk115\Common;
use \Core2\Mod\Minsk115\Common\Bot;
use \Core2\Mod\Minsk115;


/**
 * @property  \ModMinsk115Controller $modMinsk115
 */
class Communicate extends \Common {

    private $tg_user_id = 0;
    private $bot        = null;
    private $messages   = [];


    /**
     * @param int                            $tg_user_id
     * @param \Core2\Mod\Minsk115\Common\Bot $bot
     */
    public function __construct(int $tg_user_id, Minsk115\Common\Bot $bot) {
        parent::__construct();

        $this->tg_user_id = $tg_user_id;
        $this->bot        = $bot;
    }


    /**
     * Ответ на сообщения пользователя
     * @param array $messages
     * @throws \Exception
     */
    public function processAnswer(array $messages) {

        $author = $this->modMinsk115->dataMinsk115Authors->getRowByTelegramId($this->tg_user_id);

        // Зарегистрированный пользователь
        if ($author) {
            $author->date_last_activity = new \Zend_Db_Expr('NOW()');
            $author->save();

            if ($author->is_banned_sw == 'Y') {
                $this->messages[] = new Bot\Message('text', "Доступ заблокирован");

            } else {
                $this->messages = (new Communicate\User\Controller($author, $this->bot))
                    ->getAnswer($messages);
            }

        } else {
            $this->messages = (new Communicate\Anonymous\Controller($this->tg_user_id))
                ->getAnswers($messages);
        }
    }


    /**
     * @return array
     */
    public function getMessages(): array {

        return $this->messages;
    }
}