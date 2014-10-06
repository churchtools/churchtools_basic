<?php
/**
 *  core functions used in churchtools
 */

/**
 * @ignore
 */
//if (!defined('IN_CT')) exit; //TODO: should this be added to each php file to prevent using it from outside CT?

/**
 * autoloads needed classes
 * TODO: need module home to be handled separate?
 * can we identify core classes without file_exists?
 * look at http://php.net/manual/en/function.spl-autoload.php if we should use this instead
 * 
 * @param string $class_name
 * @return nothing
 *
 **/
function __autoload($class_name)
{
  if (file_exists(CHURCHCORE.CLASSES."/".$class_name.'.class.php')) include CHURCHCORE.CLASSES."/".$class_name.'.class.php';
  else include constant(strtoupper($GLOBALS['currentModule'])).CLASSES."/".$class_name.'.class.php';
}

/**
 * Read var from $_REQUEST or from any other array and return the value or default.
 * $_REQUEST as default array doesnt work.
 *
 * @param array $var
 * @param bool $default; default false 
 * @param array $array as reference; default false ==> $_REQUEST is used, or any array
 * @return mixed value
 */
function getVar($var, $default = false, &$array = false) {
  if ($array === false) $array =& $_REQUEST;
  $var = isset($array[$var]) ? $array[$var] : $default;
  return $var;
}

/**
 * Read var from $config or $mapping and return the value or default.
 *
 * @param array $var
 * @param mixed $default; default false 
 * @return mixed value
 */
function getConf($var, $default = false) {
  global $config, $mapping;
  
  $var = isset($config[$var]) ? $config[$var] : (isset($mapping[$var]) ? $mapping[$var] : $default);
  return $var;
}

/**
 * Delete all files in folder $dir
 *
 * @param string $dir; directory name
 */
function cleandir($dir) {
  if (is_dir($dir)) {
    $objects = scandir($dir);
    foreach ($objects as $object) {
      if ($object != "." && $object != "..") {
        if (filetype($dir."/".$object) != "dir") {
          unlink($dir."/".$object);
        }
      }
    }
  }
}

/**
 * Recursively delete all files and directories in folder $dir
 *
 * @param string $dir; directory name
 */
function rrmdir($dir) {
  if (is_dir($dir)) {
    $objects = scandir($dir);
    foreach ($objects as $object) {
      if ($object != "." && $object != "..") {
        if (filetype($dir."/".$object) == "dir") rrmdir($dir."/".$object); else unlink($dir."/".$object);
      }
    }
    reset($objects);
    rmdir($dir);
  }
}

/**
 * Get the base url in form of http(s)://subdomain.churchtools.de/ or http(s)://server.de/churchtools/
 *
 * @return string
 */
function getBaseUrl() { 
  $baseUrl = $_SERVER['HTTP_HOST'];
  $b = $_SERVER['REQUEST_URI'];
  if (strpos($b, "/index.php")!==false)
    $b=substr($b,0,strpos($b, "/index.php"));
  if (strpos($b, "?")!==false)
    $b=substr($b,0,strpos($b, "?"));
  $baseUrl=$baseUrl.$b;  
  if ($baseUrl[strlen($baseUrl)-1]!="/")
    $baseUrl.="/";
  if ((isset($_SERVER['HTTPS'])) && ($_SERVER['HTTPS']!=false))
    $baseUrl="https://".$baseUrl;
  else $baseUrl="http://".$baseUrl;
  
  return $baseUrl;
}

/**
 * TODO: not finished
 * 
 * @param string $template, may include an folder like email/file
 * @param string $module
 */
function getTemplateContent($template, $module) {
  $file = constant(strtoupper($module)) . TEMPLATES . "/$template.html";
  
  return file_get_contents($file);;
}
/**
 * TODO: not finished
 * 
 * @param string $template
 * @param string $module
 */
function getTemplateContent1($template, $module, $params) {
  extract ($params);
  ob_start();
  include(constant(strtoupper($module)) . TEMPLATES . "/$template.html");
//   include(constant(strtoupper($module)) . TEMPLATES . (empty($hasError) ? "/$template.html" : 'error.html'));
  $content = ob_get_contents();
  ob_end_clean();
  
  return $content;
}
