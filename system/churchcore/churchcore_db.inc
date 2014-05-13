<?php

define("CDB_LOG_PERSON", 'person');
define("CDB_LOG_GROUP", 'group');
define("CDB_LOG_MASTERDATA", 'masterData');
define("CDB_LOG_TAG", 'tag');


/**
 * Interface for implementing ChurchTools-Modules
 */
interface CTModuleInterface {
  /**
   * A string containing the module name
   */
  public function getModuleName();
  
  /**
   * Relative path to the module, e.g. system/churchresource
   */
  public function getModulePath();
    
  /**
   * A complete array with relevant master data for JS-View
   */
  public function getMasterData();
  
  /**
   * Saves conrete one row in one table
   *  
   * @param unknown_type $params array(id, table and col0..x columname and value0..x for data).
   */
  public function saveMasterData($params);
  
  /**
   * Deletes one row in one table. 
   *  
   * @param unknown_type $params array(id, table)
   */
  public function deleteMasterData($params);
  
  /**
   * Saves the user setting for the current login user
   * 
   * @param unknown_type $params array(sub, val) 
   */
  public function saveSetting($params);

  /**
   * Get all user settings for current login user
   */
  public function getSettings();  
  
  /**
   * Return an array with churchcore_getMasterDataEntry() entries, for all MasterData tables. 
   * Or null if there is no table needed.
   */
  public function getMasterDataTablenames();    
}

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

function cleanI18nFiles() {
  global $files_dir;
  cleandir("$files_dir/files/messages/");
}

function loadI18nFile($modulename) {
  global $config;
  $i18n = new TextBundle("system/$modulename/resources/messages");
  $i18n->load($modulename, $config["language"]);
  return $i18n;
}

function createI18nFile($modulename) {
  global $config, $files_dir;
  
  if (!file_exists("$files_dir/files/messages/")) 
    mkdir("$files_dir/files/messages", 0777, true);

  $filename="$files_dir/files/messages/$modulename"."_".$config["language"].".js";
  if (!file_exists($filename)) {
    $i18n=loadI18nFile($modulename);
    $i18n->writeJSFile($filename);
  }
  return $filename;  
}

abstract class CTAbstractModule implements CTModuleInterface {
  private $modulename="abstract";

  public function __construct($modulename) {
    global $config, $files_dir, $i18n;
    if ($modulename==null) die("No Modulename given in new CTModule()");
    $this->modulename=$modulename;    
  }
  
  public function getModuleName() {
    return $this->modulename;
  } 
  
  public function getModulePath() {
    return drupal_get_path('module', $this->modulename);
  }
  
  public function deleteMasterData($params) {
    if ((user_access("edit masterdata",$this->modulename)) && (churchcore_isAllowedMasterData($this->getMasterDataTablenames(), $params["table"]))) {
      churchcore_deleteMasterData($params["id"], $params["table"]);
      $this->logMasterData($params);
    }
    else throw new CTNoPermission("edit masterdata", $this->modulename);  
  }
  
  public function saveMasterData($params) {
    if ((user_access("edit masterdata",$this->modulename)) && (churchcore_isAllowedMasterData($this->getMasterDataTablenames(), $params["table"]))) {      
      churchcore_saveMasterData($params["id"], $params["table"]);
      $this->logMasterData($params);
    } 
    else throw new CTNoPermission("edit masterdata",$this->modulename);  
  }  
  
  public function saveSetting($params) {
    global $user;
    churchcore_saveUserSetting($this->modulename, $user->id, $params["sub"], $params["val"]);
  }
  
  public function getSettings() {
    global $user;
    return churchcore_getUserSettings($this->modulename,$user->id); 
  }  
  
  public function editNotification($params) {
    global $user;
    if (empty($params["person_id"]))
      $params["person_id"]=$user->id;
    
    $i = new CTInterface();
    $i->setParam("domain_type");
    $i->setParam("domain_id");
    $i->setParam("person_id");
    $i->setParam("notificationtype_id", false);
    
    // Delete when abotype is not set
    if (empty($params["notificationtype_id"])) {
      db_delete("cc_notification")
      ->fields($i->getDBInsertArrayFromParams($params))
      ->condition("domain_type", $params["domain_type"], "=")
      ->condition("domain_id", $params["domain_id"], "=")
      ->condition("person_id", $user->id, "=")
      ->execute(false);
    }
    else {
      try {
        db_insert("cc_notification")
          ->fields($i->getDBInsertArrayFromParams($params))
          ->execute(false);
      }
      catch (Exception $e) {
        db_update("cc_notification")
          ->fields($i->getDBInsertArrayFromParams($params))
          ->condition("domain_type", $params["domain_type"], "=")
          ->condition("domain_id", $params["domain_id"], "=")
          ->condition("person_id", $user->id, "=")
          ->execute(false);
      }
    }
  }  
  
  protected function getMasterDataTables() {
    $tables=$this->getMasterDataTablenames();
    foreach ($tables as $value) {
      $res[$value["shortname"]]=churchcore_getTableData($value["tablename"],$value["sql_order"]);
    }
    return $res;
  }  
  
  protected function checkPerm($auth, $modulename=null, $datafield=null) {
    if ($modulename==null) $modulename=$this->modulename;
    $perm=user_access($auth, $modulename);
    if ((!$perm) || ($datafield!=null && !isset($perm[$datafield])))
      throw new CTNoPermission($auth, $modulename);
  }
  
  public function notify($domain_type, $domain_id, $txt, $loglevel=2) {
    ct_notify($domain_type, $domain_id, $txt, $loglevel=2);
  }
  
  public function log($params, $loglevel=2) {
    ct_log(makeParamsLoggable($params), $loglevel);
  }
  public function logPerson($params, $loglevel=2) {
    ct_log(makeParamsLoggable($params), $loglevel, (isset($params["id"])?$params["id"]:null), CDB_LOG_PERSON);    
  }
  public function logGroup($params, $loglevel=2) {
    ct_log(makeParamsLoggable($params), $loglevel, (isset($params["id"])?$params["id"]:null), CDB_LOG_GROUP);
  }
  public function logMasterData($params, $loglevel=2) {
    ct_log(makeParamsLoggable($params), $loglevel, (isset($params["id"])?$params["id"]:null), CDB_LOG_MASTERDATA);
  }
  
  public function getMasterDataTablenames() {
    return null;
  }
    
}

function ct_notify($domain_type, $domain_id, $txt, $loglevel=2) {
  global $user;
  ct_log($txt, $loglevel, $domain_id, $domain_type); 
  
  $notify=db_query('select * from {cc_notification} n, {cc_notificationtype} nt 
             where n.notificationtype_id=nt.id and n.person_id=:p_id 
              and n.domain_id=:domain_id and n.domain_type=:domain_type
              and nt.delay_hours=0',
         array(':p_id'=>$user->id, ":domain_id"=>$domain_id, ":domain_type"=>$domain_type))->fetch();
  if ($notify!==false) {
    ct_sendPendingNotifications(0); 
  }  
}

/**
 * Send pending notifications. 
 * @param string $max_delayhours, can be null for all. 
 */
function ct_sendPendingNotifications($max_delayhours=null) {
  $nts=churchcore_getTableData("cc_notificationtype", "delay_hours", 
       ($max_delayhours!=null?"delay_hours<=$max_delayhours":""));
  
  foreach ($nts as $nt) {
    // Check if there a pending notifications for a person and domain_type  
    $personANDtypes=db_query('select n.person_id, n.domain_type from {cc_notification} n
             where n.notificationtype_id=:nt_id and (lastsenddate is null or
                    (time_to_sec(timediff(now(), lastsenddate)) / 3600)>:delay_hours)
                  group by n.person_id, n.domain_type',
            array(':nt_id'=>$nt->id, ':delay_hours'=>$nt->delay_hours));

    // Now collect all notifications in this type for each person and domain_type
    foreach ($personANDtypes as $personANDtype) {
      $notis=db_query('select * from {cc_notification} n 
                where n.person_id=:person_id and n.notificationtype_id=:nt_id and n.domain_type=:dt_id',
              array(':person_id'=>$personANDtype->person_id, 
                    ':nt_id'=>$nt->id, 
                    ':dt_id'=>$personANDtype->domain_type));
      
      $msg="";
      
      // Now get all logs for each notification after each lastsenddate
      foreach ($notis as $noti) {
        $logs=db_query("select l.txt, DATE_FORMAT(datum, '%e.%c.%Y %H:%i') date from {cdb_log} l where domain_type=:domain_type and domain_id=:domain_id
          and (:lastsenddate is null or time_to_sec(timediff(datum, :lastsenddate))>0) order by datum desc",
          array(":domain_type"=>$personANDtype->domain_type, ":domain_id"=>$noti->domain_id, ":lastsenddate"=>$noti->lastsenddate));
        foreach($logs as $log) {
          $msg.="<li>$log->date - <i>$log->txt</i>";
        }                        
      }    
      if ($msg!="") {
        $p=churchcore_getUserById($personANDtype->person_id);
        if ($p!=null && $p->email!="") {
          $msg="<h3>Hallo $p->vorname!</h3>".
               "<p>Hier Deine neuen Benachrichtigungen f&uuml;r ".t($personANDtype->domain_type).":</p>".
               "<ul>$msg</ul>".
               "<p><p><small>Einstellung f&uuml;r Versand: $nt->bezeichnung</small>";
          churchcore_systemmail($p->email, "[".variable_get('site_name')."] Neue Abonnement-Benachrichtigung (".t($personANDtype->domain_type).")", $msg, true);
          }
      }
      
      // Now mark notification with current send date
      $notis=db_query('update {cc_notification} n set lastsenddate=now()
                where n.person_id=:person_id and n.notificationtype_id=:nt_id and n.domain_type=:dt_id',
              array(':person_id'=>$personANDtype->person_id, 
                    ':nt_id'=>$nt->id, 
                    ':dt_id'=>$personANDtype->domain_type));
    }    
  }  
}


