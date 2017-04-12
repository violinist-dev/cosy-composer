<?php

namespace eiriksm\CosyComposer;

class Message {

  /**
   * @var string
   */
  protected $message;

  /**
   * @var int
   */
  protected $timestamp;

  /**
   * Message constructor.
   * @param $message
   */
  public function __construct($message) {
    $this->message = $message;
    $this->timestamp = time();
  }

  /**
   * @return string
   */
  public function getMessage() {
    return $this->message;
  }

  public function getTimestamp() {
    return $this->timestamp;
  }

}
