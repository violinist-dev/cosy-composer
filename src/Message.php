<?php

namespace eiriksm\CosyComposer;

class Message
{

  /**
   * @var string
   */
    protected $message;

  /**
   * @var int
   */
    protected $timestamp;

  /**
   * @var string
   */
    protected $type;

    const MESSAGE = 'message';
    const UPDATE = 'update';
    const ERROR = 'error';
    const PR_URL = 'pr_url';
    const UNUPDATEABLE = 'unupdate';
    const COMMAND = 'command';
    const VIOLINIST_ERROR = 'violinist_error';

  /**
   * Message constructor.
   * @param $message
   */
    public function __construct($message, $type = self::MESSAGE)
    {
        $this->message = $message;
        $this->type = $type;
        $this->timestamp = time();
    }

  /**
   * @return string
   */
    public function getMessage()
    {
        return $this->message;
    }

    public function getTimestamp()
    {
        return $this->timestamp;
    }

    public function getType()
    {
        return $this->type;
    }
}