function makeParamsLoggable($params) {
  $my=array();
  foreach ($params as $key=>$param) {
    if (($key!="q") && ($key!="func")) {
      if (is_array($param))
        $my[]="$key:".serialize($param);
      else
        $my[]="$key:$param";
    }
  } 
  $str="";
  if (isset($params["func"])) $str.=$params["func"]." ";
  if (count($my)>0) 
    $str.="(".implode(", ", $my).")";
  return $str;
}

class CTException extends Exception {
  // Die Exceptionmitteilung neu definieren, damit diese nicht optional ist
  public function __construct($message, $code = 0) {
      // etwas Code

      // sicherstellen, dass alles korrekt zugewiesen wird
      parent::__construct($message, $code);
  }
  // ma§geschneiderte Stringdarstellung des Objektes
  public function __toString() {
      return __CLASS__ . ": [{$this->code}]: {$this->message}\n";
  }
}

// Fail ist kein schlimmer Fehler, sondern nur ein Sache nicht korrekt behandelt, z.B. Datensatz schon vorhanden
class CTFail extends Exception {
  // Die Exceptionmitteilung neu definieren, damit diese nicht optional ist
  public function __construct($message, $code = 0) {
      // sicherstellen, dass alles korrekt zugewiesen wird
      parent::__construct($message, $code);
  }
  // ma§geschneiderte Stringdarstellung des Objektes
  public function __toString() {
      return $this->message;
  }
}

// Nicht genug Recht die Funktion auszufŸhren
class CTNoPermission extends CTFail {
  // Die Exceptionmitteilung neu definieren, damit diese nicht optional ist
  public function __construct($auth, $modulename) {
    $this->auth=$auth;
    $this->modulename=$modulename;
    // sicherstellen, dass alles korrekt zugewiesen wird
    parent::__construct($this, 0);
  }
  public function __toString() {
    return "Keine ausreichende Berechtigung. Das Recht $this->auth ($this->modulename) ist notwendig.";
  }
}

/**
 * Der AjaxHandler muss beim Erstellen mit dem Modulenamen gefŸttert werden.
 * Dann Functionen hinzufŸgen mit addFunction(NameDerFunction, Recht oder Null)
 * Dann spŠter einfach nur call() aufrufen. Er fŸhrt die Function aus und gibt es mit jsend()->success() zurŸck.
 */
class CTAjaxHandler {
  private $modulename="church";
  private $module=null;
  private $funcs = array();

  public function __construct($module) {
    $this->module=$module;
    $this->modulename=$module->getModuleName();
  }
  
  function getFunctionName($func_name) {
    return $this->modulename."_".$func_name;
  }
  
  /*
   * Add Function name and optional an auth
   */
  function addFunction($func_name, $auth_rights=null, $auth_module_name=null) {
    if (!function_exists($this->getFunctionName($func_name)))
      throw new CTException("Function ".$this->getFunctionName($func_name)." nicht gefunden!");
    if ($auth_module_name==null) $auth_module_name=$this->modulename;
    $this->funcs[$func_name]=array("name"=>$func_name, "auth"=>$auth_rights, "module"=>$auth_module_name);
  }
  
