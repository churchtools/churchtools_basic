<?php

/**
 * Exception for handling lightweight errors, like DB record already exists
 *
 * TODO: rename to CTWarnException?
 */
class CTFail extends Exception {

  /**
   * $message needed
   *
   * @param string $message          
   * @param number $code
   *          default 0
   */
  public function __construct($message, $code = 0) {
    parent::__construct($message, $code);
  }

  /**
   * string representation adapted
   */
  public function __toString() {
    return $this->message;
  }

}