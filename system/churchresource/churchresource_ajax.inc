<?php


include_once("churchresource_db.inc");


function churchresource_getAuthForAjax() {
  $res=null;
  $auth=$_SESSION["user"]->auth["churchresource"];
  
  if (isset($auth["view"]))
    $res["view"]=true;
  if (isset($auth["create bookings"])) {
  	$res["write"]=true;
  }  
  if (isset($auth["administer bookings"])) {
  	$res["write"]=true;
  	$res["editall"]=true;
  }
  if (isset($auth["edit masterdata"])) {
    $res["admin"]=true;
  }
  return $res;
}


function churchresource_mail($key, &$message, $params) {
  $language = $message['language'];
  
  // WofŸr?? Bringt Drupal 7 durcheinander
  //$variables = user_mail_tokens($params['account'], $language);
  switch($key) {
    case 'churchresource_notify':
    $message['subject'] =$params["subject"];
    $message['body'][] = $params["message"];
    break;
  }
}





function churchresource_getModulesPath() {
  return drupal_get_path('module', 'churchresource');
}

function churchresource_getVersion() {
  $config = parse_ini_file(churchresource_getModulesPath()."/churchresource.info"); 
  return $config["version"];
}

/**
 * Erzeugt eine Uebersicht ueber alle Stammdatentabellen, die per MaintainView gepflegt werden sollen
 * Diese Infos werden per JSON an das JS uebergeben
 */
function churchresource_getMasterDataTablenames() {
  $res=array();
  $res[1]=churchcore_getMasterDataEntry(1, "Ressource", "res", "cr_resource","resourcetype_id,sortkey,bezeichnung");
  $res[2]=churchcore_getMasterDataEntry(2, "Ressourcen-Typ", "resTypes", "cr_resourcetype");
  $res[3]=churchcore_getMasterDataEntry(3, "Status", "status", "cr_status");
  
  return $res;
}

function churchresource_getMasterDataTables() {
  $tables=churchresource_getMasterDataTablenames();
  foreach ($tables as $value) {
    $res[$value["shortname"]]=churchcore_getTableData($value["tablename"],$value["sql_order"]);
  }
  return $res;
}

function churchresource_getUserSettings($user_pid) {
  return churchcore_getUserSettings("churchresource",$user_pid);  
}

function churchresource_ajax() {
global $user, $base_url;

  if (isset($_GET["func"]))
    $function=$_GET["func"];
  else  
    $function=$_POST["func"];
  $id=null;
  $sub=null;
  if (isset($_GET["id"])) $id=$_GET["id"];
  if (isset($_GET["sub"])) $sub=$_GET["sub"];
  
  switch ($function) {
    case "getMasterData":
      include_once(drupal_get_path('module', 'churchcal') .'/churchcal_db.inc');
      $res=churchresource_getMasterDataTables();
      $res["version"] = churchresource_getVersion();
      $res["masterDataTables"] = churchresource_getMasterDataTablenames();
      $res["auth"] = churchresource_getAuthForAjax();
	  $res["status"] = churchcore_getTableData("cr_status");
      $res["minutes"] = churchcore_getTableData("cr_minutes");
      $res["hours"] = churchcore_getTableData("cr_hours");
      $res["repeat"] = churchcore_getTableData("cc_repeat");
      $res["modulename"] = "churchresource";
      $res["modulespath"] = churchresource_getModulesPath();
	  $res["userid"] = $user->cmsuserid; // CMS Username#
	  $res["user_pid"] = $user->id;
	  $res["user_name"] = "$user->vorname $user->name";
	  $res["settings"] =  churchresource_getUserSettings($user->id);
      $res["lastLogId"] = churchresource_getLastLogId();	   
	  $res["bootstrap"] = variable_get('bootstrap', false);
	  $res["churchcal_name"] =variable_get('churchcal_name');
	  $res["category"] =churchcore_getTableData("cc_calcategory", null, null, "id, color, bezeichnung");  
	  
      break;
    case "pollForNews": 
      $res = churchresource_pollForNews($_GET["last_id"]);
      break;
	case "saveSetting":
      churchcore_saveUserSetting("churchresource", $user->id, $sub, $_GET["val"]);
      $res="ok";
      break;     
    case "deleteMasterData" :
      if ((user_access("edit masterdata","churchresource")) && (churchcore_isAllowedMasterData(churchresource_getMasterDataTablenames(), $_GET["table"]))) {
        churchcore_deleteMasterData($id, $_GET["table"]);
        cr_log("f:$function id:$id",2);
        $res=jsend()->success();
      }
      else $res=jsend()->error("Keine Rechte! ".(!user_access("edit masterdata","churchresource")?"Bitte edit masterdata vergeben.":"Unerlaubte Tabelle!"));
      break;
    case "saveMasterData" :
      if ((user_access("edit masterdata","churchresource")) && (churchcore_isAllowedMasterData(churchresource_getMasterDataTablenames(), $_GET["table"]))) {      
        churchcore_saveMasterData($id, $_GET["table"]);
        cr_log("f:$function id:$id",2);
        $res=jsend()->success();
      } 
      else $res=jsend()->error("Keine Rechte! ".(!user_access("edit masterdata","churchresource")?"Bitte edit masterdata vergeben.":"Unerlaubte Tabelle!"));
      break;
    case "getBookings":
      $res=getBookings();
      break;  
    case "getLogs":
      $res=churchresource_getLogs($_GET["id"]);
      break;
    case "delException":
      if (!user_access("administer bookings","churchresource"))
        $res=jsend()->error("Keine Berechtigung");
      else {  
        $res=churchresource_delException($_GET["id"]);
      }
      break;
    case "delBooking":
      if (user_access("edit masterdata","churchresource")) {      
        $res=delBooking($_GET["id"]);
      } else $res="no rights!";  
      break;
    case "createBooking":
      $res=churchresource_createBooking($_POST);
      break;
    case "updateBooking":
      $res=churchresource_updateBooking($_POST);
      break;        
    	
	default: $res[]="Unknown function call: ".$function;    
  }
  drupal_json_output($res);
}

