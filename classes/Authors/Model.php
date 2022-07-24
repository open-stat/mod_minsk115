<?php
namespace Core2\Mod\Minsk115\Authors;
use \Core2\Mod\Minsk115;

/**
 * @property \ModMinsk115Controller $modMinsk115
 */
class Model extends \Common {

    /**
     * @var Minsk115\Common\Bot
     */
    private $bot;


    /**
     * @param \Zend_Db_Table_Row_Abstract $author
     * @param string                      $message_text
     * @return bool
     * @throws \Longman\TelegramBot\Exception\TelegramException
     * @throws \Zend_Config_Exception
     * @throws \Exception
     */
    public function sendMessageText(\Zend_Db_Table_Row_Abstract $author, string $message_text): bool {

        if ( ! $author->telegram_id) {
            return false;
        }


        $message = new Minsk115\Common\Bot\Message('text', $message_text);
//        $message->setParseMode($message::PARSE_MODE_MARKDOWN_V2);

        $this->getBot()->sendMessage($author->telegram_id, $message);

        return true;
    }


    /**
     * @param string $message_text
     * @return bool
     * @throws \Longman\TelegramBot\Exception\TelegramException
     * @throws \Zend_Config_Exception
     */
    public function sendMessageAdmins(string $message_text): bool {

        $admins = $this->modMinsk115->dataMinsk115Authors->getRowsAdmin();

        if ($admins) {
            foreach ($admins as $admin) {
                $this->sendMessageText($admin, $message_text);
            }

            return true;
        }

        return false;
    }


    /**
     * @return Minsk115\Common\Bot
     * @throws \Longman\TelegramBot\Exception\TelegramException
     * @throws \Zend_Config_Exception
     */
    private function getBot(): Minsk115\Common\Bot {

        if ( ! $this->bot) {
            $config = $this->getModuleConfig('minsk115');
            $token  = $config?->bot?->tg?->token;

            $this->bot = new Minsk115\Common\Bot($token, [
                'bot_username' => $config?->bot?->tg?->bot_username ?? '',
                'download_dir' => $config?->bot?->tg?->download_dir ?: $this->config->temp,
                'log_dir'      => $config?->bot?->tg?->log_dir ?: dirname(realpath($this->config?->log?->path)),
            ]);
        }

        return $this->bot;
    }
}