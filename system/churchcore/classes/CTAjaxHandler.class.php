<?php

/**
 * Handle Ajax calls
 *
 * Add as much functions as you want.
 * The wished function for call has to be specified in the get or post varible $func
 * using call() and returns jsend()->success().
 * 
 * Usage: addFunction(Function, Right or Null, $auth_module_name )
 *
 * Example:
 * $ajax = new CTAjaxHandler($module);
 * $ajax->addFunction("getCalEvents", "view");
 * $ajax->call();
 */
class CTAjaxHandler {
  private $modulename = "church";
  private $module = null;
  private $funcs = array();

  /**
   * 
   * @param string $module
   */
  public function __construct($module) {
    global $ajax;
    $ajax = true;
    $this->module = $module;
    $this->modulename = $module->getModuleName();
  }

  /**
   * Get function name preceded by $modulname_
   *
   * TODO: is it really needed? replace it in class functions (3x used) by "{$this->modulename}_$func_name"
   *
   * @param string $func_name          
   *
   * @return string
   */
  function getFunctionName($func_name) {
    return $this->modulename . "_" . $func_name;
  }

  /**
   * Add Function with optional rights
   *
   * @param string $func_name          
   * @param string $auth_rights, default:null
   * @param string $auth_module_name, default:null
   *          
   * @throws CTException
   */
  function addFunction($func_name, $auth_rights = null, $auth_module_name = null) {
    if (!function_exists($this->getFunctionName($func_name))) {
      throw new CTException("Function " . $this->getFunctionName($func_name) . " nicht gefunden!");
    }
    if ($auth_module_name == null) $auth_module_name = $this->modulename;
    $this->funcs[$func_name] = array ("name" => $func_name, "auth" => $auth_rights, "module" => $auth_module_name);
  }

  /**
   * Call function and returns JSON result
   *
   * @return string
   */
  function call() {
    $params = isset($_GET["func"]) ? $_GET : $_POST;
    
    if (!$func = getVar('func')) {
      return jsend()->error("Parameter func nicht definiert!");
    }
    
    try {
      if (method_exists($this->module, $func)) {
        return jsend()->success($this->module->$func($params));
      }
      
      if (!isset($this->funcs[$func])) {
        return jsend()->error("Function $func was not defined as Function!");
      }
      $func = $this->funcs[$func];
      
      if ($func["auth"] != null) {
        // Split auth string for OR-Combinations
        $allowed = false;
        foreach (explode('||', $func["auth"]) as $val) {
          if (user_access(trim($val), $func["module"])) $allowed = true;
        }
        if (!$allowed) throw new CTNoPermission($func["auth"], $func["module"]);
      }
      
      return jsend()->success(call_user_func($this->getFunctionName($func["name"]), $params));
    }
    // Lightweight error like record already exists, handled by client
    catch (CTFail $e) {
      return jsend()->fail($e);
    }
    // No Permissions
    catch (CTNoPermission $e) {
      return jsend()->fail($e);
    }
    // Fatal error, immediate stop the application
    catch (Exception $e) {
      return jsend()->error($e);
    }
  }

}
