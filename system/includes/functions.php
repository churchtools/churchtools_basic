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

function includePlugins() {
  foreach (glob("system/main/plugins/*.php") as $filename) {
    include $filename;
  }
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
function cleanDir($dir) {
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
 * TODO: not used
 *
 * @param string $dir; directory name
 */
function rrmDir($dir) {
  if (is_dir($dir)) {
    $objects = scandir($dir);
    foreach ($objects as $object) {
      if ($object != "." && $object != "..") {
        if (filetype($dir."/".$object) == "dir") rrmDir($dir."/".$object); else unlink($dir."/".$object);
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
// TODO: please explain where/whats the problem with the urls
// function getBaseUrl() {
//   // get path part from requested url and remove index.php
//   $baseUrl = str_replace('index.php', '', parse_url($_SERVER['HTTP_HOST']. $_SERVER['REQUEST_URI'], PHP_URL_PATH));
//   // add http(s):// and assure a single trailing /
//   $baseUrl = (!empty($_SERVER['HTTPS']) ? "https://" : "http://"). trim($baseUrl, '/') . '/';
//   // echo " ::: URL: $baseUrl ::: ";
//
//   return $baseUrl;
// }

/**
 * Get the base url in form of http(s)://subdomain.churchtools.de/ or http(s)://server.de/churchtools/
 *
 * @return string
 */
function getBaseUrl() {
  $baseUrl = $_SERVER['HTTP_HOST'];
  $b = $_SERVER['REQUEST_URI'];
  if (strpos($b, "/index.php") !== false)
    $b = substr($b, 0, strpos($b, "/index.php"));
  if (strpos($b, "?") !== false)
    $b = substr($b, 0, strpos($b, "?"));
  $baseUrl = $baseUrl . $b;
  if ($baseUrl[strlen($baseUrl) - 1] != "/")
    $baseUrl .= "/";
  if ((isset($_SERVER['HTTPS'])) && ($_SERVER['HTTPS'] != false))
    $baseUrl = "https://" . $baseUrl;
  else $baseUrl = "http://" . $baseUrl;

  return $baseUrl;
}

/**
 * Get html or txt template. If no data is specified, return content (to eval later).
 * Otherwise replace variables with data and return eval'ed content
 *
 * Always available variables are:
 *  $user from globals and $name, $surename, $nickname of $user
 *  $sitename
 *  $modulename
 * They will be overwritten by variables of $data.
 *
 * @param string $template, may include an folder like email/filename
 * @param string $module
 * @param array  $data; default: false
 * @param string $type; default: html, txt or any other file extension
 */
function getTemplateContent($template, $module, $data = false, $type = 'html') {
  global $user;

  if (!$type) $type = 'html';
  $lang = '_'. getConf("language", 'de');
  $defaultLang = '_en'; // TODO: use constant?
  $template = constant(strtoupper($module)) . TEMPLATES . "/$template";
  $filename = "$template$lang.$type";

  // try to find template file for current or default lang or lang independently or as plain text with or without lang
  if (file_exists("$template$lang.$type"))             $filename = "$template$lang.$type";
  elseif (file_exists("$template$defaultLang.$type"))  $filename = "$template$defaultLang.$type";
  elseif (file_exists("$template.$type"))              $filename = "$template.$type";
  elseif (file_exists("$template$lang.$type"))         $filename = "$template$lang.txt";
  elseif (file_exists("$template$defaultLang.$type"))  $filename = "$template$defaultLang.txt";
  elseif (file_exists("$template.$type"))              $filename = "$template.txt";

  if (!file_exists($filename)) throw new CTFail(t('template.x.not.found', "$template.$type"));

  //get template prefixed with closing php tag to prevent eval take content as php code
  $content =  '?>' . file_get_contents($filename);

  // if no data specified return content
  if (empty($data)) return $content;

  // else extract data into current symbole table and eval content to populate variables
  $nickname = isset($user->spitzname) ? $user->spitzname : $user->vorname;
  $surname  = $user->vorname;
  $name     = $user->name;
  $sitename = getConf('site_name');
  $modulename = getConf($module . '_name', $module);
  if (count($data)) extract($data);
  ob_start();
//  eval('$return = "$content"');
  eval($content);
  $content = ob_get_contents();
  ob_end_clean();
  if (!$content) throw new CTFail(t('error.occured'));  //TODO: refine error message

  return $content;
}

/**
 * TODO: not finished function, maybe not needed
 *
 * @param string $template
 * @param string $module
 */
function getTemplateContentTemp($template, $module, $params) {
  extract ($params);
  ob_start();
  include(constant(strtoupper($module)) . TEMPLATES . "/$template.html");
//   include(constant(strtoupper($module)) . TEMPLATES . (empty($hasError) ? "/$template.html" : 'error.html'));
  $content = ob_get_contents();
  ob_end_clean();

  return $content;
}

$filters;
function addFilter($filterName, $funcName, $prio = 10) {
  global $filters;
  $filters[$filterName][$prio] = array('function' => $funcName);
}

function applyFilter($filterName, $value) {
  global $filters;
  if (!isset($filters[$filterName])) return $value;

  ksort($filters[$filterName]);
  do {
    foreach( (array) current($filters[$filterName]) as $the_ )
    if ( !is_null($the_) ){
      $args = array();
      $args[0] = $value;
      $value = call_user_func_array($the_, $args);
    }
  } while ( next($filters[$filterName]) !== false);
  return $value;
}
