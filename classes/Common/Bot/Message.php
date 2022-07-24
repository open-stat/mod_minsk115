<?php
namespace Core2\Mod\Minsk115\Common\Bot;


class Message {

    const TYPE_TEXT             = 'text';
    const TYPE_BUTTONS          = 'buttons';
    const TYPE_LOCATION         = 'location';
    const TYPE_REQUEST_CONTACT  = 'request_contact';
    const TYPE_REQUEST_LOCATION = 'request_location';

    const PARSE_MODE_TEXT        = '';
    const PARSE_MODE_HTML        = 'HTML';
    const PARSE_MODE_MARKDOWN    = 'Markdown';
    const PARSE_MODE_MARKDOWN_V2 = 'MarkdownV2';

    private $type         = 'text';
    private $message_text = '';
    private $parse_mode   = '';
    private $btn_text     = '';
    private $buttons      = [];
    private $location     = [];


    /**
     * @param string $type
     * @param string $message_text
     * @throws \Exception
     */
    public function __construct(string $type = 'text', string $message_text = '') {

        $this->setType($type);
        $this->setMessageText($message_text);
    }


    /**
     * @param string $type
     * @return void
     * @throws \Exception
     */
    public function setType(string $type): void {

        if ( ! in_array($type, [
            self::TYPE_TEXT,
            self::TYPE_BUTTONS,
            self::TYPE_LOCATION,
            self::TYPE_REQUEST_CONTACT,
            self::TYPE_REQUEST_LOCATION])
        ) {
            throw new \Exception('incorrect type answer');
        }

        $this->type = $type;
    }


    /**
     * @return string
     */
    public function getType(): string {
        return $this->type;
    }


    /**
     * @param string $message_text
     */
    public function setMessageText(string $message_text): void {
        $this->message_text = $message_text;
    }


    /**
     * @return string
     */
    public function getMessageText(): string {
        return $this->message_text;
    }


    /**
     * @param array $buttons
     */
    public function setButtons(array $buttons): void {
        $this->buttons = $buttons;
    }


    /**
     * @return array
     */
    public function getButtons(): array {
        return $this->buttons;
    }


    /**
     * @param string $btn_text
     */
    public function setBtnText(string $btn_text): void {
        $this->btn_text = $btn_text;
    }


    /**
     * @return string
     */
    public function getBtnText(): string {
        return $this->btn_text;
    }


    /**
     * @return array
     */
    public function getLocation(): array {
        return $this->location;
    }


    /**
     * @param string $lat
     * @param string $lng
     * @return void
     */
    public function setLocation(string $lat, string $lng): void {
        $this->location = [$lat, $lng];
    }


    /**
     * @return string
     */
    public function getParseMode(): string {
        return $this->parse_mode;
    }


    /**
     * @param string $parse_mode
     * @return void
     * @throws \Exception
     */
    public function setParseMode(string $parse_mode): void {

        if ( ! in_array($parse_mode, [
            self::PARSE_MODE_TEXT,
            self::PARSE_MODE_HTML,
            self::PARSE_MODE_MARKDOWN,
            self::PARSE_MODE_MARKDOWN_V2
        ])
        ) {
            throw new \Exception('incorrect parse_mode');
        }

        $this->parse_mode = $parse_mode;
    }


    /**
     * @return array
     */
    public function toArray(): array {

        switch ($this->type) {
            case self::TYPE_TEXT:
                $answer = [
                    'type'         => $this->type,
                    'message_text' => $this->message_text,
                ];
                break;

            case self::TYPE_BUTTONS:
                $answer = [
                    'type'         => $this->type,
                    'message_text' => $this->message_text,
                    'buttons'      => $this->buttons,
                ];
                break;

            case self::TYPE_LOCATION:
                $answer = [
                    'type'     => $this->type,
                    'location' => $this->location,
                ];
                break;

            case self::TYPE_REQUEST_CONTACT:
            case self::TYPE_REQUEST_LOCATION:
                $answer = [
                    'type'         => $this->type,
                    'message_text' => $this->message_text,
                    'btn_text'     => $this->buttons,
                ];
                break;

            default:
                $answer = [];
        }


        return $answer;
    }
}