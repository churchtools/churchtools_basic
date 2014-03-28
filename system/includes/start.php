<?php 
/**
 * ChurchTools 2.0
 * http://www.churchtools.de
 *
 * Copyright (c) 2013 Jens Martin Rauen
 * Licensed under the MIT license, located in LICENSE.txt 
 *
 */

$add_header="";
$q="";
$config=array();
$mapping=array();
$i18n=null;
$content="";
$embedded=false;
$user=null;
$files_dir="sites/default";
  
function handleShutdown() {
  $error = error_get_last();
  $info="";
  if($error !== NULL){
    $info = "[ERROR] file:".$error['file'].":".$error['line']." <br/><i>".$error['message'] .'</i>'.PHP_EOL;
    echo '<div class="alert alert-error">'.$info.'</div>';
  }
}

/**
 * PrŸfe auf Mulitsite-Installation. Hier gibt es eine Config fŸr subdomain
 * z.Bsp. mghh.churchtools.de muss es dann config/churchtools.mggh.config geben.
 * Gibt die Config als Array zurŸck oder null wenn es keine zu laden gibt.
 */
function loadConfig() {
    global $files_dir;
    // Unix default. Should have ".conf" extension as per standards.
    $config = null;

    // Config, based on subdomain.
    // WARNING: This code will break per IP address access and supports only last subdomain.
    if ($config == null && strpos($_SERVER["SERVER_NAME"],".") > 0) {
        $hostname=substr($_SERVER["SERVER_NAME"],0,strpos($_SERVER["SERVER_NAME"],"."));
        $cnf_location = "sites/$hostname/churchtools.config";
        if (file_exists($cnf_location)) {
            $config = parse_ini_file($cnf_location);
            $files_dir="sites/".$hostname;
        }
    }

    // Default domain
    $cnf_location = "sites/default/churchtools.config";
    if ($config == null && file_exists($cnf_location)) {
        $config = parse_ini_file($cnf_location);
    }

    // Config in default linux etc location
    $cnf_location = "/etc/churchtools/default.conf";
    if ($config == null && @file_exists($cnf_location)) {
      $config = parse_ini_file($cnf_location);
    }
    
    // Package installed, per domain.
    // All possible virt-hosts in HTTP server has to be symlinked to it.
    $cnf_location = "/etc/churchtools/hosts/" . $_SERVER["SERVER_NAME"] . ".conf";
    if ($config == null && @file_exists($cnf_location)) {
      $config = parse_ini_file($cnf_location);
    }
    
    if ($config == null) {
        $error_message = "<h3>" . "Error: Configuration file was not found." . "</h3>";
        $error_message .= "<p>" . "Expected locations are:
            <ul>
                <li>Default appliance: <code>/etc/churchtools/default.conf</code></li>
                <li>Per-domain appliance: <code>/etc/churchtools/hosts/" . $_SERVER["SERVER_NAME"] . ".conf</code></li>
                <li>Shared hosting per domain: <code><i>YOUR_INSTALLATION</i>/sites/" . $_SERVER["SERVER_NAME"] . "/churchtools.config</code></li>
                <li>Hosting per sub-domain: <code><i>YOUR_INSTALLATION</i>/sites/<b>&lt;subdomain&gt;.&lt;domain&gt;</b>/churchtools.config</code></li>
                <li>Shared hosting default (single installation): <code><i>YOUR_INSTALLATION</i>/sites/default/churchtools.config</code></li>
            </ul>
            <div class=\"alert alert-info\">You can also use <strong>example</strong> file in
            <code><i>INSTALLATION</i>/sites/default/churchtools.example.config</code> by renaming it to
            either one location that suits your setup and further editing it accordingly.</div>";
        addErrorMessage($error_message);
    } else {
        $config["_current_config_file"] = $cnf_location;
    }
  
    return $config;
}


function loadDBConfig() {
  global $config;    
  try {
    $res=db_query("select * from {cc_config}", null, false);
    foreach($res as $val) {
      $config[$val->name]=$val->value;
    }
  }
  catch (Exception $e) {
  }  
}

function loadMapping() {
  $mapping=parse_ini_file("system/churchtools.mapping");
  // Load mappings from modules like system/churchdb/churchdb.mapping
  foreach (churchcore_getModulesSorted(true) as $module) {
    if (file_exists("system/$module/$module.mapping")) {
      $map=parse_ini_file("system/$module/$module.mapping");
      if (isset($map["page_with_noauth"]) && isset($mapping["page_with_noauth"]))
        $map["page_with_noauth"]=array_merge($mapping["page_with_noauth"], $map["page_with_noauth"]); 
      $mapping=array_merge($mapping, $map);
    }
  }
  return $mapping;
}