  function call() {
    if (isset($_GET["func"]))
      $params=$_GET;
    else $params=$_POST;

    if (!isset($params["func"])) 
      return jsend()->error("Parameter func nicht definiert!");
            
    try {
      if (method_exists($this->module, $params["func"]))
        return jsend()->success($this->module->$params["func"]($params));        
        
      if (!isset($this->funcs[$params["func"]])) 
        return jsend()->error("Func ".$params["func"]." wurde nicht als Function definiert!");
      $func=$this->funcs[$params["func"]];
      
      if ($func["auth"]!=null) {
        // Split auth string for OR-Combinations
        $drin=false;
        foreach (explode('||',$func["auth"]) as $val) {
          if (user_access(trim($val), $func["module"])) $drin=true;
        }
        if (!$drin)
          throw new CTNoPermission($func["auth"], $func["module"]);        
      }      
      return jsend()->success(call_user_func($this->getFunctionName($func["name"]), $params));      
    } 
    // Kein schlimmer Fehler, soll in der Anwendung behandelt werden. Z.B. Datensatz schon vorhanden
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

function ajax() {
  return new CTAjaxHandler();  
}

class CTInterface {
  private $data = null;
  
  function setParam($name, $mandatory=true) {
    $this->data[$name]=$mandatory;
  }
  // Nimmt modifieddate und modifieduser dazu!
  function addModifiedParams() {
    $this->data["modified_date"]=false;
    $this->data["modified_pid"]=false;
  }
  
  function addTypicalDateFields() {
    $this->setParam("startdate");
    $this->setParam("enddate");
    $this->setParam("repeat_id");
    $this->setParam("repeat_frequence", false);
    $this->setParam("repeat_until", false);
    $this->setParam("repeat_option_id", false);    
  }
        
  function getDBParamsArrayFromParams($params) {
    $res=array();
    foreach ($this->data as $key=>$val) {
      if (!isset($params[$key])) {
        if ($val==true)
          throw new CTException("Pflicht-Parameter $key wurde nicht uebergeben!");                  
      }      
      else {
        $res[":".$key]=$params[$key];
      }
    }
    return $res;
  }
  function getDBInsertArrayFromParams($params, $setOptionalToNullValue=false) {
    global $user;
    $res=array();
    foreach ($this->data as $key=>$val) {
      if (!isset($params[$key])) {
        if ($val==true) {
          throw new CTException("Pflicht-Parameter $key wurde nicht uebergeben!");                  
        }
        else if ($setOptionalToNullValue) {
          $res[$key]=null;
        }
        else if ($key=="modified_date") {
          $dt = new DateTime();            
          $res[$key]=$dt->format('Y-m-d H:i:s');
        }
        else if ($key=="modified_pid") {
          $res[$key]=$user->id;
        }
      }      
      else { 
        $res[$key]=$params[$key];
      }
    }
    return $res;
  }
}


class JSONResultObject {
  public $ok = false;
  function setStatus($ok=true) {
    $this->ok=$ok;
  }
}

function jsend() {
  return new JSEND();
}

class JSEND {
  function success($data=null) {
    return array("status"=>"success", "data"=>$data);  //."" weggenommen am 26.3.2013 fŸr DatenrŸckgabe. Warum .""??
  }
  // Hinweis es hat nicht geklappt. Z.B. keine Rechte
  function fail($data) {
    return array("status"=>"fail", "data"=>$data."");
  }
  // Was schlimmes, es wird Fehlerbox aufgeblendet und Anwendung neu gestartet!
  function error($message, $data=null) {
    if ($data==null)
      return array("status"=>"error", "message"=>$message."");
    else  
      return array("status"=>"fail", "message"=>$message."", "data"=>$data);
  }
}

function current_date() {
  $dt=new DateTime();
  return $dt->format('Y-m-d H:i:s');
}


function l($name, $url, $params=null) {
  if ($params==null) $param="";
  else {
    $param="?";
    $first=true;
    foreach ($params as $key=>$p) {
      if (!$first) $param.="&";
      $first=false;
      $param.="$key=$p";
    }
  }
  return '<a href="'.$url.$param.'">'.$name.'</a>';
}

function addErrorMessage($msg) {
  global $content;
  $content.='<div class="alert alert-error">'.$msg.'</div>';  
}
function addInfoMessage($msg, $hide=false) {
  global $content;
  $content.='<div class="alert alert-info '.($hide?"hide_automatically":"").'">'.$msg.'</div>';  
}

function t($txt, $p1=null) {
  global $i18n;
  if (isset($i18n))
    return $i18n->getText($txt, $p1);
  else return "$txt";
}

function language_default() {
  return null;
}

/**
 * 
 * @param unknown_type $txt
 * @param unknown_type $level  3=Unwichtig 2=Erscheint in PersonDetails 1=Wichtig!!
 * @param unknown_type $personid  Wenn Bezug zur PersonId
 */
function ct_log($txt,$level=3,$domainid=-1,$domaintype=CDB_LOG_PERSON,$schreibzugriff_yn=0,$_user=null) {
  global $user;
  if ($_user==null) $_user=$user;
  $dt = new DateTime();  
  db_query("insert into {cdb_log} (person_id, level, datum, domain_id, domain_type, schreiben_yn, txt) values (
     :person_id, :level, :datum, :domain_id, :domain_type, :schreiben_yn, :txt)",
   array(":person_id"=>(isset($_user->id)?$_user->id:-1),
         ":level"=>$level,
         ":datum"=>$dt->format('Y-m-d H:i:s'), 
         ":domain_id"=>$domainid, 
         ":domain_type"=>$domaintype,
         ":schreiben_yn"=>$schreibzugriff_yn,
         ":txt"=>substr($txt,0,1999)));
}

define('PHP_QPRINT_MAXL', 75);

function php_quot_print_encode($str) {
    $lp = 0;
    $ret = '';
    $hex = "0123456789ABCDEF";
    $length = strlen($str);
    $str_index = 0;
    
    while ($length--) {
        if ((($c = $str[$str_index++]) == "\015") && ($str[$str_index] == "\012") && $length > 0) {
            $ret .= "\015";
            $ret .= $str[$str_index++];
            $length--;
            $lp = 0;
        } else {
            if (ctype_cntrl($c) 
                || (ord($c) == 0x7f) 
                || (ord($c) & 0x80) 
                || ($c == '=') 
                || (($c == ' ') && (isset($str[$str_index])) && ($str[$str_index] == "\015")))
            {
                if (($lp += 3) > PHP_QPRINT_MAXL)
                {
                    $ret .= '=';
                    $ret .= "\015";
                    $ret .= "\012";
                    $lp = 3;
                }
                $ret .= '=';
                $ret .= $hex[ord($c) >> 4];
                $ret .= $hex[ord($c) & 0xf];
            } 
            else 
            {
                if ((++$lp) > PHP_QPRINT_MAXL) 
                {
                    $ret .= '=';
                    $ret .= "\015";
                    $ret .= "\012";
                    $lp = 1;
                }
                $ret .= $c;
            }
        }
    }
  return $ret;
}


function churchcore_getModulesSorted($withCoreModule=false, $withOfflineModules=false) {
  global $config;

  if ($withCoreModule) $config["churchcore_sortcode"]=0;
  
  // Get the module names out of the file system in directory system/*
  $arr=array();
  $scandir=scandir("system");
  foreach ($scandir as $file) {
    if ((strPos($file,'church')!==false) && (($file!="churchcore" || $withCoreModule)) 
        && (is_dir("system/".$file))) {
      $arr[]=$file;
    }   
  }
  
  $sort_arr=array();    
  $mysort=1000;
  foreach ($arr as $module) {
    if ((!isset($config[$module."_name"]) || ($config[$module."_name"]!="") || $withOfflineModules)) {
      if ((!isset($config[$module."_sortcode"])) || isset($sort_arr[$config[$module."_sortcode"]])) {
        $mysort++;
        $sort_arr[$mysort]=$module;        
      }
      else
        $sort_arr[$config[$module."_sortcode"]]=$module;      
    }
  }
  ksort($sort_arr);
  return $sort_arr;
}

/**
 * Das hier ist die aktuelle Mail-Methode!
 * @param unknown_type $from
 * @param unknown_type $to
 * @param unknown_type $subject
 * @param unknown_type $content
 * @param unknown_type $htmlmail true / false(default)
 * prio 1=sofort, 2=bald, 3=wenn alle 2er weg sind
 */
function churchcore_mail($from, $to, $subject, $content, $htmlmail=false, $withtemplate=true, $priority=2) {
  global $base_url, $files_dir, $user;

  $header="";
  $body="";
//  $header.='MIME-Version: 1.0' . "\n";
  if ($htmlmail) {
  //  $header.='Content-type: text/html; charset=utf-8' . "\n";    //'Content-Transfer-Encoding: quoted-printable'. "\n" .
    if ($withtemplate) {
      if (file_exists("$files_dir/mailtemplate.html"))
        $body=file_get_contents("$files_dir/mailtemplate.html");
      else  
        $body=file_get_contents("system/includes/mailtemplate.html");
    }
    else $body="%content";
  }
  else {   
//    $header.='Content-type: text/plain; charset=utf-8' . "\n";    //'Content-Transfer-Encoding: quoted-printable'. "\n" .
    if ($withtemplate) {
      if (file_exists("$files_dir/mailtemplate.plain"))
        $body=file_get_contents("$files_dir/mailtemplate.plain");
      else  
        $body=file_get_contents("system/includes/mailtemplate.plain");
    }
    else $body="%content";
  }
//  $header.="From: $from\n";
  
  
//  $header.='X-Mailer: PHP/' . phpversion();

  $variables = array(
    '%username' => (isset($user->cmsuserid)?$user->cmsuserid:"anonymus"),
    '%useremail' => (isset($user->email)?$user->email:"anonymus"),
    '%sitename' => variable_get('site_name', 'drupal'),
    '%sitemail' => variable_get('site_mail', 'info@churchtools.de'),
    '%siteurl' => $base_url,
  );
  // Content durchgehen
  $content=strtr($content, $variables);
  // Nun durch %content honzufŸgen fŸr den Body
  $variables["%content"]=$content;
  
  ct_log("Speichere Mail an $to von $from - $subject",2,-1,"mail");  
  //mail($to, "=?utf-8?Q?".php_quot_print_encode($subject)."?=\n", strtr($body, $variables), $header);
  $dt=new DateTime();
  if ($to==null) $to="";
  db_query("insert into {cc_mail_queue} 
              (receiver, sender, subject, body, htmlmail_yn, priority, modified_date, modified_pid)
              values (:receiver, :sender, :subject, :body, :htmlmail_yn, :priority, :modified_date, :modified_pid)",
     array(
      ":receiver"=>$to, ":sender"=>$from, ":subject"=>php_quot_print_encode($subject),
       ":body"=>strtr($body, $variables),
       ":htmlmail_yn"=>($htmlmail?1:0),
       ":priority"=>$priority,
       ":modified_date"=>$dt->format('Y-m-d H:i:s'),
       ":modified_pid"=>(isset($user)?$user->id:-1)));
}

/**
 * System Plain-EMail mit Sender vom Admin und Infoanhang
 * @param unknown_type $recipients (Mehrere mit Komma getrennt!)
 * @param unknown_type $subject
 * @param unknown_type $content
 */
function churchcore_systemmail($recipients, $subject, $content, $htmlmail=false, $priority=2) {
  if (variable_get("mail_enabled")) {
    $recipients_array=explode(",", $recipients);
    foreach ($recipients_array as $recipient) {
      churchcore_mail(variable_get('site_mail', ini_get('sendmail_from')), trim($recipient), $subject, $content, $htmlmail, true, $priority);
    }
  }  
}


function churchcore_sendMails_PHPMAIL($maxmails=10) {
  global $config, $base_url;
  $db=db_query("select value from {cc_config} where name='currently_mail_sending'")->fetch();
  if ($db==false) {
    db_query("insert into {cc_config} values ('currently_mail_sending', '0')");
    $db=new stdClass(); $db->value=0;
  }    
  
  if ($db->value=="0") {
    db_query("update {cc_config} set value='1' where name='currently_mail_sending'");    
    $db=db_query("select * from {cc_mail_queue} where send_date is null order by priority limit $maxmails");
    if ($db!=false) {
      $counter=0;
      $counter_error=0;
      foreach ($db as $mail) {
        $header='MIME-Version: 1.0' . "\n";
        $body=$mail->body;
        if ($mail->htmlmail_yn==1) {
          $header.='Content-type: text/html; charset=utf-8' . "\n";    //'Content-Transfer-Encoding: quoted-printable'. "\n" .
          $body.='<img src="'.$base_url.'?q=cron&standby=true&mailqueue_id='.$mail->id.'"/>';
        }
        else  
          $header.='Content-type: text/plain; charset=utf-8' . "\n";    //'Content-Transfer-Encoding: quoted-printable'. "\n" .
        
        $header.="From: $mail->sender\n";
        $header.='X-Mailer: PHP/' . phpversion();
        $error=0;
        $counter++;
        // Wenn test gesetzt ist, soll er keine Mails senden, sondern nur so tun!
        if (!isset($config["test"])) {
          if (!mail($mail->receiver, "=?utf-8?Q?".$mail->subject."?=\n", $body, $header)) {
            $counter_error++;
            $error=1; 
          }
        }
        db_query("update {cc_mail_queue} set send_date=now(), error=$error where id=$mail->id");
      }
      if ($counter>0)
        ct_log("$counter E-Mails wurden gesendet. ".($counter_error>0?"$counter_error davon konnten nicht gesendet werden!":""),2,-1,"mail");        
    }
    db_query("update {cc_config} set value='0' where name='currently_mail_sending'");    
  }
  // To many errors, so the process was killed or something like that.
  else if ($db->value>"10") {
    db_query("update {cc_config} set value='0' where name='currently_mail_sending'");
  }
  else {
    // Increment
    db_query("update {cc_config} set value=value+1 where name='currently_mail_sending'");    
  }
}

function churchcore_sendMails_PEARMAIL($maxmails=10) {
  global $config, $base_url;
  
  include_once 'Mail.php';
  include_once 'Mail/mime.php' ;
  
  $db=db_query("select value from {cc_config} where name='currently_mail_sending'")->fetch();
  if ($db==false) {
    db_query("insert into {cc_config} values ('currently_mail_sending', '0')");
    $db=new stdClass(); $db->value=0;
  }    
  
  if ($db->value=="0") {
    db_query("update {cc_config} set value='1' where name='currently_mail_sending'");    
    $db=db_query("select * from {cc_mail_queue} where send_date is null order by priority limit $maxmails");
    if ($db!=false) {
      $counter=0;
      $counter_error=0;
      foreach ($db as $mail) {    
        $headers = array(
          'From'          => $mail->sender,
          'Return-Path'   => $mail->sender,
          'Subject'       => $mail->subject,
          'Content-Type'  => 'text/html; charset=UTF-8',
          'X-Mailer'      => 'PHP/' . phpversion()
        );
        
        $mime_params = array(
          'text_encoding' => '7bit',
          'text_charset'  => 'UTF-8',
          'html_charset'  => 'UTF-8',
          'head_charset'  => 'UTF-8'
        );
        
        $mime = new Mail_mime();
        
        if ($mail->htmlmail_yn==1) {
          $html = $mail->body;
          $html.='<img src="'.$base_url.'?q=cron&standby=true&mailqueue_id='.$mail->id.'"/>';
          $mime->setHTMLBody($html);
        }
        else {           
          $text = $mail->body;        
          $mime->setTXTBody($text);
        }
        

        $error=0;
        $counter++;
        // Wenn test gesetzt ist, soll er keine Mails senden, sondern nur so tun!
        if (!isset($config["test"])) {
          $body = $mime->get($mime_params);
          $headers = $mime->headers($headers);
          $mail_object =& Mail::factory(variable_get('mail_pear_type','mail'), (isset($config["mail_pear_args"])?$config["mail_pear_args"]:null));
          $ret=$mail_object->send($mail->receiver, $headers, $body);
          if ($ret!=true) {
            $counter_error++;
            $error=1; 
          }
        }
        db_query("update {cc_mail_queue} set send_date=now(), error=$error where id=$mail->id");
      }
      if ($counter>0)
        ct_log("$counter E-Mails wurden gesendet. ".($counter_error>0?"$counter_error davon konnten nicht gesendet werden!":""),2,-1,"mail");        
    }
    db_query("update {cc_config} set value='0' where name='currently_mail_sending'");    
  }
}

function churchcore_sendMails($maxmails=10) {
  if (variable_get('mail_type','phpmail')=="phpmail")
    churchcore_sendMails_PHPMAIL($maxmails);
  else
    churchcore_sendMails_PEARMAIL($maxmails);
}

function churchcore_createPersonLoginStr($id) {
  $loginstr=random_string(60);
  db_query("update {cdb_person} set loginstr='1' where id=$id and loginstr is null");
  db_query("insert into {cc_loginstr} (person_id, loginstr, create_date) 
              values ($id, '$loginstr', current_date)");
  return $loginstr;
}


/**
 * 
 * @param unknown_type $ids ids, kommasepariert
 * @param unknown_type $betreff
 * @param unknown_type $inhalt
 * @param $from, absender email, oder wenn null dann current user email 
 * @return ok, oder fehlermeldung
 */
function churchcore_sendEMailToPersonids($ids, $betreff, $inhalt, $from=null, $htmlmail=false, $withtemplate=true) {
  global $base_url;

  if ($from==null) {
    $user_pid=churchcore_getCurrentUserPid();
    $res=db_query("select vorname, name, email from {cdb_person} where id =$user_pid")->fetch();
    $from="$res->vorname $res->name <$res->email>";
  }
        
  $arr=db_query("select * from {cdb_person} where id in ($ids)");
  $error=array();
  foreach($arr as $p) {
    $mailtxt=$inhalt;
    if ($p->email=='') 
      $error[]=$p->vorname." ".$p->name;
    else {    
      $mailtxt=str_replace('\"','"',$mailtxt);
      $mailtxt=churchcore_replacePersonTemplate($mailtxt, $p);
      //ct_log("[ChurchCore] - Sende Mail an $p->email $mailtxt",2,-1,"mail");
      
      churchcore_mail($from, $p->email, $betreff, $mailtxt, $htmlmail, $withtemplate);  
    }
  }
  if (count($error)>0)
    throw new CTFail("An folgende Personen konnte wegen fehlender EMail-Adresse keine EMail gesendet werden: ".implode($error,","));    
}

function churchcore_replacePersonTemplate($txt, $p) {
  if ($p==null) return $txt;
  $txt=str_replace("[Vorname]",$p->vorname,$txt);
  $txt=str_replace("[Nachname]",$p->name,$txt);
  $txt=str_replace("[Titel]",$p->titel,$txt);
  $txt=str_replace("[Spitzname]",($p->spitzname==""?$p->vorname:$p->spitzname),$txt);
  $txt=str_replace("[Id]",$p->id,$txt);
  return $txt;
}


function checkForDBUpdates() {
  global $mapping;

  if ($mapping["churchtools_version"]==null)
    die("churchtools_version nicht gefunden!");
  $software_version=$mapping["churchtools_version"];

  $db_version="nodb";

  try {
    // test if cc_config table is present
    $a=db_query("select * from {cc_config} where name='version'",null,false);
    // if we arrive here, it is, so fetch current database version
    $db_version=$a->fetch()->value;
  }
  catch (Exception $e) {
    try {
      /* if cdb_person is present, but not cc_config, it's a pre-2.0 database */
      $a=db_query("select * from {cdb_person}",null,false);    
      $db_version="pre-2.0";
    }
    catch (Exception $e) {
      /* still not? start from scratch */
      $db_version="nodb";
    }
  }
  /* anything to do? */
  if ($db_version == $software_version)
    return true;

  include_once("system/includes/db_updates.php");
  return run_db_updates($db_version);
}

function variable_get($var, $default=null) {
  global $config, $mapping;
  if (isset($config[$var]))  
    return $config[$var];
  else if (isset($mapping[$var]))  
    return $mapping[$var];
  else if ($default!=null)
    return $default;
  else
    die("Variable $var nicht bekannt!");
}

function churchcore_sort (&$array, $key) {
  $sorter=array();
  $ret=array();
  reset($array);
  foreach ($array as $ii => $va) {
    $sorter[$ii]=$va[$key];
  }
  asort($sorter);
  foreach ($sorter as $ii => $va) {
    $ret[$ii]=$array[$ii];
  }
  $array=$ret;
}

function drupal_get_path($m, $module) {
  return "system/".$module."";
}

function drupal_add_css($str) {
  global $add_header;
  $add_header.='<link href="'.$str.'" rel="stylesheet">';
}

function drupal_add_js($str) {
  global $add_header, $config;
  $add_header.='<script src="'.$str.'?'.$config["version"].'"></script>';
}

function drupal_get_header() {
  global $add_header;
  return $add_header;
}

function drupal_add_header($header) {
  global $add_header;
  $add_header.=$header."\n";
}

function drupal_add_http_header($name, $val, $replace) {
  header("$name: $val", $replace);
}

// ----------------------------------------
// --- JSON_TOOLS
// ----------------------------------------

function drupal_json_output($mixed) {
  header('Content-Type: application/json');
  echo json_encode($mixed, JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_AMP | JSON_HEX_QUOT);
}

function churchcore_getTableData($tablename, $sql_order="", $sql_where="", $sql_cols="*") {
  if ($sql_order!="") $sql_order=" order by ".$sql_order;
    $res = db_query("SELECT $sql_cols FROM `{".$tablename."}` ".($sql_where==""?"":"WHERE $sql_where")." $sql_order");
  $arrs=null;
  foreach ($res as $arr) {
    if (isset($arr->id))
      $arrs[$arr->id]=$arr;
    else  
      $arrs[]=$arr;
  }
  return $arrs; 
}

function churchcore_getTableDataSorted($tablename, $sortkey) {
  $res = db_query("SELECT * FROM {".$tablename."} order by $sortkey");
  foreach ($res as $arr) {
    $arrs[$arr->id]=$arr;       
  }
  return $arrs; 
}

function churchcore_getFiles($domain_type) {
  $res=db_query("select f.*, concat(p.vorname,' ',p.name) as modified_username 
                   from {cc_file} f left join {cdb_person} p on (f.modified_pid=p.id)
                   where f.domain_type='$domain_type'");
  $arrs=null;
  foreach ($res as $arr) {
    $arrs[$arr->id]=$arr;
  }
  return $arrs;
}
/**
 * Holt sich die Dateien als Array per DomainId
 * @param $domain_type
 * @return files
 */
function churchcore_getFilesAsDomainIdArr($domain_type, $domain_id=null) {
  $sql="select f.*, concat(p.vorname,' ',p.name) as modified_username 
                   from {cc_file} f left join {cdb_person} p on (f.modified_pid=p.id)
                   where f.domain_type=:domain_type";
  if ($domain_id!=null) $sql.=" and domain_id='$domain_id'";
  $dbs=db_query($sql, array(':domain_type'=>$domain_type));
  $files=array();   
  foreach ($dbs as $db) {
    if (isset($files[$db->domain_id]))
      $arrs=$files[$db->domain_id];
    else $arrs=array();
    $id=$db->domain_id;
    $arrs[$db->id]=$db;  
    $files[$id]=$arrs;    
  }   
  return $files;
}

function churchcore_copyFileToOtherDomainId($id, $domain_ids) {
  global $files_dir;
  $res=db_query("select * from {cc_file} where id=:id", array(":id"=>$id), false)->fetch();
  if (!$res) throw new CTFail("Datei nicht in der Datenbank gefunden!");
  $arr=explode(",", $domain_ids);
  foreach ($arr as $val) {
    if ($val!="") {
      if (!file_exists("$files_dir/files/$res->domain_type/$val")) mkdir("$files_dir/files/$res->domain_type/$val",0777,true);
      if (!copy("$files_dir/files/$res->domain_type/$res->domain_id/$res->filename", "$files_dir/files/$res->domain_type/$val/$res->filename"))
        throw new CTFail("Datei konnte nicht nach $files_dir/files/$res->domain_type/$val/$res->filename kopiert werden!");
      db_query("insert into {cc_file} (domain_type, domain_id, bezeichnung, filename, modified_date, modified_pid) 
         values (:domain_type, :domain_id, :bezeichnung, :filename, :modified_date, :modified_pid)",
         array(":domain_type"=>$res->domain_type, ":domain_id"=>$val, ":bezeichnung"=>$res->bezeichnung, 
                 ":filename"=>$res->filename,":modified_date"=>$res->modified_date, ":modified_pid"=>$res->modified_pid));
    }      
  }
}

function churchcore_renameFile($id, $filename) {
  global $files_dir;
  $res=db_query("select * from {cc_file} where id=:id", array(":id"=>$id), false)->fetch();
  if (!$res) throw new CTFail("Datei nicht in der Datenbank gefunden!");
  db_query("update {cc_file} set bezeichnung=:bezeichnung where id=:id", 
    array(":id"=>$id, ":bezeichnung"=>$filename), false);
}

function churchcore_delFile($id) {
  global $files_dir;
  $res=db_query("select * from {cc_file} where id=:id", array(":id"=>$id), false)->fetch();
  if (!$res) throw new CTFail("Datei nicht in der Datenbank gefunden!");
  db_query("delete from {cc_file} where id=:id", array(":id"=>$id), false);
  if (!unlink("$files_dir/files/$res->domain_type/$res->domain_id/$res->filename")) 
    throw new CTFail("Datei konnte auf dem Server nicht entfernt werden.");  
}

function churchcore_renderFile($file) { 
  $i = strrpos($file->bezeichnung,'.');
  $ext="paperclip";
  if ($i>0) {
    switch (substr($file->bezeichnung,$i,99)) { 
    case '.mp3': 
      $ext="mp3";
      break;
    case '.m4a': 
      $ext="mp3";
      break;
    case '.pdf': 
      $ext="pdf";
      break;
    case '.doc': 
      $ext="word";
      break;
    case '.docx': 
      $ext="word";
      break;
    case '.rtf': 
      $ext="word";
      break;
    }
  }
  $txt='<a target="_clean" href="?q=churchservice/filedownload&id='.$file->id.'&filename='.$file->filename.'" title="'.$file->bezeichnung.'">';
  $txt.=churchcore_renderImage("$ext.png",20);
  $txt.='</a>';
  return $txt;    
}

function churchcore_renderImage($filename, $width=24) {
  global $base_url;
  return '<img src="'.$base_url.'/system/churchcore/images/'.$filename.'" style="max-width:'.$width.'px"/>';
  
}

/**
 * Holt Person unabhŠngig von der Autorisierung
 * @param unknown_type $id
 */
function churchcore_getPersonById($id) {  
  $sql="select * from {cdb_person} where id=:id";
  $res=db_query($sql, array(":id"=>$id))->fetch();
  return $res;  
}


function churchcore_getUserById($id) {
  $p=db_query("select * from {cdb_person} p where id=:id", array(":id"=>$id))->fetch();
  if ($p!==false)
    return $p;
  else return null;  
}


/**
 * 
 * @param unknown_type $CMSID
 * @param unknown_type $multiple  wenn false dann nur den ersten, ansonsten Kommaseparierte liste
 * @return Bei nicht multiple nur einen, bei multpiple alle, immer null wenn keiner
 */
function churchcore_getUserByCMSId($CMSID, $multiple=false) {
  $sql="select id from {cdb_person} where cmsuserid='$CMSID'";
  if (!$multiple) {
    if ($obj=db_query($sql)->fetch())
      return $obj->id;
    else return null;  
  }
  else {
    $obj=db_query($sql);
    $res=array();
    foreach ($obj as $p) {
      $res[]=$p->id;
    }
    if (count($res)>0)
      return $res;
    else return null;  
  }
}

/**
 * 
 * @param unknown_type $CMSID
 * @param unknown_type $multiple  wenn false dann nur den ersten, ansonsten Kommaseparierte liste
 * @return Bei nicht multiple nur einen, bei multpiple alle, immer null wenn keiner
 */
function churchcore_getCompleteUserByCMSId($CMSID, $multiple=false) {
  $sql="select id, email, vorname, name from {cdb_person} where cmsuserid='$CMSID'";
  if (!$multiple) {
    if ($obj=db_query($sql)->fetch())
      return $obj;
    else return null;  
  }
  else {
    $obj=db_query($sql);
    $res=array();
    foreach ($obj as $p) {
      $res[]=$p;
    }
    if (count($res)>0)
      return $res;
    else return null;  
  }
}

function churchcore_getCurrentUserPid() {
  return $_SESSION["user"]->id; 
}

/**
 * 
 * @param $modulename
 * @param $user_pid Array oder nur die pid
 */
function churchcore_getUserSettings($modulename, $user_pid) {
  if ($user_pid==null)
    return array();
    
  if (gettype($user_pid)=="array") $user_pid=$user_pid[0];
  $res=db_query("select attrib, value from {cc_usersettings} where modulename='$modulename' and person_id=$user_pid");
  $arr=array();
  foreach ($res as $entry) {
    $arr[$entry->attrib]=preg_replace('/\\\/', "", $entry->value);    
  }
  return $arr;
}

function _churchcore_savePidUserSetting($modulename, $pid, $attrib, $val) {
  $res=db_query("select * from {cc_usersettings} where modulename='$modulename' and person_id=$pid and attrib='$attrib'")->fetch();
  if ($res==null)
    db_query("insert into {cc_usersettings} (person_id, modulename, attrib, value) values ($pid, '$modulename', '$attrib', :val)",
            array(":val"=>$val));
  else   
    db_query("update {cc_usersettings} set value=:val where modulename='$modulename' and person_id=$pid and attrib='$attrib'",
            array (":val"=>$val));    
}

/**
 * Notifications for mailing by updates
 * Optional parameters are $domain_type, $domain_id and $person_id
 */
function churchcore_getMyNotifications() {
  global $user;
  
  $db=db_query("select id, notificationtype_id, domain_id, domain_type, lastsenddate from {cc_notification} where person_id=:p_id", array(":p_id"=>$user->id));
  $abos=array();
  foreach ($db as $abo) {
    if (!isset($abos[$abo->domain_type]))
      $domaintype=array();
    else $domaintype=$abos[$abo->domain_type];
    if (!isset($domaintype[$abo->domain_id]))
      $domain_id=array();
    else $domain_id=$domaintype[$abo->domain_id];

    $domain_id["notificationtype_id"]=$abo->notificationtype_id;
    $domain_id["lastsenddate"]=$abo->lastsenddate;

    $domaintype[$abo->domain_id]=$domain_id;
    $abos[$abo->domain_type]=$domaintype;
  }
  return $abos;
}

/**
 * 
 * @param unknown_type $modulename
 * @param unknown_type $user_pid Array oder nur die pid
 * @param unknown_type $attrib
 * @param unknown_type $val
 */
function churchcore_saveUserSetting($modulename, $user_pid, $attrib, $val) {
  if (($user_pid==null) || ($user_pid<=0)) return;
  
  if (gettype($user_pid)=="array") { 
    foreach ($user_pid as $pid) {
      _churchcore_savePidUserSetting($modulename, $pid, $attrib, $val);
    }
  }
  else {
     _churchcore_savePidUserSetting($modulename, $user_pid, $attrib, $val);
  }      
}

/**
 * 
 * @param $longtext - Beim Editieren
 * @param $shorttext - Bei der Ansicht
 * @param $column_name - Datenbankspalte
 * @param $eol - Eol
 */
function churchcore_getTextField($longtext, $shorttext, $column_name, $eol='<br/>', $auth=null) {
  $res["type"]="text";
  $res["text"]=$longtext;  // Bei Editieren etc.
  $res["shorttext"]=$shorttext; // In der Ansicht
  if ($eol=="") $eol="&nbsp;";
  $res["eol"]=$eol;
  $res["sql"]=$column_name;
  $res["auth"]=$auth;  
  return $res;
}

/**
 * 
 * @param $longtext
 * @param $shorttext
 * @param $column_name
 * @param $eol
 */
function churchcore_getDateField($longtext, $shorttext, $column_name, $eol='<br/>', $auth=null) {
  $res["type"]="date";
  $res["text"]=$longtext;  // Bei Editieren etc.
  $res["shorttext"]=$shorttext; // In der Ansicht
  if ($eol=="") $eol="&nbsp;";
  $res["eol"]=$eol;
  $res["sql"]=$column_name;
  $res["auth"]=$auth;  
  return $res;
}

/**
 * Gibt eine lesbare Liste zurueck mit allen Aenderungen oder sonst NULL
 * @param $oldarr
 * @param $newarr
 */
function churchcore_getFieldChanges($fields, $oldarr, $newarr, $cut_dates=true) {
  $txt="";
  foreach($newarr as $name => $wert) {
  	$oldval=null;
    if (isset($fields[$name])) {
  	  if ($oldarr!=null)
        $oldval=$oldarr->$fields[$name]["sql"];
      
      if ($fields[$name]["type"]=="date") {
        // Beim Datum nur Jahr, Datum und Tag vergleichen, Uhrzeit egal
        if (($cut_dates) && ($fields[$name]["type"]=="date"))
          $oldval=substr($oldval,0,10);
        
        $oldval=churchcore_stringToDateDe($oldval);
        $wert=churchcore_stringToDateDe($wert);
      }
      
	  if (($oldval!=null) && ($wert!=$oldval))
	    $txt=$txt.$fields[$name]["text"].": $wert  (Vorher: $oldval)\n";       	    
      else if (($oldval==null) && ($wert!=null))   
        $txt=$txt.$fields[$name]["text"].": $wert  (Neu)\n";
  	}    
  	// For infos which are not in the field-set
    else {
      if (($oldarr!=null) && (isset($oldarr->$name))) {
        $oldval=$oldarr->$name;
      }      
      if (($oldval==null) && ($wert!=null))       
        $txt=$txt."$name: $wert (Neu)\n";
      else if ($oldval!=$wert) 
        $txt=$txt."$name: $wert (Vorher: $oldval)\n";
     }
   }
   if ($txt!="")
     return $txt;
   else return null;  		
}

/**
 * 
 * @param unknown_type $id e.g. 3
 * @param unknown_type $bezeichnung e.g. Service-Gruppe 
 * @param unknown_type $shortname e.g. servicegroup
 * @param unknown_type $tablename e.g. cs_servicegroup
 * @param unknown_type $sql_order e.g. sortkey
 */
function churchcore_getMasterDataEntry($id, $bezeichnung, $shortname, $tablename, $sql_order="") {
  $res2["id"]=$id;
  $res2["bezeichnung"]=$bezeichnung;
  $res2["shortname"]=$shortname;
  $res2["tablename"]=$tablename;
  $res2["sql_order"]=$sql_order;
  
  $sql="describe {".$tablename."}";
  $tabledesc=db_query($sql);
  $field=array();
  foreach ($tabledesc as $desc) {
    // Seit Drupal 7,14 komischerweise immer in Gro§buchstaben
    if (isset($desc->Field)) $desc->field=$desc->Field;
    if (isset($desc->Type)) $desc->type=$desc->Type;
    
    $field[$desc->field]=$desc;
  }
  $res2["desc"]=$field;
  return $res2; 
}


function churchcore_stringToDateDe($string, $withTime=true) {
  if ($string==null) return null;
  
  if (strlen($string)<11) $string.=" 00:00:00"; 
  $dt=new Datetime($string);
  if ($withTime)
    return $dt->format('d.m.Y H:i');
  else    
    return $dt->format('d.m.Y');
}

function churchcore_stringToDateICal($string) {
  $dt=new Datetime($string);
  return $dt->format('Ymd\THis');  
} 

function churchcore_stringToDateTime($string) {  
  return $dt=new Datetime($string);
}

function isFullDay($start, $end) {
  if (($start->format('H:i:s')=="00:00:00") && ($end==null || $end->format('H:i:s')=="00:00:00"))
    return true;
  return false;  
}

function datesInConflict($startdate, $enddate, $startdate2, $enddate2) {
  $_enddate=$enddate;
  $_enddate2=$enddate2;
  if (isFullDay($startdate, $enddate)) {
    $_enddate->modify("+1 day");
    $_enddate->modify("-1 second");
  }
  if (isFullDay($startdate2, $enddate2)) {
    $_enddate2->modify("+1 day");
    $_enddate2->modify("-1 second");
  }
  // Enddatum2 liegt innerhalb des Datums
  if ((($_enddate2>$startdate) && ($_enddate2<$_enddate))
      // oder Startdatum 2liegt innerhalb des Datums
      || (($startdate2>$startdate) && ($startdate2<$_enddate))
      // oder Datum2 komplett au§erhalb 1
      || (($startdate2<=$startdate) && ($_enddate2>=$_enddate))
      // oder Datum2 komplett innerhalb 1
      || (($startdate2>=$startdate) && ($_enddate2<=$_enddate))) {
    return true;
  }
  return false;    
}

function churchcore_getAge($date) {
  $d=new DateTime($date);
  $alter = floor((date("Ymd") - date("Ymd", $d->getTimestamp())) / 10000);
  return $alter;
}

function getAllDatesWithRepeats($r, $_von=-1, $_bis=1) {
  // $dates wird mein spŠterer Datumscontainer, wo alle Vorkommen enthalten sind.
  $dates=array();
  // Max verhindert bei Fehlern eine Endlosschleife
  $max=999;
  
  $von=new DateTime();
  $von->modify("+$_von days");
  $bis=new DateTime();
  $bis->modify("+$_bis days");
   
  $d=new DateTime($r->startdate->format('d.m.Y H:i'));  
  $e=new DateTime($r->enddate->format('d.m.Y H:i'));
  
  $repeat_until=new DateTime($r->repeat_until);
  $repeat_until=$repeat_until->modify('+1 day'); // Da der Tag ja mit gelten soll!
  if ($bis<$repeat_until) $repeat_until=$bis; 
  
  if (isset($r->additions))
    $additions=$r->additions;
  else $additions=array();

  $my=new stdClass();
  $my->add_date=$d->format('d.m.Y H:i');
  $my->with_repeat_yn=1;
  
  $additions[0]=$my;
  //array_unshift($additions, $my);
  foreach($additions as $key=>$add) {
    $d=new DateTime(substr($add->add_date,0,10)." ".$d->format('H:i:s'));
    $e=new DateTime(substr($add->add_date,0,10)." ".$e->format('H:i:s'));        
    
    // Mark exception as used, so the "datesInConflict" will be called only for fresh exceptions to save time!
    if (isset($r->exceptions)) 
      foreach($r->exceptions as $exc) $exc->used=false;
    
    do {
      $exception=false;
      if (isset($r->exceptions)) {
        foreach($r->exceptions as $exc) {
          // only is exception not used then proove conflict with exception date
          if ((!$exception) && (!$exc->used) && (datesInConflict(new DateTime($exc->except_date_start), new DateTime($exc->except_date_end), 
                  $d, $e))) {
            $exception=true;
            $exc->used=true;
          }
        }
      }
      if (!$exception) {
        if ((($d<=$von) && ($e>=$von)) || (($e>=$von) && ($e<=$bis)))       
          $dates[]=new DateTime($d->format('Y-m-d H:i:s'));
      }  
      
      if (($r->repeat_id==1) || ($r->repeat_id==7)) {     
        $repeat=$r->repeat_id*$r->repeat_frequence; // Also jede zweite Woche gleich 7*2 => 14 Tage
        $d->modify("+$repeat days");
        $e->modify("+$repeat days");
      }
      // monatlich nach Datum
      else if ($r->repeat_id==31) {
        $counter=0;
        do {
          $tester=new DateTime($d->format('Y-m-d H:i:s'));
          $tester->modify("+ ".($counter+1*$r->repeat_frequence)." month");
          if ($tester->format('d')==$d->format('d')) {
            $d->modify("+ ".($counter+1*$r->repeat_frequence)." month");
            $e->modify("+ ".($counter+1*$r->repeat_frequence)." month");
            $counter=999;
          }
          $counter=$counter+1;
        } while ($counter<99);
      }
      // Monatlich nach Wochentag
      else if ($r->repeat_id==32) {
        // letzten Wochentag finden
        if ($r->repeat_option_id==6) {
          // Erst mal einige Tage zurŸck, damit wir nicht Ÿber die Monatsgrenze springen und ein Monat auslassen
          $d->modify("- 5 days");
          $e->modify("- 5 days");
          // Addiere nun die Monate
          $d->modify("+ ".(1+1*$r->repeat_frequence)." month");
          $e->modify("+ ".(1+1*$r->repeat_frequence)." month");
          // Gehe erst mal auf den ersten des Tages
          while ($d->format('d')>1) {
            $d->modify("-1 day");
            $e->modify("-1 day");
          }
          $d->modify("-1 day");
          $e->modify("-1 day");
          // Suche jetzt nach dem gleichen Wochentag
          while ($d->format('N')!=$r->startdate->format('N')) {
            $d->modify("-1 day");
            $e->modify("-1 day");
          }
        }
        // konkreten Wochentag, also z.B. der a.repeat_option_id te Wochentag im Monat, wenn es ihn gibt
        else {
          $counter=0;
          // Addiere die Monate
          $d->setDate($d->format('Y'), $d->format('m')+(1*$r->repeat_frequence), 0);
          $e->setDate($e->format('Y'), $e->format('m')+(1*$r->repeat_frequence), 0);
          while ($counter<$r->repeat_option_id) {
            $m=$d->format("m");
            $d->modify("+1 day");
            $e->modify("+1 day");
            // PrŸfe ob ich den Monat Ÿberspringe, dann hat der Monat nicht genug Tage 
            // und Termin fŠllt flach, v.a. beim 5.Wochentag im Monat)
            if ($d->format("m")!=$m) $counter=0;
            if ($d->format("N")==$r->startdate->format("N")) $counter=$counter+1;
          }
        }
      }
      else if ($r->repeat_id==365) {
        $counter=0;
        $d->modify("+ ".($counter+1*$r->repeat_frequence)." year");
        $e->modify("+ ".($counter+1*$r->repeat_frequence)." year");
      }
      
      $max=$max-1;
      if ($max==0) {
        addErrorMessage("Zu viele Wiederholungen in getAllDatesWithRepeats! [$r->id]");
        return false; 
      }
    } while (($d<$repeat_until) && ($add->with_repeat_yn==1) && (isset($r->repeat_id)) && ($r->repeat_id>0) 
                      && (isset($r->repeat_frequence)) && ($r->repeat_frequence>0));
    
  }  
  return $dates;
}

function surroundWithVCALENDER($txt) {
  global $config;
  
  return "BEGIN:VCALENDAR\r\n" 
  ."VERSION:2.0\r\n"
  ."PRODID:-//ChurchTools//DE\r\n" 
  ."CALSCALE:GREGORIAN\r\n"
  ."X-WR-CALNAME:".variable_get('site_name', 'ChurchTools')." ChurchCal-Kalender\r\n"
  ."X-WR-TIMEZONE:".$config["timezone"]."\r\n"
  ."METHOD:PUSH\r\n"
  .$txt
  ."END:VCALENDAR\r\n";   
}

function createAnonymousUser() {
  $user=new stdClass();
  $user->id=-1;
  $user->name="Anonymous";
  $user->vorname="";
  $user->email="";
  $user->auth=getUserAuthorization($user->id);
  $_SESSION['user']=$user;
}


// schaut ob einer der Zahlen von $array1 in $in_array2 ist
function array_in_array($array1, $in_array2) {
  $drin=false;
  foreach ($array1 as $id) {
    if (in_array($id, $in_array2)) {
      $drin=true;
    }    
  }
  return $drin;      
}

function _implantAuth($auth_table, $IamAdmin, $res, $auth) {
  foreach ($res as $entry) {
    $auth_entry=null;
    if (isset($auth_table[$entry->auth_id]))
      $auth_entry=$auth_table[$entry->auth_id];
    // Only when I am not admin or I am admin and admindarfsehen = false otherwise already set!
    if ($auth_entry!=null && (!$IamAdmin || $auth_entry->admindarfsehen_yn==0)) {
      if ($entry->daten_id==null) {
        $auth[$auth_entry->modulename][$auth_entry->auth]=true;
      }
      else {
        // Wenn ich alles sehen darf, dann ist daten_id==-1
        if ($entry->daten_id==-1) {
          $res2=db_query("select id from {".$auth_entry->datenfeld."}");
          $auth2=null;
          foreach ($res2 as $entry2) {
            $auth2[$entry2->id]=$entry2->id;
          }
          $auth[$auth_entry->modulename][$auth_entry->auth]=$auth2;
        }
        else {
          $arr=array();
          if (isset($auth[$auth_entry->modulename][$auth_entry->auth]))
            $arr=$auth[$auth_entry->modulename][$auth_entry->auth];
          // Datenautorisierung nicht mit true, sondern mit [id]=id. 1. Implode geht und 2. Direkter Zugriff geht!

          $arr[$entry->daten_id]=$entry->daten_id;
          $auth[$auth_entry->modulename][$auth_entry->auth]=$arr;
        }
      }
    }
  }
  return $auth;
}

function getUserAuthorization($user_id) {
  global $config;
  $auth=null;
  
  if ($user_id==null) return null;
  
  $auth_table=getAuthTable();
  $IamAdmin=false;
  if (in_array($user_id, $config["admin_ids"]))
    $IamAdmin=true;
  
  // Wenn ich in den Admin-Mails bin, dann schuster ich mir alle Rechte zu, die der Admin sehen darf
  if ($IamAdmin) {
    foreach ($auth_table as $entry) {
      if ($entry->admindarfsehen_yn==1) {
        if ($entry->datenfeld==null) {
          $auth[$entry->modulename][$entry->auth]=true;        
        }
        else {
          $res2=db_query("select id from {".$entry->datenfeld."}");
          $auth2=null;
          foreach ($res2 as $entry2) {
            $auth2[$entry2->id]=$entry2->id;
          }
          $auth[$entry->modulename][$entry->auth]=$auth2;
        }
        $auth[$entry->modulename]["view"]=true;
      }
    }
  } 
  

  
  // FŸr normale Benutzer und bei Admins nach Where nur die, wo es nicht fŸr Admin alles gibt.
  // Autorisierung Ÿber direkte Personenzuordnung
  $res=db_query("select daten_id, auth_id from {cc_domain_auth} pa 
                    where pa.domain_type='person' and pa.domain_id=$user_id");
  $auth=_implantAuth($auth_table, $IamAdmin, $res,$auth);
  
  // Autorisierung Ÿber Status
  $res=db_query("select daten_id, auth_id  from {cdb_gemeindeperson} gp, {cc_domain_auth} da 
                   where da.domain_type='status'  
                   and da.domain_id=gp.status_id and gp.person_id=$user_id");
  $auth=_implantAuth($auth_table, $IamAdmin, $res,$auth);

  // Autorisierung Ÿber Gruppen
  $res=db_query("select daten_id, auth_id  from {cdb_gemeindeperson} gp, {cdb_gemeindeperson_gruppe} gpg, {cc_domain_auth} da 
                   where da.domain_type='gruppe' and gpg.gemeindeperson_id=gp.id and gpg.status_no>=0
                   and da.domain_id=gpg.gruppe_id and gp.person_id=$user_id");
  $auth=_implantAuth($auth_table, $IamAdmin, $res,$auth);

  // Wenn es kein Anonymous ist
  if ($user_id>0) {
    $auth["home"]["view"]=true;
    $auth["logout"]["view"]=true;
    $auth["login"]["view"]=true;
    $auth["profile"]["view"]=true;
    $auth["help"]["view"]=true;
    $auth["cron"]["view"]=true;
    $auth["ical"]["view"]=true;
    $auth["churchauth"]["view"]=true;
    if ((isset($auth["churchcore"])) && (isset($auth["churchcore"]["administer persons"])))
      $auth["simulate"]["view"]=true;
    if ((isset($auth["churchcore"])) && (isset($auth["churchcore"]["administer settings"])))
      $auth["admin"]["view"]=true;
    if ((isset($auth["churchcore"])) && (isset($auth["churchcore"]["view logfile"])))
      $auth["churchcore"]["view"]=true;
      
    if (isset($_SESSION["simulate"])) 
      $auth["simulate"]["view"]=true;
  }        
    
  return $auth;
}

function logout_current_user() {
  if (isset($_SESSION["sessionid"])) {
    db_query("delete from {cc_session} where session='".$_SESSION["sessionid"]."'");    
    session_destroy();
  }
  if (isset($_SESSION["user"])) {
    $user=$_SESSION["user"];
    if ($user->id>0)
      ct_log("Logout erfolgreich: ".$user->email,2,-1, "login");
    unset($_SESSION["user"]);
  }
  // Wenn ich mich abmelde, will ich wirklich abgemeldet sein, deshalb Cookie zurŸcksetzen auf 0!
  setcookie("RememberMe", 0);
  // Nun bin ich wieder Anonym unterwegs
  createAnonymousUser();
}

function user_access($auth, $modulename) {
  global $config;
  if (!isset($modulename)) {
    addErrorMessage("Bei user_access wurde der Modulename nicht gesetzt");
    return false;
  }
  // Wenn kein User angemeldest, dann darf er sich nur einloggen
  if (!isset($_SESSION["user"])) {
    if (($auth=="view") && ($modulename=="login")) return true;
  }
  else {
    $auths=$_SESSION["user"]->auth;
    if ((isset($auths)) && (isset($auths[$modulename])) && (isset($auths[$modulename][$auth]))) 
      return $auths[$modulename][$auth];
  }
  return false;  
}

function userLoggedIn() {
  return ((isset($_SESSION['user'])) && ($_SESSION['user']->id>0));   
}

function addAuth($auth_arr, $id, $auth, $modulename, $datafield, $desc, $adminallowed=1) {
  if (isset($auth_arr[$id]))
    throw new CTException("Auth ID $id already set!");
  $o=new stdClass();
  $o->id=$id;
  $o->auth=$auth;
  $o->modulename=$modulename;
  $o->datenfeld=$datafield;
  $o->bezeichnung=$desc;
  $o->admindarfsehen_yn=$adminallowed;
  $auth_arr[$id]=$o;
  return $auth_arr;
}

function getAuthTable() {
  $modules=churchcore_getModulesSorted(true, false);
  $auth=array();
  $sortkey=0;
  foreach ($modules as $module) {
    include_once("system/$module/$module.php");
    if (function_exists($module."_getAuth")) {
      $res=call_user_func($module."_getAuth");
      foreach ($res as $key=>$val) {
        $val->sortkey=$sortkey;
        $sortkey++;
        $auth[$key]=$val;
      }
    }
  }
  return $auth;
}


/**
 * @param string $email
 * return false wenn nicht gefunden oder sonst das User-Objekt
 */
function churchcore_getPersonByEMail($email) {
  $res=db_query("select * from {cdb_person} where email='$email'")->fetch();
  return $res;  
}

function random_string($l = 20){
    $c = "ABCDEFGHIJKLMNOPQRSTUVWXYZabcdefghijklmnopqrstuvwxwz0123456789";
    $s = "";
    for(;$l > 0;$l--) $s .= $c{rand(0,strlen($c)-1)};
    return str_shuffle($s);
}

function shorten_string($str, $l=20) {
  if (strlen($str)>$l)
    return substr($str, 0, $l-1)."..";
  else return $str;    
}

if (!function_exists('password_hash') && function_exists('crypt')) {
  /* try if we can use the polyfill */
  $hash = '$2y$04$usesomesillystringfore7hnbRJHxXVLeakoG8K30oukPsA.ztMG';
  $test = crypt("password", $hash);
  if ($test == $hash) {
    require_once('system/assets/password_hash-polyfill/password.php');
  }
}

function scramble_password($plain_password) {
  if (($plain_password==null) || ($plain_password=="")) return null;
  if (function_exists('password_hash')) {
    $val = password_hash($plain_password, PASSWORD_DEFAULT);
    if ($val == FALSE) return null;
    return $val;
  } else
    return md5(trim($plain_password));
}

function user_check_password($plain_password, $user) {
  $stored_password = $user->password;
  if (($plain_password==null) || ($plain_password=="")) return null;
  if (function_exists('password_verify')) {
    if (password_verify($plain_password, $stored_password)) {
      /* maybe the parameters changed, so we should rekey */
      if (password_needs_rehash($stored_password, PASSWORD_DEFAULT)) {
        $new_stored_password = scramble_password($plain_password);
        db_query("update {cdb_person} set password=:password where id=:id", array(":id"=>$user->id, ":password"=>$new_stored_password), false);
      }
      return true;
    } else {
      /* maybe the password is still MD5? If so, rekey */
      $compare = md5(trim($plain_password));
      if ($compare == $stored_password) {
        $new_stored_password = scramble_password($plain_password);
        db_query("update {cdb_person} set password=:password where id=:id", array(":id"=>$user->id, ":password"=>$new_stored_password), false);
        return true;
      }
      return false;
    }
  } else {
    /* no password_verify, use old md5 method */
    $compare = md5(trim($plain_password));
    return $compare == $stored_password;
  }
}

function user_save() {
  addInfoMessage("<i>user_save</i> not implemented!");
}



// ----------------------------------------
// --- DATENBANK_TOOLS
// ----------------------------------------

$db_pdo;

class SQLException extends Exception {
  // Die Exceptionmitteilung neu definieren, damit diese nicht optional ist
  public function __construct($message, $code = 0) {
      // etwas Code

      // sicherstellen, dass alles korrekt zugewiesen wird
      parent::__construct($message, $code);
  }
  // ma§geschneiderte Stringdarstellung des Objektes
  public function __toString() {
      return __CLASS__ . ": [{$this->code}]: {$this->message}\n";
  } 
  
}

/* TODO: remove all users */
function escape_string($str) {
  global $db_pdo;
  return $db_pdo->quote($str);
}

/*
 * Connect to the MySQL database.
 */
function db_connect() {
    global $config, $db_pdo;
    try {
        $db_pdo = new PDO("mysql:host=${config["db_server"]};dbname=${config["db_name"]};charset=utf8", $config["db_user"], $config["db_password"], array(PDO::ATTR_PERSISTENT => TRUE, PDO::MYSQL_ATTR_INIT_COMMAND => 'SET NAMES utf8'));
    } catch(PDOException $e) {
        $error_message = "<h3>Database connection error</h3>";
        $error_message .= "<p><strong>Reason: </strong>" . $e->getMessage() . "</p>";
        $error_message .= "<div class='alert alert-info'>";
        $error_message .= "Please edit your default configuration file <code>".$config["_current_config_file"]."</code>, perhaps?";
        $error_message .= "</div>";
        
        addErrorMessage($error_message);
        return false;
    }
    
    return true;
}

/**
 * ChurchTools primary db access.
 * @param unknown_type $sql String
 * @param unknown_type $params params in an array
 * @param unknown_type $print_error true=>echo the error message, otherwise SQLException will be thrown 
 */
function db_query($sql, $params=null, $print_error=true) {
  global $db_pdo, $config;
  
  $sql=str_replace("{",$config["prefix"],$sql);
  $sql=str_replace("}","",$sql);
  $res = $db_pdo->prepare($sql);
  if ($res === FALSE) {
    $err = $db_pdo->errorInfo();
    if ($print_error) {
      echo $err[2]."\nGesamtes SQL: ".$sql;
    }
    else throw new SQLException($err[2]."\nGesamtes SQL: ".$sql);
    return false;
  }
  $d = new db_accessor($res, $params, $sql, $print_error);
  return $d;
}



class db_accessor implements Iterator {
  private $res=null;
  private $current=null;
  private $print_error=false;

  public function __construct($_res, $params = null, $sql = null, $print_error=false) {
    $this->res = $_res;
    $this->print_error = $print_error;
    if (!$this->res instanceof PDOStatement) {
      return null;
    }
    if (!$this->res->execute($params)) {
      $err = $this->res->errorInfo();
      if (!$this->print_error)
        throw new SQLException($err[2]."\nSQL: $sql\n".print_r($params, true));
      else echo "<p>".$err[2]."\nSQL: $sql\n".print_r($params, true);
    }
    $this->next();
  }    
  public function fetch() {
    return $this->current;
  }

  function rewind() {
  }

  function current() {
    return $this->current;
  }

  function key() {
    return null;
  }

  function next() {
    $this->current=$this->res->fetchObject();
  }

  function valid() {
    return $this->current!=null;
  }  
  
  /* FIXME: unused, but also not useful right now in "basic" version */
  function getResult() {
    return $this->res; 
  }
}

/**
 * db_update("cs_event")
 * ->fields($fields)
 * ->condition('id',$_GET["id"],"=")
 * ->execute();
 * @param $tablename
 * @return db_updatefields
 */
function db_update($tablename) {
  return new db_updatefields($tablename);  
}

class db_fields {
  protected $tablename;
  public function __construct($tablename) {
    $this->tablename=$tablename;
  }
}

class db_updatefields extends db_fields {
  function fields($arr) {
    $sql="update {".$this->tablename."} set ";
    $first=true;
    foreach ($arr as $key=>$val) {
      if ($first) $first=false;
      else $sql.=", ";
      $sql.="$key=";
      if (!isset($val))
        $sql.="null";
      else {
        if (is_string($val))
          $sql.=escape_string($val);
        else  
          $sql.="$val";
      }      
    }
    $sql.=" WHERE 1 ";
    return new db_execute($sql);
  }
}



function db_insert($tablename) {  
  return new db_insertfields($tablename);
}

class db_insertfields extends db_fields {
  function fields($arr) {
    $sql="insert into {".$this->tablename."} (";
    $first=true;
    foreach ($arr as $key=>$val) {
      if ($first) $first=false;
      else $sql.=", ";
      $sql.="$key";  
    }
    $sql.=") values (";
    $first=true;
    foreach ($arr as $val) {
      if ($first) $first=false;
      else $sql.=", ";
      if (!isset($val))
        $sql.="null";
      else {
        if (is_string($val))
          $sql.=escape_string($val);
        else  
          $sql.="$val";
      }  
    }
    $sql.=")";
    return new db_execute($sql);
  }
}

function db_delete($tablename, $print_error=true) {  
  return new db_deletefields($tablename, $print_error);
}

class db_deletefields extends db_fields {
  function fields($arr) {
    $sql="delete from {".$this->tablename."}"; 
    $sql.=" WHERE 1=1 ";
    return new db_execute($sql);
  }
}
class db_execute {
  private $sql;
  
  public function __construct($sql) {
    $this->sql=$sql;
  }
  
  function condition($field, $value, $eq) {
    if (!is_string($value)) //>0)
      $this->sql.=" AND $field $eq $value";
    else  
      $this->sql.=" AND $field $eq '$value'";
    return new db_execute($this->sql);
  }
  
  function execute($print_error=true) {
    db_query($this->sql, null, $print_error);
    return db_query("SELECT LAST_INSERT_ID( ) as a", null, $print_error)->fetch()->a;
  }
}

function isCTDBTable($table) {
  global $config;
  $prefix=$config["prefix"];
  return ((strpos($table,$prefix."cc_")!==false) 
        || (strpos($table,$prefix."cr_")!==false)
        || (strpos($table,$prefix."cdb_")!==false)
        || (strpos($table,$prefix."cs_")!==false)); 
}

function dump_database() {
  global $files_dir;
  
  $dir=$files_dir."/db_backup";
  if (!file_exists($dir))
    mkdir($dir,0777,true); //XXX: Security alert!
  if (!is_writable($dir)) {
    addErrorMessage("Das Verzeichnis $dir muss beschreibbar sein. Bitte Rechte daf&uuml;r setzen!");
  }
  else {
    if (!file_exists($dir."/.htaccess")) {
      $handle = fopen($dir."/.htaccess",'w+');
      fwrite($handle,"Deny from all");
      fclose($handle);
    }
  
    $tables = array();
    $res = db_query('SHOW TABLES');
    foreach ($res as $row) {
      $table="";
      foreach ($row as $key=>$val) {
        $table=$val;
        break;
      }
      if (isCTDBTable($table)) 
        $tables[] = $table;
    }
    $return="";
    $dt = new DateTime();
    
    $filename=$dir.'/db-backup-'.$dt->format('YmdHi').'-'.(md5(implode(',',$tables))).'.sql';
    $handle = fopen($filename,'w+');
    
    foreach($tables as $table) {
      $return.= 'DROP TABLE IF EXISTS '.$table.';';    
      $row2 = db_query('SHOW CREATE TABLE '.$table)->fetch();
      $row2 = (Array) $row2;
      $return.= "\n".$row2["Create Table"].";\n\n";
      
      $result = db_query('SELECT * FROM '.$table);
      foreach($result as $content) {
        $return.= 'INSERT INTO '.$table.' VALUES(';
        $arr=array();
        foreach ($content as $key=>$val) {
  //        $val = ereg_replace("\n","\\n",$val);
          if (!isset($val)) 
            $val="null";
          else
            $val='"'.addslashes($val).'"';
          $arr[]=$val;
        }      
        $return.=implode(",", $arr).");\n";
      }
      $return.="\n\n\n";
      fwrite($handle,$return);
      $return="";    
    }
    
    //save file
    fclose($handle);
    $zip = new ZipArchive();
    if ($zip->open($dir.'/db-backup-'.$dt->format('YmdHi').'.zip',ZIPARCHIVE::OVERWRITE) !== true) {
      return false;
    }
    $zip->addFile($filename);
    $zip->close();
    unlink($filename);
  }  
}


function churchcore_isAllowedMasterData($masterDataTables, $tablename) {
  $res=false;
  foreach ($masterDataTables as $table) {
    if ($table["tablename"]==$tablename) $res=true;        
  }  
  return $res;
}

function churchcore_deleteMasterData($id, $tablename) {
  db_query("delete from {".$tablename."} where id=:id", array(":id"=>$id));
}


/**
 * Update oder Insert je nach dem ob $Id gesetzt ist oder nicht
 * Wenn Value=null dann macht er auch ein Null. 
 * @param unknown_type $id
 * @param unknown_type $table
 */
function churchcore_saveMasterData($id, $table) {
  // id gibt es schon, also UPDATE
  if ($id!="null" && $id!="") {
    $i=0;
    $sql="update {".$table."} set ";
    while (isset($_GET["col".$i])) {
      if ($_GET["value".$i]!="null")
        $sql=$sql.$_GET["col".$i]."='".$_GET["value".$i]."', ";
      else
        $sql=$sql.$_GET["col".$i]."=null, ";
      $i++;
    }
    $sql=substr($sql,0,strlen($sql)-2);
    $sql=$sql." where id=$id";
  }
  // id gibt es nicht, also insert
  else {
    // hole die MaxId fuer den neuen Datensatz, wir arbeiten hier nicht mit auto_inecrement, damit man IDs waehlen kann
    $arr=db_query("select max(id) id from {".$table."}")->fetch();
    $max_id=$arr->id+1;
    
    $sql="insert into {".$_GET["table"]."} (id, ";
    // Baue Cols zusammen
    $i=0;
    while (isset($_GET["col".$i])) {
      $sql=$sql.$_GET["col".$i].", ";
      $i++;
    }
    $sql=substr($sql,0,strlen($sql)-2);
    // Baue Values zusammen
    $sql=$sql.") values (".$max_id.",";
    $i=0;
    while (isset($_GET["col".$i])) {
      if ($_GET["value".$i]!="null")
        $sql=$sql."'".$_GET["value".$i]."', ";
      else
        $sql=$sql."null, ";
      $i++;
    }
    $sql=substr($sql,0,strlen($sql)-2);
    $sql=$sql.") ";
  } 
  db_query($sql);  
}
