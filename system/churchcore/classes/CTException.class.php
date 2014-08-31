<?php
/**
 * 
 * CLass for handling exceptions, 
 *
 */
class CTException extends Exception {
  
  /**
   * $message is not optional like in php exception
   * 
   * @param string $message
   * @param number $code, default 0
   */
  public function __construct($message, $code = 0) {
      parent::__construct($message, $code);
  }
  
  /**
   * report error with styling 
   * 
   * TODO: integrate error into a CT website template
   */
  public function reportError() {
      return __CLASS__ . ": [{$this->code}]: {$this->message}\n";
  }
  
  /**
   * string representation adapted 
   */
  public function __toString() {
      return __CLASS__ . ": [{$this->code}]: {$this->message}\n";
  }
}

?>