/**
 * 
 */
function loadUserObjectInSession() {
  global $q;
  if (!isset($_SESSION['user'])) {          
    // Wenn nicht ausgeloggt wird und RememberMe bei der letzten Anmeldung aktiviert wurde
    if (($q!="logout") && (isset($_COOKIE['RememberMe'])) && ($_COOKIE['RememberMe']==1)) {
      if (isset($_COOKIE['CC_SessionId'])) {
        $res=db_query("select * from {cc_session} where session=:session and hostname=:hostname",
            array(":session"=>$_COOKIE['CC_SessionId'], ":hostname"=>$_SERVER["HTTP_HOST"]));
        // Wenn es die Session noch gibt, hole ihn wieder ein!
        if ($res!=false) {
          $res=$res->fetch();
          if (isset($res->person_id)) {
            $res=db_query("select * from {cdb_person} where id=:id", array(":id"=>$res->person_id))->fetch();
            $res->auth=getUserAuthorization($res->id);
            $_SESSION['user']=$res;
            addInfoMessage("Willkommen zur&uuml;ck, ".$res->vorname."!", true);
          }
        }                
      }
    }
    if (!isset($_SESSION['user'])) {
      createAnonymousUser();
    }
  }  
  else {
    $_SESSION["user"]->auth=getUserAuthorization($_SESSION["user"]->id);          
    if (isset($_COOKIE['CC_SessionId'])) {
      $dt=new DateTime();    
      db_query("update {cc_session} set datum=:datum where person_id=:p_id and session=:session and hostname=:hostname",
                  array(":datum"=>$dt->format('Y-m-d H:i:s'),
                        ":session"=>$_COOKIE['CC_SessionId'], 
                        ":p_id"=>$_SESSION["user"]->id, 
                        ":hostname"=>$_SERVER["HTTP_HOST"]));
    }
  }
}

function getBaseUrl() {      
  $base_url=$_SERVER['HTTP_HOST'];
  $b=$_SERVER['REQUEST_URI'];
  if (strpos($b, "/index.php")!==false)
    $b=substr($b,0,strpos($b, "/index.php"));
  if (strpos($b, "?")!==false)
    $b=substr($b,0,strpos($b, "?"));
  $base_url=$base_url.$b;  
  if ($base_url[strlen($base_url)-1]!="/")
    $base_url.="/";
  if ((isset($_SERVER['HTTPS'])) && ($_SERVER['HTTPS']!=false))
    $base_url="https://".$base_url;
  else $base_url="http://".$base_url;
  return $base_url;
}

