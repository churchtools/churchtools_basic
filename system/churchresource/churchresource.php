<?php

  
function churchresource_main() {
  drupal_add_js('system/assets/js/jquery.history.js'); 
  
  drupal_add_js(drupal_get_path('module', 'churchcore') .'/cc_abstractview.js'); 
  drupal_add_js(drupal_get_path('module', 'churchcore') .'/cc_standardview.js'); 
  drupal_add_js(drupal_get_path('module', 'churchcore') .'/cc_maintainstandardview.js'); 
  drupal_add_js(drupal_get_path('module', 'churchcore') .'/cc_interface.js'); 

  drupal_add_js(drupal_get_path('module', 'churchresource') .'/cr_loadandmap.js');
  drupal_add_js(drupal_get_path('module', 'churchresource') .'/cr_maintainview.js');
  drupal_add_js(drupal_get_path('module', 'churchresource') .'/cr_weekview.js');
  drupal_add_js(drupal_get_path('module', 'churchresource') .'/cr_main.js');
  
  $content='';

  // Übergabe der ID für den Direkteinstieg eines Eintrags
  if (isset($_GET["id"]) && ($_GET["id"]!=null))
    $content=$content."<input type=\"hidden\" id=\"filter_id\" value=\"".$_GET["id"]."\"/>";

  //$content=$content."<div id=\"cdb_menu\"></div> <div id=\"cdb_filter\"></div> <div id=\"cdb_content\">Fehler: Ist JavaScript deaktiviert?</div>";
  $content=$content.
  $content=$content." 
<div class=\"row-fluid\">
  <div class=\"span3\">
    <div id=\"cdb_menu\"></div>
    <div id=\"cdb_filter\"></div>
  </div>  
  <div class=\"span9\">
    <div id=\"cdb_search\"></div> 
    <div id=\"cdb_group\"></div> 
    <div id=\"cdb_content\"></div>
  </div>
</div>";
  return $content;
}  

function churchresource__ajax() {
  include_once("churchresource_db.inc");
  
  $module=new CTChurchResourceModule("churchresource");

  $ajax = new CTAjaxHandler($module);
  $ajax->addFunction("delException", "administer bookings"); 
  $ajax->addFunction("delBooking", "edit masterdata"); 
  $ajax->addFunction("createBooking", "view"); 
  $ajax->addFunction("updateBooking", "view"); 
  
  drupal_json_output($ajax->call());  
}

function churchresource_getAdminModel() {
  global $config;
  
  $model = new CC_ModulModel("churchresource");      
  $model->addField("churchresource_entries_last_days","", "INPUT_REQUIRED","Wieviel Tage zur&uuml;ck in ChurchResource-Daten geladen werden");
  $model->fields["churchresource_entries_last_days"]->setValue($config["churchresource_entries_last_days"]);  
  return $model;
}  

function churchresource_getOpenBookings() {
  $txt="";
  if (user_access("administer bookings","churchresource")) {      
    include_once("churchresource_db.inc");
    $arr=getOpenBookings();
	if ($arr!=null) {
      foreach ($arr as $val) {
        $txt=$txt."<li><p><a href=\"?q=churchresource&id=$val->id\">$val->text</a> ($val->resource)<br/><small>$val->startdate $val->person_name</small><br/>";
      }
      if ($txt!="") 
        $txt="<ul>$txt</ul>";
	}
  }	
  return $txt;  
}

