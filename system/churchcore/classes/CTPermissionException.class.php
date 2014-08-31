<?php
/**
 * Exception for handling missed permissions
 *
 * TODO: change name, e.g. to CTRightsException?
 */
class CTNoPermission extends CTFail {

  /**
   * @param string $auth
   * @param string $modulename
   */
  public function __construct($auth, $modulename) {
    parent::__construct( t('no.sufficient.permission', "$auth ($modulename)"));
  }
}
?>