function churchtools_main() {
  global $q, $q_orig, $add_header, $config, $mapping, $content, $base_url, $files_dir, $user, $embedded, $i18n;
  
  $base_url=getBaseUrl();
  
  include_once("system/churchcore/churchcore_db.inc");
  include_once("system/lib/i18n.php");
  
  $config = loadConfig();
  
  if ($config!=null) {  
    if (db_connect()) { 
      // DBConfig overwrites the config files
      loadDBConfig();
      
      date_default_timezone_set(variable_get("timezone", "Europe/Berlin"));
      
      // Load i18n churchcore-bundle 
      if (!isset($config["language"])) {
        if (isset($_SERVER['HTTP_ACCEPT_LANGUAGE']))
          $config["language"]=substr($_SERVER['HTTP_ACCEPT_LANGUAGE'],0,2);
        else
          $config["language"]="de";
      }
      $i18n = new TextBundle("system/churchcore/resources/messages");
      $i18n->load("churchcore", ($config["language"]!=null ? $config["language"] : null));
      
      // Session Init
      if (!file_exists($files_dir."/tmp")) 
        @mkdir($files_dir."/tmp",0775,true);  
      if (!file_exists($files_dir."/tmp")) {
        // Admin should act accordingly, default suggestion is 0755.
        addErrorMessage(t("permission.denied.write.dir", $files_dir));
      }
      else {
        session_save_path($files_dir."/tmp");
      }
      session_name("ChurchTools_".$config["db_name"]);
      session_start();    
      register_shutdown_function('handleShutdown');

      // PrŸfe auf Offline-Modus !
      if ((isset($config["site_offline"]) && ($config["site_offline"]==1))) {
        if ((!isset($_SESSION["user"]) || (!in_array($_SESSION["user"]->id, $config["admin_ids"])))) {
          echo t("site.is.down");
          return false;
        }
      }
      
      if (isset($_GET["q"])) {
        $q=$_GET["q"];  
      }
      if ($q=="")
        if (userLoggedIn()) $q="home";
         else $q=variable_get("site_startpage", "home");
      
      $q_orig=$q;    
      
      if ((isset($_GET["embedded"]) && ($_GET["embedded"]==true))) $embedded=true;
    
      $mapping = loadMapping(); 

      $success=true;
      // Check for DB-Updates and loginstr only if this is not an ajax call. 
      if (strrpos($q, "ajax")===false) { 
        $success=checkForDBUpdates();
        if ($success) {                
          // Is there a loginstr which does not fitt to the current logged in user?
          if ((isset($_GET["loginstr"])) && (isset($_GET["id"])) && (userLoggedIn())
             && ($_SESSION["user"]->id!=$_GET["id"])) {           
            logout_current_user();
            session_start();    
          } 
          else      
            loadUserObjectInSession();
        }
      }
      if ($success) {   
        if (isset($_SESSION['user'])) $user=$_SESSION['user'];
          
        // Accept data security?
        if ((userLoggedIn()) && (!isset($_SESSION["simulate"])) && ($q!="logout") && (isset($config["accept_datasecurity"])) && ($config["accept_datasecurity"]==1) && (!isset($user->acceptedsecurity)))
          $content.=pleaseAcceptDatasecurity();
        else
          $content.=processRequest($q);
      }
    }
  }
  include("system/includes/header.php");    
  echo $content;
  include("system/includes/body.php");
}

function pleaseAcceptDatasecurity() {
  global $user, $q;
  include_once("system/churchwiki/churchwiki.php");
  if (isset($_GET["acceptsecurity"])) {
    db_query("update {cdb_person} set acceptedsecurity=current_date() where id=$user->id");
    $user->acceptedsecurity=new DateTime();
    addInfoMessage(t("datasecurity.accept.thanks"));
    return processRequest($q);
  }
    
  $data=churchwiki_load("Sicherheitsbestimmungen", 0);
  $text=str_replace("[Vorname]",$user->vorname,$data->text);
  $text=str_replace("[Nachname]",$user->name,$text);
  $text=str_replace("[Spitzname]",($user->spitzname==""?$user->vorname:$spitzname),$text);
  
  $text='<div class="container-fluid"><div class="well">'.$text;
  $text.='<a href="?q='.$q.'&acceptsecurity=true" class="btn btn-important">'.t("datasecurity.accept").'</a>';
  $text.='</div></div>';
  return $text;
}

/**
 * Will call churchservice => churchservice_main or churchservice/ajax => churchservice_ajax
 * @param $q - Complete request URL inkl. suburl e.g. churchservice/ajax
 */
function processRequest($_q) {
  global $mapping, $config, $q;
  
  $content="";

  // PrŸfe Mapping
  if (isset($mapping[$_q])) {
    include_once("system/".$mapping[$_q]);
    
    $param="main";
    if (strpos($_q,"/")>0) {
      $param="_".substr($_q,strpos($_q,"/")+1,99);
      $_q=substr($_q,0,strpos($_q,"/"));
    }    
    
    if ((!user_access("view",$_q)) && (!in_array($_q,$mapping["page_with_noauth"])) && ($_q!="login")
                && (!in_array($_q,(isset($config["page_with_noauth"])?$config["page_with_noauth"]:array()))))  {
      // Wenn kein Benutzer angemeldet ist, dann zeige nun die Anmeldemaske
      if (!userLoggedIn()) {
        if (strrpos($q, "ajax")===false) { 
          $q="login";
          return processRequest("login");
        }
        else {
          drupal_json_output(jsend()->error("Session expired!"));
          die();
        }
      }
      else {
        $name=$_q;
        if (isset($config[$_q."_name"])) 
          $name=$config[$_q."_name"];
        addInfoMessage(t("no.permission.for", $name));
        return "";
      }
    }
    $content.=call_user_func($_q."_".$param);
    if ($content==null) die();
  }
  else 
    addErrorMessage(t("mapping.not.found", $_q));
  return $content;
}

?>