function churchresource_getCurrentBookings() {
  $txt="";
  if (user_access("view","churchresource")) {      
    include_once("churchresource_db.inc");       
    
	// Alle buchungen ab jetzt bis morgen mit Status 2
	$res=getBookings(0, 1, "2");
	if ($res!=null) {
  	  $arr=array();
      $counter=0;
  	  foreach ($res as $r) {
        $r->startdate=new DateTime($r->startdate);
        $r->enddate=new DateTime($r->enddate);
        foreach (getAllDatesWithRepeats($r,0,1) as $d) {
          $counter=$counter+1;
          $a=array();
          $a["realstart"]=new DateTime($d->format('Y-m-d H:i:s'));
          $a["startdate"]=$r->startdate;
          $a["enddate"]=$r->enddate;
          $a["person_name"]=$r->person_name;
          $a["resource_id"]=$r->resource_id;
          $a["repeat_id"]=$r->repeat_id;
          $a["text"]=$r->text;
          $a["id"]=$r->id;
          $arr[]=$a;
        }
  	  }
      
      if ($arr!=null) {
        $resources=churchcore_getTableData("cr_resource");
        function cmp($a, $b) {
          if ($a["realstart"]==$b["realstart"]) return 0;
          else 
            if ($a["realstart"]>$b["realstart"]) return 1; else -1;
        }
        usort($arr, "cmp");
        
        foreach ($arr as $val) {
          $txt.="<li><p><a href=\"?q=churchresource&id=".$val["id"]."\">".$val["text"]."</a> ";
          if ($val["repeat_id"]>0) $txt.='<img title="Serie startet vom '.$val["startdate"]->format('d.m.Y H:i').'" src="system/churchresource/images/recurring.png" width="16px"/> ';        
          $txt.="(".$resources[$val["resource_id"]]->bezeichnung.")<br/><small>".$val["realstart"]->format('d.m.Y H:i')." ".$val["person_name"]."</small><br/>";
        }
        if ($txt!="") 
          $txt="<ul>$txt</ul>"; 
      }
	}
	
  }	
  return $txt;
}

function churchresource_blocks() {
  return (array(
    1=>array(
      "label"=>"Offene Buchungsanfragen",
      "col"=>3,
      "sortkey"=>1,
      "html"=>churchresource_getOpenBookings()
    ),  
    2=>array(
      "label"=>"Aktuelle Buchungen",
      "col"=>3,
      "sortkey"=>2,
      "html"=>churchresource_getCurrentBookings()
    ),  
    ));
} 

function churchresource__printview() {
global $user;
  
  $content="<html><head>";
  
  drupal_add_js("system/assets/js/jquery-1.10.2.min.js");
  drupal_add_js("system/assets/js/jquery-migrate-1.2.1.min.js");
  
  drupal_add_js(drupal_get_path('module', 'churchcore') .'/shortcut.js'); 
  drupal_add_js('system/assets/js/jquery.history.js'); 
  
  drupal_add_js('system/assets/ui/jquery.ui.core.min.js');
  drupal_add_js('system/assets/ui/jquery.ui.datepicker.min.js');
  drupal_add_js('system/assets/ui/jquery.ui.position.min.js');
  drupal_add_js('system/assets/ui/jquery.ui.widget.min.js');
  drupal_add_js('system/assets/ui/jquery.ui.autocomplete.min.js');
  drupal_add_js('system/assets/ui/jquery.ui.dialog.min.js');
  drupal_add_js('system/assets/ui/jquery.ui.mouse.min.js');
  drupal_add_js('system/assets/ui/jquery.ui.draggable.min.js');
  drupal_add_js('system/assets/ui/jquery.ui.resizable.min.js');
  
  drupal_add_js(drupal_get_path('module', 'churchcore') .'/churchcore.js'); 
  drupal_add_js(drupal_get_path('module', 'churchcore') .'/churchforms.js'); 
  drupal_add_js(drupal_get_path('module', 'churchcore') .'/cc_abstractview.js'); 
  drupal_add_js(drupal_get_path('module', 'churchcore') .'/cc_standardview.js'); 
  drupal_add_js(drupal_get_path('module', 'churchcore') .'/cc_maintainstandardview.js'); 
  drupal_add_js(drupal_get_path('module', 'churchcore') .'/cc_interface.js'); 

  drupal_add_js(drupal_get_path('module', 'churchresource') .'/cr_loadandmap.js');
  drupal_add_js(drupal_get_path('module', 'churchresource') .'/cr_maintainview.js');
  drupal_add_js(drupal_get_path('module', 'churchresource') .'/cr_weekview.js');
  drupal_add_js(drupal_get_path('module', 'churchresource') .'/cr_main.js');
   
  $content=$content.drupal_get_header();
  
  $content=$content.'<link type="text/css" rel="stylesheet" media="all" href="'.drupal_get_path('module', 'includes').'/churchtools.css" />';
  $content=$content.'<link type="text/css" rel="stylesheet" media="all" href="'.drupal_get_path('module', 'churchresource').'/cr_printview.css" />';
    
  $content=$content."</head><body>";
  $content=$content."<input type=\"hidden\" id=\"printview\" value=\"true\"/>";

  if (isset($_GET["curdate"]) && ($_GET["curdate"]!=null))
    $content=$content."<input type=\"hidden\" id=\"curdate\" value=\"".$_GET["curdate"]."\"/>";
  
  $content=$content."<div id=\"cdb_f_ilter\"></div></div> <div id=\"cdb_content\">Seite wird aufgebaut...</div>";
  $content=$content."</body></html>";
  echo $content;
}

