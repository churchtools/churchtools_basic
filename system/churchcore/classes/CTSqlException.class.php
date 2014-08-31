<?php
class SQLException extends Exception {
  // Die Exceptionmitteilung neu definieren, damit diese nicht optional ist
  public function __construct($message, $code = 0) {
    // etwas Code

    // sicherstellen, dass alles korrekt zugewiesen wird
    parent::__construct($message, $code);
  }
  // ma�geschneiderte Stringdarstellung des Objektes
  public function __toString() {
    return __CLASS__ . ": [{$this->code}]: {$this->message}\n";
  }

}
?>