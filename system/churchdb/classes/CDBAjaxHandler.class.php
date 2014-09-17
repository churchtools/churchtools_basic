<?php
/**
 * Handles Ajax calls
 *
 * Add as much functions as you want.
 * The wished function for call has to be specified in the request varible $func
 * using call() and returns jsend()->success().
 *
 * Usage: addFunction(Function, Right or Null, $auth_module_name )
 *
 * Example:
 *   $ajax = new CDBAjaxHandler();
 *   $ajax->addFunction("getCalEvents", "view");
 *   $ajax->call();
 */
class CDBAjaxHandler extends CTAjaxHandler {
//   TODO: use module specific ajax handlers for ajax functions? Is this the same as CTChurchXxxModule?
}

?>