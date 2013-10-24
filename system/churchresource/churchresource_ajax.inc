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


function churchresource_getMasterData() {
  global $user;
  $res=array();
  include_once(drupal_get_path('module', 'churchcal') .'/churchcal_db.inc');
  $res=churchresource_getMasterDataTables();
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
  $res["churchcal_name"] =variable_get('churchcal_name');
  $res["category"] =churchcore_getTableData("cc_calcategory", null, null, "id, color, bezeichnung");  
  return $res;
}

function churchresource_deleteMasterData($params) {
  if ((user_access("edit masterdata","churchresource")) && (churchcore_isAllowedMasterData(churchresource_getMasterDataTablenames(), $params["table"]))) {
    churchcore_deleteMasterData($params["id"], $params["table"]);
    cr_log("f:deleteMasterData id:".$params["id"],2);
  }
  else throw new CTNoPermission("edit masterdata", "churchresource");  
}

function churchresource_saveSetting($params) {
  global $user;
  churchcore_saveUserSetting("churchresource", $user->id, $params["sub"], $params["val"]);
}

function churchresource_saveMasterData($params) {
  if ((user_access("edit masterdata","churchresource")) && (churchcore_isAllowedMasterData(churchresource_getMasterDataTablenames(), $params["table"]))) {      
    churchcore_saveMasterData($params["id"], $params["table"]);
    cr_log("f:saveMasterData id:".$params["id"],2);
  } 
  else throw new CTNoPermission("edit masterdata","churchresource");  
}

function churchresource_getBookings($params) {
  return getBookings();
}

function churchresource_ajax() {
  $ajax = new CTAjaxHandler("churchresource");
  $ajax->addFunction("getMasterData", "view"); 
  $ajax->addFunction("pollForNews", "view"); 
  $ajax->addFunction("saveSetting", "view"); 
  $ajax->addFunction("deleteMasterData", "edit masterdata"); 
  $ajax->addFunction("saveMasterData", "edit masterdata"); 
  $ajax->addFunction("getBookings", "view"); 
  $ajax->addFunction("getLogs", "view"); 
  $ajax->addFunction("delException", "administer bookings"); 
  $ajax->addFunction("delBooking", "edit masterdata"); 
  $ajax->addFunction("createBooking", "view"); 
  $ajax->addFunction("updateBooking", "view"); 

  drupal_json_output($ajax->call());  
}