function churchresource_getAuth() {
  $cc_auth = array();
  $cc_auth=addAuth($cc_auth, 201,'view', 'churchresource', null, 'ChurchResource sehen', 1);
  $cc_auth=addAuth($cc_auth, 306,'create bookings', 'churchresource', null, 'Eigene Buchugsanfragen erstellen', 1);
  $cc_auth=addAuth($cc_auth, 202,'administer bookings', 'churchresource', null, 'Alle Anfragen editieren, ablehnen, etc.', 1);
  $cc_auth=addAuth($cc_auth, 203,'assistance mode', 'churchresource', null, 'Im Auftrag eines anderen Buchungen durchf&uuml;hren', 1);
  $cc_auth=addAuth($cc_auth, 299,'edit masterdata', 'churchresource', null, 'Stammdaten editieren', 1);
  return $cc_auth;
}



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
  if (isset($auth["assistance mode"])) {
    $res["assistance mode"]=true;
  }
  
  // For assistance mode
  if (user_access("create person", "churchdb")) {
    $res["create person"]=true;
  }
  
  if (isset($auth["edit masterdata"])) {
    $res["admin"]=true;
  }
  return $res;
}


class CTChurchResourceModule extends CTAbstractModule {
  
  public function getBookings($params) {
    return getBookings();  
  }
  
  public function getMasterDataTablenames() {
    $res=array();
    $res[1]=churchcore_getMasterDataEntry(1, "Ressource", "resources", "cr_resource","resourcetype_id,sortkey,bezeichnung");
    $res[2]=churchcore_getMasterDataEntry(2, "Ressourcen-Typ", "resourceTypes", "cr_resourcetype");
    $res[3]=churchcore_getMasterDataEntry(3, "Status", "status", "cr_status");  
    return $res;
  }
  
  public function getMasterData() {
    global $user;
    $res=array();
    include_once(drupal_get_path('module', 'churchcal') .'/churchcal_db.inc');
    $res=$this->getMasterDataTables();
    $res["masterDataTables"] = $this->getMasterDataTablenames();
    $res["auth"] = churchresource_getAuthForAjax();
    $res["status"] = churchcore_getTableData("cr_status");
    $res["minutes"] = churchcore_getTableData("cr_minutes");
    $res["hours"] = churchcore_getTableData("cr_hours");
    $res["repeat"] = churchcore_getTableData("cc_repeat");
    $res["cdb_bereich"] = churchcore_getTableData("cdb_bereich");
    $res["cdb_status"] = churchcore_getTableData("cdb_status");
    $res["cdb_station"] = churchcore_getTableData("cdb_station");
   
    $res["modulename"] = $this->getModuleName();
    $res["modulespath"] = $this->getModulePath();
    $res["userid"] = $user->cmsuserid; // CMS Username#
    $res["user_pid"] = $user->id;
    $res["user_name"] = "$user->vorname $user->name";
    $res["settings"] =  $this->getSettings();
    $res["lastLogId"] = churchresource_getLastLogId();	   
    $res["churchcal_name"] =variable_get('churchcal_name');
    $res["category"] =churchcore_getTableData("cc_calcategory", null, null, "id, color, bezeichnung");  
    return $res;
  } 
  
  function pollForNews($params) {
    global $user;
    $last_id=$params["last_id"];
    $res=db_query("select * from {cr_log} where id > $last_id and person_id!='".$user->id."'");
    $arrs=Array();
    foreach ($res as $arr) {
      $arrs[$arr->id]=$arr;   
    }
    $arr=Array();
    $arr["lastLogId"]=churchresource_getLastLogId();
    $arr["logs"]=$arrs;  
    return $arr;
  }  
  
  function getLogs($params) {
    $id=$params["id"];
    $res=db_query("SELECT l.*, concat(p.vorname,' ',p.name) as person_name from {cr_log} l, {cdb_person} p 
               where l.person_id=p.id and booking_id=".$id." order by datum desc");
    $ret=null; 
    foreach ($res as $arr) {
      $ret[]=$arr;
    }
    return $ret; 	 
  }
}

?>
