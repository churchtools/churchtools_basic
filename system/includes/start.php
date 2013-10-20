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
  db_close();
}

/**
 * PrŸfe auf Mulitsite-Installation. Hier gibt es eine Config fŸr subdomain
 * z.Bsp. mghh.churchtools.de muss es dann config/churchtools.mggh.config geben.
 * Gibt die Config als Array zurŸck oder null wenn es keine zu laden gibt.
 */
function loadConfig() {
  global $files_dir;

  // Unix default. Should have ".conf" extension as per standards.
  $config = parse_ini_file("/etc/churchtools.conf");

  // Domain-specific config.
  if ($config == null && strpos($_SERVER["SERVER_NAME"],".") > 0) {
    $substr=substr($_SERVER["SERVER_NAME"],0,strpos($_SERVER["SERVER_NAME"],"."));
    if (file_exists("sites/$substr/churchtools.config")) {
      $config = parse_ini_file("sites/$substr/churchtools.config");
      $files_dir="sites/".$substr;
    }
  }

  // Default domain
  if ($config==null) {
    $config = parse_ini_file("sites/default/churchtools.config");
  }
  
  if ($config==null) {
     addErrorMessage("<p><h3>Error: Configuration file was not
     found.</h3></p><br/><p>Expected locations are either
     <code>/etc/churchtools.conf</code> or <code><i>INSTALLATION</i>/sites/default/churchtools.config</code>
     files.</p><p>Hint: You can also use <strong>example</strong> file in
     <code><i>INSTALLATION</i>/sites/default/churchtools.example.config</code> by renaming it to
     either one and editing it accordingly.</p>");
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
  return parse_ini_file("system/churchtools.mapping");
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
  global $q, $q_orig, $add_header, $config, $mapping, $content, $base_url, $files_dir, $user, $embedded;
  
  $base_url=getBaseUrl();
  
  include("system/churchcore/churchcore_db.inc");
  
  $config = loadConfig();
  
  if ($config!=null) {  
    // Session Init
    if (!file_exists($files_dir."/tmp")) 
      @mkdir($files_dir."/tmp",0775,true);  
    if (!file_exists($files_dir."/tmp")) {
      // Admin should act accordingly, default suggestion is 0755.
      addErrorMessage("Permission denied write to the directory $files_dir");
    }
    session_name("ChurchTools_".$config["db_name"]);
    session_start();    
    register_shutdown_function('handleShutdown');
    
    if (isset($_GET["q"])) {
      $q=$_GET["q"];  
    }
    if ($q=="")
      $q="home";
    
    $q_orig=$q;    
    
    if ((isset($_GET["embedded"]) && ($_GET["embedded"]==true))) $embedded=true;
  
    $mapping = loadMapping(); 
    if (db_connect()) { 
      loadDBConfig();
      
      // PrŸfe auf Offline-Modus !
      if ((isset($config["site_offline"]) && ($config["site_offline"]==1))) {
        if ((!isset($_SESSION["user"]) || (!in_array($_SESSION["user"]->id, $config["admin_ids"])))) {
          echo "Diese Seite wird gerade gewartet. Bitte versuche es sp&auml;ter noch einmal.";
          return false;
        }
      }

      $success=true;
      if (strrpos($q, "ajax")===false) { 
        $success=checkForDBUpdates();
        if ($success) {                
          // PrŸfe, ob ich ein loginstr habe und die Id nicht dem aktuellen User entspricht
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
          
        // Datensicherheitsbestimmungen akzeptiert?
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
    addInfoMessage("Danke f&uuml;r das Akzeptieren der Datenschutzbestimmungen!");
    return processRequest($q);
  }
    
  $data=churchwiki_load("Sicherheitsbestimmungen", 0);
  $text=str_replace("[Vorname]",$user->vorname,$data->text);
  $text=str_replace("[Nachname]",$user->name,$text);
  $text=str_replace("[Spitzname]",($user->spitzname==""?$user->vorname:$spitzname),$text);
  
  $text='<div class="container-fluid"><div class="well">'.$text;
  $text.='<a href="?q='.$q.'&acceptsecurity=true" class="btn btn-important"> Datenschutzbestimmungen akzeptieren</a>';
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
        $q="login";
        return processRequest("login");
      }
      else {
        $name=$_q;
        if (function_exists($_q."_getName"))
          $name=call_user_func($_q."_getName");
        addInfoMessage("Keine Berechtigung f&uuml;r ".$name);
        return "";
      }
    }
    $content.=call_user_func($_q."_".$param);
    if ($content==null)
      die();
  }
  else 
    addErrorMessage($_q." nicht gefunden in churchtools.mapping!");
  return $content;
}

?>
