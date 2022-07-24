<?php
namespace Core2\Mod\Minsk115\Common\Communicate\Anonymous;
use \Core2\Mod\Minsk115;
use \Core2\Mod\Minsk115\Common\Bot;


/**
 * @property  \ModMinsk115Controller $modMinsk115
 */
class Controller  extends \Common {

    private $tg_user_id = 0;


    /**
     * @param int $tg_user_id
     */
    public function __construct(int $tg_user_id) {
        parent::__construct();

        $this->tg_user_id = $tg_user_id;
    }


    /**
     * @param array $messages
     * @return array
     * @throws \Zend_Config_Exception
     */
    public function getAnswers(array $messages): array {

        $message = end($messages);
        $answers = [];

        switch ($message['type']) {
            case 'text':
                if ($message['text']) {
                    if ($message['text'] == '/start') {
                        $answers[] = $this->startRegister();

                    } elseif (mb_substr($message['text'], 0, 1) == '/') {
                        $answers[] = $this->errorMessage();

                    } elseif (mb_strlen($message['text']) >= 2 && mb_strlen($message['text']) <= 30) {
                        $author_name = str_replace('"', '', $message['text']);
                        $author_name = trim($author_name);

                        if ($author_name) {
                            $author = $this->modMinsk115->dataMinsk115Authors->createRow([
                                'name'        => $author_name,
                                'telegram_id' => $this->tg_user_id,
                            ]);
                            $author->save();

                            $answers[] = $this->register($author_name);

                            if ($this->getModuleConfig('minsk115')?->notify?->admin_new_user) {
                                (new Minsk115\Authors\Model())
                                    ->sendMessageAdmins("Зарегистрирован новый пользователь {$author_name}");
                            }
                        }

                    } else {
                        $answers[] = $this->errorMessage();
                    }
                }
                break;

            default:
                $answers[] = $this->errorMessage();
        }

        return $answers;
    }


    /**
     * @return Bot\Message
     */
    private function startRegister(): Bot\Message {

         $message = new Bot\Message('text', "Напишите как вас называть");

        return $message;
    }


    /**
     * @return Bot\Message
     */
    private function errorMessage(): Bot\Message {

        $message = new Bot\Message('text', "Что-то не то. Давайте еще раз. Жмите /start");

        return $message;
    }


    /**
     * @param string $name
     * @return Bot\Message
     */
    private function register(string $name): Bot\Message {

        $message = new Bot\Message('text', "Готово {$name}. Нажмите на /new чтобы добавить заявку или выберите пункт меню");

        return $message;
    }
}